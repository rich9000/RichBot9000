<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = DB::table('roles')->get();
echo "Current Roles:\n";
foreach ($roles as $role) {
    echo "- {$role->name}\n";
} 