class AudioRecorderWidget {
    constructor(containerId, config = {}) {
        this.config = {
            audioFileId: config.audioFileId || null,
            onSave: config.onSave || null,
            onError: config.onError || null,
            ...config
        };

        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container element with id "${containerId}" not found`);
        }

        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecording = false;
        this.currentAudio = null;
        this.audioContext = null;
        this.recordingStartTime = null;
        this.recordingTimer = null;
        this.clipStartTime = null;
        this.clipEndTime = null;

        this.initialize();
    }

    initialize() {
        this.render();
        this.attachEventListeners();
        
        // If audioFileId is provided, load the audio file
        if (this.config.audioFileId) {
            this.loadAudioFile(this.config.audioFileId);
        }
    }

    render() {
        this.container.innerHTML = `
            <div class="audio-recorder-widget">
                <div class="recording-controls mb-3">
                    <div class="d-flex align-items-center">
                        <button id="toggle-record-btn" class="btn btn-danger me-2">
                            <i class="fas fa-microphone"></i> Record
                        </button>
                        <button id="stop-recording-btn" class="btn btn-secondary me-2" disabled>
                            <i class="fas fa-stop"></i> Stop
                        </button>
                        <div id="recording-indicator" class="d-none">
                            <span class="recording-dot"></span>
                            <span id="recording-timer">00:00</span>
                        </div>
                    </div>
                </div>

                <div id="recording-preview" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <button id="play-recording-btn" class="btn btn-primary me-2">
                            <i class="fas fa-play"></i> Play
                        </button>
                        <button id="save-recording-btn" class="btn btn-success me-2">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <button id="clip-audio-btn" class="btn btn-info me-2">
                            <i class="fas fa-cut"></i> Clip Audio
                        </button>
                        <button id="convert-ulaw-btn" class="btn btn-warning me-2">
                            <i class="fas fa-exchange-alt"></i> Convert to G.711 µ-law
                        </button>
                        <button id="clone-audio-btn" class="btn btn-secondary me-2">
                            <i class="fas fa-copy"></i> Clone
                        </button>
                        <button id="cancel-recording-btn" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                    <audio id="recording-player" controls class="w-100 mb-3"></audio>
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="audio-name" class="form-label">File Name</label>
                                <input type="text" class="form-control" id="audio-name" placeholder="Enter file name">
                            </div>
                            <div class="col-md-6">
                                <label for="audio-type" class="form-label">Type</label>
                                <select class="form-control" id="audio-type">
                                    <option value="general" selected>General</option>
                                    <option value="phone-tree">Phone Tree</option>
                                    <option value="user">User</option>
                                    <option value="system">System</option>
                                    <option value="memo">Memo</option>
                                    <option value="stream">Stream</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="clip-controls" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Clip Selection</label>
                            <div class="d-flex justify-content-between mb-2">
                                <span id="current-time">00:00</span>
                                <span id="total-time">00:00</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <button id="set-start-btn" class="btn btn-primary me-2">Set Start</button>
                                <button id="set-end-btn" class="btn btn-primary me-2">Set End</button>
                                <span id="clip-start-time" class="me-2">Start: 00:00</span>
                                <span id="clip-end-time">End: 00:00</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <button id="play-clip-btn" class="btn btn-success me-2">Play Clip</button>
                                <button id="save-clip-btn" class="btn btn-success me-2">Save Clip As</button>
                                <input type="text" class="form-control" id="clip-name" placeholder="New clip name" style="width: 200px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="error-message" class="alert alert-danger d-none" role="alert"></div>
            </div>
        `;
    }

    attachEventListeners() {
        // Record button
        this.container.querySelector('#toggle-record-btn').addEventListener('click', () => this.toggleRecording());
        
        // Stop button
        this.container.querySelector('#stop-recording-btn').addEventListener('click', () => this.stopRecording());
        
        // Play button
        this.container.querySelector('#play-recording-btn').addEventListener('click', () => this.playRecording());
        
        // Save button
        this.container.querySelector('#save-recording-btn').addEventListener('click', () => this.saveRecording());
        
        // Cancel button
        this.container.querySelector('#cancel-recording-btn').addEventListener('click', () => this.cancelRecording());
        
        // Clip audio button
        this.container.querySelector('#clip-audio-btn').addEventListener('click', () => {
            const clipControls = this.container.querySelector('#clip-controls');
            clipControls.classList.toggle('d-none');
        });

        // Set start/end buttons
        this.container.querySelector('#set-start-btn').addEventListener('click', () => {
            const player = this.container.querySelector('#recording-player');
            this.clipStartTime = player.currentTime;
            this.container.querySelector('#clip-start-time').textContent = `Start: ${this.formatTime(this.clipStartTime)}`;
        });

        this.container.querySelector('#set-end-btn').addEventListener('click', () => {
            const player = this.container.querySelector('#recording-player');
            this.clipEndTime = player.currentTime;
            this.container.querySelector('#clip-end-time').textContent = `End: ${this.formatTime(this.clipEndTime)}`;
        });

        // Play clip button
        this.container.querySelector('#play-clip-btn').addEventListener('click', () => {
            if (this.clipStartTime !== undefined && this.clipEndTime !== undefined) {
                this.createClip();
            } else {
                this.showError('Please set both start and end points');
            }
        });

        // Save clip button
        this.container.querySelector('#save-clip-btn').addEventListener('click', () => {
            if (this.clipStartTime !== undefined && this.clipEndTime !== undefined) {
                const clipName = this.container.querySelector('#clip-name').value.trim();
                if (!clipName) {
                    this.showError('Please enter a name for the clip');
                    return;
                }
                this.createClip(clipName);
            } else {
                this.showError('Please set both start and end points');
            }
        });

        // Update time display when audio is playing
        const player = this.container.querySelector('#recording-player');
        player.addEventListener('timeupdate', () => {
            this.container.querySelector('#current-time').textContent = this.formatTime(player.currentTime);
        });

        player.addEventListener('loadedmetadata', () => {
            this.container.querySelector('#total-time').textContent = this.formatTime(player.duration);
        });

        // Convert to G.711 µ-law button
        this.container.querySelector('#convert-ulaw-btn').addEventListener('click', () => this.convertToUlaw());
        
        // Clone audio button
        this.container.querySelector('#clone-audio-btn').addEventListener('click', () => this.cloneAudio());
    }

    async toggleRecording() {
        if (this.isRecording) {
            await this.stopRecording();
        } else {
            await this.startRecording();
        }
    }

    async startRecording() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.recordingStream = stream;
            this.input = this.audioContext.createMediaStreamSource(stream);
            this.processor = this.audioContext.createScriptProcessor(4096, 1, 1);
            this.pcmData = [];

            this.processor.onaudioprocess = (e) => {
                const channelData = e.inputBuffer.getChannelData(0);
                this.pcmData.push(new Float32Array(channelData));
            };

            this.input.connect(this.processor);
            this.processor.connect(this.audioContext.destination);

            this.isRecording = true;
            this.updateRecordingUI(true);
            this.startRecordingTimer();
        } catch (error) {
            console.error('Error starting recording:', error);
            this.showError('Failed to start recording');
        }
    }

    async stopRecording() {
        if (this.isRecording && this.audioContext && this.processor) {
            this.isRecording = false;
            this.updateRecordingUI(false);
            this.stopRecordingTimer();

            this.input.disconnect();
            this.processor.disconnect();
            this.recordingStream.getTracks().forEach(track => track.stop());

            // Concatenate all PCM data
            const length = this.pcmData.reduce((acc, arr) => acc + arr.length, 0);
            const merged = new Float32Array(length);
            let offset = 0;
            for (const arr of this.pcmData) {
                merged.set(arr, offset);
                offset += arr.length;
            }

            // Create AudioBuffer
            const buffer = this.audioContext.createBuffer(1, merged.length, this.audioContext.sampleRate);
            buffer.copyToChannel(merged, 0);

            // Encode to WAV
            const wavBlob = this.bufferToWav(buffer);
            this.currentAudio = wavBlob;
            this.showRecordingPreview(wavBlob);

            // Clean up
            this.audioContext.close();
            this.audioContext = null;
            this.processor = null;
            this.input = null;
            this.pcmData = null;
        }
    }

    showRecordingPreview(blob) {
        const preview = this.container.querySelector('#recording-preview');
        const player = this.container.querySelector('#recording-player');
        
        if (preview && player) {
            preview.classList.remove('d-none');
            player.src = URL.createObjectURL(blob);
        }
    }

    playRecording() {
        const player = this.container.querySelector('#recording-player');
        if (player) {
            player.play();
        }
    }

    cancelRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
            this.stopRecording();
        }
        this.currentAudio = null;
        this.audioChunks = [];
        this.stopRecordingTimer();
        this.updateRecordingUI(false);
        const preview = this.container.querySelector('#recording-preview');
        if (preview) {
            preview.classList.add('d-none');
        }
    }

    async saveRecording() {
        if (!this.currentAudio) {
            this.showError('No recording to save');
            return;
        }

        const fileName = this.container.querySelector('#audio-name').value.trim();
        if (!fileName) {
            this.showError('Please enter a file name');
            return;
        }

        try {
            let response;
            if (this.config.audioFileId) {
                // Update existing file
                const formData = new FormData();
                formData.append('name', fileName);
                formData.append('type', this.container.querySelector('#audio-type').value);
                
                // Only append file if we have a new recording
                if (this.currentAudio) {
                    formData.append('file', this.currentAudio, `${fileName}.wav`);
                }

                response = await fetch(`/api/audio-files/${this.config.audioFileId}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
            } else {
                // Create new file
                const formData = new FormData();
                formData.append('file', this.currentAudio, `${fileName}.wav`);
                formData.append('name', fileName);
                formData.append('type', this.container.querySelector('#audio-type').value);
                formData.append('source_type', 'recording');

                response = await fetch('/api/audio-files', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`
                    },
                    body: formData
                });
            }

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save recording');
            }

            const result = await response.json();
            if (this.config.onSave) {
                this.config.onSave(result);
            }

            this.showSuccess('Audio saved successfully');
            this.cancelRecording();
        } catch (error) {
            console.error('Error saving recording:', error);
            this.showError(error.message || 'Failed to save recording');
        }
    }

    async loadAudioFile(audioFileId) {
        try {
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load audio file');
            }

            const result = await response.json();
            if (result.success) {
                const audioFile = result.data;
                const audioUrl = `/storage/${audioFile.file_path}`;
                const audioResponse = await fetch(audioUrl);
                const audioBlob = await audioResponse.blob();
                this.currentAudio = audioBlob;
                this.showRecordingPreview(audioBlob);
                this.container.querySelector('#audio-name').value = audioFile.name;
                this.container.querySelector('#audio-type').value = audioFile.type;
            }
        } catch (error) {
            console.error('Error loading audio file:', error);
            this.showError('Failed to load audio file');
        }
    }

    async createClip(clipName = null) {
        if (!this.currentAudio || this.clipStartTime === undefined || this.clipEndTime === undefined) {
            this.showError('Invalid clip parameters');
            return;
        }

        const startTime = this.clipStartTime;
        const endTime = this.clipEndTime;
        
        if (startTime >= endTime) {
            this.showError('Start time must be before end time');
            return;
        }

        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const reader = new FileReader();
        
        reader.onload = async () => {
            try {
                const arrayBuffer = reader.result;
                const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
                
                const clipDuration = endTime - startTime;
                const clipBuffer = audioContext.createBuffer(
                    audioBuffer.numberOfChannels,
                    clipDuration * audioBuffer.sampleRate,
                    audioBuffer.sampleRate
                );
                
                for (let channel = 0; channel < audioBuffer.numberOfChannels; channel++) {
                    const channelData = audioBuffer.getChannelData(channel);
                    const clipData = clipBuffer.getChannelData(channel);
                    const startOffset = startTime * audioBuffer.sampleRate;
                    const endOffset = endTime * audioBuffer.sampleRate;
                    
                    for (let i = startOffset; i < endOffset; i++) {
                        clipData[i - startOffset] = channelData[i];
                    }
                }
                
                const wavBlob = this.bufferToWav(clipBuffer);
                
                if (clipName) {
                    const formData = new FormData();
                    formData.append('file', wavBlob, `${clipName}.wav`);
                    formData.append('name', clipName);
                    formData.append('source_type', 'recording');
                    formData.append('type', this.container.querySelector('#audio-type').value);

                    const response = await fetch('/api/audio-files', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('Failed to save clip');
                    }

                    const result = await response.json();
                    if (this.config.onSave) {
                        this.config.onSave(result);
                    }
                } else {
                    this.currentAudio = wavBlob;
                    this.showRecordingPreview(wavBlob);
                }
            } catch (error) {
                console.error('Error creating clip:', error);
                this.showError('Failed to create clip');
            }
        };
        
        reader.readAsArrayBuffer(this.currentAudio);
    }

    bufferToWav(buffer) {
        const numChannels = buffer.numberOfChannels;
        const sampleRate = buffer.sampleRate;
        const format = 1; // PCM
        const bitDepth = 16;
        
        const bytesPerSample = bitDepth / 8;
        const blockAlign = numChannels * bytesPerSample;
        
        const wav = new ArrayBuffer(44 + buffer.length * blockAlign);
        const view = new DataView(wav);
        
        // Write WAV header
        this.writeString(view, 0, 'RIFF');
        view.setUint32(4, 36 + buffer.length * blockAlign, true);
        this.writeString(view, 8, 'WAVE');
        this.writeString(view, 12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, format, true);
        view.setUint16(22, numChannels, true);
        view.setUint32(24, sampleRate, true);
        view.setUint32(28, sampleRate * blockAlign, true);
        view.setUint16(32, blockAlign, true);
        view.setUint16(34, bitDepth, true);
        this.writeString(view, 36, 'data');
        view.setUint32(40, buffer.length * blockAlign, true);
        
        // Write audio data
        const offset = 44;
        const volume = 1;
        const data = new Int16Array(wav, offset);
        const channelData = buffer.getChannelData(0);
        
        for (let i = 0; i < channelData.length; i++) {
            data[i] = Math.max(-1, Math.min(1, channelData[i])) * 0x7FFF;
        }
        
        return new Blob([wav], { type: 'audio/wav' });
    }

    writeString(view, offset, string) {
        for (let i = 0; i < string.length; i++) {
            view.setUint8(offset + i, string.charCodeAt(i));
        }
    }

    updateRecordingUI(isRecording) {
        const recordBtn = this.container.querySelector('#toggle-record-btn');
        const stopBtn = this.container.querySelector('#stop-recording-btn');
        const indicator = this.container.querySelector('#recording-indicator');
        
        if (recordBtn) recordBtn.disabled = isRecording;
        if (stopBtn) stopBtn.disabled = !isRecording;
        if (indicator) {
            if (isRecording) {
                indicator.classList.remove('d-none');
            } else {
                indicator.classList.add('d-none');
            }
        }
    }

    startRecordingTimer() {
        this.recordingStartTime = Date.now();
        this.recordingTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.recordingStartTime) / 1000);
            const timerElement = this.container.querySelector('#recording-timer');
            if (timerElement) {
                timerElement.textContent = this.formatTime(elapsed);
            }
        }, 1000);
    }

    stopRecordingTimer() {
        if (this.recordingTimer) {
            clearInterval(this.recordingTimer);
            this.recordingTimer = null;
        }
        const timerElement = this.container.querySelector('#recording-timer');
        if (timerElement) {
            timerElement.textContent = '00:00';
        }
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    showError(message) {
        const errorElement = this.container.querySelector('#error-message');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('d-none');
            setTimeout(() => {
                errorElement.classList.add('d-none');
            }, 5000);
        }
    }

    async convertToUlaw() {
        if (!this.currentAudio) {
            this.showError('No audio to convert');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('file', this.currentAudio);
            formData.append('name', this.container.querySelector('#audio-name').value.trim() + ' (G.711)');
            formData.append('type', this.container.querySelector('#audio-type').value);
            formData.append('source_type', 'conversion');
            formData.append('convert_to', 'ulaw');

            const response = await fetch('/api/audio-files/convert', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to convert audio');
            }

            const result = await response.json();
            if (this.config.onSave) {
                this.config.onSave(result);
            }

            this.showSuccess('Audio converted successfully');
        } catch (error) {
            console.error('Error converting audio:', error);
            this.showError(error.message || 'Failed to convert audio');
        }
    }

    async cloneAudio() {
        if (!this.currentAudio) {
            this.showError('No audio to clone');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('file', this.currentAudio);
            formData.append('name', this.container.querySelector('#audio-name').value.trim() + ' (Copy)');
            formData.append('type', this.container.querySelector('#audio-type').value);
            formData.append('source_type', 'clone');

            const response = await fetch('/api/audio-files', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to clone audio');
            }

            const result = await response.json();
            if (this.config.onSave) {
                this.config.onSave(result);
            }

            this.showSuccess('Audio cloned successfully');
        } catch (error) {
            console.error('Error cloning audio:', error);
            this.showError('Failed to clone audio');
        }
    }

    showSuccess(message) {
        const successElement = document.createElement('div');
        successElement.className = 'alert alert-success';
        successElement.textContent = message;
        this.container.insertBefore(successElement, this.container.firstChild);
        setTimeout(() => {
            successElement.remove();
        }, 3000);
    }
}

// Export the class for use in other files
window.AudioRecorderWidget = AudioRecorderWidget; 