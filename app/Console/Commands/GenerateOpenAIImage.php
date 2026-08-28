<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\OpenAIImageExecutor;

class GenerateOpenAIImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:image \
        {prompt : The prompt for the image} \
        {--model=dall-e-2 : Model to use (dall-e-2,dall-e-3,gpt-image-1)} \
        {--size=1024x1024 : Image size (256x256,512x512,1024x1024,1024x1792,1792x1024)} \
        {--n=1 : Number of images to generate} \
        {--quality=standard : Image quality (standard,hd)} \
        {--style=vivid : Image style (vivid,natural)} \
        {--output= : Output file path (default: storage/app/public/generated/ai_image.png)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an image using OpenAI DALL-E from the command line';

    /**
     * Execute the console command.
     */
    public function handle()
    {

       
        $time = time();

        $prompt = $this->argument('prompt');
        $model = $this->option('model');
        $size = $this->option('size');
        $n = (int) $this->option('n');
        $quality = $this->option('quality');
        
        $style = $this->option('style');

        $output = $this->option('output') ?: "public/generated/ai_image_{$time}.png";

        $args = [
            'prompt' => $prompt,
        ];
        if ($model) $args['model'] = $model;
        if ($n) $args['n'] = $n;
        if ($size) $args['size'] = $size;
        if ($quality) $args['quality'] = $quality;
        if ($style) $args['style'] = $style;
        if ($output) $args['save_path'] = $output;

        $executor = new OpenAIImageExecutor();
        $result = $executor->openai_generate_image($args);

        if ($result['success']) {
            $this->info('Image generated successfully!');
            if (isset($result['data']['saved_path'])) {
                $this->info('Saved to: ' . $result['data']['saved_path']);
            } else {
                $this->info('Image data: ' . json_encode($result['data']));
            }
        } else {
            $this->error('Failed to generate image: ' . ($result['error'] ?? 'Unknown error'));
        }
    }
} 