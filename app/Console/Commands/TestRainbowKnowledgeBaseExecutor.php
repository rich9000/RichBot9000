<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\RainbowKnowledgeBaseExecutor;
use Illuminate\Support\Facades\Log;

class TestRainbowKnowledgeBaseExecutor extends Command
{
    protected $signature = 'test:rainbowkb';
    protected $description = 'Test the Rainbow Knowledge Base functionality';

    protected $executor;

    public function __construct()
    {
        parent::__construct();
        $this->executor = new RainbowKnowledgeBaseExecutor();
    }

    public function handle()
    {
        $this->info('Starting Rainbow Knowledge Base Test...');
    
        $this->info('Starting Login Test...');
       
        $loginResult = $this->executor->login(false);
        $this->info('Login Response:');
        $this->line(json_encode($loginResult, JSON_PRETTY_PRINT));

        if($this->executor->getToken()) {
            $this->info('Login successful - Token received');
            $this->info('Token: ' . substr($this->executor->getToken(), 0, 20) . '...');
        } else {
            $this->error('Login failed - No token received');
            if (isset($loginResult['error'])) {
                $this->error('Login error details: ' . json_encode($loginResult['error']));
            }
            return;
        }

        // Test getting categories
        $this->testGetCategories();
        
        // Test creating an article
        $this->testCreateArticle();
        
        // Test searching KB articles
        $this->testSearchKBArticles();
        
        // Test getting a specific article
        $this->testGetKBArticle();
        
        // Test emailing an article
        $this->testEmailKBArticle();

        $this->info('Rainbow Knowledge Base Test completed.');
    }

    protected function testGetCategories()
    {
        $this->info("\nTesting get categories...");
        
        $result = $this->executor->rainbow_dashboard_get_kb_categories();
        
        // Log the raw response
        $this->info('Raw API Response:');
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        if ($result['success']) {
            $categories = $result['data'] ?? [];
            $this->info('✓ Found ' . count($categories) . ' categories');
            
            if (count($categories) > 0) {
                $this->table(
                    ['ID', 'Name', 'Description'],
                    array_map(function($category) {
                        return [
                            $category['id'] ?? 'N/A',
                            $category['name'] ?? 'N/A',
                            substr($category['description'] ?? 'N/A', 0, 50) . '...'
                        ];
                    }, $categories)
                );
                return $categories[0] ?? null;
            } else {
                $this->warn('No categories found in the response data');
            }
        } else {
            $this->error('✗ Get categories failed: ' . ($result['message'] ?? 'Unknown error'));
            if (isset($result['error'])) {
                $this->error('Error details: ' . json_encode($result['error']));
            }
            if (isset($result['trace'])) {
                $this->error('Stack trace: ' . $result['trace']);
            }
        }
        return null;
    }

    protected function testCreateArticle()
    {
        $this->info("\nTesting create article...");
        
        $category = $this->testGetCategories();
        if (!$category) {
            $this->error('✗ Cannot create article without a category');
            return null;
        }

        $result = $this->executor->rainbow_dashboard_create_article([
            'title' => 'Test Article ' . now()->format('Y-m-d H:i:s'),
            'content' => 'This is a test article created by the automated test.',
            'k_b_category_id' => $category['id'],
            'help_desk_article' => true
        ]);

        if ($result['success']) {
            $this->info('✓ Article created successfully');
            $article = $result['data'] ?? [];
            $this->info('Article ID: ' . ($article['id'] ?? 'N/A'));
            return $article;
        } else {
            $this->error('✗ Failed to create article: ' . ($result['message'] ?? 'Unknown error'));
            return null;
        }
    }

    protected function testSearchKBArticles()
    {
        $this->info("\nTesting search KB articles...");
        
        // Test with a valid search string
        $result = $this->executor->rainbow_dashboard_search_kb_article([
            'search_string' => 'test'
        ]);
        
        if ($result['success']) {
            $this->info('✓ Search KB articles successful');
            $articles = $result['data'] ?? [];
            $this->info('Found ' . count($articles) . ' articles');
            
            if (count($articles) > 0) {
                $this->info("\nFirst article details:");
                $this->table(
                    ['ID', 'Title', 'Category', 'Created'],
                    array_map(function($article) {
                        return [
                            $article['id'] ?? 'N/A',
                            $article['title'] ?? 'N/A',
                            $article['category'] ?? 'N/A',
                            $article['created_at'] ?? 'N/A'
                        ];
                    }, array_slice($articles, 0, 5))
                );
            }
        } else {
            $this->error('✗ Search KB articles failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        // Test with empty search string
        $result = $this->executor->rainbow_dashboard_search_kb_article([
            'search_string' => ''
        ]);
        
        if (!$result['success']) {
            $this->info('✓ Empty search string validation working as expected');
        } else {
            $this->error('✗ Empty search string validation failed');
        }
    }

    protected function testGetKBArticle()
    {
        $this->info("\nTesting get KB article...");
        
        // First create an article to test with
        $article = $this->testCreateArticle();
        if (!$article) {
            $this->error('✗ Cannot test get article without a test article');
            return;
        }
        
        // Test with the created article ID
        $result = $this->executor->rainbow_dashboard_get_kb_article([
            'article_id' => $article['id']
        ]);
        
        if ($result['success']) {
            $this->info('✓ Get KB article successful');
            $article = $result['data'] ?? [];
            
            if ($article) {
                $this->info("\nArticle details:");
                $this->table(
                    ['ID', 'Title', 'Category', 'Content'],
                    [[
                        $article['id'] ?? 'N/A',
                        $article['title'] ?? 'N/A',
                        $article['category'] ?? 'N/A',
                        substr($article['content'] ?? 'N/A', 0, 50) . '...'
                    ]]
                );
            }
        } else {
            $this->error('✗ Get KB article failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        // Test with invalid article ID
        $result = $this->executor->rainbow_dashboard_get_kb_article([
            'article_id' => 999999
        ]);
        
        if (!$result['success']) {
            $this->info('✓ Invalid article ID validation working as expected');
        } else {
            $this->error('✗ Invalid article ID validation failed');
        }
    }

    protected function testEmailKBArticle()
    {
        $this->info("\nTesting email KB article...");
        
        // First create an article to test with
        $article = $this->testCreateArticle();
        if (!$article) {
            $this->error('✗ Cannot test email article without a test article');
            return;
        }
        
        // Test with valid article ID and user ID
        $result = $this->executor->rainbow_dashboard_email_kb_article([
            'article_id' => $article['id'],
            'user_id' => 1 // Assuming user ID 1 exists
        ]);
        
        if ($result['success']) {
            $this->info('✓ Email KB article successful');
        } else {
            $this->error('✗ Email KB article failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        // Test with missing parameters
        $result = $this->executor->rainbow_dashboard_email_kb_article([
            'article_id' => $article['id']
        ]);
        
        if (!$result['success']) {
            $this->info('✓ Missing parameters validation working as expected');
        } else {
            $this->error('✗ Missing parameters validation failed');
        }
    }
} 