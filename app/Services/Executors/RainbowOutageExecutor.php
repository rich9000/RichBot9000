<?php

namespace App\Services\Executors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RainbowOutageExecutor
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        // For local testing, use localhost
       
            $this->baseUrl = 'https://dash.rainbowtel.net';
       
        $this->token = null;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function login($cache = false)
    {
        if ($cache) {
            if (Cache::has('rainbow_outage_api_token')) {
                $this->token = Cache::get('rainbow_outage_api_token');
                return true;
            }
        }

        try {
            $email = 'rich@rainbowtel.com';
            $password = 'richlikestowork';
           
            $response = Http::post('https://dash.rainbowtel.net/api/auth/login', [
                'email' => $email,
                'password' => $password
            ]);          
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('[RainbowOutageExecutor] Login response', [
                    'data' => $data
                ]);
                $this->token = $data['access_token'] ?? $data['token'];

                if ($cache) {
                    Cache::put('rainbow_outage_api_token', $this->token, now()->addHours(24));
                }
     
                return true;
            }

            Log::warning('[RainbowOutageExecutor] Login failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] Login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function getAuthHeaders()
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json'
        ];
    }

    public function rainbow_outage_list($status = null)
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        try {
            $params = [];
            if ($status) {
                $params['status'] = $status;
            }

            $response = Http::withHeaders($this->getAuthHeaders())
                ->get($this->baseUrl . '/api/outages', $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data']['active_outages'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to list outages',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] List outages error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error listing outages',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_outage_create($params)
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        try {

            
            // Format the start time into the required fields
            // $startTime = \Carbon\Carbon::parse($params['start_time']);
            //$startTime = \Carbon\Carbon::parse($params['start_time']);

            Log::info('[RainbowOutageExecutor] Start Time: ' , [
                'start_time' => $params['start_time'],            
            ]);

            $formattedParams = [
                'description' => $params['description'],
                'type' => $params['type'],
                'start_time' => $params['start_time'],
                
                'outage_type' => $params['outage_type'],
                'duration' => $params['duration']
            ];

            // Add optional parameters if they exist
            if (isset($params['areas'])) {
                $formattedParams['areas'] = $params['areas'];
            }
            if (isset($params['services'])) {
                $formattedParams['services'] = $params['services'];
            }

            Log::info('[RainbowOutageExecutor] Creating outage with formatted params', [
                'params' => $formattedParams
            ]);

            $response = Http::withHeaders($this->getAuthHeaders())
                ->post($this->baseUrl . '/api/outages', $formattedParams);

            Log::info('[RainbowOutageExecutor] ##### Create Outage Response: ' , [$response->body()]);
      
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Outage created successfully',
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create outage',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] Create outage error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error creating outage',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_outage_add_comment($outageId, $comment)
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        try {



            Log::info('[RainbowOutageExecutor] ##### Add Comment Request: ' , [$outageId, $comment]);



            $response = Http::withHeaders($this->getAuthHeaders())
                ->post($this->baseUrl . '/api/outages/' . $outageId . '/comments', [
                    'comment' => $comment
                ]);

            Log::info('[RainbowOutageExecutor] #&&&&&&&&&&&&#### Add Comment Response: ' , [$response->body()]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] Add comment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error adding comment',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_outage_resolve($outageId, $reason = null)
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        try {
            $data = [];
            if ($reason) {
                $data['reason'] = $reason;
            }

            $response = Http::withHeaders($this->getAuthHeaders())
                ->post($this->baseUrl . '/api/outages/' . $outageId . '/resolve', $data);

            Log::info('[RainbowOutageExecutor] ##### Resolve Outage Response: ' , [$response->body()]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Outage resolved successfully',
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to resolve outage',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] Resolve outage error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error resolving outage',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_outage_list_areas()
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        Log::info('[RainbowOutageExecutor] Listing areas');
        Log::info('[RainbowOutageExecutor] Token: ' . $this->token);
        Log::info('[RainbowOutageExecutor] Base URL: ' . $this->baseUrl);
        Log::info('[RainbowOutageExecutor] Auth Headers: ' . json_encode($this->getAuthHeaders()));
       

        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->get($this->baseUrl . '/api/areas');

            Log::info('[RainbowOutageExecutor] Response: ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to list areas',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] List areas error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error listing areas',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_outage_list_services()
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->get($this->baseUrl . '/api/services');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to list services',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] List services error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error listing services',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_outage_get_comments($outageId)
    {
        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Outage API'
                ];
            }
        }

        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->get($this->baseUrl . '/api/outages/' . $outageId . '/comments');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get comments',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowOutageExecutor] Get comments error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error getting comments',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getMethodSchema()
    {
        return [
            [
                'name' => 'rainbow_outage_list',
                'description' => 'List all outages with optional status filter',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'status',
                        'type' => 'string',
                        'description' => 'Filter outages by status (active, resolved)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_outage_create',
                'description' => 'Create a new outage with optional areas and services',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'description' => 'Description of the outage',
                        'required' => true,
                    ],
                    [
                        'name' => 'type',
                        'type' => 'string',
                        'description' => 'Type of outage (global, local)',
                        'required' => true,
                    ],
                    [
                        'name' => 'start_time',
                        'type' => 'datetime',
                        'description' => 'When the outage starts',
                        'required' => true,
                    ],
                    [
                        'name' => 'outage_type',
                        'type' => 'string',
                        'description' => 'Type of outage (emergency, planned)',
                        'required' => true,
                    ],
                    [
                        'name' => 'duration',
                        'type' => 'integer',
                        'description' => 'Duration of outage in minutes',
                        'required' => true,
                    ],
                    [
                        'name' => 'areas',
                        'type' => 'array',
                        'description' => 'Array of area IDs affected by the outage',
                        'required' => false,
                    ],
                    [
                        'name' => 'services',
                        'type' => 'array',
                        'description' => 'Array of service IDs affected by the outage',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_outage_add_comment',
                'description' => 'Add a comment to an existing outage',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'outageId',
                        'type' => 'integer',
                        'description' => 'ID of the outage to comment on',
                        'required' => true,
                    ],
                    [
                        'name' => 'comment',
                        'type' => 'string',
                        'description' => 'Comment text to add',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_outage_resolve',
                'description' => 'Mark an outage as resolved',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'outageId',
                        'type' => 'integer',
                        'description' => 'ID of the outage to resolve',
                        'required' => true,
                    ],
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Reason for resolving the outage',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_outage_list_areas',
                'description' => 'List all available areas',
                'strict' => true,
                'parameters' => [],
            ],
            [
                'name' => 'rainbow_outage_list_services',
                'description' => 'List all available services',
                'strict' => true,
                'parameters' => [],
            ],
        ];
    }
} 