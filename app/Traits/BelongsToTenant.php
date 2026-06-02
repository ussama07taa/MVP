<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;

trait BelongsToTenant {
    protected static function bootBelongsToTenant() {
        // Multi-tenancy is disabled, but we keep the trait for DB compatibility.
        // We set a default tenant_id to prevent null constraints.
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = 1;
            }
        });
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
