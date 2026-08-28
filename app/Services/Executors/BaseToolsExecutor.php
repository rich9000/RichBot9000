<?php

namespace App\Services\Executors;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * BaseToolsExecutor - Basic tools that every assistant could use to gather information
 * 
 * Usage Guide for AI Assistants:
 * 
 * 1. URL Fetching Flow:
 *    - Use url_fetch() for basic HTTP GET requests
 *    - Use url_bearer_fetch() when API requires Bearer token authentication
 *    - Use url_basic_fetch() when API requires username/password authentication
 *    - All methods return consistent response format with success/error handling
 * 
 * 2. Web Content Processing Flow:
 *    - Use webpage_summarize() to fetch and summarize webpage content
 *    - Use url_status_check() to verify if a URL is accessible before processing
 *    - Combine with ping() to check network connectivity first
 * 
 * 3. Network Diagnostics Flow:
 *    - Use ping() to check if a host is reachable
 *    - Use url_status_check() to verify specific URL accessibility
 *    - Use url_fetch() to get actual content if needed
 * 
 * 4. Error Handling:
 *    - All methods return a consistent response format:
 *      {
 *        'success': boolean,
 *        'data': {...} | null,
 *        'error': string | null
 *      }
 *    - Always check the 'success' field before using the data
 *    - Handle network timeouts and authentication errors gracefully
 * 
 * 5. Best Practices:
 *    - Use ping() before attempting URL operations to check connectivity
 *    - Use url_status_check() before fetching large content
 *    - Implement proper timeout handling for all network operations
 *    - Cache results when possible to avoid repeated requests
 *    - Handle authentication errors with user-friendly messages
 * 
 * Example Usage Pattern:
 * 1. Check connectivity:
 *    $ping = $executor->ping(['host' => 'google.com']);
 * 
 * 2. Check URL status:
 *    $status = $executor->url_status_check(['url' => 'https://api.example.com']);
 * 
 * 3. Fetch content:
 *    $content = $executor->url_fetch(['url' => 'https://api.example.com/data']);
 * 
 * 4. Summarize webpage:
 *    $summary = $executor->webpage_summarize(['url' => 'https://example.com/article']);
 */
class BaseToolsExecutor
{
    private $user;
    private $conversation;
    private $defaultTimeout = 30;
    private $userAgent = 'RichBot9000 BaseTools/1.0 (richbot9000.com)';

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
                'name' => 'url_fetch',
                'description' => 'Make a basic HTTP GET request to fetch content from a URL',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'url',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The URL to fetch content from'
                    ],
                    [
                        'name' => 'headers',
                        'type' => 'object',
                        'required' => false,
                        'description' => 'Additional HTTP headers to include in the request'
                    ],
                    [
                        'name' => 'timeout',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Request timeout in seconds (default: 30)'
                    ]
                ]
            ],
            [
                'name' => 'url_bearer_fetch',
                'description' => 'Make an HTTP GET request with Bearer token authentication',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'url',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The URL to fetch content from'
                    ],
                    [
                        'name' => 'token',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The Bearer token for authentication'
                    ],
                    [
                        'name' => 'headers',
                        'type' => 'object',
                        'required' => false,
                        'description' => 'Additional HTTP headers to include in the request'
                    ],
                    [
                        'name' => 'timeout',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Request timeout in seconds (default: 30)'
                    ]
                ]
            ],
            [
                'name' => 'url_basic_fetch',
                'description' => 'Make an HTTP GET request with Basic authentication',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'url',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The URL to fetch content from'
                    ],
                    [
                        'name' => 'username',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Username for Basic authentication'
                    ],
                    [
                        'name' => 'password',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Password for Basic authentication'
                    ],
                    [
                        'name' => 'headers',
                        'type' => 'object',
                        'required' => false,
                        'description' => 'Additional HTTP headers to include in the request'
                    ],
                    [
                        'name' => 'timeout',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Request timeout in seconds (default: 30)'
                    ]
                ]
            ],
            [
                'name' => 'webpage_summarize',
                'description' => 'Fetch and summarize webpage content',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'url',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The URL of the webpage to summarize'
                    ],
                    [
                        'name' => 'max_length',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Maximum length of the summary (default: 500 characters)'
                    ],
                    [
                        'name' => 'include_links',
                        'type' => 'boolean',
                        'required' => false,
                        'description' => 'Whether to include links in the summary (default: false)'
                    ],
                    [
                        'name' => 'timeout',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Request timeout in seconds (default: 30)'
                    ]
                ]
            ],
            [
                'name' => 'url_status_check',
                'description' => 'Check if a URL is accessible and return its status',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'url',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The URL to check'
                    ],
                    [
                        'name' => 'timeout',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Request timeout in seconds (default: 10)'
                    ]
                ]
            ],
            [
                'name' => 'ping',
                'description' => 'Ping a host to check network connectivity',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'host',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The hostname or IP address to ping'
                    ],
                    [
                        'name' => 'count',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Number of ping attempts (default: 3)'
                    ],
                    [
                        'name' => 'timeout',
                        'type' => 'number',
                        'required' => false,
                        'description' => 'Timeout for each ping in seconds (default: 5)'
                    ]
                ]
            ],
            [
                'name' => 'email_self',
                'description' => 'Send an email to the current user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'subject',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The subject line of the email'
                    ],
                    [
                        'name' => 'body',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The body content of the email'
                    ]
                ]
            ],
            [
                'name' => 'sms_self',
                'description' => 'Send an SMS message to the current user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'message',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The SMS message content'
                    ]
                ]
            ]
        ];
    }

    /**
     * Make a basic HTTP GET request
     */
    public function url_fetch($arguments)
    {
        try {
            $url = $arguments['url'] ?? null;
            $headers = $arguments['headers'] ?? [];
            $timeout = $arguments['timeout'] ?? $this->defaultTimeout;

            if (!$url) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'URL is required'
                ];
            }

            // Add default headers
            $defaultHeaders = [
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-US,en;q=0.9',
            ];

            $requestHeaders = array_merge($defaultHeaders, $headers);

            $response = Http::timeout($timeout)
                ->withHeaders($requestHeaders)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => [
                        'status_code' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                        'content_type' => $response->header('Content-Type'),
                        'content_length' => $response->header('Content-Length'),
                        'url' => $url
                    ],
                    'error' => null
                ];
            } else {
                return [
                    'success' => false,
                    'data' => [
                        'status_code' => $response->status(),
                        'url' => $url
                    ],
                    'error' => "HTTP request failed with status code: {$response->status()}"
                ];
            }
        } catch (Exception $e) {
            Log::error('BaseToolsExecutor url_fetch error: ' . $e->getMessage(), [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Failed to fetch URL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Make an HTTP GET request with Bearer token authentication
     */
    public function url_bearer_fetch($arguments)
    {
        try {
            $url = $arguments['url'] ?? null;
            $token = $arguments['token'] ?? null;
            $headers = $arguments['headers'] ?? [];
            $timeout = $arguments['timeout'] ?? $this->defaultTimeout;

            if (!$url) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'URL is required'
                ];
            }

            if (!$token) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Bearer token is required'
                ];
            }

            // Add default headers with Bearer token
            $defaultHeaders = [
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Authorization' => "Bearer {$token}",
            ];

            $requestHeaders = array_merge($defaultHeaders, $headers);

            $response = Http::timeout($timeout)
                ->withHeaders($requestHeaders)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => [
                        'status_code' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                        'content_type' => $response->header('Content-Type'),
                        'content_length' => $response->header('Content-Length'),
                        'url' => $url
                    ],
                    'error' => null
                ];
            } else {
                return [
                    'success' => false,
                    'data' => [
                        'status_code' => $response->status(),
                        'url' => $url
                    ],
                    'error' => "HTTP request failed with status code: {$response->status()}"
                ];
            }
        } catch (Exception $e) {
            Log::error('BaseToolsExecutor url_bearer_fetch error: ' . $e->getMessage(), [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Failed to fetch URL with Bearer token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Make an HTTP GET request with Basic authentication
     */
    public function url_basic_fetch($arguments)
    {
        try {
            $url = $arguments['url'] ?? null;
            $username = $arguments['username'] ?? null;
            $password = $arguments['password'] ?? null;
            $headers = $arguments['headers'] ?? [];
            $timeout = $arguments['timeout'] ?? $this->defaultTimeout;

            if (!$url) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'URL is required'
                ];
            }

            if (!$username || !$password) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Username and password are required for Basic authentication'
                ];
            }

            // Add default headers
            $defaultHeaders = [
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-US,en;q=0.9',
            ];

            $requestHeaders = array_merge($defaultHeaders, $headers);

            $response = Http::timeout($timeout)
                ->withHeaders($requestHeaders)
                ->withBasicAuth($username, $password)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => [
                        'status_code' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                        'content_type' => $response->header('Content-Type'),
                        'content_length' => $response->header('Content-Length'),
                        'url' => $url
                    ],
                    'error' => null
                ];
            } else {
                return [
                    'success' => false,
                    'data' => [
                        'status_code' => $response->status(),
                        'url' => $url
                    ],
                    'error' => "HTTP request failed with status code: {$response->status()}"
                ];
            }
        } catch (Exception $e) {
            Log::error('BaseToolsExecutor url_basic_fetch error: ' . $e->getMessage(), [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Failed to fetch URL with Basic authentication: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch and summarize webpage content
     */
    public function webpage_summarize($arguments)
    {
        try {
            $url = $arguments['url'] ?? null;
            $maxLength = $arguments['max_length'] ?? 500;
            $includeLinks = $arguments['include_links'] ?? false;
            $timeout = $arguments['timeout'] ?? $this->defaultTimeout;

            if (!$url) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'URL is required'
                ];
            }

            // First fetch the webpage
            $fetchResult = $this->url_fetch([
                'url' => $url,
                'timeout' => $timeout
            ]);

            if (!$fetchResult['success']) {
                return $fetchResult;
            }

            $content = $fetchResult['data']['body'];
            $contentType = $fetchResult['data']['content_type'];

            // Check if it's HTML content
            if (strpos($contentType, 'text/html') === false) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'URL does not return HTML content'
                ];
            }

            // Extract text content from HTML
            $text = $this->extractTextFromHtml($content, $includeLinks);

            // Create summary
            $summary = $this->createSummary($text, $maxLength);

            return [
                'success' => true,
                'data' => [
                    'url' => $url,
                    'summary' => $summary,
                    'original_length' => strlen($text),
                    'summary_length' => strlen($summary),
                    'content_type' => $contentType,
                    'include_links' => $includeLinks
                ],
                'error' => null
            ];

        } catch (Exception $e) {
            Log::error('BaseToolsExecutor webpage_summarize error: ' . $e->getMessage(), [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Failed to summarize webpage: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a URL is accessible
     */
    public function url_status_check($arguments)
    {
        try {
            $url = $arguments['url'] ?? null;
            $timeout = $arguments['timeout'] ?? 10;

            if (!$url) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'URL is required'
                ];
            }

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => $this->userAgent
                ])
                ->head($url);

            return [
                'success' => true,
                'data' => [
                    'url' => $url,
                    'status_code' => $response->status(),
                    'accessible' => $response->successful(),
                    'content_type' => $response->header('Content-Type'),
                    'content_length' => $response->header('Content-Length'),
                    'last_modified' => $response->header('Last-Modified'),
                    'server' => $response->header('Server')
                ],
                'error' => null
            ];

        } catch (Exception $e) {
            Log::error('BaseToolsExecutor url_status_check error: ' . $e->getMessage(), [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => [
                    'url' => $url ?? 'unknown',
                    'accessible' => false
                ],
                'error' => 'Failed to check URL status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ping a host to check network connectivity
     */
    public function ping($arguments)
    {
        try {
            $host = $arguments['host'] ?? null;
            $count = $arguments['count'] ?? 3;
            $timeout = $arguments['timeout'] ?? 5;

            if (!$host) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Host is required'
                ];
            }

            // Sanitize host input
            $host = escapeshellarg($host);
            $count = max(1, min(10, (int)$count)); // Limit between 1-10
            $timeout = max(1, min(30, (int)$timeout)); // Limit between 1-30 seconds

            // Build ping command
            $command = "ping -c {$count} -W {$timeout} {$host} 2>&1";
            
            $output = shell_exec($command);


            Log::info('[BaseToolsExecutor][PING] Ping command', [
                'command' => $command,
                'output' => $output
            ]);

            $returnCode = intval(trim($this->getLastReturnCode()));

            Log::info('[BaseToolsExecutor][PING] Return code', [
                'return_code' => $returnCode
            ]);

            if ($returnCode === 0) {
                // Parse ping output
                $pingStats = $this->parsePingOutput($output);
                
                return [
                    'success' => true,
                    'data' => [
                        'host' => trim($host, "'"),
                        'reachable' => true,
                        'packets_sent' => $pingStats['packets_sent'] ?? 0,
                        'packets_received' => $pingStats['packets_received'] ?? 0,
                        'packet_loss' => $pingStats['packet_loss'] ?? 0,
                        'min_time' => $pingStats['min_time'] ?? null,
                        'avg_time' => $pingStats['avg_time'] ?? null,
                        'max_time' => $pingStats['max_time'] ?? null,
                        'mdev_time' => $pingStats['mdev_time'] ?? null,
                        'raw_output' => $output
                    ],
                    'error' => null
                ];
            } else {
                return [
                    'success' => false,
                    'data' => [
                        'host' => trim($host, "'"),
                        'reachable' => false,
                        'return_code' => $returnCode,
                        'raw_output' => $output
                    ],
                    'error' => "Host is not reachable (return code: {$returnCode})"
                ];
            }

        } catch (Exception $e) {
            Log::error('BaseToolsExecutor ping error: ' . $e->getMessage(), [
                'host' => $host ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Failed to ping host: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send an email to the current user
     */
    public function email_self($arguments)
    {
        $subject = $arguments['subject'] ?? null;
        $body = $arguments['body'] ?? null;

        if (!$subject || !$body) {
            return ['success' => false, 'error' => 'Missing required parameters: subject, body'];
        }

        if (!$this->user) {
            return ['success' => false, 'error' => 'No authenticated user found'];
        }

        if (!$this->user->email) {
            return ['success' => false, 'error' => 'User does not have an email address configured'];
        }

        try {
            \Mail::raw($body, function ($message) use ($subject) {
                $message->to($this->user->email)->subject($subject);
            });

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send email: ' . $e->getMessage()];
        }
    }

    /**
     * Send an SMS message to the current user
     */
    public function sms_self($arguments)
    {
        $message = $arguments['message'] ?? null;

        if (!$message) {
            return ['success' => false, 'error' => 'Missing required parameter: message'];
        }

        if (!$this->user) {
            return ['success' => false, 'error' => 'No authenticated user found'];
        }

        if (!$this->user->phone_number) {
            return ['success' => false, 'error' => 'User does not have a phone number configured'];
        }

        $body = "From: {$this->user->name} {$this->user->email}\n$message";

        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $twilioNumber = env('TWILIO_FROM');

        $client = new \Twilio\Rest\Client($sid, $token);

        try {
            $twilioMessage = $client->messages->create(
                $this->user->phone_number,
                [
                    'from' => $twilioNumber,
                    'body' => $body,
                ]
            );

            return ['success' => true, 'message' => 'SMS sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send SMS: ' . $e->getMessage()];
        }
    }

    /**
     * Extract text content from HTML
     */
    private function extractTextFromHtml($html, $includeLinks = false)
    {
        // Remove script and style elements
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Remove HTML comments
        $html = preg_replace('/<!--[\s\S]*?-->/', '', $html);
        
        // Convert HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Extract text content
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Optionally include links
        if ($includeLinks) {
            preg_match_all('/<a[^>]+href=([\'"])(.*?)\1[^>]*>(.*?)<\/a>/i', $html, $matches);
            if (!empty($matches[2])) {
                $links = [];
                foreach ($matches[2] as $i => $link) {
                    $linkText = strip_tags($matches[3][$i]);
                    if (!empty($linkText)) {
                        $links[] = "{$linkText}: {$link}";
                    }
                }
                if (!empty($links)) {
                    $text .= "\n\nLinks found:\n" . implode("\n", array_slice($links, 0, 10)); // Limit to 10 links
                }
            }
        }
        
        return $text;
    }

    /**
     * Create a summary of text content
     */
    private function createSummary($text, $maxLength)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        // Simple summary: take first sentence or first N characters
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, 2);
        $firstSentence = trim($sentences[0]);
        
        if (strlen($firstSentence) <= $maxLength) {
            return $firstSentence;
        }
        
        // If first sentence is too long, truncate
        return substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Parse ping command output
     */
    private function parsePingOutput($output)
    {
        $stats = [];
        
        // Extract packet statistics
        if (preg_match('/(\d+) packets transmitted, (\d+) received/', $output, $matches)) {
            $stats['packets_sent'] = (int)$matches[1];
            $stats['packets_received'] = (int)$matches[2];
            $stats['packet_loss'] = $stats['packets_sent'] > 0 ? 
                (($stats['packets_sent'] - $stats['packets_received']) / $stats['packets_sent']) * 100 : 0;
        }
        
        // Extract timing statistics
        if (preg_match('/rtt min\/avg\/max\/mdev = ([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+) ms/', $output, $matches)) {
            $stats['min_time'] = (float)$matches[1];
            $stats['avg_time'] = (float)$matches[2];
            $stats['max_time'] = (float)$matches[3];
            $stats['mdev_time'] = (float)$matches[4];
        }
        
        return $stats;
    }

    /**
     * Get the return code of the last shell command
     */
    private function getLastReturnCode()
    {
        return shell_exec('echo $?');
    }
} 