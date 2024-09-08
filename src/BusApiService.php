<?php
namespace Nikba\BusSystem;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Nikba\BusSystem\Exceptions\BusApiException;

class BusApiService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('bussystem');
    }

    protected function getBaseUrl()
    {
        return $this->config['test_mode'] ? $this->config['endpoints']['test'] : $this->config['endpoints']['production'];
    }

    protected function makeRequest($endpoint, $params = [])
    {
        $url = $this->getBaseUrl() . $endpoint;
        $params = array_merge([
            'login' => $this->config['login'],
            'password' => $this->config['password'],
            'lang' => $this->config['lang']
        ], $params);

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $params);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new BusApiException('API request failed with status code: ' . $response->status());
            }
        } catch (\Exception $e) {
            throw new BusApiException($e->getMessage());
        }
    }

    public function getPoints()
    {
        $cacheKey = 'busapi_get_points';

        return Cache::remember($cacheKey, $this->config['cache_times']['get_points'], function () {
            return $this->makeRequest('/curl/get_points.php');
        });
    }
}
