<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{WorkshopQueue, WorkshopQueueService, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkshopStatsController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $jobs = WorkshopQueue::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $services = WorkshopQueueService::withoutGlobalScopes()
            ->whereHas('queue', fn($q) => $q->withoutGlobalScopes()->whereBetween('created_at', [$start, $end]))
            ->get();

        // --- Summary Stats ---
        $totalJobs       = $jobs->count();
        $deliveredJobs   = $jobs->where('status', 'delivered')->count();
        $pendingJobs     = $jobs->whereIn('status', ['waiting', 'in_progress', 'done'])->count();
        $totalServices   = $services->count();
        $completedServices = $services->where('is_done', true)->count();

        // Average time from created_at to done_at (minutes)
        $jobsWithDone = $jobs->filter(fn($j) => $j->done_at !== null);
        $avgCompletionMinutes = $jobsWithDone->count() > 0
            ? round($jobsWithDone->avg(fn($j) => $j->created_at->diffInMinutes($j->done_at)))
            : 0;

        // Average time from created_at to delivered_at
        $jobsWithDelivered = $jobs->filter(fn($j) => $j->delivered_at !== null);
        $avgDeliveryMinutes = $jobsWithDelivered->count() > 0
            ? round($jobsWithDelivered->avg(fn($j) => $j->created_at->diffInMinutes($j->delivered_at)))
            : 0;

        // --- Jobs per day chart (for the month) ---
        $jobsPerDay = $jobs->groupBy(fn($j) => $j->created_at->format('d'))
            ->map(fn($group, $day) => ['day' => (int) $day, 'count' => $group->count()])
            ->values()
            ->sortBy('day')
            ->values();

        // Fill missing days
        $daysInMonth = $start->daysInMonth;
        $dayMap = $jobsPerDay->keyBy('day');
        $jobsPerDayFull = collect();
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $jobsPerDayFull->push([
                'day'   => $d,
                'label' => str_pad($d, 2, '0', STR_PAD_LEFT) . '/' . str_pad($month, 2, '0', STR_PAD_LEFT),
                'count' => $dayMap->has($d) ? $dayMap[$d]['count'] : 0,
            ]);
        }

        // --- Service type distribution ---
        $serviceDistribution = $services->groupBy(function ($s) {
                $label = strtolower(trim($s->label));
                if (str_contains($label, 'découpe') || str_contains($label, 'decoupe') || str_contains($label, 'tefsil')) return 'Découpe';
                if (str_contains($label, 'collage') || str_contains($label, 'canto') || str_contains($label, 'chant')) return 'Collage Chant';
                if (str_contains($label, 'ponçage') || str_contains($label, 'poncage') || str_contains($label, 'ponsage')) return 'Ponçage';
                if (str_contains($label, 'placage') || str_contains($label, 'plaquage')) return 'Placage';
                return ucfirst($s->label);
            })
            ->map(fn($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->values()
            ->sortByDesc('count')
            ->values();

        // --- Worker performance ---
        $completedByWorker = $services->where('is_done', true)
            ->whereNotNull('done_by')
            ->groupBy('done_by')
            ->map(function ($group, $userId) {
                $user = User::find($userId);
                return [
                    'worker_id'   => $userId,
                    'worker_name' => $user ? $user->name : 'Inconnu',
                    'services_done' => $group->count(),
                    'avg_minutes' => $group->filter(fn($s) => $s->done_at && $s->created_at)
                        ->avg(fn($s) => $s->created_at->diffInMinutes($s->done_at)) ?? 0,
                ];
            })
            ->values()
            ->sortByDesc('services_done')
            ->values();

        // --- Status distribution ---
        $statusDistribution = $jobs->groupBy('status')
            ->map(fn($group, $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        // --- Busiest hours ---
        $busiestHours = $jobs->groupBy(fn($j) => $j->created_at->format('H'))
            ->map(fn($group, $hour) => ['hour' => $hour . 'h', 'count' => $group->count()])
            ->sortBy('hour')
            ->values();

        // Month name for display
        $monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

        return response()->json([
            'month_name'       => $monthNames[$month - 1] . ' ' . $year,
            'summary' => [
                'total_jobs'           => $totalJobs,
                'delivered_jobs'       => $deliveredJobs,
                'pending_jobs'         => $pendingJobs,
                'total_services'       => $totalServices,
                'completed_services'   => $completedServices,
                'avg_completion_min'   => $avgCompletionMinutes,
                'avg_delivery_min'     => $avgDeliveryMinutes,
                'delivery_rate'        => $totalJobs > 0 ? round(($deliveredJobs / $totalJobs) * 100, 1) : 0,
            ],
            'jobs_per_day'          => $jobsPerDayFull,
            'service_distribution'  => $serviceDistribution,
            'worker_performance'    => $completedByWorker,
            'status_distribution'   => $statusDistribution,
            'busiest_hours'         => $busiestHours,
        ]);
    }
}
