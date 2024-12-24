<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Twilio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class SmsVerificationController extends Controller
{
    // Request a new SMS verification token
    public function requestSmsVerificationToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Generate a random verification token (e.g., 6 digits)
        $token = Str::random(6);

        // Store the token in the cache or your preferred storage (e.g., database)
        Cache::put('sms_verification_' . $user->id, $token, 10 * 60); // 10 minutes expiration


        $sms = new Twilio();
        $sms->sendTwilioText('RichBot9000 secret sms code: '.$token,$user->phone_number);


        // Here, you would integrate with an SMS service to send the token to the user's phone number
        // Example: SendSMS::send($user->phone_number, "Your verification code is: $token");

        return response()->json(['message' => "SMS Verification token sent successfully ($token)"]);
    }

    // Verify the SMS token
    public function verifySmsToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve the stored token from the cache or your preferred storage
        $storedToken = Cache::get('sms_verification_' . $user->id);

        if (!$storedToken || $storedToken !== $request->token) {
            return response()->json(['error' => 'Invalid or expired token'], 422);
        }

        // Mark the phone number as verified (you can update the user's record in the database)
        $user->phone_verified_at = now();
        $user->save();

        // Optionally, remove the token from the cache/storage
        Cache::forget('sms_verification_' . $user->id);

        return response()->json(['message' => 'Phone number verified successfully','user'=>$user]);
    }
}
