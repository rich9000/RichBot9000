<?php

namespace App\Http\Controllers;

use App\Services\AisCrmLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LandingContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $saved = app(AisCrmLead::class)->capture([
            'company' => $data['name'],
            'contact' => $data['name'],
            'email' => $data['email'],
            'message' => "Subject: {$data['subject']}\n\n{$data['message']}",
            'source' => 'contact_form',
            'link' => 'https://richbot9000.com/',
            'legacy' => 'RB-'.substr(hash('sha256', strtolower($data['email']).'|'.$data['subject'].'|'.$data['message']), 0, 24),
        ]);

        if (! $saved) {
            return response()->json([
                'message' => 'The message was not saved. Your text is still here. Email support@'.config('app.domain').' if this keeps happening.',
            ], 502);
        }

        $to = 'support@'.config('app.domain');
        $subject = '[richbot9000.com] '.$data['subject'];
        $body = "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}";

        try {
            Mail::raw($body, function ($message) use ($to, $subject, $data) {
                $message->to($to)
                    ->replyTo($data['email'], $data['name'])
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Landing contact form failed to send', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Received. Support has the message.',
        ]);
    }
}
