<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assistant;
use App\Models\ScheduledCronbot;
use App\Models\Tool;
use App\Models\Conversation;
use App\Models\EventLog;
use App\Models\Contact;
use App\Models\Integration;
use App\Models\Survey;
use App\Models\Pipeline;
use App\Models\RemoteRichbot;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats()
    {
        return response()->json([
            'activeAssistants' => Assistant::count(),
            'activeCronbots' => ScheduledCronbot::where('is_active', true)->count(),
            'availableTools' => Tool::count(),
            'totalChats' => Conversation::count(),
            'contacts' => Contact::count(),
            'integrations' => Integration::count(),
            'surveys' => Survey::count(),
            'pipelines' => Pipeline::count(),
        ]);
    }

    public function getActivity()
    {
        $activities = EventLog::with(['user', 'loggable'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->log_type,
                    'description' => $activity->message,
                    'status' => $activity->status,
                    'created_at' => $activity->created_at,
                    'user' => $activity->user ? $activity->user->name : 'System'
                ];
            });

        return response()->json($activities);
    }

    public function getUpcomingCronbots()
    {
        $cronbots = ScheduledCronbot::with('assistant')
            ->where('next_run_at', '>', Carbon::now())
            ->where('is_active', true)
            ->orderBy('next_run_at', 'asc')
            ->take(5)
            ->get()
            ->map(function ($cronbot) {
                return [
                    'id' => $cronbot->id,
                    'assistant_name' => $cronbot->assistant?->name ?? 'N/A',
                    'prompt' => $cronbot->prompt,
                    'next_run_at' => $cronbot->next_run_at,
                    'status' => $cronbot->status,
                    'repeat_interval' => $cronbot->repeat_interval
                ];
            });

        return response()->json($cronbots);
    }

    public function getRemoteRichbots()
    {
        try {


            $richbots = RemoteRichbot::where('status','!=', 'inactive')
                ->orderBy('last_seen', 'desc')             
                ->get()
                ->map(function ($richbot) {
                    return [
                        'id' => $richbot->id,
                        'name' => $richbot->name,
                        'status' => $this->getRichbotStatus($richbot),
                        'last_seen' => $richbot->last_seen ? Carbon::parse($richbot->last_seen)->format('Y-m-d H:i:s') : 'Never',
                        'location' => $richbot->location
                    ];
                });

            return response()->json($richbots);
        } catch (\Exception $e) {
            \Log::error('Error fetching remote richbots: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch remote richbots'], 500);
        }
    }

    private function getRichbotStatus($richbot)
    {
        if (!$richbot->last_seen) {
            return 'offline';
        }

        $lastSeen = Carbon::parse($richbot->last_seen);
        $now = Carbon::now();
        $minutesSinceLastSeen = $now->diffInMinutes($lastSeen);

        if ($minutesSinceLastSeen < 5) {
            return 'online';
        } elseif ($minutesSinceLastSeen < 15) {
            return 'idle';
        } else {
            return 'offline';
        }
    }
} 