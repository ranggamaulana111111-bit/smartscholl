<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (is_null($model->tenant_id) && Auth::hasUser()) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $query) {
            if (Auth::hasUser() && ! Auth::user()->isSuperAdmin()) {
                $query->where('tenant_id', Auth::user()->tenant_id);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        if (Auth::hasUser() && ! Auth::user()->isSuperAdmin()) {
            return $query->where('tenant_id', Auth::user()->tenant_id);
        }

        return $query;
    }
}
