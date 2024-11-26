<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Arr;

class IpGeolocationService
{
    protected Http $http;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.ipgeolocation.io/ipgeo';

    public function __construct(Http $http, string $apiKey)
    {
        $this->http = $http;
        $this->apiKey = $apiKey;
    }

    public function locate(string $ip): array
    {
        $response = $this->http->get($this->baseUrl, [
            'apiKey' => $this->apiKey,
            'ip' => $ip,
        ]);

        if (!$response->successful()) {
            throw new \Exception("IP Geolocation request failed: {$response->body()}");
        }

        return $this->formatResponse($response->json());
    }

    protected function formatResponse(array $data): array
    {
        return [
            'ip' => $data['ip'],
            'country' => $data['country_name'],
            'country_code' => $data['country_code2'],
            'city' => $data['city'],
            'state' => $data['state_prov'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ];
    }
}
