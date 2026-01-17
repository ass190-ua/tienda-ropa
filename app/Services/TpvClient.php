<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TpvClient
{
    public function refundExternal(string $transactionToken, float $amount): array
    {
        $baseUrl = rtrim(config('services.tpv.base_url'), '/');
        $apiKey  = config('services.tpv.api_key');

        $url = $baseUrl . '/api/v1/refunds/external';

        $resp = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->post($url, [
            'transactionToken' => $transactionToken,
            'amount' => $amount,
        ]);

        return [
            'ok' => $resp->successful(),
            'status' => $resp->status(),
            'data' => $resp->json(),
        ];
    }
}
