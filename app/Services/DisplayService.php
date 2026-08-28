<?php

namespace App\Services;

use App\Models\Display;
use Illuminate\Support\Facades\Log;

class DisplayService
{
    public function getDisplayByName(string $displayName)
    {
        try {
            return Display::where('name', $displayName)->first();
        } catch (\Exception $e) {
            Log::error("Error getting display by name", [
                'display_name' => $displayName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function updateDisplayByName(string $displayName, array $data)
    {
        try {
            $display = $this->getDisplayByName($displayName);
            
            if (!$display) {
                // Create new display if it doesn't exist
                $display = new Display();
                $display->name = $displayName;
            }

            if (isset($data['content'])) {
                $display->content = $data['content'];
            }

            if (isset($data['audio_url'])) {
                $display->audio_url = $data['audio_url'];
            }

            if (isset($data['status'])) {
                $display->status = $data['status'];
            }

            $display->save();

            return $display;

        } catch (\Exception $e) {
            Log::error("Error updating display by name", [
                'display_name' => $displayName,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getDisplay($id)
    {
        try {
            return Display::find($id);
        } catch (\Exception $e) {
            Log::error("Error getting display", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function updateDisplay($id, array $data)
    {
        try {
            $display = $this->getDisplay($id);
            
            if (!$display) {
                return null;
            }

            if (isset($data['content'])) {
                $display->content = $data['content'];
            }

            if (isset($data['audio_url'])) {
                $display->audio_url = $data['audio_url'];
            }

            if (isset($data['status'])) {
                $display->status = $data['status'];
            }

            $display->save();

            return $display;

        } catch (\Exception $e) {
            Log::error("Error updating display", [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
} 