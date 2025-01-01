<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

class TwilioLogger
{
    private static function log($direction, $from, $to, $message, $context = [])
    {
        $logMessage = sprintf(
            "\n[%s] %s -> %s\n%s\n%s\n",
            $direction,
            $from,
            $to,
            json_encode($message, JSON_PRETTY_PRINT),
            json_encode($context, JSON_PRETTY_PRINT),
            str_repeat('-', 80)
        );

        Log::channel('twilio')->info($logMessage, $context);
    }

    public static function inbound($message, $context = [])
    {
        self::log('INBOUND', 'Twilio', 'Richbot', $message, $context);
    }

    public static function outbound($message, $context = [])
    {
        self::log('OUTBOUND', 'Richbot', 'Twilio', $message, $context);
    }

    public static function converted($from, $to, $context = [])
    {
        $logMessage = sprintf(
            "\n[CONVERTED]\nFrom: %s\nTo: %s\nContext: %s\n%s\n",
            json_encode($from, JSON_PRETTY_PRINT),
            json_encode($to, JSON_PRETTY_PRINT),
            json_encode($context, JSON_PRETTY_PRINT),
            str_repeat('-', 80)
        );

        Log::channel('twilio')->info($logMessage);
    }
} 