<?php

use App\Models\AuditLog;
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

if (! function_exists('deskripsiCapaian')) {
    function deskripsiCapaian(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Sangat Baik (A)',
            $score >= 75 => 'Baik (B)',
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
