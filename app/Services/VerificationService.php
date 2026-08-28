<?php

namespace App\Services;

use App\Models\User;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\SmsVerificationController;
use Illuminate\Http\Request;

class VerificationService
{
    /**
     * Send verification tokens to a user
     */
    public function sendVerificationTokens(User $user): array
    {
        $results = [
            'email' => false,
            'sms' => false,
            'errors' => []
        ];

        try {
            // Send email verification token
            $emailController = new EmailVerificationController();
            $emailRequest = $this->createRequestWithUser($user);
            $emailController->requestEmailVerificationToken($emailRequest);
            $results['email'] = true;

        } catch (\Exception $e) {
            $results['errors']['email'] = $e->getMessage();
        }

        try {
            // Send SMS verification token
            $smsController = new SmsVerificationController();
            $smsRequest = $this->createRequestWithUser($user);
            $smsController->requestSmsVerificationToken($smsRequest);
            $results['sms'] = true;

        } catch (\Exception $e) {
            $results['errors']['sms'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create a request object with the user set
     */
    private function createRequestWithUser(User $user): Request
    {
        $request = new Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $request;
    }
} 