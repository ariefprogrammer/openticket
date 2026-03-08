<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DokuService
{
    public function createCheckout(array $params)
    {
        $clientId = env('DOKU_CLIENT_ID');
        $secretKey = env('DOKU_SECRET_KEY');
        $requestId = (string) Str::uuid();
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        
        $targetPath = '/checkout/v1/payment';
        $baseUrl = env('DOKU_IS_PRODUCTION') 
            ? 'https://api.doku.com' 
            : 'https://api-sandbox.doku.com';

        $body = [
            'order' => [
                'amount' => (int) $params['amount'],
                'invoice_number' => $params['invoice'],
                'callback_url' => route('doku.callback'), // Akan kita buat
            ],
            'customer' => [
                'name' => $params['customer_name'],
                'email' => $params['customer_email'],
            ],
        ];

        // Membuat Signature DOKU
        $digest = base64_encode(hash('sha256', json_encode($body), true));
        $rawSignature = "Client-Id:" . $clientId . "\n" .
                        "Request-Id:" . $requestId . "\n" .
                        "Request-Timestamp:" . $timestamp . "\n" .
                        "Request-Target:" . $targetPath . "\n" .
                        "Digest:" . $digest;
        
        $signature = base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));

        $response = Http::withHeaders([
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => "HMACSHA256=" . $signature,
        ])->post($baseUrl . $targetPath, $body);

        return $response->json();
    }
}