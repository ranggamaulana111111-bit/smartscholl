<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasFactory, Notifiable;

    /**
     * Cache izin milik instance ini supaya tidak resolve berulang dalam satu request.
     *
     * @var array<string, bool>|null
     */
    protected ?array $resolvedPermissions = null;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdminSekolah(): bool
    {
        return $this->role === 'admin_sekolah';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }

    public function isOrangTua(): bool
    {
        return $this->role === 'orang_tua';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Izin milik role pengguna, dihitung dari tabel role_permissions.
     * Kolom pivot `role` dipasangkan dengan atribut `role` milik pengguna.
     */
    public function rolePermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role', 'permission_id', 'role');
    }

    /**
     * Overrides per pengguna. Efeknya menang atas izin bawaan role.
     */
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Seluruh izin yang dimiliki pengguna setelah override diterapkan, bentuk
     * nama izin => true. Di-resolve sekali per instance lalu dipakai ulang.
     *
     * @return array<string, bool>
     */
    public function resolvedPermissions(): array
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }

        if ($this->isSuperAdmin()) {
            return $this->resolvedPermissions = array_fill_keys(permissionNames(), true);
        }

        $resolved = array_fill_keys($this->rolePermissions()->pluck('name')->all(), true);

        foreach ($this->permissionOverrides()->with('permission')->get() as $override) {
            if ($override->permission) {
                $resolved[$override->permission->name] = $override->effect === 'grant';
            }
        }

        return $this->resolvedPermissions = $resolved;
    }

    public function hasPermission(string $name): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->resolvedPermissions()[$name] ?? false;
    }

    /**
     * Peran yang boleh ditetapkan pengguna ini. Super Admin memegang seluruh peran,
     * sedangkan Admin Sekolah hanya mengelola peran di sekolahnya sendiri.
     */
    public function assignableRoles(): array
    {
        if ($this->isSuperAdmin()) {
            return array_keys(appRoles());
        }

        return ['guru', 'siswa', 'orang_tua'];
    }

    /**
     * Apakah pengguna ini boleh membuka, mengubah, atau menghapus akun lain.
     * Admin Sekolah dibatasi pada tenant sendiri dan tidak boleh menyentuh
     * akun Super Admin maupun Admin Sekolah lain.
     */
    public function canManage(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isAdminSekolah()) {
            return false;
        }

        if ($target->is($this)) {
            return true;
        }

        return $target->tenant_id === $this->tenant_id
            && in_array($target->role, $this->assignableRoles(), true);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'user_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'user_id');
    }

    public function taughtAssessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'teacher_id');
    }

    public function studentParentLinks(): HasMany
    {
        return $this->hasMany(StudentParent::class, 'user_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }
}
