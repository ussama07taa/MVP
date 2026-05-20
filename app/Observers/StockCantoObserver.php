<?php

namespace App\Observers;

use App\Models\StockCanto;
use Illuminate\Support\Facades\Cache;

class StockCantoObserver
{
    private function clearCache(StockCanto $canto)
    {
        // Clear the cached cantos list for this specific tenant
        Cache::forget("tenant.{$canto->tenant_id}.cantos");
    }

    public function saved(StockCanto $canto): void
    {
        $this->clearCache($canto);
    }

    public function deleted(StockCanto $canto): void
    {
        $this->clearCache($canto);
    }

    public function restored(StockCanto $canto): void
    {
        $this->clearCache($canto);
    }

    public function forceDeleted(StockCanto $canto): void
    {
        $this->clearCache($canto);
    }
}
