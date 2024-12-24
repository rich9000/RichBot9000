<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class VideoUploadController extends Controller 
{
    public function upload(Request $request)
    {
        $user = Auth::user();
        $userFolder = 'videos/' . $user->id;

        Storage::disk('public')->makeDirectory($userFolder);

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $segmentName = 'segment-' . time() . '.webm'; // Use a timestamp to keep order
            $path = $file->storeAs($userFolder, $segmentName, 'public');

            $this->updatePlaylist($userFolder, $segmentName);
            $this->updateJsonPlaylist($userFolder, $segmentName);

            return response()->json(['status' => 'success', 'path' => $path]);
        }

        return response()->json(['status' => 'error', 'message' => 'No video file uploaded.']);
    }

    private function updateJsonPlaylist($userFolder, $segmentName)
    {
        $playlistPath = storage_path('app/public/' . $userFolder . '/playlist.json');

        // Read the existing playlist or initialize a new one
        $playlist = file_exists($playlistPath) ? json_decode(file_get_contents($playlistPath), true) : [];

        // Append the new segment to the playlist
        $playlist[] = $segmentName;

        // Keep only the last 20 segments
        if (count($playlist) > 20) {
            $playlist = array_slice($playlist, -20);
        }

        // Write the updated playlist back to the file
        file_put_contents($playlistPath, json_encode($playlist));
    }

    private function updatePlaylist($userFolder, $segmentName)
    {
        $playlistPath = storage_path('app/public/' . $userFolder . '/playlist.m3u8');

        // Read the current playlist
        $playlist = file_exists($playlistPath) ? file($playlistPath) : [];

        // Initialize playlist if it does not exist
        if (empty($playlist)) {
            $playlist[] = "#EXTM3U\n";
            $playlist[] = "#EXT-X-VERSION:3\n";
            $playlist[] = "#EXT-X-TARGETDURATION:3\n";
            $playlist[] = "#EXT-X-MEDIA-SEQUENCE:0\n";
        }

        // Remove any segments beyond the last 20
        $mediaSequence = 0;
        $segmentCount = 0;
        foreach ($playlist as $index => $line) {
            if (strpos($line, "#EXTINF") !== false) {
                $segmentCount++;
                if ($segmentCount <= count($playlist) - 20 * 2) { // Each segment has 2 lines: #EXTINF and the segment file name
                    unset($playlist[$index], $playlist[$index + 1]);
                } else {
                    $mediaSequence++;
                }
            }
        }

        // Update media sequence number in the playlist
        $playlist = array_values($playlist); // Reindex array after unset
        $playlist[3] = "#EXT-X-MEDIA-SEQUENCE:$mediaSequence\n";

        // Append the new segment to the playlist
        $playlist[] = "#EXTINF:3.0,\n";
        $playlist[] = $segmentName . "\n";

        // Write back the updated playlist
        file_put_contents($playlistPath, implode('', $playlist));
    }
}
