<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\OpenAIImageExecutor;

class VariationOpenAIImage extends Command
{
    protected $signature = 'openai:image-variation \
        {image : Path to the image to create variation from} \
        {--model=dall-e-2 : Only dall-e-2 available} \
        {--n=1 : Number of variations to generate} \
        {--size=1024x1024 : Image size (256x256,512x512,1024x1024)} \
        {--output= : Output file path (default: public/generated/ai_image_variation.png)}';

    protected $description = 'Create a variation of an image using OpenAI DALL-E from the command line';

    public function handle()
    {
        $image = $this->argument('image');
        $model = $this->option('model') ?: 'dall-e-2';
        $n = $this->option('n') ?: 1;
        $size = $this->option('size') ?: '1024x1024';
        $output = $this->option('output') ?: 'public/generated/ai_image_variation.png';

        $executor = new OpenAIImageExecutor();
        $args = [
            'image_path' => $image,
            'model' => $model,
            'n' => $n,
            'size' => $size,
            'save_path' => $output,
        ];
        $result = $executor->openai_create_variation($args);

        if ($result['success']) {
            $this->info('Image variation created successfully!');
            if (isset($result['data']['saved_path'])) {
                $this->info('Saved to: ' . $result['data']['saved_path']);
            } else {
                $this->info('Image data: ' . json_encode($result['data']));
            }
        } else {
            $this->error('Failed to create image variation: ' . ($result['error'] ?? 'Unknown error'));
        }
    }
} 