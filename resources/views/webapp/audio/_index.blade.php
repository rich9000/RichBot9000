<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>Audio Manager</h1>
            
            <!-- Audio Drop Zone -->
            <div id="audio-drop-zone" class="drop-zone mb-4">
                <div class="drop-zone-content">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                    <p>Drag and drop audio files here</p>
                    <small class="text-muted">or click to select files</small>
                </div>
            </div>
            <!-- Audio Recorder Widget -->
            <div id="audio-recorder-container"></div>

           

            <!-- Streaming Recording Widget -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Streaming Record</h5>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="transcribe-checkbox">
                            <label class="form-check-label" for="transcribe-checkbox">
                                Transcribe Audio
                            </label>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <button id="toggle-stream-record-btn" class="btn btn-danger me-2">
                            <i class="fas fa-microphone"></i> Start Streaming
                        </button>
                        <div id="stream-recording-indicator" class="d-none">
                            <span class="recording-dot"></span>
                            <span id="stream-timer">00:00</span>
                            <div class="progress mt-2" style="height: 5px;">
                                <div id="stream-progress" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div id="stream-recording-preview" class="d-none">
                        <div class="d-flex align-items-center mb-3">
                            <button id="play-stream-btn" class="btn btn-primary me-2">
                                <i class="fas fa-play"></i> Play
                            </button>
                            <button id="save-stream-btn" class="btn btn-success me-2">
                                <i class="fas fa-save"></i> Save Stream
                            </button>
                            <button id="cancel-stream-btn" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                        <audio id="stream-player" controls class="w-100 mb-3"></audio>
                        <div id="transcription-preview" class="d-none">
                            <h6>Transcription Preview</h6>
                            <div class="card">
                                <div class="card-body">
                                    <p id="transcription-text" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Alert -->
            <div id="audio-error" class="alert alert-danger d-none" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <span id="error-message"></span>
            </div>

            <!-- Audio Details Form (Hidden by default) -->
            <div id="audio-details-form" class="card mb-4 d-none">
                <div class="card-body">
                    <h5 class="card-title">Audio Details</h5>
                    <form id="save-audio-form">
                        <div class="mb-3">
                            <label for="audio-name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="audio-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="audio-description" class="form-label">Description</label>
                            <textarea class="form-control" id="audio-description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="audio-type" class="form-label">Type</label>
                            <select class="form-control" id="audio-type" required>
                                <option value="general" selected>General</option>
                                <option value="phone-tree">Phone Tree</option>
                                <option value="user">User</option>
                                <option value="system">System</option>
                                <option value="memo">Memo</option>
                                <option value="stream">Stream</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="audio-context" class="form-label">Context</label>
                            <input type="text" class="form-control" id="audio-context">
                        </div>
                        <button type="button" class="btn btn-primary" id="save-audio-btn">Save Audio</button>
                    </form>
                </div>
            </div>

            <!-- Audio Files List -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Audio Files</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Context</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="audio-files-list">
                                <!-- Audio files will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .drop-zone {
        border: 2px dashed #ccc;
        border-radius: 4px;
        padding: 20px;
        text-align: center;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .drop-zone.highlight {
        border-color: #007bff;
        background-color: #e9ecef;
    }

    .drop-zone-content {
        color: #6c757d;
    }

    .drop-zone.highlight .drop-zone-content {
        color: #007bff;
    }

    .recording-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        background-color: red;
        border-radius: 50%;
        margin-right: 5px;
        animation: pulse 1s infinite;
    }

    #recording-timer, #stream-timer {
        font-family: monospace;
        font-size: 1.1em;
        margin-left: 10px;
    }

    .progress {
        background-color: #e9ecef;
        border-radius: 2px;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
</style>

<script>









const audioRecorder = new AudioRecorderWidget('audio-recorder-container', {
   // audioFileId: '123', // Optional: Load an existing audio file
    onSave: function(result) {
        console.log('Audio saved:', result);
    },
    onError: function(error) {
        console.error('Error:', error);
    }
});








    // Wait for any dynamic content to be loaded
    const initializeAudioManager = () => {
        if (typeof window.audioManager === 'undefined') {
            window.audioManager = new AudioManager();
        }
        
        // Check if all required elements are present
        const requiredElements = [
            'toggle-record-btn',
            'stop-recording-btn',
            'recording-preview',
            'audio-details-form',
            'audio-drop-zone'
        ];
        
        const allElementsPresent = requiredElements.every(id => document.getElementById(id));
        
        if (allElementsPresent) {
            window.audioManager.initialize();
        } else {
            // If elements are not present yet, wait and try again
            setTimeout(initializeAudioManager, 100);
        }
    };

    // Start initialization
    initializeAudioManager();
</script>




