<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

trait VoIPNotification
{
    public function sendVoIPNotification($token, $message)
    {
        try {
            $isProduction = config('services.apns.production');
            $keyfile = public_path(config('services.apns.keyfile'));
            $keyid = config('services.apns.keyid');
            $teamid = config('services.apns.teamid');
            $bundleid = config('services.apns.bundleid');
            $url = $isProduction ? 'https://api.push.apple.com' : 'https://api.development.push.apple.com';
            // dd($url);
            $signature = '8c03b624427331b7ca570210ea54b25af70c6c7d28f82e43fd05685e67d2e731';
            $dataSendToApns = $message;
            $key = openssl_pkey_get_private('file://' . $keyfile);
            $header = ['alg' => 'ES256', 'kid' => $keyid];
            $claims = ['iss' => $teamid, 'iat' => time()];

            $header_encoded = $this->base64($header);
            $claims_encoded = $this->base64($claims);

            openssl_sign($header_encoded . '.' . $claims_encoded, $signature, $key, 'sha256');
            $jwt = $header_encoded . '.' . $claims_encoded . '.' . base64_encode($signature);

            // Use Guzzle HTTP client directly with custom options
            $client = new Client([
                'http_errors' => false, // Disable throwing exceptions on HTTP errors
                'verify' => false, // Disable SSL verification (temporary)
                'curl' => [
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                ],
            ]);

            $response = $client->post("$url/3/device/$token", [
                'headers' => [
                    'apns-topic' => $bundleid,
                    'authorization' => 'bearer ' . $jwt,
                    'apns-push-type' => 'voip', // VoIP notification type
                    'content-type' => 'application/json',
                ],
                'json' => json_decode($dataSendToApns, true),
            ]);

            $status = $response->getStatusCode();
            Log::info("Status " . $status);

            if ($status == 200) {
                // Successful response
                $responseHeaders = $response->getHeaders();
                $responseBody = $response->getBody()->getContents();
                Log::info("Response Headers: " . json_encode($responseHeaders));
            } else {
                // Extracting detailed error information
                $responseBody = json_decode($response->getBody()->getContents(), true);
                $errorCode = isset($responseBody['reason']) ? $responseBody['reason'] : 'Unknown';
                $errorDescription = isset($responseBody['description']) ? $responseBody['description'] : 'No description';
                throw new Exception("Error: $errorCode - $errorDescription");
            }

            return response()->json([
                'status' => $status,
                'message' => "Notification send successfully",
                'body' => $responseBody
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    private function base64($data)
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }
}
