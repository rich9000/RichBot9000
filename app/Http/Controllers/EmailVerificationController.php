<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    // Request a new email verification token
    public function requestEmailVerificationToken(Request $request)
    {
        $user = $request->user();

        Log::info(json_encode($user));

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified'], 400);
        }

        // Generate a random verification token (e.g., 6 digits or a UUID)
        $token = Str::random(6);

        // Store the token in the cache or your preferred storage (e.g., database)
        Cache::put('email_verification_' . $user->id, $token, 60 * 60); // 1-hour expiration

        // Send the token via email
        Mail::send('emails.verify', ['token' => $token], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Email Verification');
        });

        return response()->json(['message' => 'Verification email sent successfully']);
    }

    // Verify the email token
    public function verifyEmailToken(Request $request)
    {
        $user = Auth::user();

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
        $storedToken = Cache::get('email_verification_' . $user->id);

        if (!$storedToken || $storedToken !== $request->token) {
            return response()->json(['error' => 'Invalid or expired token'], 422);
        }

        // Mark the email as verified (update the user's record in the database)
        $user->email_verified_at = now();
        $user->save();

        // Optionally, remove the token from the cache/storage
        Cache::forget('email_verification_' . $user->id);

        return response()->json(['message' => 'Email verified successfully']);
    }
}
