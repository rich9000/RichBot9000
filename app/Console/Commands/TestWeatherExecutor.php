<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\WeatherExecutor;

class TestWeatherExecutor extends Command
{
    protected $signature = 'weather:test-executor {--ip= : IP address to test} {--address= : Address to test} {--city= : City to test} {--lat= : Latitude to test} {--lon= : Longitude to test} {--state= : State code to test}';
    protected $description = 'Test the WeatherExecutor methods';

    public function handle()
    {
        $executor = new WeatherExecutor();
        $this->info('Testing WeatherExecutor methods...');

        // Test coordinates weather
        if ($this->option('lat') && $this->option('lon')) {
            $this->testCoordinatesWeather();
        }

        // Test IP weather
        if ($this->option('ip')) {
            $this->testIpWeather();
        }

        // Test address weather
        if ($this->option('address')) {
            $this->testAddressWeather();
        }

        // Test city weather
        if ($this->option('city')) {
            $this->testCityWeather();
        }

        // Test alerts
        if ($this->option('lat') && $this->option('lon')) {
            $this->testAlertsByCoordinates();
        }
        if ($this->option('state')) {
            $this->testAlertsByState();
        }

        // Test hourly forecast
        if ($this->option('lat') && $this->option('lon')) {
            $this->testHourlyForecast();
        }

        // Test grid data
        if ($this->option('lat') && $this->option('lon')) {
            $this->testGridData();
        }

        $this->info('WeatherExecutor tests completed.');
    }

    protected function testCoordinatesWeather()
    {
        $this->info("\nTesting weather_get_by_coordinates...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_get_by_coordinates([
            'latitude' => $this->option('lat'),
            'longitude' => $this->option('lon')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved weather by coordinates');
            $this->line('Location: ' . json_encode($result['data']['location']));
            $this->line('Forecast periods: ' . count($result['data']['forecast']));
        } else {
            $this->error('✗ Failed to get weather by coordinates: ' . $result['error']);
        }
    }

    protected function testIpWeather()
    {
        $this->info("\nTesting weather_get_by_ip...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_get_by_ip([
            'ip' => $this->option('ip')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved weather by IP');
            $this->line('Location: ' . json_encode($result['data']['location']));
            $this->line('Forecast periods: ' . count($result['data']['forecast']));
        } else {
            $this->error('✗ Failed to get weather by IP: ' . $result['error']);
        }
    }

    protected function testAddressWeather()
    {
        $this->info("\nTesting weather_by_address...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_by_address([
            'address' => $this->option('address')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved weather by address');
            $this->line('Location: ' . json_encode($result['data']['location']));
            $this->line('Coordinates: ' . json_encode($result['data']['coordinates']));
            $this->line('Forecast periods: ' . count($result['data']['forecast']));
        } else {
            $this->error('✗ Failed to get weather by address: ' . $result['error']);
        }
    }

    protected function testCityWeather()
    {
        $this->info("\nTesting weather_by_address with city...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_by_address([
            'address' => $this->option('city')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved weather for city');
            $this->line('Location: ' . json_encode($result['data']['location']));
            $this->line('Coordinates: ' . json_encode($result['data']['coordinates']));
            $this->line('Forecast periods: ' . count($result['data']['forecast']));
        } else {
            $this->error('✗ Failed to get weather for city: ' . $result['error']);
        }
    }

    protected function testAlertsByCoordinates()
    {
        $this->info("\nTesting weather_alerts_get by coordinates...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_alerts_get([
            'latitude' => $this->option('lat'),
            'longitude' => $this->option('lon')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved alerts by coordinates');
            $this->line('Number of alerts: ' . count($result['data']['alerts']));
        } else {
            $this->error('✗ Failed to get alerts by coordinates: ' . $result['error']);
        }
    }

    protected function testAlertsByState()
    {
        $this->info("\nTesting weather_alerts_get by state...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_alerts_get([
            'state' => $this->option('state')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved alerts by state');
            $this->line('Number of alerts: ' . count($result['data']['alerts']));
        } else {
            $this->error('✗ Failed to get alerts by state: ' . $result['error']);
        }
    }

    protected function testHourlyForecast()
    {
        $this->info("\nTesting weather_hourly_get...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_hourly_get([
            'latitude' => $this->option('lat'),
            'longitude' => $this->option('lon')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved hourly forecast');
            $this->line('Number of hourly periods: ' . count($result['data']['hourly_forecast']));
        } else {
            $this->error('✗ Failed to get hourly forecast: ' . $result['error']);
        }
    }

    protected function testGridData()
    {
        $this->info("\nTesting weather_grid_data_get...");
        $executor = new WeatherExecutor();
        $result = $executor->weather_grid_data_get([
            'latitude' => $this->option('lat'),
            'longitude' => $this->option('lon')
        ]);

        if ($result['success']) {
            $this->info('✓ Successfully retrieved grid data');
            $this->line('Grid data properties: ' . implode(', ', array_keys($result['data']['grid_data'])));
        } else {
            $this->error('✗ Failed to get grid data: ' . $result['error']);
        }
    }
} 