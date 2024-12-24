<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ConversationManager;
use App\Services\ToolExecutor;
use App\Services\OpenAIAssistant;
use App\Services\OllamaApiClient;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ConversationManager::class, function ($app) {
            return new ConversationManager(
                $app->make(OllamaApiClient::class),
                $app->make(ToolExecutor::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
