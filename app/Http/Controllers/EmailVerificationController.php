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

        Log::info('requestEmailVerificationToken');
        Log::info('user',[$user]);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified'], 400);
        }

        // Generate a random verification token (e.g., 6 digits or a UUID)
        $token = Str::random(6);

        // Store the token in the cache with user ID
        Cache::put('email_verification_' . $user->id, $token, 60 * 60); // 1-hour expiration
        
        // Also store a reverse lookup for efficient token-to-user mapping
        Cache::put('email_verification_user_' . $token, $user->id, 60 * 60); // 1-hour expiration

        // Try to send the email, but don't fail if it doesn't work
        try {
            Mail::send('emails.verify', ['token' => $token], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Email Verification');
            });
            $mailStatus = 'Email sent successfully.';
        } catch (\Exception $e) {
            Log::warning('Failed to send verification email: ' . $e->getMessage());
            $mailStatus = 'Email delivery failed (testing environment).';
        }

        // Always return the token for testing purposes
        return response()->json([
            'message' => $mailStatus . ' Your verification code is: ' . $token,
            'token' => $token,
            'email_status' => $mailStatus
        ]);
    }

    // Verify the email token
    public function verifyEmailToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $token = $request->token;

        // Find the user ID using the reverse lookup
        $userId = Cache::get('email_verification_user_' . $token);

        if (!$userId) {
            return response()->json(['error' => 'Invalid or expired token', 'token' => $token, 'userId' => $userId], 422);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified'], 400);
        }

        // Verify the token matches what's stored for this user
        $storedToken = Cache::get('email_verification_' . $user->id);
        if ($storedToken !== $token) {
            return response()->json(['error' => 'Invalid or expired token'], 422);
        }

        // Mark the email as verified (update the user's record in the database)
        $user->email_verified_at = now();
        $user->save();

        // Remove both cache entries
        Cache::forget('email_verification_' . $user->id);
        Cache::forget('email_verification_user_' . $token);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => $user
        ]);
    }

    // Public verification endpoint that doesn't require authentication
    public function verifyEmailTokenPublic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $token = $request->token;

        // Find the user ID using the reverse lookup
        $userId = Cache::get('email_verification_user_' . $token);

        if (!$userId) {
            return response()->json(['error' => 'Invalid or expired token'], 422);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified'], 400);
        }

        // Verify the token matches what's stored for this user
        $storedToken = Cache::get('email_verification_' . $user->id);
        if ($storedToken !== $token) {
            return response()->json(['error' => 'Invalid or expired token'], 422);
        }

        // Mark the email as verified
        $user->email_verified_at = now();
        $user->save();

        // Remove both cache entries
        Cache::forget('email_verification_' . $user->id);
        Cache::forget('email_verification_user_' . $token);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => $user
        ]);
    }
}
