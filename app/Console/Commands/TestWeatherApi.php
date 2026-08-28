<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWeatherApi extends Command
{
    protected $signature = 'weather:test {latitude?} {longitude?} {--ip=}';
    protected $description = 'Test the weather.gov API with a location or IP address';

    public function handle()
    {
        $latitude = $this->argument('latitude');
        $longitude = $this->argument('longitude');
        $ip = $this->option('ip');

        // If IP is provided, get coordinates from IP
        if ($ip) {
            $coordinates = $this->getCoordinatesFromIp($ip);
            if (!$coordinates) {
                $this->error("Failed to get coordinates from IP address");
                return 1;
            }
            $latitude = $coordinates['latitude'];
            $longitude = $coordinates['longitude'];
        }

        // Default to New York City coordinates if none provided
        $latitude = $latitude ?? 40.7128;
        $longitude = $longitude ?? -74.0060;

        $this->info("Testing weather API for coordinates: {$latitude}, {$longitude}");

        try {
            // First get the grid point data
            $response = Http::withHeaders([
                'User-Agent' => 'RichBot9000 Weather Test/1.0 (richbot9000.com)'
            ])->get("https://api.weather.gov/points/{$latitude},{$longitude}");

            if (!$response->successful()) {
                $this->error("Failed to get grid point data: " . $response->status());
                return 1;
            }

            $gridData = $response->json();
            $this->info("\nLocation Information:");
            $this->info("Forecast Office: " . ($gridData['properties']['forecastOffice'] ?? 'N/A'));
            $this->info("Grid ID: " . ($gridData['properties']['gridId'] ?? 'N/A'));
            $this->info("Grid X: " . ($gridData['properties']['gridX'] ?? 'N/A'));
            $this->info("Grid Y: " . ($gridData['properties']['gridY'] ?? 'N/A'));

            // Get the forecast
            $forecastUrl = $gridData['properties']['forecast'];
            $forecastResponse = Http::withHeaders([
                'User-Agent' => 'RichBot9000 Weather Test/1.0 (richbot9000.com)'
            ])->get($forecastUrl);

            if (!$forecastResponse->successful()) {
                $this->error("Failed to get forecast data: " . $forecastResponse->status());
                return 1;
            }

            $forecastData = $forecastResponse->json();
            $this->info("\nCurrent Forecast:");
            foreach ($forecastData['properties']['periods'] as $period) {
                $this->info("\n" . $period['name'] . ":");
                $this->info("Temperature: " . $period['temperature'] . "°" . $period['temperatureUnit']);
                $this->info("Forecast: " . $period['shortForecast']);
                $this->info("Wind: " . $period['windSpeed'] . " " . $period['windDirection']);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    protected function getCoordinatesFromIp($ip)
    {
        try {
            // Using ipapi.co service (free tier available)
            $response = Http::get("http://ip-api.com/json/{$ip}");
            
            
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            if (isset($data['latitude']) && isset($data['longitude'])) {
                return [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude']
                ];
            }

            return null;
        } catch (\Exception $e) {
            $this->error("Error getting IP geolocation: " . $e->getMessage());
            return null;
        }
    }
} 