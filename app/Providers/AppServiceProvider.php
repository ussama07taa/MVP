<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Model::shouldBeStrict(! $this->app->environment('production'));

        \App\Models\StockPanel::observe(\App\Observers\StockPanelObserver::class);
        \App\Models\StockCanto::observe(\App\Observers\StockCantoObserver::class);

        // Automatic tenant_id for Activity Logs
        \Spatie\Activitylog\Models\Activity::saving(function ($activity) {
            if (auth()->check() && !$activity->tenant_id) {
                $activity->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
