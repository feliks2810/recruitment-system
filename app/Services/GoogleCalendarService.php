<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected $client;
    protected $calendarId;

    public function __construct()
    {
        $this->calendarId = env('GOOGLE_CALENDAR_ID');
        $credentialsPath = env('GOOGLE_APPLICATION_CREDENTIALS');

        if (!file_exists($credentialsPath)) {
            throw new \Exception('Google credentials file not found at path: ' . $credentialsPath);
        }

        $this->client = new Client();
        $this->client->setApplicationName('Recruitment System Calendar');
        $this->client->setScopes([Calendar::CALENDAR_READONLY]);
        $this->client->setAuthConfig($credentialsPath);
        $this->client->setAccessType('offline');
    }

    public function getEvents(\DateTime $startDateTime = null, \DateTime $endDateTime = null)
    {
        try {
            $service = new Calendar($this->client);
            $optParams = [
                'orderBy' => 'startTime',
                'singleEvents' => true,
            ];

            if ($startDateTime) {
                $optParams['timeMin'] = $startDateTime->format(\DateTime::RFC3339);
            }

            if ($endDateTime) {
                $optParams['timeMax'] = $endDateTime->format(\DateTime::RFC3339);
            }

            $results = $service->events->listEvents($this->calendarId, $optParams);
            return $results->getItems();
        } catch (\Exception $e) {
            Log::error('Error fetching Google Calendar events: ' . $e->getMessage());
            return [];
        }
    }
}
