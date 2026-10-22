<?php

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

if (! function_exists('hariName')) {
    /**
     * Nama hari bahasa Indonesia dari angka ISO weekday (1 = Senin sampai 7 = Minggu).
     * Dipakai seluruh label hari di Jadwal, Jurnal KBM, Absen Manual, Guru, Rombel, dan Mata Pelajaran.
     */
    function hariName(int $day): string
    {
        $hari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return $hari[$day] ?? '-';
    }
}

if (! function_exists('hariPendek')) {
    /**
     * Bentuk tiga huruf dari nama hari, untuk label sumbu grafik dan pesan ringkas.
     */
    function hariPendek(int $day): string
    {
        $hari = [
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            6 => 'Sab',
            7 => 'Min',
        ];

        return $hari[$day] ?? '-';
    }
}

if (! function_exists('currentTenantId')) {
    function currentTenantId(): ?string
    {
        if (app()->bound('currentTenant')) {
            return app('currentTenant')->id;
        }

        if (Auth::hasUser() && Auth::user()->tenant_id) {
            return Auth::user()->tenant_id;
        }

        return null;
    }
}

if (! function_exists('setting')) {
    /**
     * Baca nilai pengaturan.
     *
     * Group `tenant_scoped` hanya boleh dibaca ketika konteks pemanggil benar
     * punya tenant. Super Admin dan konteks tanpa sesi (queue, console, job)
     * tidak punya sekolah sendiri, jadi nilai bawaan dari config yang dikembalikan,
     * bukan milik tenant lain. Kalau tidak, pengaturan yang disimpan Super Admin
     * akan diam-diam menimpa profil sekolah tenant tertua.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        [$group, $name] = explode('.', $key, 2);

        $tenantScoped = (bool) config("school-settings.$group.tenant_scoped", false);
        $cfg = config("school-settings.$group.fields.$name");
        $fallback = $default ?? ($cfg['default'] ?? null);

        $query = Setting::query()->where('group', $group)->where('key', $name);

        if ($tenantScoped) {
            $tenantId = currentTenantId();

            if (! $tenantId) {
                return $fallback;
            }

            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        $value = $query->value('value');

        if (is_null($value) || $value === '') {
            return $fallback;
        }

        return match ($cfg['type'] ?? 'text') {
            'number' => (int) $value,
            'toggle' => $value === '1',
            default => $value,
        };
    }
}

if (! function_exists('appRoles')) {
    function appRoles(): array
    {
        return config('permissions.roles', []);
    }
}

if (! function_exists('roleLabel')) {
    function roleLabel(string $role): string
    {
        return config("permissions.roles.{$role}", $role);
    }
}

if (! function_exists('permissionGroups')) {
    /**
     * Grup izin lengkap dengan labelnya, diambil dari config/permissions.php.
     *
     * @return array<string, array{label: string, description: string, permissions: array<int, array{name: string, label: string, roles: array<int, string>}>}>
     */
    function permissionGroups(): array
    {
        return config('permissions.groups', []);
    }
}

if (! function_exists('permissionCatalogue')) {
    /**
     * Katalog izin datar: nama izin => definisinya.
     *
     * @return array<string, array{name: string, label: string, group: string, group_label: string, description: string, roles: array<int, string>}>
     */
    function permissionCatalogue(): array
    {
        $catalogue = [];

        foreach (permissionGroups() as $groupKey => $group) {
            foreach ($group['permissions'] as $permission) {
                $catalogue[$permission['name']] = [
                    'name' => $permission['name'],
                    'label' => $permission['label'],
                    'group' => $groupKey,
                    'group_label' => $group['label'],
                    'description' => $group['description'],
                    'roles' => $permission['roles'],
                ];
            }
        }

        return $catalogue;
    }
}

if (! function_exists('permissionNames')) {
    /**
     * @return array<int, string>
     */
    function permissionNames(): array
    {
        return array_keys(permissionCatalogue());
    }
}

if (! function_exists('permissionDefaultsForRole')) {
    /**
     * Izin default sebuah role, mengikuti gate `role:` yang sudah berjalan di routes/web.php.
     *
     * @return array<int, string>
     */
    function permissionDefaultsForRole(string $role): array
    {
        $names = [];

        foreach (permissionCatalogue() as $name => $permission) {
            if (in_array($role, $permission['roles'], true)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}

if (! function_exists('deskripsiCapaian')) {
    function deskripsiCapaian(float $score): string
    {
        $kkm = (int) setting('penilaian.kkm', 75);

        return match (true) {
            $score >= 90 => 'Sangat Baik (A)',
            $score >= $kkm => 'Baik (B)',
            $score >= 60 => 'Cukup (C)',
            default => 'Perlu Bimbingan (D)',
        };
    }
}

if (! function_exists('log_audit')) {
    function log_audit(
        string $action,
        ?Model $entity,
        ?array $old = null,
        ?array $new = null,
        ?int $entityId = null,
        string $entityType = 'System'
    ): void {
        if (! Auth::hasUser()) {
            return;
        }

        $user = Auth::user();

        AuditLog::create([
            'tenant_id' => currentTenantId(),
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : $entityType,
            'entity_id' => $entityId ?? $entity?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }
}
