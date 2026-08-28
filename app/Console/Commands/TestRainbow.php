<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RainbowDashService;
use App\Services\Executors\RainbowExecutor;
use Illuminate\Support\Facades\Log;

class TestRainbow extends Command
{
    protected $signature = 'test:rainbow';
    protected $description = 'Test RainbowDashService and RainbowExecutor functionality';

    private $rainbowService;
    private $rainbowExecutor;
    private $baseUrl = 'https://dash.rainbowtel.net/api';

    public function __construct()
    {
        parent::__construct();
        $this->rainbowService = new RainbowDashService();
        $this->rainbowExecutor = new RainbowExecutor();
    }

    public function handle()
    {
        Log::info('[TestRainbow] Starting Rainbow Service Tests...');
        $this->info('Starting Rainbow Service Tests...');
        
        // Get token for all tests
        $login_info = $this->rainbowService->login('rich@rainbowtel.com', 'richlikestowork');
        if (!$login_info || !isset($login_info['token'])) {
            $this->error('Failed to get authentication token');
            return 1;
        }
        $token = $login_info['token'];

        $this->info("\nAuthentication Token: " . $token);
        
        // Step 1: Search for Scott Wheeler
        $this->info("\nStep 1: Searching for Scott Wheeler...");
        $nameResult = $this->rainbowExecutor->rainbow_customer_search([
            'search_string' => 'Scott Wheeler',
            'search_type' => 'name'
        ]);

        if (!$nameResult['success'] || empty($nameResult['customers'])) {
            $this->error('Failed to find Scott Wheeler');
            return 1;
        }

        $customer = $nameResult['customers'][0];
        $accountNumber = $customer['account_number'] ?? null;
        $account = $customer['account'] ?? null;

        if (!$accountNumber) {
            $this->error('No account number found for Scott Wheeler');
            return 1;
        }

        $this->info("Found account number: " . $account);

        // Step 2: Get CPNI Questions
        $this->info("\nStep 2: Getting CPNI Questions...");
        $this->info("Account Number being used: " . $account);
        
        Log::info('[TestRainbow] Attempting to get CPNI questions', [
            'account' => $account,
            'token' => substr($token, 0, 10) . '...' // Log partial token for security
        ]);

        // Display the curl command for debugging
        $this->info("\nCPNI Questions Curl Command:");
        $this->displayCurlCommand('POST', '/get_cpni_questions', [
            'account' => $account,
        ], $token);

        $cpniQuestions = $this->rainbowExecutor->rainbow_get_cpni_questions([
            'account' => $account
        ]);
        
        Log::info('[TestRainbow] CPNI Questions Response', [
            'response' => $cpniQuestions
        ]);

        $this->info("Raw CPNI Questions Response:");
        $this->line(json_encode($cpniQuestions, JSON_PRETTY_PRINT));
        
        if (!$cpniQuestions) {
            $this->error('No response received from CPNI questions request');
            return 1;
        }

        if (!$cpniQuestions['success']) {
            $this->error('CPNI questions request failed');
            $this->line("Error: " . ($cpniQuestions['message'] ?? 'Unknown error'));
            return 1;
        }

        if (empty($cpniQuestions['questions'])) {
            $this->error('No CPNI questions available for this account');
            return 1;
        }

        $this->info("CPNI Questions received:");
        $this->line(json_encode($cpniQuestions['questions'], JSON_PRETTY_PRINT));

        // Step 3: Verify CPNI Answer
        $this->info("\nStep 3: Verifying CPNI Answers...");
        $passcodes = ['1234', '3333'];
        $question = $cpniQuestions['questions'][0] ?? null;

        if (!$question) {
            $this->error('No CPNI question found');
            return 1;
        }

        foreach ($passcodes as $passcode) {
            $this->info("\nTrying passcode: " . $passcode);
            $verifyResult = $this->rainbowExecutor->rainbow_verify_cpni([
                'account' => $customer['account'],
                'question' => $question,
                'answer' => $passcode
            ]);
            
            $this->info("CPNI Verification Result:");
            $this->line("Question: " . $question);
            $this->line("Answer Attempted: " . $passcode);
            $this->line("Full Response:");
            $this->line(json_encode($verifyResult, JSON_PRETTY_PRINT));

            if ($verifyResult['success']) {
                $this->info("CPNI Verification Successful!");
                break;
            } else {
                $this->error("CPNI Verification Failed!");
                if (isset($verifyResult['message'])) {
                    $this->error("Error: " . $verifyResult['message']);
                }
            }
        }

        return 0;
    }

    private function displayResult($result)
    {
        if ($result['success'] ?? false) {
            $this->info('Success: ' . ($result['message'] ?? 'Operation successful'));
            if (isset($result['customers'])) {
                $this->info('Found ' . count($result['customers']) . ' customers:');
                foreach ($result['customers'] as $customer) {
                    $this->line('- ' . json_encode($customer));
                }
            }
        } else {
            $this->error('Error: ' . ($result['error'] ?? 'Unknown error occurred'));
        }
    }

    private function displayCurlCommand($method, $endpoint, $data, $token)
    {
        $this->info("\n=== COPY-PASTE READY CURL COMMAND ===");
        $this->line("echo 'Running curl command for {$endpoint}...'");
        
        // Single line version for easy copy-paste
        $singleLineCommand = "curl -X {$method} '{$this->baseUrl}{$endpoint}' -H 'Authorization: Bearer {$token}' -H 'Content-Type: application/json' -d '" . json_encode($data) . "'";
        $this->line($singleLineCommand);
        
        // Pretty version for reference
        $this->info("\n=== FORMATTED VERSION (FOR REFERENCE) ===");
        $curlCommand = "curl -X {$method} \\\n";
        $curlCommand .= "  '{$this->baseUrl}{$endpoint}' \\\n";
        $curlCommand .= "  -H 'Authorization: Bearer {$token}' \\\n";
        $curlCommand .= "  -H 'Content-Type: application/json' \\\n";
        $curlCommand .= "  -d '" . json_encode($data) . "'";
        
        $this->line($curlCommand);
        $this->line("\n=== END CURL COMMAND ===\n");
    }
} 