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


//dump($request->all());



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
}
