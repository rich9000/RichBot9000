<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\OpenAIImageExecutor;

class EditOpenAIImage extends Command
{
    protected $signature = 'openai:image-edit \
        {image : Path to the image to edit} \
        {prompt : The prompt for the edit} \
        {--model=dall-e-2 : Model to use (dall-e-2,gpt-image-1)} \
        {--mask= : Optional mask image path} \
        {--n=1 : Number of images to generate} \
        {--size=1024x1024 : Image size (256x256,512x512,1024x1024)} \
        {--output= : Output file path (default: public/generated/ai_image_edited.png)}';

    protected $description = 'Edit an image using OpenAI DALL-E from the command line';

    public function handle()
    {
        $image = $this->argument('image');
        $prompt = $this->argument('prompt');
        $mask = $this->option('mask');
        $n = $this->option('n') ?: 1;
        $size = $this->option('size') ?: '1024x1024';
        $output = $this->option('output') ?: 'public/generated/ai_image_edited.png';
        $model = $this->option('model') ?: 'dall-e-2';

        $executor = new OpenAIImageExecutor();
        $args = [
            'image_path' => $image,
            'prompt' => $prompt,
            'model' => $model,
            'n' => $n,
            'size' => $size,
            'save_path' => $output,
        ];
        if ($mask) {
            $args['mask_path'] = $mask;
        }
        $result = $executor->openai_edit_image($args);

        if ($result['success']) {
            $this->info('Image edited successfully!');
            if (isset($result['data']['saved_path'])) {
                $this->info('Saved to: ' . $result['data']['saved_path']);
            } else {
                $this->info('Image data: ' . json_encode($result['data']));
            }
        } else {
            $this->error('Failed to edit image: ' . ($result['error'] ?? 'Unknown error'));
        }
    }
} 