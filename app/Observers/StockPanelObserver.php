<?php

namespace App\Observers;

use App\Models\StockPanel;
use Illuminate\Support\Facades\Cache;

class StockPanelObserver
{
    private function clearCache(StockPanel $panel)
    {
        // Clear the cached panels list for this specific tenant
        Cache::forget("tenant.{$panel->tenant_id}.panels");
    }

    public function saved(StockPanel $panel): void
    {
        $this->clearCache($panel);
    }

    public function deleted(StockPanel $panel): void
    {
        $this->clearCache($panel);
    }

    public function restored(StockPanel $panel): void
    {
        $this->clearCache($panel);
    }

    public function forceDeleted(StockPanel $panel): void
    {
        $this->clearCache($panel);
    }
}
