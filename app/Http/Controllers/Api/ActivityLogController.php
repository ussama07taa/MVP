<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Ensure only Admins can view logs. In a real app, use Policies/Middleware.
        if ($request->user()->role !== 'admin') abort(403);

        $query = Activity::where('tenant_id', auth()->user()->tenant_id)
            ->with('causer'); // 'causer' is the User who did the action

        // Optional: Filter by event type (created, updated, deleted)
        if ($request->has('event') && $request->event !== 'all') {
            $query->where('event', $request->event);
        }

        $logs = $query->latest()->paginate(50);

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->causer ? $log->causer->name : 'Système',
                    'action' => $log->description,
                    'subject_type' => class_basename($log->subject_type),
                    'subject_id' => $log->subject_id,
                    'properties' => [
                        'old' => $log->properties->get('old', []),
                        'new' => $log->properties->get('attributes', []),
                    ],
                    'is_update' => $log->event === 'updated' && $log->properties->has('old'),
                    'date' => $log->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $log->created_at->diffForHumans()
                ];
            }),
            'meta' => ['total' => $logs->total(), 'last_page' => $logs->lastPage()]
        ]);
    }
}
