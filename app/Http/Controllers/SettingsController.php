<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $tenantId = $this->targetTenantId($request);

        $groups = collect(config('school-settings'))
            ->filter(
                fn (array $cfg): bool => in_array($user->role, $cfg['roles'] ?? ['super_admin', 'admin_sekolah'], true)
            )
            ->mapWithKeys(function (array $cfg, string $group) use ($tenantId): array {
                $tenantScoped = (bool) $cfg['tenant_scoped'];
                $values = $tenantScoped && $tenantId
                    ? Setting::withoutGlobalScopes()
                        ->where('group', $group)
                        ->where('tenant_id', $tenantId)
                        ->pluck('value', 'key')
                    : [];

                $fields = [];

                foreach ($cfg['fields'] as $name => $field) {
                    $fields[$name] = [
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'options' => $field['options'] ?? [],
                        'placeholder' => $field['placeholder'] ?? '',
                        'hint' => $field['hint'] ?? '',
                        'value' => $values[$name] ?? $field['default'],
                    ];
                }

                return [$group => [
                    'label' => $cfg['label'],
                    'description' => $cfg['description'] ?? '',
                    'tenant_scoped' => $tenantScoped,
                    'fields' => $fields,
                ]];
            })
            ->all();

        return view('settings.index', [
            'groups' => $groups,
            'tenantId' => $tenantId,
            'tenantName' => $tenantId ? Tenant::where('id', $tenantId)->value('name') : null,
            'tenants' => $user->isSuperAdmin()
                ? Tenant::query()->where('status', 'active')->orderBy('name')->get(['id', 'name'])
                : collect(),
            'needsTenant' => ! currentTenantId() && ! $tenantId,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $group = $request->string('group')->toString();
        $tenantScoped = (bool) config("school-settings.$group.tenant_scoped", false);
        $tenantId = $tenantScoped ? $this->targetTenantId($request) : null;
        $fields = config("school-settings.$group.fields", []);

        $previous = $tenantScoped && $tenantId
            ? Setting::withoutGlobalScopes()
                ->where('group', $group)
                ->where('tenant_id', $tenantId)
                ->pluck('value', 'key')
            : [];

        $old = [];
        $new = [];
        $writing = null;

        foreach ($fields as $name => $cfg) {
            $value = $cfg['type'] === 'toggle'
                ? ($request->boolean($name) ? '1' : '0')
                : (string) ($data[$name] ?? '');

            if (($previous[$name] ?? null) !== $value) {
                $old[$name] = $previous[$name] ?? null;
                $new[$name] = $value;
            }

            $writing = Setting::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'group' => $group, 'key' => $name],
                ['tenant_id' => $tenantId, 'group' => $group, 'key' => $name, 'value' => $value]
            );
        }

        if ($new !== []) {
            log_audit('update_settings', $writing, $old, $new);
        }

        $label = (string) config("school-settings.$group.label", $group);
        $message = $new === []
            ? 'Tidak ada perubahan pengaturan.'
            : "Pengaturan {$label} berhasil disimpan.";

        return redirect(route('settings.index', $tenantId ? ['tenant' => $tenantId] : []) . '#' . $group)
            ->with('success', $message);
    }

    /**
     * Tenant yang jadi sasaran simpan/baca pengaturan.
     *
     * Admin sekolah terkunci pada sekolahnya sendiri. Super Admin tidak punya
     * sekolah, jadi ia harus memilih sekolah secara eksplisit lewat parameter
     * `tenant`. Falls back ke tenant aktif pertama sudah dihapus karena menulis
     * profil NPSN ke sekolah yang tidak dipilih adalah perubahan data lintas
     * tenant yang tidak terlihat oleh siapa pun.
     */
    private function targetTenantId(Request $request): ?string
    {
        if ($tenantId = currentTenantId()) {
            return $tenantId;
        }

        $requested = $request->query('tenant') ?: $request->input('tenant');

        if (! $requested) {
            return null;
        }

        return Tenant::query()
            ->where('id', $requested)
            ->where('status', 'active')
            ->value('id');
    }
}
