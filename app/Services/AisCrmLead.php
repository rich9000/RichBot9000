<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AisCrmLead
{
    /**
     * @param  array<string, mixed>  $fields
     */
    public function capture(array $fields): ?int
    {
        $url = rtrim((string) config('services.ais_crm.url'), '/');
        $token = (string) config('services.ais_crm.token');
        if ($url === '' || $token === '') {
            Log::warning('AIS CRM intake is not configured');

            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-Crm-Intake-Token' => $token,
                    'Accept' => 'application/json',
                ])
                ->post($url.'/api/crm/leads', $fields);
        } catch (\Throwable $e) {
            Log::warning('AIS CRM intake failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('AIS CRM intake failed', ['status' => $response->status()]);

            return null;
        }

        $id = (int) $response->json('company_id');

        return $id > 0 ? $id : null;
    }
}
