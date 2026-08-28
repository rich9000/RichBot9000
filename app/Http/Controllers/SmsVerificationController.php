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
use Illuminate\Support\Facades\Log;
class SmsVerificationController extends Controller
{
    // Request a new SMS verification token
    public function requestSmsVerificationToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // CHANGE THE CODE TO RANDOM NUMBERS
        $token = rand(100000, 999999);
        // Generate a random verification token (e.g., 6 digits)
        //$token = Str::random(6);
        // Store the token in the cache or your preferred storage (e.g., database)

        Cache::put('sms_verification_' . $user->id, $token, 10 * 60); // 10 minutes expiration

        Log::info('sms_verification_'.$user->id, [$token]);
        



        $sms = new Twilio();
        $sms->sendTwilioText('RichBot9000 secret sms code: '.$token,$user->phone_number);        
        //Log::info('sms sent to '.$user->phone_number);


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

        //dd($request->all());
        //dd($request->all());

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'size:6'],
        ]);


        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cache_key = 'sms_verification_' . $user->id;

        //dd($cache_key);
        // Retrieve the stored token from the cache or your preferred storage
        $storedToken = intval(Cache::get($cache_key));

        $token = intval($request->token);

        //dd($storedToken, $request->token);
        //dd($storedToken, $request->token,$cache_key);

        //dd($storedToken,$token,$cache_key);

        if (!$storedToken || $storedToken !== $token) {
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
