$sessionMaker = new OpenAISessionMaker();

// Generate full config
$config = $sessionMaker->generateSessionConfig($assistant);

// Generate text-only config
$textConfig = $sessionMaker->generateTextOnlyConfig($assistant);

// Update existing config
$updatedConfig = $sessionMaker->updateSessionConfig($currentConfig, [
    'temperature' => 0.8,
    'turn_detection' => null
]);
