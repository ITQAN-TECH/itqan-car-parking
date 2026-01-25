<?php

namespace App\services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;

class FCMService
{
    protected $client;

    protected $credentialsPath;

    protected $projectId;

    public function __construct()
    {
        $this->credentialsPath = storage_path(config('services.fcm.credentialsPath'));
        $this->projectId = config('services.fcm.project_id');
    }

    public function sendNotification($to, $title, $body)
    {
        // Load Service Account credentials from JSON file
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            $this->credentialsPath
        );

        // Get an OAuth 2.0 token
        $authToken = $credentials->fetchAuthToken()['access_token'];

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $to,
                'data' => [
                    'title' => $title,
                    'body' => $body, // Changed to match the function's $body parameter
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                            'badge' => 0,
                            'priority' => 'high',
                        ],
                    ],
                ],
            ],
        ];

        try {
            // Send POST request with raw JSON
            $response = Http::withToken($authToken)
                ->withHeaders(['Content-Type' => 'application/json']) // Explicitly set content type
                ->withBody(json_encode($payload), 'application/json') // Raw JSON
                ->post($url);

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }
}
