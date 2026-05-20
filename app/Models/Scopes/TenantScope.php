<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Prevent infinite recursion if the model is User
        if ($model instanceof \App\Models\User) {
            return;
        }

        // 1) Authenticated user takes priority (works in HTTP requests AND in tests via actingAs).
        if (Auth::check() && Auth::user()->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', Auth::user()->tenant_id);
            return;
        }

        // 2) Explicitly-bound tenant (queues, cron jobs that set current_tenant_id).
        if (app()->bound('current_tenant_id')) {
            $builder->where($model->getTable() . '.tenant_id', app('current_tenant_id'));
            return;
        }

        // 3) Pure console context (artisan migrate, seeders, manual maintenance) — skip scoping.
        if (App::runningInConsole()) {
            return;
        }
    }
}
