<?php

namespace App\Services;

class FinancialStatsService
{
    public function __construct(private FinancialReportService $reports)
    {
    }

    public function getMonthlyStats($month, $year, $tenantId = null): array
    {
        return $this->reports->getMonthlyStats((int) $month, (int) $year);
    }
}
