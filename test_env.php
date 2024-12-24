<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

echo "Testing OPENAI_API_KEY access methods:\n\n";
echo "Using env(): " . env('OPENAI_API_KEY') . "\n";
echo "Using getenv(): " . getenv('OPENAI_API_KEY') . "\n"; 