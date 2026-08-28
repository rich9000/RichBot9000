<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Services\EventLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\SmsVerificationController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function store(Request $request)
    {



        try {

        // Validate the incoming request data
     $items =    $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
     //       'phone_number' => ['required', 'string', 'max:15'], // Add validation for phone number
           'phone_number' => ['required', 'string', 'max:15', 'unique:' . User::class], // Add validation for phone number
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

      //dd($request->all());
//dd($items);


        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number, // Store the phone number
            'password' => Hash::make($request->password),
            'email_verification_token' => Str::random(6),
            'phone_verification_token' => Str::random(6), // Generate and store phone verification token
        ]);

        // Fire the registered event
        event(new Registered($user));

        // Log the user in
        Auth::login($user);

        // Send verification tokens
        $this->sendVerificationTokens($user);

        $token = $user->createToken('API Token')->plainTextToken;

        // Log the token creation event
        EventLogger::log($user, 'registration', 'User registered and API Auth Token Created.', [
            'ip' => $request->ip(),
            'token' => $token,
        ]);

        // Check if the request expects a JSON response
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Registration successful',
                'token' => $token,
                'user' => $user,
            ], 201);
        }


        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => $user,
        ], 201);


        // Redirect to the dashboard for web requests
        return redirect()->route('dashboard')->with('status', 'User registered successfully.');
    }

    /**
     * Send verification tokens to the newly registered user
     */
    private function sendVerificationTokens(User $user)
    {
        try {
            // Send email verification token
            $this->sendEmailVerificationToken($user);

            // Send SMS verification token
            $this->sendSmsVerificationToken($user);

            // Log the verification tokens sent
            EventLogger::log($user, 'verification_tokens_sent', 'Email and SMS verification tokens sent to new user.', [
                'email' => $user->email,
                'phone' => $user->phone_number,
            ]);

        } catch (\Exception $e) {
            // Log the error but don't fail the registration
            EventLogger::log($user, 'verification_tokens_failed', 'Failed to send verification tokens.', [
                'error' => $e->getMessage(),
                'email' => $user->email,
                'phone' => $user->phone_number,
            ]);
        }
    }

    /**
     * Send email verification token
     */
    private function sendEmailVerificationToken(User $user)
    {
        // Generate a random verification token
        $token = Str::random(6);

        // Store the token in cache
        Cache::put('email_verification_' . $user->id, $token, 60 * 60); // 1 hour
        
        // Also store a reverse lookup for efficient token-to-user mapping
        Cache::put('email_verification_user_' . $token, $user->id, 60 * 60); // 1-hour expiration

        Log::info('email_verification_'.$user->id, [$token]);

        // Send email
        try {
            Mail::send('emails.verify', ['token' => $token], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Email Verification');
            });
        } catch (\Exception $e) {
            // Log email failure but don't break registration
            EventLogger::log($user, 'email_verification_failed', 'Failed to send email verification.', [
                'error' => $e->getMessage(),
                'email' => $user->email,
            ]);
        }
    }

    /**
     * Send SMS verification token
     */
    private function sendSmsVerificationToken(User $user)
    {
        // Generate a random verification token
        //
        $token = rand(100000, 999999);




        // Store the token in cache
        
        Cache::put('sms_verification_' . $user->id, $token, 10 * 60); // 10 minutes


        Log::info('sms_verification_'.$user->id, [$token]);

        // Send SMS
        try {
            $sms = new \App\Services\Twilio();
            $sms->sendTwilioText('RichBot9000 secret sms code: ' . $token, $user->phone_number);
        } catch (\Exception $e) {
            // Log SMS failure but don't break registration
            EventLogger::log($user, 'sms_verification_failed', 'Failed to send SMS verification.', [
                'error' => $e->getMessage(),
                'phone' => $user->phone_number,
            ]);
        }
    }
}
