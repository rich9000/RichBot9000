class AudioProcessor extends AudioWorkletProcessor {
    constructor() {
        super();
        this.bufferSize = 2048;
        this.buffer = new Float32Array(this.bufferSize);
        this.bufferIndex = 0;
        this.exp_lut = new Float32Array(256);
        this.sampleRate = 8000;
        this.targetSampleRate = sampleRate;
        this.isPlaying = false;
        this.lastSample = 0;
        this.echoThreshold = 0.1;
        this.smoothingFactor = 0.95; // Add smoothing factor for better transitions
        
        // Initialize exponential lookup table for μ-law decoding
        for (let i = 0; i < 256; i++) {
            const sign = (i & 0x80) ? -1 : 1;
            const exponent = ((i & 0x70) >> 4) + 1;
            const mantissa = (i & 0x0f) / 16.0;
            this.exp_lut[i] = sign * (1.0 + mantissa) * Math.pow(2, exponent);
        }

        this.port.onmessage = (event) => {
            if (event.data.type === 'audioData') {
                this.processAudioData(event.data.data);
            } else if (event.data.type === 'stop') {
                this.isPlaying = false;
                this.bufferIndex = 0;
                this.buffer.fill(0);
                this.lastSample = 0;
            }
        };
    }

    base64ToUint8Array(base64) {
        const base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
        const bytes = [];
        let i = 0;
        
        while (i < base64.length) {
            const char1 = base64Chars.indexOf(base64[i++]);
            const char2 = base64Chars.indexOf(base64[i++]);
            const char3 = base64Chars.indexOf(base64[i++]);
            const char4 = base64Chars.indexOf(base64[i++]);
            
            const byte1 = (char1 << 2) | (char2 >> 4);
            const byte2 = ((char2 & 15) << 4) | (char3 >> 2);
            const byte3 = ((char3 & 3) << 6) | char4;
            
            bytes.push(byte1);
            if (char3 !== 64) bytes.push(byte2);
            if (char4 !== 64) bytes.push(byte3);
        }
        
        return new Uint8Array(bytes);
    }

    processAudioData(data) {
        try {
            console.log('[AudioWorklet] Processing audio data');



            const bytes = this.base64ToUint8Array(data);
            const pcmData = new Float32Array(bytes.length);
            
            // Process μ-law to PCM with improved scaling and smoothing
            for (let i = 0; i < bytes.length; i++) {
                const mu = bytes[i] ^ 0xFF;
                const pcm = this.exp_lut[mu];
                // Scale to [-1, 1] with reduced gain and apply smoothing
                const scaledPcm = (pcm / 32768.0) * 0.8; // Reduced gain to 0.8
                pcmData[i] = this.lastSample * this.smoothingFactor + scaledPcm * (1 - this.smoothingFactor);
                this.lastSample = pcmData[i];
            }

            // Update the buffer for playback with improved echo cancellation
            for (let i = 0; i < pcmData.length; i++) {
                const currentSample = pcmData[i];
                
                // Improved echo cancellation
                if (Math.abs(currentSample - this.lastSample) < this.echoThreshold) {
                    pcmData[i] = currentSample * 0.3; // More aggressive echo reduction
                }
                
                this.buffer[this.bufferIndex] = pcmData[i];
                this.bufferIndex = (this.bufferIndex + 1) % this.bufferSize;
                this.lastSample = currentSample;
            }
            
            this.isPlaying = true;
            
        } catch (error) {
            console.error('[AudioWorklet] Error processing audio data:', error);
        }
    }

    process(inputs, outputs) {
        const output = outputs[0];
        
        if (!this.isPlaying) {
            output[0].fill(0);
            return true;
        }

        // Copy from buffer to output with improved interpolation
        for (let i = 0; i < output[0].length; i++) {
            const currentSample = this.buffer[this.bufferIndex];
            const nextSample = this.buffer[(this.bufferIndex + 1) % this.bufferSize];
            
            // Improved interpolation for smoother transitions
            output[0][i] = currentSample + (nextSample - currentSample) * 0.5;
            
            this.bufferIndex = (this.bufferIndex + 1) % this.bufferSize;
        }

        return true;
    }
}

registerProcessor('audio-processor', AudioProcessor);