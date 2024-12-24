<?php
namespace App\Services;

use App\Models\EventLog;
use Illuminate\Support\Facades\Auth;

class EventLogger
{
    /**
     * Log an event to the database.
     *
     * @param mixed $loggable
     * @param string $eventType
     * @param string|null $description
     * @param array $data
     * @return void
     */
    public static function log($loggable, string $eventType, ?string $description = null, array $data = [])
    {

        EventLog::create([
            'user_id' => Auth::id(), // Optional: Associate the event with the currently authenticated user
            'event_type' => $eventType,
            'description' => $description,
            'data' => $data,
            'loggable_type' => get_class($loggable),
            'loggable_id' => $loggable->id,
        ]);
    }
    public static function simpleLog(string $eventType, ?string $description = null, array $data = [])
    {

        EventLog::create([
            //'user_id' => Auth::id(), // Optional: Associate the event with the currently authenticated user
            'event_type' => $eventType,
            'description' => $description,
            'data' => $data,
            //'loggable_type' => get_class($loggable),
            //'loggable_id' => $loggable->id,
        ]);
    }
}
