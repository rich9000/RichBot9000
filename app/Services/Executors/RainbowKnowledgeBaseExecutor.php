<?php

namespace App\Services\Executors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RainbowKnowledgeBaseExecutor
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        // For local testing, use localhost
        if (app()->environment('local')) {
            $this->baseUrl = 'http://localhost';
        } else {
            $this->baseUrl = config('services.rainbow.api_url');
        }
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

    public function login($cache = true)
    {
        if ($cache) {
            if (Cache::has('rainbow_kb_api_token')) {
                $this->token = Cache::get('rainbow_kb_api_token');
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
                Log::info('[RainbowKnowledgeBaseExecutor] Login response', [
                    'data' => $data
                ]);
                // Make sure we're getting the token in the correct format
                $this->token = $data['access_token'] ?? $data['token'];

                if ($cache) {
                    Cache::put('rainbow_kb_api_token', $this->token, now()->addHours(24));
                }
     
                return true;
            }

            Log::warning('[RainbowKnowledgeBaseExecutor] Login failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('[RainbowKnowledgeBaseExecutor] Login exception', [
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

    public function rainbow_dashboard_get_kb_categories()
    {

Log::info('[RainbowKnowledgeBaseExecutor] Getting categories &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&', [
    'token' => $this->token
]);


        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Knowledge Base'
                ];
            }
        }
     

        Log::info('[RainbowKnowledgeBaseExecutor] Getting categories', [
            'token' => $this->token
        ]);

        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->get('https://dash.rainbowtel.net/api/knowledge-base/categories');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => $data['success'] ?? true,
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get categories',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowKnowledgeBaseExecutor] Get categories error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error getting categories',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_dashboard_create_article($params)
    {

        //title content and k_b_category_id are required


        if (!$this->token) {
            $this->login();
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Knowledge Base'
                ];
            }
        }

            if(is_string($params)) {
                $params = json_decode($params, true);
            }

        try {
            Log::info('[RainbowKnowledgeBaseExecutor] Creating article with params', [
                'params' => $params,
                'token' => $this->token
            ]);

            $response = Http::withHeaders($this->getAuthHeaders())
                ->post('https://dash.rainbowtel.net/api/knowledge-base/articles', $params);

            Log::info('[RainbowKnowledgeBaseExecutor] Create article response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('[RainbowKnowledgeBaseExecutor] Article created successfully', [
                    'article' => $data['data'] ?? $data
                ]);
                return [
                    'success' => $data['success'] ?? true,
                    'message' => $data['message'] ?? 'Article created successfully',
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create article',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowKnowledgeBaseExecutor] Create article error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error creating article',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_dashboard_search_kb_article($params)
    {
        // if (!$this->token) {
            $this->login(false);
            if (!$this->token) {
                return [
                    'success' => false,
                    'message' => 'Failed to login to Rainbow Knowledge Base'
                ];
            }
        // }

        // If params is a JSON string, decode it
        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        Log::info('[RainbowKnowledgeBaseExecutor] Searching articles', [
            'params' => $params,
        ]);

        try {
            $searchString = $params['search_string'] ?? '';
            
            if (empty($searchString)) {
                return [
                    'success' => false,
                    'message' => 'Search string is required'
                ];
            }

            Log::info('[RainbowKnowledgeBaseExecutor] Searching articles', [
                'search_string' => $searchString,
                'token' => $this->token
            ]);

            $response = Http::withHeaders($this->getAuthHeaders())
                ->post('https://dash.rainbowtel.net/api/knowledge-base/articles/search', [
                    'search_string' => $searchString
                ]);

            Log::info('[RainbowKnowledgeBaseExecutor] Search response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('[RainbowKnowledgeBaseExecutor] Search successful', [
                    'results' => $data['data'] ?? $data
                ]);
                return [
                    'success' => $data['success'] ?? true,
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to search knowledge base articles',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowKnowledgeBaseExecutor] Search KB article error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error searching knowledge base articles',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_dashboard_get_kb_article($params)
    {
        try {
            if (!$this->token) {
                $this->login();
    
                if (!$this->token) {
                    return [
                        'success' => false,
                        'message' => 'Failed to login to Rainbow Knowledge Base'
                    ];
                }
            }

            if(is_string($params)) {
                $params = json_decode($params, true);
            }

            $articleId = $params['article_id'] ?? null;
            
            if (empty($articleId)) {
                return [
                    'success' => false,
                    'message' => 'Article ID is required'
                ];
            }

            Log::info('[RainbowKnowledgeBaseExecutor] Getting article', [
                'article_id' => $articleId,
                'token' => $this->token
            ]);

            $response = Http::withHeaders($this->getAuthHeaders())
                ->get('https://dash.rainbowtel.net/api/knowledge-base/articles/' . $articleId);

            Log::info('[RainbowKnowledgeBaseExecutor] Get article response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('[RainbowKnowledgeBaseExecutor] Article retrieved successfully', [
                    'article' => $data['data'] ?? $data
                ]);
                return [
                    'success' => $data['success'] ?? true,
                    'data' => $data['data'] ?? $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get knowledge base article',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowKnowledgeBaseExecutor] Get KB article error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error getting knowledge base article',
                'error' => $e->getMessage()
            ];
        }
    }

    public function rainbow_dashboard_email_kb_article($params)
    {
        try {
            if (!$this->token) {
                $this->login();
    
                if (!$this->token) {
                    return [
                        'success' => false,
                        'message' => 'Failed to login to Rainbow Knowledge Base'
                    ];
                }
            }

            if(is_string($params)) {
                $params = json_decode($params, true);
            }

            $articleId = $params['article_id'] ?? null;
            $userId = $params['user_id'] ?? null;
            
            if (empty($articleId) || empty($userId)) {
                return [
                    'success' => false,
                    'message' => 'Article ID and User ID are required'
                ];
            }

            Log::info('[RainbowKnowledgeBaseExecutor] Emailing article', [
                'article_id' => $articleId,
                'user_id' => $userId,
                'token' => $this->token,
                'url' => 'https://dash.rainbowtel.net/api/knowledge-base/articles/' . $articleId . '/email-user/' . $userId
            ]);

            $response = Http::withHeaders($this->getAuthHeaders())
                ->post('https://dash.rainbowtel.net/api/knowledge-base/articles/' . $articleId . '/email-user/' . $userId);

            Log::info('[RainbowKnowledgeBaseExecutor] Email response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
                'raw_response' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('[RainbowKnowledgeBaseExecutor] Article emailed successfully', [
                    'response' => $data
                ]);
                return [
                    'success' => $data['success'] ?? true,
                    'message' => $data['message'] ?? 'Article emailed successfully',
                    'data' => $data['data'] ?? $data
                ];
            }

            Log::error('[RainbowKnowledgeBaseExecutor] Email failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
                'raw_response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to email knowledge base article',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[RainbowKnowledgeBaseExecutor] Email KB article error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $params
            ]);

            return [
                'success' => false,
                'message' => 'Error emailing knowledge base article',
                'error' => $e->getMessage()
            ];
        }
    }
} 