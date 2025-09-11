<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    protected $client;
    protected $accountId;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->clientId = env('ZOOM_CLIENT_ID');
        $this->clientSecret = env('ZOOM_CLIENT_SECRET');
        $this->accountId = env('ZOOM_ACCOUNT_ID');

        $this->client = new Client(['base_uri' => 'https://api.zoom.us/v2/']);
    }

    public function getAccessToken()
    {
        return Cache::remember('zoom_access_token', 55 * 60, function () {
            $response = (new Client())->post('https://zoom.us/oauth/token', [
                'form_params' => [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->accountId,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'];
        });
    }

    public function getMeetings($userId = 'me', $from = null, $to = null)
    {
        try {
            $accessToken = $this->getAccessToken();
            $queryParams = [
                'type' => 'scheduled',
                'page_size' => 300,
            ];

            if ($from) {
                $queryParams['from'] = $from->format('Y-m-d');
            }
            if ($to) {
                $queryParams['to'] = $to->format('Y-m-d');
            }

            $response = $this->client->get("users/{$userId}/meetings", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => $queryParams,
            ]);

            return json_decode($response->getBody(), true)['meetings'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching Zoom meetings: ' . $e->getMessage());
            return [];
        }
    }

    public function getMeetingDetails($meetingId)
    {
        try {
            $accessToken = $this->getAccessToken();
            $response = $this->client->get("meetings/{$meetingId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error("Error fetching Zoom meeting details for ID {$meetingId}: " . $e->getMessage());
            return null;
        }
    }
}
