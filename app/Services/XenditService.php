<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    private string $secretKey;

    private string $baseUrl = 'https://api.xendit.co/v2';

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key');
    }

    public function createInvoice(array $data): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(30)
                ->post("{$this->baseUrl}/invoices", [
                    'external_id' => $data['external_id'],
                    'amount' => $data['amount'],
                    'payer_email' => $data['payer_email'],
                    'description' => $data['description'],
                    'success_redirect_url' => $data['success_url'],
                    'failure_redirect_url' => $data['failure_url'],
                    'currency' => 'IDR',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Xendit API Error: '.$response->body());

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Payment gateway error',
            ];

        } catch (\Exception $e) {
            Log::error('Xendit Service Exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Service unavailable',
            ];
        }
    }
}
