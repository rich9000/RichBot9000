<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\RainbowOutageExecutor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TestRainbowOutageExecutor extends Command
{
    protected $signature = 'test:rainbow-outage-executor';
    protected $description = 'Test the Rainbow Outage Executor functionality';

    protected $executor;

    public function __construct(RainbowOutageExecutor $executor)
    {
        parent::__construct();
        $this->executor = $executor;
    }

    public function handle()
    {
        $this->info('Testing Rainbow Outage Executor...');

        // First login
        if (!$this->executor->login()) {
            $this->error('Failed to login to Rainbow Outage API');
            return 1;
        }

        $this->info('Successfully logged in');

        // Test listing areas
        $this->testListAreas();

        // Test listing services
        $this->testListServices();

        // Test creating an outage
        $this->testCreateOutage();

        // Test listing outages
        $this->testListOutages();

        // Test adding a comment
        $this->testAddComment();

        // Test resolving an outage
        $this->testResolveOutage();

        $this->info('All tests completed!');
        return 0;
    }

    protected function testListAreas()
    {
        $this->info("\nTesting list areas...");
        $result = $this->executor->rainbow_outage_list_areas();

        if ($result['success']) {
            $this->info('Successfully listed areas:');
            $areas = collect($result['data'])->map(function($area) {

                Log::info('[TestRainbowOutageExecutor] Area: ' . json_encode($area));
                Log::info('[TestRainbowOutageExecutor] Area ID: ' . $area['id']);
                Log::info('[TestRainbowOutageExecutor] Area Name: ' . $area['name']);
                Log::info('[TestRainbowOutageExecutor] Area Description: ' . $area['description']);

                return [
                    'id' => $area['id'],
                    'name' => $area['name'],
                    'description' => $area['description']
                ];
            })->toArray();
            $this->table(['ID', 'Name', 'Description'], $areas);
        } else {
            $this->error('Failed to list areas: ' . ($result['message'] ?? 'Unknown error'));
        }
    }

    protected function testListServices()
    {
        $this->info("\nTesting list services...");
        $result = $this->executor->rainbow_outage_list_services();

        if ($result['success']) {
            $this->info('Successfully listed services:');

            Log::info('[TestRainbowOutageExecutor] Services: ' . json_encode($result));
           
            $services = collect($result['data'])->map(function($service) {

                Log::info('[TestRainbowOutageExecutor] Services ID: ' . $service['id']);
                Log::info('[TestRainbowOutageExecutor] Services Name: ' . $service['name']);
                Log::info('[TestRainbowOutageExecutor] Services Description: ' . $service['description']);


                return [
                    'id' => $service['id'],
                    'name' => $service['name'],
                    'description' => $service['description']
                ];
            })->toArray();
            $this->table(['ID', 'Name', 'Description'], $services);
        } else {
            $this->error('Failed to list services: ' . ($result['message'] ?? 'Unknown error'));
        }
    }

    protected function testCreateOutage()
    {
        $this->info("\nTesting create outage...");

        // First get an area and service to use
        $areasResult = $this->executor->rainbow_outage_list_areas();
        $servicesResult = $this->executor->rainbow_outage_list_services();

        if (!$areasResult['success'] || !$servicesResult['success']) {
            $this->error('Failed to get areas or services for outage creation');
            return;
        }

        $area = collect($areasResult['data'])->first();
        $service = collect($servicesResult['data'])->first();

        if (!$area || !$service) {
            $this->error('No areas or services available for outage creation');
            return;
        }

        $params = [
            'description' => 'Test outage created by TestRainbowOutageExecutor',
            'type' => 'local',
            'start_time' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
            'outage_type' => 'planned',
            'duration' => 30,
            'areas' => [$area['id']],
            'services' => [$service['id']]
        ];

        Log::info('[TestRainbowOutageExecutor] Params: ' . json_encode($params));

        $result = $this->executor->rainbow_outage_create($params);

        if ($result['success']) {
            $this->info('Successfully created outage:');

            Log::info('[TestRainbowOutageExecutor] Result: ' . json_encode($result));

            $this->table(
                ['ID', 'Description', 'Type', 'Start Time', 'Outage Type', 'Duration'],
                [[
                    $result['data']['id'],
                    $result['data']['description'],
                    $result['data']['type'],
                    $result['data']['start_time'],
                    $result['data']['outage_type'],
                    $result['data']['duration']
                ]]
            );
        } else {
            $this->error('Failed to create outage: ' . ($result['message'] ?? 'Unknown error'));
        }
    }

    protected function testListOutages()
    {
        $this->info("\nTesting list outages...");
        $result = $this->executor->rainbow_outage_list();

        Log::info('[TestRainbowOutageExecutor] List Outages: ' . json_encode($result));

        if ($result['success']) {
            $this->info('Successfully listed outages:');
            $outages = collect($result['data']['active_outages'])->map(function($outage) {
                return [
                    'id' => $outage['id'],
                    'description' => $outage['description'],
                    'type' => $outage['type'],
                    'status' => $outage['status'],
                    'start_time' => $outage['start_time'],
                    'outage_type' => $outage['outage_type'],
                    'duration' => $outage['duration']
                ];
            })->toArray();
            $this->table(
                ['ID', 'Description', 'Type', 'Status', 'Start Time', 'Outage Type', 'Duration'],
                $outages
            );
        } else {
            $this->error('Failed to list outages: ' . ($result['message'] ?? 'Unknown error'));
        }
    }

    protected function testAddComment()
    {
        $this->info("\nTesting add comment...");

        // First get an outage to comment on
        $result = $this->executor->rainbow_outage_list();

        if (!$result['success']) {
            $this->error('Failed to get outages for comment test');
            return;
        }

        $outage = collect($result['data']['active_outages'])->first();

        Log::info('[TestRainbowOutageExecutor] Outage: ' , [$outage]);

        if (!$outage) {
            $this->error('No outages available for comment test');
            return;
        }

        // Get comments before adding new comment
        $this->info('Getting comments before adding new comment...');
        $beforeResult = $this->executor->rainbow_outage_get_comments($outage['id']);
        $beforeComments = $beforeResult['success'] ? $beforeResult['data'] : [];
        $this->info('Comments before: ' . count($beforeComments));

        // Add new comment
        $comment = 'Test comment added by TestRainbowOutageExecutor at ' . now()->format('Y-m-d H:i:s');
        $result = $this->executor->rainbow_outage_add_comment($outage['id'], $comment);

        if ($result['success']) {
            $this->info('Successfully added comment to outage ' . $outage['id']);

            // Get comments after adding new comment
            $this->info('Getting comments after adding new comment...');
            $afterResult = $this->executor->rainbow_outage_get_comments($outage['id']);
            $afterComments = $afterResult['success'] ? $afterResult['data'] : [];
            $this->info('Comments after: ' . count($afterComments));

            // Verify the comment was added
            if (count($afterComments) > count($beforeComments)) {
                $this->info('Comment count increased - test passed!');
                $this->info('New comment: ' . $comment);
            } else {
                $this->error('Comment count did not increase - test failed!');
            }
        } else {
            $this->error('Failed to add comment: ' . ($result['message'] ?? 'Unknown error'));
        }
    }

    protected function testResolveOutage()
    {
        $this->info("\nTesting resolve outage...");

        // First get an active outage to resolve
        $result = $this->executor->rainbow_outage_list('active');

        if (!$result['success']) {
            $this->error('Failed to get active outages for resolve test');
            return;
        }

        Log::info('[TestRainbowOutageExecutor] Result: ' . json_encode($result));

        // Fix the data access - check if we have active_outages in the response
        $outages = $result['data']['active_outages'] ?? [];
        $outage = collect($outages)->first();

        if (!$outage) {
            $this->error('No active outages available for resolve test');
            return;
        }

        Log::info('[TestRainbowOutageExecutor] Outage to resolve: ' . json_encode($outage));

        // Test resolving with a reason
        $reason = 'Test resolution by TestRainbowOutageExecutor at ' . now()->format('Y-m-d H:i:s');
        $result = $this->executor->rainbow_outage_resolve($outage['id'], $reason);

        Log::info('[TestRainbowOutageExecutor] Resolve Outage Result: ' . json_encode($result));

        if ($result['success']) {
            $this->info('Successfully resolved outage ' . $outage['id'] . ' with reason: ' . $reason);

            // Verify the outage status was updated
            $verifyResult = $this->executor->rainbow_outage_list('closed');
            if ($verifyResult['success']) {
                Log::info('[TestRainbowOutageExecutor] Verification Result: ' . json_encode($verifyResult));
                
                $closedOutages = $verifyResult['data']['closed_outages'] ?? [];
                Log::info('[TestRainbowOutageExecutor] Number of closed outages: ' . count($closedOutages));
                
                $resolvedOutage = collect($closedOutages)->first(function($o) use ($outage) {
                    $matches = $o['id'] === $outage['id'];
                    Log::info('[TestRainbowOutageExecutor] Comparing outage IDs - Current: ' . $o['id'] . ', Target: ' . $outage['id'] . ', Matches: ' . ($matches ? 'Yes' : 'No'));
                    return $matches;
                });
                
                if ($resolvedOutage) {
                    Log::info('[TestRainbowOutageExecutor] Found resolved outage: ' . json_encode($resolvedOutage));
                    Log::info('[TestRainbowOutageExecutor] Current status: ' . ($resolvedOutage['status'] ?? 'undefined'));
                    
                    if ($resolvedOutage['status'] === 'resolved') {
                        $this->info('Outage status successfully updated to resolved');
                    } else {
                        $this->error('Outage status was not updated correctly. Expected: resolved, Got: ' . ($resolvedOutage['status'] ?? 'undefined'));
                    }
                } else {
                    $this->error('Could not find the resolved outage in the closed outages list. Outage ID: ' . $outage['id']);
                }
            } else {
                $this->error('Failed to verify outage status: ' . ($verifyResult['message'] ?? 'Unknown error'));
                Log::error('[TestRainbowOutageExecutor] Verification failed: ' . json_encode($verifyResult));
            }
        } else {
            $this->error('Failed to resolve outage: ' . ($result['message'] ?? 'Unknown error'));
        }
    }
} 