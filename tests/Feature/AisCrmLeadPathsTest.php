<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AisCrmLeadPathsTest extends TestCase
{
    public function test_homepage_form_writes_a_crm_lead_before_mail(): void
    {
        Mail::fake();
        config(['services.ais_crm.url' => 'https://aisolutionskc.com', 'services.ais_crm.token' => 'test-token']);
        Http::fake([
            'https://aisolutionskc.com/api/crm/leads' => Http::response(['company_id' => 41, 'url' => 'https://aisolutionskc.com/admin/crm/41']),
        ]);

        $this->postJson('/contact', [
            'name' => 'Pat Example',
            'email' => 'pat@example.com',
            'subject' => 'A question',
            'message' => 'Please call me back.',
        ])->assertOk()->assertJsonPath('message', 'Received. Support has the message.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://aisolutionskc.com/api/crm/leads'
                && $request->hasHeader('X-Crm-Intake-Token', 'test-token')
                && $request['source'] === 'contact_form'
                && $request['email'] === 'pat@example.com'
                && $request['link'] === 'https://richbot9000.com/';
        });
    }

    public function test_homepage_form_does_not_claim_success_when_crm_rejects_it(): void
    {
        Mail::fake();
        config(['services.ais_crm.url' => 'https://aisolutionskc.com', 'services.ais_crm.token' => 'test-token']);
        Http::fake([
            'https://aisolutionskc.com/api/crm/leads' => Http::response(['message' => 'no'], 500),
        ]);

        $this->postJson('/contact', [
            'name' => 'Pat Example',
            'email' => 'pat@example.com',
            'subject' => 'A question',
            'message' => 'Please call me back.',
        ])->assertStatus(502);

        Mail::assertNothingSent();
    }
}
