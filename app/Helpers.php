<?php

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

if (! Carbon::hasMacro('day')) {
    Carbon::macro('day', fn (int $day): Carbon => Carbon::parse('2026-01-05')->addDays($day - 1));
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
    function setting(string $key, mixed $default = null): mixed
    {
        [$group, $name] = explode('.', $key, 2);

        $tenantScoped = (bool) config("school-settings.$group.tenant_scoped", false);
        $cfg = config("school-settings.$group.fields.$name");

        $query = Setting::query()->where('group', $group)->where('key', $name);

        if ($tenantScoped) {
            $query->where('tenant_id', currentTenantId() ?: Tenant::query()
                ->where('status', 'active')
                ->orderBy('created_at')
                ->value('id'));
        } else {
            $query->whereNull('tenant_id');
        }

        $value = $query->value('value');

        if (is_null($value) || $value === '') {
            return $default ?? ($cfg['default'] ?? null);
        }

        return match ($cfg['type'] ?? 'text') {
            'number' => (int) $value,
            'toggle' => $value === '1',
            default => $value,
        };
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
    function log_audit(string $action, Model $entity, ?array $old = null, ?array $new = null): void
    {
        if (! Auth::hasUser()) {
            return;
        }

        $user = Auth::user();

        AuditLog::create([
            'tenant_id' => currentTenantId(),
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }
}
