<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class EventLogController extends Controller
{
    /**
     * Display a listing of the event logs.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $eventLogs = EventLog::with('user')->latest()->paginate(10);

        $isUserSpecific = $request->input('user_specific', false);

        if ($isUserSpecific) {
            // Get the authenticated user
            $user = $request->user();

            // Fetch event logs related to the current user
            $query = EventLog::where('user_id', $user->id)->latest();

            // Return JSON if the request expects JSON
            if ($request->wantsJson()) {
                return datatables()->eloquent($query)
                    ->addColumn('user.name', function ($eventLog) {
                        return $eventLog->user->name ?? 'N/A';
                    })
                    ->toJson();
            }

            // Return the view for web requests
            $eventLogs = $query->paginate(10);
            return view('eventlogs.index', compact('eventLogs'));
        }

        // Return JSON if the request expects JSON
        if ($request->wantsJson()) {
            $query = EventLog::with('user');

            return datatables()->eloquent($query)
                ->addColumn('user.name', function ($eventLog) {
                    return $eventLog->user->name ?? 'N/A';
                })
                ->toJson();
        }

        // Return the view for web requests
        return view('eventlogs.index', compact('eventLogs'));
    }

    /**
     * Display the specified event log.
     *
     * @param  EventLog  $eventLog
     * @param  Request  $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(EventLog $eventLog, Request $request)
    {
        $eventLog->load('user');

        // Return JSON if the request expects JSON
        if ($request->wantsJson()) {
            return response()->json($eventLog);
        }

        // Return the view for web requests
        return view('eventlogs.show', compact('eventLog'));
    }
}
