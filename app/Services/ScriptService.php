<?php

namespace App\Services;

use App\Models\Script;
use Illuminate\Support\Facades\Storage;

class ScriptService
{
    public function create(array $data)
    {
        return Script::create($data);
    }

    public function update(Script $script, array $data)
    {
        $script->update($data);
        return $script;
    }

    public function delete(Script $script)
    {
        return $script->delete();
    }

    public function execute(Script $script)
    {
        $script->increment('execution_count');
        $script->update(['last_executed_at' => now()]);
        
        // Here you would implement the actual script execution logic
        // based on the script type and path
        return true;
    }

    public function getScriptContent(Script $script)
    {
        return Storage::get($script->path);
    }
} 