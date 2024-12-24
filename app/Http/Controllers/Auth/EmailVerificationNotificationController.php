<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse | JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Email already verified.'], 200);
            }

            return redirect()->intended(route('dashboard', absolute: false));

        }

        $request->user()->sendEmailVerificationNotification();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Email sent to '.$request->user()->email], 200);
        }

        return back()->with('status', 'verification-link-sent');

    }
}
