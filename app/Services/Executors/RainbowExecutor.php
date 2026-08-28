<?php

namespace App\Services\executors;

use App\Services\RainbowDashService;
use Illuminate\Support\Facades\Log;

class RainbowExecutor
{
    private $rainbowService;
    private $token;

    public function __construct()
    {
        $this->rainbowService = new RainbowDashService();
        //$this->login();
    }

    private function login()
    {
        $login_info = $this->rainbowService->login('rich@rainbowtel.com', 'richlikestowork');
        if ($login_info['token']) {
            $this->token = $login_info['token'];
        } else {
            $this->token = false;
        }
    }

    /**
     * Search for customers using various criteria
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_customer_search($arguments)
    {
        Log::info('[RainbowExecutor] rainbow_customer_search arguments: ' . json_encode($arguments));

        try {
            if(!$this->token){
                $this->login();
            }

            $search_string = $arguments['search_string'] ?? $arguments['search_term'] ?? null;
            $search_type = $arguments['search_type'] ?? 'name'; // Default to name search

            if (!$search_string) {
                return ['success' => false, 'error' => 'Search string is required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }


            if (preg_match('/^\+?[1-9]\d{1,14}$/', $search_string)) {
                $search_type = 'phone_number';
            }

            // Map search types to Rainbow API fields
            $search_field_map = [
                'name' => 'account',
                'account' => 'account',
                'account_number' => 'account_number',
                'address' => 'service_address',
                'phone' => 'phone_number',
                'phone_number' => 'phone_number'
            ];

            if (!isset($search_field_map[$search_type])) {
                return ['success' => false, 'error' => 'Invalid search type'];
            }

            $search_field = $search_field_map[$search_type];
            $results = $this->rainbowService->customerSearch($this->token, $search_field, $search_string);

         //   Log::info('[RainbowExecutor] customerSearch results: ' . json_encode($results));

            $count = count($results['accounts'] ?? []);
            $message = $count . ' customers found';
            
            if ($results && isset($results['status']) && $results['status'] == 'success') {
                return [
                    'success' => true,
                    'message' => $message,
                    'customers' => $results['accounts'] ?? []
                ];
            }
           
         //   Log::error('[RainbowExecutor] customerSearch error: ' . json_encode($results));
            return ['success' => false, 'message' => 'No customers found'];

        } catch (\Exception $e) {
            Log::error('[RainbowExecutor] customerSearch error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get CPNI questions for a customer
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_get_cpni_questions($arguments)
    {
        Log::info('[RainbowExecutor] get_cpni_question arguments: ' . json_encode($arguments));

        try {
            if(!$this->token){
                $this->login();
            }

            $account = $arguments['account'] ?? null;

            if (!$account) {
                return ['success' => false, 'error' => 'Account is required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $response = $this->rainbowService->getCpniQuestions($this->token, $account);

            Log::info('[RainbowExecutor] get_cpni_question response: ' . json_encode($response));

            if ($response && isset($response['status']) && $response['status'] === 'success') {
                return [
                    'success' => true,
                    'message' => 'CPNI questions retrieved',
                    'questions' => $response['questions'] ?? []
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Failed to get CPNI questions'
            ];

        } catch (\Exception $e) {
            Log::error('Rainbow get CPNI question error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify CPNI answer for a customer
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_verify_cpni($arguments)
    {
        try {

            if(!$this->token){
                $this->login();
            }

            $account  = $arguments['account'] ?? null;
            $answer = $arguments['answer'] ?? null;
            $question = $arguments['question'] ?? null;

            if (!$account || !$answer) {
                return ['success' => false, 'error' => 'Customer ID and answer are required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $response = $this->rainbowService->verifyCpniAnswer($this->token, $account, $question, $answer);

            if ($response && $response['status'] == 'success') {
                return [
                    'success' => true,
                    'message' => 'CPNI verification successful',
                    'verified' => true
                ];
            }

            return [
                'success' => false,
                'message' => 'CPNI verification failed',
                'verified' => false
            ];

        } catch (\Exception $e) {
            Log::error('Rainbow verify CPNI error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lookup a helpdesk user by user_name
     *
     * @param array $arguments
     * @return array
     */
    public function helpdesk_user_lookup($arguments)
    {
        Log::info('[RainbowExecutor] helpdesk_user_lookup arguments: ' . json_encode($arguments));
        try {
            if (!$this->token) {
                $this->login();
            }
            $user_name = $arguments['user_name'] ?? null;
            if (!$user_name) {
                return ['success' => false, 'error' => 'user_name is required'];
            }
            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }
            $result = $this->rainbowService->helpdesk_user_lookup($this->token, $user_name);
            Log::info('[RainbowExecutor] helpdesk_user_lookup result: ' . json_encode($result));
            if ($result && isset($result['status']) && $result['status'] === 'success') {
                return [
                    'success' => true,
                    'message' => 'User found',
                    'user' => $result['user'] ?? $result['data'] ?? $result
                ];
            }
            return ['success' => false, 'message' => 'User not found', 'result' => $result];
        } catch (\Exception $e) {
            Log::error('[RainbowExecutor] helpdesk_user_lookup error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
