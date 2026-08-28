class AudioManager {
    constructor() {
        this.audioContext = null;
        this.streamRecorder = null;
        this.streamChunks = [];
        this.isStreaming = false;
        this.audioPlayer = null;
        this.dropZone = null;
        this.streamPlayer = null;
        this.initialized = false;
        this.streamingTimer = null;
        this.streamingStartTime = null;
        this.transcriptionText = '';
        this.audioFiles = [];
        this.recordingStream = null;
        this.audioChunks = [];
        this.currentStreamFile = null;
    }

    initialize() {
        if (this.initialized) return;
        
        try {
            this.initializeEventListeners();
            this.initializeAudioContext();
            this.initializeDropZone();
            this.loadAudioFiles();
            this.initialized = true;
        } catch (error) {
            console.error('Error initializing AudioManager:', error);
            // Retry initialization after a delay
            setTimeout(() => this.initialize(), 1000);
        }
    }

    initializeEventListeners() {
        const addEventListener = (id, event, handler) => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener(event, handler);
            } else {
                console.warn(`Element with id ${id} not found for event listener`);
            }
        };

        // Audio file management
        addEventListener('toggle-stream-record-btn', 'click', () => this.toggleStreaming());
        addEventListener('play-stream-btn', 'click', () => this.playStream());
        addEventListener('save-stream-btn', 'click', () => this.saveStream());
        addEventListener('cancel-stream-btn', 'click', () => this.cancelStreaming());
        addEventListener('delete-audio-btn', 'click', () => this.deleteAudio());

        // Playback controls
        addEventListener('play-audio-btn', 'click', () => this.playAudio());
        addEventListener('pause-audio-btn', 'click', () => this.pauseAudio());
        addEventListener('seek-audio', 'input', (e) => this.seekAudio(e));
    }

    initializeDropZone() {
        this.dropZone = document.getElementById('audio-drop-zone');
        if (!this.dropZone) return;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, () => {
                this.dropZone.classList.add('highlight');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, () => {
                this.dropZone.classList.remove('highlight');
            }, false);
        });

        // Handle dropped files
        this.dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            this.handleFiles(files);
        }, false);
    }

    async handleFiles(files) {
        if (!files || files.length === 0) return;

        const file = files[0];
        if (!file.type.startsWith('audio/')) {
            this.showError('Please drop an audio file');
            return;
        }

        try {
            // First save the file
            const formData = new FormData();
            formData.append('file', file);
            formData.append('source_type', 'upload');
            formData.append('type', 'general'); // Default type, can be changed in form
            formData.append('name', file.name); // Use the original filename as the name

            const response = await fetch('/api/audio-files', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to upload file');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to upload file');
            }

            this.showSuccess('File uploaded successfully');
            this.loadAudioFiles();
        } catch (error) {
            console.error('Error handling file:', error);
            this.showError('Failed to upload file');
        }
    }

    async initializeAudioContext() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        } catch (error) {
            console.error('Error initializing audio context:', error);
            this.showError('Audio context initialization failed');
        }
    }

    async loadAudioFiles() {
        try {
            const response = await fetch('/api/audio-files', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio files');
            }

            this.renderAudioFilesList(result.data);
        } catch (error) {
            console.error('Error loading audio files:', error);
            this.showError('Failed to load audio files');
        }
    }

    renderAudioFilesList(audioFiles) {
        const tbody = document.getElementById('audio-files-list');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!audioFiles || !Array.isArray(audioFiles) || audioFiles.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="alert alert-info">
                            No audio files found. Upload or record audio to get started.
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        audioFiles.forEach(audioFile => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${audioFile.id}</td>
                <td>${this.escapeHtml(audioFile.name)}</td>
                <td>${this.escapeHtml(audioFile.description || '')}</td>
                <td>${this.escapeHtml(audioFile.type)}</td>
                <td>${this.escapeHtml(audioFile.context || '')}</td>
                <td>${this.escapeHtml(audioFile.created_at)}</td>
                <td>
                    <span class="badge ${audioFile.is_active ? 'bg-success' : 'bg-secondary'}">
                        ${audioFile.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary me-1" onclick="audioManager.playAudio(${audioFile.id})">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-info me-1" onclick="audioManager.editAudio(${audioFile.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-warning me-1" onclick="audioManager.convertToUlaw(${audioFile.id})">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-secondary me-1" onclick="audioManager.cloneAudio(${audioFile.id})">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-sm btn-success me-1" onclick="audioManager.openClipWidget(${audioFile.id})">
                            <i class="fas fa-cut"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="audioManager.confirmDelete(${audioFile.id}, '${this.escapeHtml(audioFile.name)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async editAudio(audioFileId) {
        try {
            // First fetch the audio file data
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio file');
            }

            const audioFile = result.data;

            // Create and show the edit modal
            let modal = document.getElementById('edit-audio-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'edit-audio-modal';
                modal.className = 'modal fade';
                modal.setAttribute('tabindex', '-1');
                modal.innerHTML = `
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Audio File</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="edit-audio-form">
                                    <div class="mb-3">
                                        <label for="edit-audio-name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="edit-audio-name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-audio-type" class="form-label">Type</label>
                                        <select class="form-control" id="edit-audio-type" required>
                                            <option value="general">General</option>
                                            <option value="phone-tree">Phone Tree</option>
                                            <option value="user">User</option>
                                            <option value="system">System</option>
                                            <option value="memo">Memo</option>
                                            <option value="stream">Stream</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="save-edit-btn">Save Changes</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            // Set the current values
            document.getElementById('edit-audio-name').value = audioFile.name;
            document.getElementById('edit-audio-type').value = audioFile.type;

            // Show the modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Handle save button click
            const saveBtn = document.getElementById('save-edit-btn');
            const handleSave = async () => {
                try {
                    const data = {
                        name: document.getElementById('edit-audio-name').value,
                        type: document.getElementById('edit-audio-type').value
                    };

                    const response = await fetch(`/api/audio-files/${audioFileId}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    if (!response.ok) {
                        throw new Error('Failed to update audio file');
                    }

                    const result = await response.json();
                    if (!result.success) {
                        throw new Error('Failed to update audio file');
                    }

                    this.showSuccess('Audio file updated successfully');
                    this.loadAudioFiles();
                    bsModal.hide();
                } catch (error) {
                    console.error('Error updating audio:', error);
                    this.showError(error.message || 'Failed to update audio file');
                }
            };

            saveBtn.onclick = handleSave;
        } catch (error) {
            console.error('Error editing audio:', error);
            this.showError('Failed to load audio file for editing');
        }
    }

    async playAudio(audioFileId) {
        try {
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio file');
            }

            const audioFile = result.data;
            
            // Stop any currently playing audio
            if (this.audioPlayer) {
                this.audioPlayer.pause();
                this.audioPlayer = null;
            }

            // Create a new audio element
            this.audioPlayer = new Audio();
            
            // Set up error handling
            this.audioPlayer.onerror = (error) => {
                console.error('Error playing audio:', error);
                this.showError('Failed to play audio file');
                this.audioPlayer = null;
            };

            // Set up loading handling
            this.audioPlayer.onloadstart = () => {
                console.log('Loading audio file...');
            };

            // Set up play handling
            this.audioPlayer.onplay = () => {
                console.log('Audio started playing');
            };

            // Set the source and start playing
            this.audioPlayer.src = `/storage/${audioFile.file_path}`;
            await this.audioPlayer.play();
            
            // Update the UI
            this.updatePlaybackUI(true);
        } catch (error) {
            console.error('Error playing audio:', error);
            this.showError('Failed to play audio');
            this.audioPlayer = null;
        }
    }

    pauseAudio() {
        if (this.audioPlayer) {
            this.audioPlayer.pause();
            this.updatePlaybackUI(false);
        }
    }

    seekAudio(event) {
        if (this.audioPlayer) {
            const seekTime = (event.target.value / 100) * this.audioPlayer.duration;
            this.audioPlayer.currentTime = seekTime;
        }
    }

    updatePlaybackUI(isPlaying) {
        const playBtn = document.getElementById('play-audio-btn');
        const pauseBtn = document.getElementById('pause-audio-btn');
        
        if (playBtn) playBtn.disabled = isPlaying;
        if (pauseBtn) pauseBtn.disabled = !isPlaying;
    }

    showSuccess(message) {
        // Create a toast notification
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove the toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            document.body.removeChild(toast);
        });
    }

    showError(message) {
        const errorAlert = document.getElementById('audio-error');
        const errorMessage = document.getElementById('error-message');
        if (errorAlert && errorMessage) {
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
            setTimeout(() => {
                errorAlert.classList.add('d-none');
            }, 5000);
        }
    }

    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    confirmDelete(audioFileId, fileName) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('delete-confirm-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'delete-confirm-modal';
            modal.className = 'modal fade';
            modal.setAttribute('tabindex', '-1');
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete <strong id="delete-file-name"></strong>?</p>
                            <p class="text-danger">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Set the file name in the modal
        document.getElementById('delete-file-name').textContent = fileName;

        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Handle the confirm button click
        const confirmBtn = document.getElementById('confirm-delete-btn');
        const handleConfirm = () => {
            this.deleteAudio(audioFileId);
            confirmBtn.removeEventListener('click', handleConfirm);
            bsModal.hide();
        };
        confirmBtn.addEventListener('click', handleConfirm);
    }

    async deleteAudio(audioFileId) {
        try {
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to delete audio file');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to delete audio file');
            }

            this.showSuccess('Audio file deleted successfully');
            this.loadAudioFiles(); // Refresh the list
        } catch (error) {
            console.error('Error deleting audio:', error);
            this.showError('Failed to delete audio file');
        }
    }

    async toggleStreaming() {
        if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
            await this.stopStreaming();
        } else {
            await this.startStreaming();
        }
    }

    async startStreaming() {
        try {
            // Create a temporary audio file for streaming first
            const createResponse = await fetch('/api/audio-files/stream/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: JSON.stringify({
                    name: `Stream Recording ${new Date().toLocaleString()}`,
                    type: 'stream',
                    context: 'recording'
                })
            });

            if (!createResponse.ok) {
                throw new Error('Failed to create stream file');
            }

            const data = await createResponse.json();
            this.currentStreamFile = data.audioFile;

            // Now that we have the stream file, set up the recording
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.recordingStream = stream;
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];

            // Set up event handlers
            this.mediaRecorder.ondataavailable = async (event) => {
                if (!this.currentStreamFile) {
                    console.error('No current stream file available');
                    return;
                }

                const formData = new FormData();
                formData.append('chunk', event.data);
                formData.append('is_final', false);

                try {
                    await fetch(`/api/audio-files/${this.currentStreamFile.id}/stream/chunk`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`
                        },
                        body: formData
                    });
                } catch (error) {
                    console.error('Error sending chunk:', error);
                }
            };

            this.mediaRecorder.onstop = async () => {
                if (!this.currentStreamFile) {
                    console.error('No current stream file available');
                    return;
                }

                const formData = new FormData();
                formData.append('chunk', new Blob(this.audioChunks, { type: 'audio/wav' }));
                formData.append('is_final', true);

                try {
                    await fetch(`/api/audio-files/${this.currentStreamFile.id}/stream/chunk`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`
                        },
                        body: formData
                    });
                } catch (error) {
                    console.error('Error sending final chunk:', error);
                }

                this.audioChunks = [];
                this.currentStreamFile = null;
            };

            // Start recording after everything is set up
            this.mediaRecorder.start(1000); // Send chunks every second
            this.updateStreamingUI(true);
            this.startStreamingTimer();
        } catch (error) {
            console.error('Error starting streaming:', error);
            this.showError('Failed to start streaming');
            // Clean up if something went wrong
            if (this.recordingStream) {
                this.recordingStream.getTracks().forEach(track => track.stop());
            }
            this.currentStreamFile = null;
        }
    }

    async stopStreaming() {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
            this.recordingStream.getTracks().forEach(track => track.stop());
        }
        this.currentAudio = null;
        this.stopStreamingTimer();
        this.updateStreamingUI(false);
        const preview = document.getElementById('stream-recording-preview');
        if (preview) {
            preview.classList.add('d-none');
        }
        const transcriptionPreview = document.getElementById('transcription-preview');
        if (transcriptionPreview) {
            transcriptionPreview.classList.add('d-none');
        }
    }

    showStreamPreview(blob) {
        const preview = document.getElementById('stream-recording-preview');
        const player = document.getElementById('stream-player');
        
        if (preview && player) {
            preview.classList.remove('d-none');
            player.src = URL.createObjectURL(blob);
        }
    }

    playStream() {
        const player = document.getElementById('stream-player');
        if (player) {
            player.play();
        }
    }

    async saveStream() {
        if (!this.currentAudio) {
            this.showError('No stream to save');
            return;
        }

        const form = document.getElementById('audio-details-form');
        if (form) {
            form.classList.remove('d-none');
        }
    }

    updateStreamingUI(isStreaming) {
        const toggleBtn = document.getElementById('toggle-stream-record-btn');
        const indicator = document.getElementById('stream-recording-indicator');
        
        if (toggleBtn) {
            toggleBtn.innerHTML = isStreaming ? 
                '<i class="fas fa-stop"></i> Stop Streaming' : 
                '<i class="fas fa-microphone"></i> Start Streaming';
            toggleBtn.classList.toggle('btn-danger', !isStreaming);
            toggleBtn.classList.toggle('btn-secondary', isStreaming);
        }
        
        if (indicator) {
            indicator.classList.toggle('d-none', !isStreaming);
        }
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    startStreamingTimer() {
        this.streamingStartTime = Date.now();
        this.streamingTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.streamingStartTime) / 1000);
            const timerElement = document.getElementById('stream-timer');
            if (timerElement) {
                timerElement.textContent = this.formatTime(elapsed);
            }
        }, 1000);
    }

    stopStreamingTimer() {
        if (this.streamingTimer) {
            clearInterval(this.streamingTimer);
            this.streamingTimer = null;
        }
        const timerElement = document.getElementById('stream-timer');
        if (timerElement) {
            timerElement.textContent = '00:00';
        }
    }

    updateStreamProgress(progress) {
        const progressBar = document.getElementById('stream-progress');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }
    }

    showTranscription(text) {
        const preview = document.getElementById('transcription-preview');
        const textElement = document.getElementById('transcription-text');
        if (preview && textElement) {
            preview.classList.remove('d-none');
            textElement.textContent = text;
        }
    }

    async cancelStreaming() {
        if (this.currentStreamFile) {
            await fetch(`/api/audio-files/${this.currentStreamFile.id}/stream`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                }
            });
            this.currentStreamFile = null;
        }
        this.currentAudio = null;
        this.stopStreamingTimer();
        this.updateStreamingUI(false);
        const preview = document.getElementById('stream-recording-preview');
        if (preview) {
            preview.classList.add('d-none');
        }
        const transcriptionPreview = document.getElementById('transcription-preview');
        if (transcriptionPreview) {
            transcriptionPreview.classList.add('d-none');
        }
    }

    async convertToUlaw(audioFileId) {
        try {
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load audio file');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio file');
            }

            const audioFile = result.data;

            // Create and show the convert modal
            let modal = document.getElementById('convert-audio-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'convert-audio-modal';
                modal.className = 'modal fade';
                modal.setAttribute('tabindex', '-1');
                modal.innerHTML = `
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Convert to G.711 µ-law</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to convert "${audioFile.name}" to G.711 µ-law format?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="convert-btn">Convert</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            const convertBtn = modal.querySelector('#convert-btn');

            const handleConvert = async () => {
                try {
                    const response = await fetch(`/api/audio-files/convert`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            audio_file_id: audioFileId,
                            name: audioFile.name + ' (G.711)',
                            type: audioFile.type,
                            convert_to: 'ulaw'
                        })
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Failed to convert audio file');
                    }

                    const result = await response.json();
                    if (!result.success) {
                        throw new Error(result.message || 'Failed to convert audio file');
                    }

                    this.showSuccess('Audio file converted successfully');
                    this.loadAudioFiles();
                    bsModal.hide();
                } catch (error) {
                    console.error('Error converting audio:', error);
                    this.showError(error.message || 'Failed to convert audio file');
                }
            };

            convertBtn.onclick = handleConvert;
        } catch (error) {
            console.error('Error preparing conversion:', error);
            this.showError('Failed to prepare audio file for conversion');
        }
    }

    async cloneAudio(audioFileId) {
        try {
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load audio file');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio file');
            }

            const audioFile = result.data;

            // Create and show the clone modal
            let modal = document.getElementById('clone-audio-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'clone-audio-modal';
                modal.className = 'modal fade';
                modal.setAttribute('tabindex', '-1');
                modal.innerHTML = `
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Clone Audio File</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="clone-audio-form">
                                    <div class="mb-3">
                                        <label for="clone-audio-name" class="form-label">New Name</label>
                                        <input type="text" class="form-control" id="clone-audio-name" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirm-clone-btn">Clone</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            // Set the default name
            document.getElementById('clone-audio-name').value = audioFile.name + ' (Copy)';

            // Show the modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Handle clone button click
            const cloneBtn = document.getElementById('confirm-clone-btn');
            const handleClone = async () => {
                try {
                    const formData = new FormData();
                    formData.append('name', document.getElementById('clone-audio-name').value);
                    formData.append('type', audioFile.type);
                    formData.append('source_type', 'clone');

                    const response = await fetch(`/api/audio-files`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('Failed to clone audio file');
                    }

                    const result = await response.json();
                    if (!result.success) {
                        throw new Error('Failed to clone audio file');
                    }

                    this.showSuccess('Audio file cloned successfully');
                    this.loadAudioFiles();
                    bsModal.hide();
                } catch (error) {
                    console.error('Error cloning audio:', error);
                    this.showError(error.message || 'Failed to clone audio file');
                }
            };

            cloneBtn.onclick = handleClone;
        } catch (error) {
            console.error('Error preparing clone:', error);
            this.showError('Failed to prepare audio file for cloning');
        }
    }

    async openClipWidget(audioFileId) {
        try {
            const response = await fetch(`/api/audio-files/${audioFileId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load audio file');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio file');
            }

            const audioFile = result.data;

            // Destroy existing widget if it exists
            const container = document.getElementById('audio-recorder-container');
            if (container) {
                container.innerHTML = '';
            }

            // Create new widget with the audio file data
            const audioRecorder = new AudioRecorderWidget('audio-recorder-container', {
                audioFileId: audioFileId,
                audioFile: audioFile,
                onSave: function(result) {
                    console.log('Audio saved:', result);
                    // Reload the audio files list
                    audioManager.loadAudioFiles();
                },
                onError: function(error) {
                    console.error('Error:', error);
                    audioManager.showError(error.message || 'Failed to edit audio file');
                }
            });

            // Show the clip controls
            const clipControls = container.querySelector('#clip-controls');
            if (clipControls) {
                clipControls.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error opening clip widget:', error);
            this.showError('Failed to open clip widget');
        }
    }
}

// Export the class for use in other files
window.AudioManager = AudioManager; 