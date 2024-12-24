<?php

namespace App\Http\Middleware;

use Closure;
use Twilio\Security\RequestValidator;
use Illuminate\Http\Request;

class ValidateTwilioRequest
{
    public function handle(Request $request, Closure $next)
    {
        $validator = new RequestValidator(config('services.twilio.auth_token'));
        
        $signature = $request->header('X-Twilio-Signature');
        $url = $request->fullUrl();
        $params = $request->toArray();

        if (!$validator->validate($signature, $url, $params)) {
            abort(403, 'Invalid Twilio signature');
        }

        return $next($request);
    }
} 