<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPermissionRequest;
use App\Http\Requests\UserRequest;
use App\Models\Permission;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();

        $users = User::query()
            ->with('tenant')
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $needle = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle));
            })
            ->when(
                $request->filled('role') && array_key_exists($request->string('role')->toString(), appRoles()),
                fn (Builder $query) => $query->where('role', $request->string('role')->toString())
            )
            ->when(
                $actor->isSuperAdmin() && $request->filled('tenant'),
                fn (Builder $query) => $query->where('tenant_id', $request->string('tenant')->toString())
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => appRoles(),
            'tenants' => $actor->isSuperAdmin() ? Tenant::query()->orderBy('name')->get() : collect(),
            'canChooseTenant' => $actor->isSuperAdmin(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.create', $this->formData($request, null));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->payload());

        log_audit('create', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'tenant_id' => $user->tenant_id,
        ]);

        return to_route('users.index')->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->guardManageable($request, $user);

        return view('users.edit', $this->formData($request, $user));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->guardManageable($request, $user);

        $old = $user->only(['name', 'email', 'role', 'tenant_id']);
        $data = $request->payload();

        if ($user->isSuperAdmin() && $data['role'] !== 'super_admin' && $this->isLastSuperAdmin()) {
            return back()->withErrors('Peran Super Admin terakhir tidak dapat diturunkan. Sistem harus selalu memiliki satu Super Admin.');
        }

        $passwordChanged = $request->changedPassword();
        unset($data['password']);

        $user->fill($data);

        if ($passwordChanged) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        $new = $user->only(['name', 'email', 'role', 'tenant_id']);

        log_audit('update', $user, $old, $new + ($passwordChanged ? ['password' => '[diubah]'] : []));

        $message = $passwordChanged
            ? "Akun {$user->name} berhasil diperbarui dan kata sandi diganti."
            : "Akun {$user->name} berhasil diperbarui.";

        return to_route('users.index')->with('success', $message);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardManageable($request, $user);

        if ($user->is($request->user())) {
            return back()->withErrors('Akun Anda sendiri tidak dapat dihapus.');
        }

        if ($user->isSuperAdmin() && $this->isLastSuperAdmin()) {
            return back()->withErrors('Akun Super Admin terakhir tidak dapat dihapus.');
        }

        if (($blocked = $this->blockingDependents($user)) !== null) {
            return back()->withErrors($blocked);
        }

        $name = $user->name;
        $snapshot = $user->only(['name', 'email', 'role', 'tenant_id']);

        DB::transaction(function () use ($user): void {
            $user->permissionOverrides()->delete();
            $user->delete();
        });

        log_audit('delete', $user, $snapshot, null);

        return to_route('users.index')->with('success', "Akun {$name} berhasil dihapus.");
    }

    /**
     * Menyimpan pengecualian akses per pengguna. Hanya Super Admin yang boleh
     * menyentuh tabel ini, dan peran Super Admin sendiri tidak pernah punya
     * pengecualian karena selalu memegang seluruh izin.
     */
    public function updatePermissions(UpdateUserPermissionRequest $request, User $user): RedirectResponse
    {
        $this->guardManageable($request, $user);

        if ($user->isSuperAdmin()) {
            return back()->withErrors('Peran Super Admin selalu memiliki seluruh izin, pengecualian tidak berlaku.');
        }

        $before = $this->overrideMap($user);

        $rows = array_map(fn (array $row): array => [
            'user_id' => $user->id,
            'permission_id' => $row['permission_id'],
            'effect' => $row['effect'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $request->overrides());

        DB::transaction(function () use ($user, $rows): void {
            $user->permissionOverrides()->delete();

            if ($rows !== []) {
                DB::table('user_permissions')->insert($rows);
            }
        });

        $names = Permission::query()->pluck('name', 'id');
        $after = [];

        foreach ($rows as $row) {
            $after[$names[$row['permission_id']]] = $row['effect'];
        }

        log_audit('update_user_permissions', $user, ['overrides' => $before], ['overrides' => $after]);

        return to_route('users.edit', $user)->with(
            'success',
            'Pengecualian akses untuk '.$user->name.' berhasil disimpan.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function overrideMap(User $user): array
    {
        return $user->permissionOverrides()
            ->with('permission')
            ->get()
            ->filter(fn (UserPermission $override): bool => $override->permission !== null)
            ->mapWithKeys(fn (UserPermission $override): array => [
                $override->permission->name => $override->effect,
            ])
            ->all();
    }

    /**
     * Otorisasi tingkat instance. Route model binding sudah membatasi Admin Sekolah
     * ke tenant sendiri lewat global scope;pemeriksaan ini menutup celah role.
     */
    private function guardManageable(Request $request, User $target): void
    {
        if (! $request->user()->canManage($target)) {
            throw new HttpException(403, 'Anda tidak berwenang mengelola akun ini.');
        }
    }

    /**
     * Menjamin selalu ada Super Admin aktif. Dipakai untuk menolak penghapusan
     * maupun penurunan peran Super Admin terakhir.
     */
    private function isLastSuperAdmin(): bool
    {
        return User::query()->withoutGlobalScopes()->where('role', 'super_admin')->count() <= 1;
    }

    /**
     * Relasi dengan FK restrict harus dicegah lebih dulu agar pesannya ramah,
     * bukan QueryException yang mentah.
     */
    private function blockingDependents(User $user): ?string
    {
        $schedules = Schedule::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->count();

        if ($schedules > 0) {
            return "Akun {$user->name} tidak dapat dihapus karena masih menjadi penanggung jawab {$schedules} jadwal pelajaran. Alihkan jadwal tersebut lebih dulu.";
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?User $user): array
    {
        $actor = $request->user();

        return [
            'user' => $user,
            'assignableRoles' => array_intersect_key(appRoles(), array_flip($actor->assignableRoles())),
            'roleLocked' => $user !== null && ! in_array($user->role, $actor->assignableRoles(), true),
            'tenants' => $actor->isSuperAdmin() ? Tenant::query()->orderBy('name')->get() : collect(),
            'showTenantField' => $actor->isSuperAdmin(),
            'overridable' => $actor->hasPermission('permissions.manage') && $user !== null && ! $user->isSuperAdmin(),
            'overrides' => $user === null ? [] : $this->overrideMap($user),
            'permissionRows' => $this->permissionRows(),
        ];
    }

    /**
     * Daftar izin datar untuk form pengecualian akses.
     *
     * @return array<int, array<string, mixed>>
     */
    private function permissionRows(): array
    {
        $rows = [];

        foreach (permissionGroups() as $group) {
            foreach ($group['permissions'] as $permission) {
                $rows[] = $permission + ['group_label' => $group['label']];
            }
        }

        return $rows;
    }
}
