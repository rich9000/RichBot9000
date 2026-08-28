<?php
// app/Console/Commands/ConvertRawAudioFiles.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConvertRawAudioFiles extends Command
{
    protected $signature = 'audio:convert-raw {stream_sid?} {--all}';
    protected $description = 'Convert raw audio files to WAV format';

    private $BIAS = 0x84;
    private $exp_lut = [0, 132, 396, 924, 1980, 4092, 8316, 16764];

    public function handle()
    {
        $streamSid = $this->argument('stream_sid');
        $convertAll = $this->option('all');

        $baseDir = storage_path('app/audio_recordings');

        if ($convertAll) {
            $streamDirs = glob($baseDir . '/*', GLOB_ONLYDIR);
            foreach ($streamDirs as $streamDir) {
                $this->convertStreamDirectory(basename($streamDir));
            }
        } elseif ($streamSid) {
            $this->convertStreamDirectory($streamSid);
        } else {
            $this->error('Please provide a stream_sid or use --all option');
            return 1;
        }
    }

    private function convertStreamDirectory($streamSid)
    {
        $this->info("Processing stream: {$streamSid}");
        $baseDir = storage_path("app/audio_recordings/{$streamSid}");

        foreach (['input', 'output'] as $direction) {
            $sourceDir = "{$baseDir}/{$direction}";
            if (!file_exists($sourceDir)) {
                continue;
            }

            $this->info("Converting {$direction} files...");
            
            $wavDir = "{$sourceDir}/wav";
            if (!file_exists($wavDir)) {
                mkdir($wavDir, 0755, true);
            }

            $rawFiles = glob("{$sourceDir}/*.raw");
            $totalFiles = count($rawFiles);
            
            if ($totalFiles === 0) {
                $this->warn("No raw files found in {$sourceDir}");
                continue;
            }

            $bar = $this->output->createProgressBar($totalFiles);
            $combinedPcmData = '';

            foreach ($rawFiles as $rawFile) {
                try {
                    $rawData = file_get_contents($rawFile);
                    $wavFile = $wavDir . '/' . basename($rawFile, '.raw') . '.wav';
                    
                    // Debug info
                    $this->line("\nProcessing file: " . basename($rawFile));
                    $this->line("Raw data size: " . strlen($rawData) . " bytes");

                    if ($direction === 'input') {
                        // Input is μ-law at 8kHz
                        $pcmData = $this->convertUlawToPcm16($rawData);
                        $sampleRate = 8000;
                    } else {
                        // Output is already PCM16 at 24kHz
                        $pcmData = $rawData;
                        $sampleRate = 24000;
                    }

                    $this->line("PCM data size: " . strlen($pcmData) . " bytes");

                    // Create individual WAV file
                    $this->createWavFile($pcmData, $wavFile, $sampleRate);
                    
                    // Add to combined data (after converting to same sample rate if needed)
                    if ($direction === 'input') {
                        // Upsample from 8kHz to 24kHz to match output format
                        $pcmData = $this->upsampleAudio($pcmData, 8000, 24000);
                    }
                    $combinedPcmData .= $pcmData;
                    
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->error("Error processing file " . basename($rawFile) . ": " . $e->getMessage());
                }
            }

            $bar->finish();
            $this->line('');

            if (!empty($combinedPcmData)) {
                $fullWavPath = "{$wavDir}/full_conversation.wav";
                $this->createWavFile($combinedPcmData, $fullWavPath, 24000); // Always 24kHz for combined files
                
                if (file_exists($fullWavPath)) {
                    $this->info("Created combined WAV file: " . filesize($fullWavPath) . " bytes");
                } else {
                    $this->error("Failed to create combined WAV file");
                }
            }
        }
    }

    private function convertUlawToPcm16($ulawData)
    {
        $pcm16 = '';
        $this->info("Converting μ-law data of length: " . strlen($ulawData));

        // Create μ-law to linear lookup table
        static $ulaw2linear = null;
        if ($ulaw2linear === null) {
            $ulaw2linear = array_fill(0, 256, 0);
            
            // Standard μ-law to linear conversion table
            for ($i = 0; $i < 256; $i++) {
                $ulawByte = ~$i; // Invert bits
                
                $sign = ($ulawByte & 0x80) ? -1 : 1;
                $exponent = ($ulawByte >> 4) & 0x07;
                $mantissa = ($ulawByte & 0x0F);
                
                // Proper scaling for 16-bit PCM
                if ($exponent == 0) {
                    $sample = (($mantissa << 3) + 132) * $sign;
                } else {
                    $sample = (($mantissa << ($exponent + 3)) + (132 << $exponent)) * $sign;
                }
                
                // Scale to full 16-bit range
                $sample *= 8;
                
                // Ensure we're in 16-bit range
                $sample = max(-32768, min(32767, $sample));
                
                $ulaw2linear[$i] = $sample;
            }
        }

        // Convert using lookup table
        for ($i = 0; $i < strlen($ulawData); $i++) {
            $ulawByte = ord($ulawData[$i]);
            $sample = $ulaw2linear[$ulawByte];
            
            // Debug first few samples
            if ($i < 5) {
                $this->line(sprintf(
                    "Sample %d: μ-law byte: %02X, PCM: %d",
                    $i, $ulawByte, $sample
                ));
            }
            
            $pcm16 .= pack('s', $sample);
        }

        // Debug: show min/max values
        $samples = unpack('s*', $pcm16);
        $min = min($samples);
        $max = max($samples);
        $this->info("Sample range: $min to $max");

        return $pcm16;
    }

    private function upsampleAudio($pcmData, $fromRate, $toRate)
    {
        $samples = unpack('s*', $pcmData);
        $ratio = $toRate / $fromRate;
        $result = '';
        $sampleCount = count($samples);
        
        // Moving average window for pre-filtering
        $windowSize = 4;
        $filtered = [];
        for ($i = 1; $i <= $sampleCount; $i++) {
            $sum = 0;
            $count = 0;
            for ($j = max(1, $i - $windowSize); $j <= min($sampleCount, $i + $windowSize); $j++) {
                $sum += $samples[$j];
                $count++;
            }
            $filtered[$i] = (int)($sum / $count);
        }
        
        // Improved upsampling with linear interpolation and post-filtering
        for ($i = 0; $i < $sampleCount * $ratio; $i++) {
            $pos = $i / $ratio;
            $index1 = floor($pos);
            $index2 = min(ceil($pos), $sampleCount - 1);
            $fraction = $pos - $index1;
            
            $sample1 = $filtered[$index1 + 1] ?? 0;
            $sample2 = $filtered[$index2 + 1] ?? $sample1;
            
            // Linear interpolation
            $interpolated = (int)($sample1 * (1 - $fraction) + $sample2 * $fraction);
            
            // Ensure the sample is within 16-bit range
            $interpolated = max(-32768, min(32767, $interpolated));
            
            $result .= pack('s', $interpolated);
        }
        
        return $result;
    }

    private function createWavFile($pcmData, $wavFile, $sampleRate)
    {
        $dataSize = strlen($pcmData);
        $this->info("Creating WAV file with:");
        $this->info("- Data size: $dataSize bytes");
        $this->info("- Sample rate: $sampleRate Hz");

        // Standard WAV header
        $header = '';
        $header .= "RIFF";                                 // ChunkID
        $header .= pack('V', $dataSize + 36);             // ChunkSize
        $header .= "WAVE";                                // Format
        $header .= "fmt ";                                // Subchunk1ID
        $header .= pack('V', 16);                         // Subchunk1Size
        $header .= pack('v', 1);                          // AudioFormat (PCM)
        $header .= pack('v', 1);                          // NumChannels (Mono)
        $header .= pack('V', $sampleRate);                // SampleRate
        $header .= pack('V', $sampleRate * 2);            // ByteRate
        $header .= pack('v', 2);                          // BlockAlign
        $header .= pack('v', 16);                         // BitsPerSample
        $header .= "data";                                // Subchunk2ID
        $header .= pack('V', $dataSize);                  // Subchunk2Size

        $this->info("Header size: " . strlen($header) . " bytes");

        // Write file in chunks to handle large files
        $fp = fopen($wavFile, 'wb');
        if (!$fp) {
            throw new \Exception("Could not open file for writing: $wavFile");
        }

        // Write header
        fwrite($fp, $header);

        // Write PCM data in chunks
        $chunkSize = 8192;
        $bytesWritten = 0;
        for ($i = 0; $i < strlen($pcmData); $i += $chunkSize) {
            $chunk = substr($pcmData, $i, $chunkSize);
            $written = fwrite($fp, $chunk);
            $bytesWritten += $written;
        }

        fclose($fp);

        $this->info("Total bytes written: " . $bytesWritten);
        $this->info("Final file size: " . filesize($wavFile) . " bytes");

        // Verify file contents
        $this->verifyWavFile($wavFile);
    }

    private function verifyWavFile($wavFile)
    {
        $fp = fopen($wavFile, 'rb');
        if (!$fp) {
            $this->error("Could not open file for verification: $wavFile");
            return false;
        }

        // Read and verify header
        $header = fread($fp, 44);
        
        // Read first few PCM samples for debugging
        $sampleData = fread($fp, 20);
        $samples = unpack('s*', $sampleData);
        
        $this->info("First 10 PCM samples in file:");
        foreach ($samples as $i => $sample) {
            $this->line("Sample $i: $sample");
        }

        fclose($fp);

        return true;
    }
}