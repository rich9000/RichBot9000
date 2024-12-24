<?php


namespace App\Services;

class Pipeline
{
    private $stages = [];
    private $context = '';
    private $initial_prompt = '';
    private $prompts = [];

    public function addStage(callable $stage)
    {
        $this->stages[] = $stage;
    }

    public function run($payload)
    {
        foreach ($this->stages as $stage) {
            $payload = $stage($payload);
        }
        return $payload;
    }
}
