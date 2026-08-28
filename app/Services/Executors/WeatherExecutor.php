<?php

namespace App\Services\Executors;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * WeatherExecutor - A service for interacting with the National Weather Service API
 * 
 * Usage Guide for AI Assistants:
 * 
 * 1. Basic Weather Information Flow:
 *    - Start with either coordinates or IP address to get basic weather data
 *    - Use weather_get_by_coordinates() for precise location weather
 *    - Use weather_get_by_ip() when you only have an IP address
 *    - Both methods return location details and forecast periods
 * 
 * 2. Alert Monitoring Flow:
 *    - After getting basic weather, check for active alerts
 *    - Use weather_alerts_get() with either:
 *      a) The same coordinates from weather_get_by_coordinates()
 *      b) The state code (e.g., 'CA', 'NY') for broader area alerts
 *    - Alerts can be used to provide warnings or important notifications
 * 
 * 3. Detailed Forecast Flow:
 *    - For more detailed weather information, use weather_hourly_get()
 *    - This provides hour-by-hour forecasts for more precise planning
 *    - Use this when users need specific time-based weather information
 * 
 * 4. Advanced Data Flow:
 *    - For technical or detailed weather data, use weather_grid_data_get()
 *    - This provides raw forecast data including:
 *      - Temperature
 *      - Wind speed and direction
 *      - Precipitation probability
 *      - Relative humidity
 *      - Cloud cover
 * 
 * 5. Error Handling:
 *    - All methods return a consistent response format:
 *      {
 *        'success': boolean,
 *        'data': {...} | null,
 *        'error': string | null
 *      }
 *    - Always check the 'success' field before using the data
 *    - Handle errors gracefully and provide user-friendly messages
 * 
 * 6. Best Practices:
 *    - Cache weather data when possible to avoid API rate limits
 *    - Combine methods to provide comprehensive weather information
 *    - Use alerts to highlight important weather events
 *    - Consider time zones when displaying weather information
 *    - Format temperature and other units according to user preferences
 * 
 * Example Usage Pattern:
 * 1. Get basic weather:
 *    $weather = $executor->weather_get_by_coordinates(['latitude' => 40.7128, 'longitude' => -74.0060]);
 * 
 * 2. Check for alerts:
 *    $alerts = $executor->weather_alerts_get(['latitude' => 40.7128, 'longitude' => -74.0060]);
 * 
 * 3. Get detailed forecast if needed:
 *    $hourly = $executor->weather_hourly_get(['latitude' => 40.7128, 'longitude' => -74.0060]);
 * 
 * 4. Combine the data to provide comprehensive weather information
 */
class WeatherExecutor
{
    private $user;
    private $conversation;
    private $baseUrl = 'https://api.weather.gov';
    private $userAgent = 'RichBot9000 Weather Service/1.0 (richbot9000.com)';

    public function __construct($user = null)
    {
        $this->user = $user;
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;
    }

    public function getMethodSchema()
    {
        return [
            [
                'name' => 'weather_get_by_coordinates',
                'description' => 'Get weather data for a location using latitude and longitude coordinates',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'The latitude coordinate (e.g., 40.7128)'
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'The longitude coordinate (e.g., -74.0060)'
                    ]
                ]
            ],
            [
                'name' => 'weather_get_by_ip',
                'description' => 'Get weather data for a location using an IP address',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ip',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The IP address to get weather for (e.g., 8.8.8.8)'
                    ]
                ]
            ],
            [
                'name' => 'weather_alerts_get',
                'description' => 'Get active weather alerts for a location or state',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'The latitude coordinate (required if longitude is provided)'
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'The longitude coordinate (required if latitude is provided)'
                    ],
                    [
                        'name' => 'state',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The two-letter state code (e.g., CA, NY)'
                    ]
                ]
            ],
            [
                'name' => 'weather_hourly_get',
                'description' => 'Get hourly forecast data for a location',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'The latitude coordinate (e.g., 40.7128)'
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'The longitude coordinate (e.g., -74.0060)'
                    ]
                ]
            ],
            [
                'name' => 'weather_grid_data_get',
                'description' => 'Get raw forecast grid data for a location',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'The latitude coordinate (e.g., 40.7128)'
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'required' => true,
                        'description' => 'The longitude coordinate (e.g., -74.0060)'
                    ]
                ]
            ],
            [
                'name' => 'weather_by_address',
                'description' => 'Get weather data for an address or city/state',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'address',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Full address or city, state (e.g., "Los Angeles, CA" or "1600 Pennsylvania Ave, Washington, DC")'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get weather data for a location by coordinates
     */
    public function weather_get_by_coordinates($arguments)
    {
        $latitude = $arguments['latitude'] ?? null;
        $longitude = $arguments['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            return ['success' => false, 'error' => 'Latitude and longitude are required'];
        }

        try {
            // First get the grid point data
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get("{$this->baseUrl}/points/{$latitude},{$longitude}");

            if (!$response->successful()) {
                return ['success' => false, 'error' => "Failed to get grid point data: " . $response->status()];
            }

            $gridData = $response->json();
            
            // Get the forecast
            $forecastUrl = $gridData['properties']['forecast'];
            $forecastResponse = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get($forecastUrl);

            if (!$forecastResponse->successful()) {
                return ['success' => false, 'error' => "Failed to get forecast data: " . $forecastResponse->status()];
            }

            $forecastData = $forecastResponse->json();

            return [
                'success' => true,
                'data' => [
                    'location' => [
                        'forecast_office' => $gridData['properties']['forecastOffice'] ?? null,
                        'grid_id' => $gridData['properties']['gridId'] ?? null,
                        'grid_x' => $gridData['properties']['gridX'] ?? null,
                        'grid_y' => $gridData['properties']['gridY'] ?? null,
                    ],
                    'forecast' => $forecastData['properties']['periods'] ?? []
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get weather data for a location by IP address
     */
    public function weather_get_by_ip($arguments)
    {
        $ip = $arguments['ip'] ?? null;

        if (!$ip) {
            return ['success' => false, 'error' => 'IP address is required'];
        }

        try {
            // Get coordinates from IP using ipapi.co
            $response = Http::get("https://ipapi.co/{$ip}/json/");
            
            if (!$response->successful()) {
                return ['success' => false, 'error' => "Failed to get coordinates from IP: " . $response->status()];
            }

            $data = $response->json();
            
            if (!isset($data['latitude']) || !isset($data['longitude'])) {
                return ['success' => false, 'error' => 'Could not determine coordinates from IP address'];
            }

            // Use the coordinates to get weather data
            return $this->weather_get_by_coordinates([
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude']
            ]);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get active weather alerts for a location
     */
    public function weather_alerts_get($arguments)
    {
        $latitude = $arguments['latitude'] ?? null;
        $longitude = $arguments['longitude'] ?? null;
        $state = $arguments['state'] ?? null;

        if (!$latitude && !$longitude && !$state) {
            return ['success' => false, 'error' => 'Either coordinates or state is required'];
        }

        try {
            $url = "{$this->baseUrl}/alerts/active";
            
            if ($state) {
                $url .= "?area={$state}";
            } else {
                $url .= "?point={$latitude},{$longitude}";
            }

            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get($url);

            if (!$response->successful()) {
                return ['success' => false, 'error' => "Failed to get alerts: " . $response->status()];
            }

            $alerts = $response->json();

            return [
                'success' => true,
                'data' => [
                    'alerts' => $alerts['features'] ?? []
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get hourly forecast for a location
     */
    public function weather_hourly_get($arguments)
    {
        $latitude = $arguments['latitude'] ?? null;
        $longitude = $arguments['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            return ['success' => false, 'error' => 'Latitude and longitude are required'];
        }

        try {
            // First get the grid point data
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get("{$this->baseUrl}/points/{$latitude},{$longitude}");

            if (!$response->successful()) {
                return ['success' => false, 'error' => "Failed to get grid point data: " . $response->status()];
            }

            $gridData = $response->json();
            
            // Get the hourly forecast
            $hourlyUrl = $gridData['properties']['forecastHourly'];
            $hourlyResponse = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get($hourlyUrl);

            if (!$hourlyResponse->successful()) {
                return ['success' => false, 'error' => "Failed to get hourly forecast: " . $hourlyResponse->status()];
            }

            $hourlyData = $hourlyResponse->json();

            return [
                'success' => true,
                'data' => [
                    'hourly_forecast' => $hourlyData['properties']['periods'] ?? []
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get raw forecast grid data for a location
     */
    public function weather_grid_data_get($arguments)
    {
        $latitude = $arguments['latitude'] ?? null;
        $longitude = $arguments['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            return ['success' => false, 'error' => 'Latitude and longitude are required'];
        }

        try {
            // First get the grid point data
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get("{$this->baseUrl}/points/{$latitude},{$longitude}");

            if (!$response->successful()) {
                return ['success' => false, 'error' => "Failed to get grid point data: " . $response->status()];
            }

            $gridData = $response->json();
            
            // Get the grid data
            $gridUrl = $gridData['properties']['forecastGridData'];
            $gridResponse = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->get($gridUrl);

            if (!$gridResponse->successful()) {
                return ['success' => false, 'error' => "Failed to get grid data: " . $gridResponse->status()];
            }

            $gridData = $gridResponse->json();

            return [
                'success' => true,
                'data' => [
                    'grid_data' => $gridData['properties'] ?? []
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get weather data for an address or city/state
     * 
     * @param array $arguments Contains the address parameter
     * @return array Response with weather data
     */
    public function weather_by_address($arguments)
    {
        $address = $arguments['address'] ?? null;

        if (!$address) {
            return ['success' => false, 'error' => 'Address is required'];
        }

        try {
            // First geocode the address using ipapi.co
            $geocodeUrl = "http://ip-api.com/json/" . urlencode($address);
            $response = Http::get($geocodeUrl);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to geocode address: ' . $response->body()];
            }

            $data = $response->json();

            dd($data);
            
            // Extract coordinates from the response
            if (!isset($data['latitude']) || !isset($data['longitude'])) {
                
                return ['success' => false, 'error' => 'Could not determine coordinates from address: ' . $response->body()];

            }

            $latitude = $data['latitude'];
            $longitude = $data['longitude'];
            
            // Get location name
            $location = isset($data['city']) && isset($data['region']) 
                ? $data['city'] . ', ' . $data['region']
                : $address;

            // Now get the weather data using the coordinates
            $weatherResult = $this->weather_get_by_coordinates([
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);

            if (!$weatherResult['success']) {
                return $weatherResult;
            }

            // Add the location name and coordinates to the response
            $weatherResult['data']['location']['name'] = $location;
            $weatherResult['data']['coordinates'] = [
                'latitude' => $latitude,
                'longitude' => $longitude
            ];

            return $weatherResult;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error getting weather data: ' . $e->getMessage()
            ];
        }
    }
} 