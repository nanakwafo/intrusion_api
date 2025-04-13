<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

class AkeselService
{
    protected $client;
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = env('AKASEL_API_URL');
        $this->apiKey = env('AKASEL_API_KEY');
    }

    public function sendSms($to, $message)
    {
        try {
            $response = $this->client->get($this->apiUrl, [
                'query' => [
                    'action' => 'send-sms',
                    'api_key' => $this->apiKey,
                    'to' => $to,
                    'from' => 'ProWork',
                    'sms' => $message
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode == 200 && $body['status'] == 'success') {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            // Handle error (log it, return false, etc.)
            return false;
        }
    }
}
