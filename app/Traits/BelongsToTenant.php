<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;

trait BelongsToTenant {
    protected static function bootBelongsToTenant() {
        static::addGlobalScope(new TenantScope);
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
