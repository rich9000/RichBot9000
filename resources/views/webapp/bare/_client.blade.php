<script src="/webapp_public/richbot-client.js"></script>

<div class="container-fluid">
    <!-- Server Status Card -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Bare Server Status</h5>
            <div class="d-flex align-items-center">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" id="ttsEnabled">
                    <label class="form-check-label text-white" for="ttsEnabled">
                        Enable Text-to-Speech
                    </label>
                </div>
                <button class="btn btn-sm btn-outline-light me-3" type="button" data-bs-toggle="collapse" data-bs-target="#serverInfoBody" aria-expanded="false" aria-controls="serverInfoBody">
                    <i class="fas fa-info-circle"></i>
                </button>
                <div class="server-status-widget">
                    <span class="badge" id="connectionStatus">Disconnected</span>
                    <button class="btn btn-sm btn-outline-light ms-2" id="connectBtn">
                        <i class="fas fa-plug"></i> Connect
                    </button>
                    <button class="btn btn-sm btn-outline-light ms-2" id="disconnectBtn" style="display: none;">
                        <i class="fas fa-plug"></i> Disconnect
                    </button>
                </div>
            </div>
        </div>
        <div class="collapse" id="serverInfoBody">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-server me-2"></i>
                            <span>Server: <span id="serverUrl"></span></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-clock me-2"></i>
                            <span>Last Connected: <span id="lastConnected">Never</span></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-sync me-2"></i>
                            <span>Reconnect Attempts: <span id="reconnectCount">0</span>/5</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-signal me-2"></i>
                            <span>Connection Type: <span id="connectionType">-</span></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>Status: <span id="connectionDetails">-</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Status Panel -->
    <div id="roomStatusCard" class="card mb-4" style="display: none;">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Room Status</h5>
            <div class="room-controls">
                <span class="badge bg-info">Room: <span id="roomName">-</span></span>
            </div>
        </div>
        <div class="card-body">
            <!-- Initial Options View -->
            <div id="initialOptions" class="text-center">
                <div>Loading...</div>
            </div>

            <!-- Active Participants (hidden initially) -->
            <div id="activeParticipants" class="participants-container" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Active Participants</h6>
                    <div class="audio-controls">
                        <button class="btn btn-sm btn-outline-danger rounded-circle" title="Mute Microphone">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger rounded-circle" title="End Call">
                            <i class="fas fa-phone-slash"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning rounded-circle" title="Mute Audio">
                            <i class="fas fa-volume-mute"></i>
                        </button>
                    </div>
                </div>
                <div class="participants-grid" id="participantsGrid">
                    <!-- Participants will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Area (hidden initially) -->
    <div id="chatArea" class="card" style="display: none;">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Chat</h5>
        </div>
        <div class="card-body chat-container">
            <div class="chat-messages">
                <!-- Messages will be dynamically inserted here -->
            </div>
            <div class="chat-input">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Type a message...">
                    <button class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Cards Row -->
    <div id="formCardsRow" class="row">
        <!-- Assistant Phone Call Section -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Assistant Phone Call</h5>
                </div>
                <div class="card-body">
                    <form class="assistant-phone-form">
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phoneCallNumber" id="phoneCallNumber" placeholder="+1 (555) 123-4567">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Assistant</label>
                            <select class="form-select" id="assistantPhoneSelect">
                                <!-- Assistants will be loaded dynamically -->
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-phone me-2"></i>Start Call
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assistant Chat Section -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Assistant Chat</h5>
                </div>
                <div class="card-body">
                    <form class="assistant-chat-form">
                        <div class="mb-3">
                            <label class="form-label">Select Assistant</label>
                            <select class="form-select" id="assistantChatSelect">
                                <!-- Assistants will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="voiceEnabled">
                            <label class="form-check-label" for="voiceEnabled">
                                Enable Voice Chat
                            </label>
                        </div>
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-comments me-2"></i>Start Chat
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Phone Call Section -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Phone Call</h5>
                </div>
                <div class="card-body">
                    <form class="phone-call-form">
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phoneCallNumber" placeholder="+1 (555) 123-4567">
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-phone me-2"></i>Start Call
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern UI Styles */
.participants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.participant-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.participant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.participant-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.participant-info {
    flex: 1;
}

.participant-info h6 {
    margin: 0;
    font-size: 0.9rem;
}

.participant-controls {
    display: flex;
    gap: 0.5rem;
}

.audio-controls {
    display: flex;
    gap: 0.5rem;
}

.audio-controls .btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.3s ease;
    padding: 0;
}

.audio-controls .btn.active {
    transform: scale(1.1);
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.audio-controls .btn[title="Mute Microphone"] {
    border-color: #dc3545;
    color: #dc3545;
}

.audio-controls .btn[title="Mute Microphone"]:hover,
.audio-controls .btn[title="Mute Microphone"].active {
    background-color: #dc3545;
    color: white;
}

.audio-controls .btn[title="End Call"] {
    border-color: #dc3545;
    color: #dc3545;
}

.audio-controls .btn[title="End Call"]:hover,
.audio-controls .btn[title="End Call"].active {
    background-color: #dc3545;
    color: white;
}

.audio-controls .btn[title="Mute Audio"] {
    border-color: #ffc107;
    color: #ffc107;
}

.audio-controls .btn[title="Mute Audio"]:hover,
.audio-controls .btn[title="Mute Audio"].active {
    background-color: #ffc107;
    color: #000;
}

.chat-container {
    height: 400px;
    display: flex;
    flex-direction: column;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.message {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    max-width: 80%;
}

.message.ai {
    align-self: flex-start;
}

.message.user {
    align-self: flex-end;
}

.message-content {
    padding: 0.75rem 1rem;
    border-radius: 15px;
    background: #f8f9fa;
}

.message.ai .message-content {
    background: #e3f2fd;
}

.message.user .message-content {
    background: #e8f5e9;
}

.message-time {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.chat-input {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
}

/* Animations */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.participant-card.active {
    animation: pulse 2s infinite;
}

/* Responsive Design */
@media (max-width: 768px) {
    .participants-grid {
        grid-template-columns: 1fr;
    }
    
    .audio-controls .btn {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}

/* Microphone Button States */
.participant-controls .btn.active {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.participant-controls .btn.active:hover {
    background-color: #bb2d3b;
    border-color: #b02a37;
}

.participant-controls .btn[title*="Send audio"] {
    border-color: #0d6efd;
}

.participant-controls .btn[title*="Send audio"]:hover {
    background-color: #0d6efd;
    color: white;
}

.participant-controls .btn[title*="Receive audio"] {
    border-color: #198754;
}

.participant-controls .btn[title*="Receive audio"]:hover {
    background-color: #198754;
    color: white;
}

/* Tooltip styles */
[title] {
    position: relative;
}

[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
}

/* Initial Options Styles */
#initialOptions .btn {
    min-width: 200px;
    padding: 1rem 2rem;
    transition: all 0.3s ease;
}

#initialOptions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

#initialOptions .btn i {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

/* Server Status Widget Styles */
.server-status-widget {
    display: flex;
    align-items: center;
}

.server-status-widget .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}

.server-status-widget .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.server-status-widget .btn i {
    margin-right: 0.25rem;
}

#connectionStatus {
    min-width: 100px;
    text-align: center;
}

.card-body .fas {
    width: 20px;
    text-align: center;
}

#richbot-client-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.back-to-forms {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.main-content-area {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    padding: 1.5rem;
}

.richbot-container {
    background: #fff;
    border-radius: 8px;
    margin-top: 1rem;
}

.back-button {
    min-width: 100px;
}
</style>

<script>
const userAudioStreams = new Map();

// μ-law to linear PCM conversion
function ulawToLinear(ulawByte) {
    const BIAS = 0x84;
    const CLIP = 32635;
    const exp_lut = [0, 132, 396, 924, 1980, 4092, 8316, 16764];
    
    ulawByte = ~ulawByte;
    let sign = (ulawByte & 0x80) ? -1 : 1;
    let exponent = (ulawByte >> 4) & 0x07;
    let mantissa = ulawByte & 0x0F;
    let sample = exp_lut[exponent] + (mantissa << (exponent + 3));
    
    return sign * (sample - BIAS);
}

// Audio context and processing
let audioContext = null;
let audioQueue = [];
let isPlaying = false;
let audioInitialized = false;
let nextPlayTime = 0;

// Initialize audio context
async function initializeAudioContext(preferredSampleRate = 8000) {
    if (!audioContext) {
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)({
                latencyHint: 'interactive',
                //sampleRate: preferredSampleRate
            });
            audioInitialized = true;
            nextPlayTime = audioContext.currentTime;
            console.log('[Client] Audio context initialized successfully with sample rate:', preferredSampleRate);
        } catch (error) {
            console.error('[Client] Failed to initialize audio context:', error);
            return false;
        }
    }
    return true;
}

// Function to play next audio in queue
async function playNextInQueue() {
    if (isPlaying || audioQueue.length === 0) {
        return;
    }

    try {
        // Ensure audio context is initialized
        if (!await initializeAudioContext()) {
            console.error('[Client] Cannot play audio - context initialization failed');
            return;
        }

        isPlaying = true;
        const audioData = audioQueue.shift();

        // Create audio processing nodes
        const source = audioContext.createBufferSource();
        source.buffer = audioData.buffer;

        const gainNode = audioContext.createGain();
        gainNode.gain.value = audioData.source === 'twilio' ? 2.5 : 0.8;

        const compressor = audioContext.createDynamicsCompressor();
        compressor.threshold.value = -24;
        compressor.knee.value = 30;
        compressor.ratio.value = 12;
        compressor.attack.value = 0.003;
        compressor.release.value = 0.25;

        const lowpass = audioContext.createBiquadFilter();
        lowpass.type = 'lowpass';
        lowpass.frequency.value = audioData.source === 'twilio' ? 4000 : 10000;
        lowpass.Q.value = 0.7;

        // Connect the audio processing chain
        source.connect(gainNode);
        gainNode.connect(compressor);
        compressor.connect(lowpass);
        lowpass.connect(audioContext.destination);

        // Calculate the next play time
        const currentTime = audioContext.currentTime;
        const playTime = Math.max(nextPlayTime, currentTime);
        nextPlayTime = playTime + audioData.buffer.duration;

        // Handle completion
        source.onended = () => {
            isPlaying = false;
            playNextInQueue();
        };

        // Start playback at the calculated time
        source.start(playTime);

    } catch (error) {
        console.error('[Client] Error playing audio:', error);
        isPlaying = false;
        playNextInQueue(); // Try next item in queue
    }
}

// Function to handle Twilio media data
async function handleTwilioMediaData(data) {
    if (isMuted) {
        console.log('[Client] Audio muted, ignoring Twilio media data');
        return;
    }
    
    console.log('[Client] Received Twilio media data:', {
        dataLength: data.length,
        timestamp: Date.now()
    });
    
    try {
        // Ensure audio context is initialized
        if (!await initializeAudioContext()) {
            console.error('[Client] Cannot process Twilio audio - context initialization failed');
            return;
        }

        // Convert base64 to binary data
        const binaryString = window.atob(data);
        const audioData = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            audioData[i] = binaryString.charCodeAt(i);
        }

        // Convert μ-law to PCM
        const pcmData = new Int16Array(audioData.length);
        for (let i = 0; i < audioData.length; i++) {
            pcmData[i] = ulawToLinear(audioData[i]);
        }

        // Convert to normalized float32 (-1 to 1)
        const float32Data = new Float32Array(pcmData.length);
        for (let i = 0; i < pcmData.length; i++) {
            float32Data[i] = pcmData[i] / 32768.0;
        }

        // Create audio buffer with 8kHz sample rate
        const audioBuffer = audioContext.createBuffer(1, float32Data.length, 8000);
        audioBuffer.getChannelData(0).set(float32Data);

        // Add to queue
        audioQueue.push({
            source: 'twilio',
            buffer: audioBuffer
        });

        // Start playing if not already playing
        if (!isPlaying) {
            nextPlayTime = audioContext.currentTime;
            playNextInQueue();
        }

        console.log('[Client] Queued Twilio audio:', {
            sampleRate: 8000,
            samples: float32Data.length,
            duration: audioBuffer.duration
        });
    } catch (error) {
        console.error('[Client] Error processing Twilio audio:', error);
    }
}

// Function to handle OpenAI media data
async function handleOpenAIMediaData(data) {
    if (isMuted) {
        console.log('[Client] Audio muted, ignoring OpenAI media data');
        return;
    }
    
    console.log('[Client] Received OpenAI media data:', {
        dataLength: data.length,
        timestamp: Date.now()
    });
    
    try {
        // Ensure audio context is initialized
        if (!await initializeAudioContext()) {
            console.error('[Client] Cannot process OpenAI audio - context initialization failed');
            return;
        }

        // Convert base64 to binary data
        const binaryString = window.atob(data);
        const audioData = new Int16Array(binaryString.length / 2);
        for (let i = 0; i < binaryString.length; i += 2) {
            const low = binaryString.charCodeAt(i);
            const high = binaryString.charCodeAt(i + 1);
            audioData[i/2] = (high << 8) | low;
        }

        // Convert to normalized float32 (-1 to 1)
        const float32Data = new Float32Array(audioData.length);
        for (let i = 0; i < audioData.length; i++) {
            float32Data[i] = audioData[i] / 32768.0;
        }

        // Create audio buffer with 24kHz sample rate
        const audioBuffer = audioContext.createBuffer(1, float32Data.length, 24000);
        audioBuffer.getChannelData(0).set(float32Data);

        // Add to queue
        audioQueue.push({
            source: 'openai',
            buffer: audioBuffer
        });

        // Start playing if not already playing
        if (!isPlaying) {
            nextPlayTime = audioContext.currentTime;
            playNextInQueue();
        }

        console.log('[Client] Queued OpenAI audio:', {
            sampleRate: 24000,
            samples: float32Data.length,
            duration: audioBuffer.duration
        });
    } catch (error) {
        console.error('[Client] Error processing OpenAI audio:', error);
    }
}

// Function to toggle audio for a specific source
function toggleAudio(source) {
    const audioStream = userAudioStreams.get(source);
    if (audioStream) {
        audioStream.muted = !audioStream.muted;
        audioStream.gainNode.gain.value = audioStream.muted ? 0 : (source === 'twilio' ? 2.5 : 0.8);
        
        // Update UI
        const button = document.querySelector(`button[title="Mute ${source === 'twilio' ? 'Phone' : 'Assistant'}"]`);
        if (button) {
            const icon = button.querySelector('i');
            icon.className = audioStream.muted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
            button.classList.toggle('active', audioStream.muted);
        }
    }
}

async function playAudio(base64Audio) {
    try {
        // Create AudioContext
        const audioContext = new AudioContext();

        // Load the AudioWorklet processor
        await audioContext.audioWorklet.addModule('js/audio-processor.js');

        // Create and connect the processor node
        const processorNode = new AudioWorkletNode(audioContext, 'audio-processor');
        processorNode.connect(audioContext.destination);

        // Handle errors from the processor
        processorNode.port.onmessage = (event) => {
            if (event.data.type === 'error') {
                console.error('AudioProcessor Error:', event.data.message);
            }
        };

        // Send Base64-encoded μ-law audio data
        processorNode.port.postMessage({ type: 'audioData', data: base64Audio });

        // Example: Stop playback after 10 seconds
        setTimeout(() => {
            processorNode.port.postMessage({ type: 'stop' });
            processorNode.disconnect();
            audioContext.close();
        }, 10000);

    } catch (error) {
        console.error('Error setting up audio:', error);
    }
}

    // Simulate active participants
    const participants = document.querySelectorAll('.participant-card');
    participants.forEach(participant => {
        participant.addEventListener('click', function() {
            this.classList.toggle('active');
        });
    });

    // Simulate message sending
    const chatInput = document.querySelector('.chat-input input');
    const chatMessages = document.querySelector('.chat-messages');
    
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const message = this.value.trim();
            if (message) {
                addMessage(message, 'user');
                this.value = '';
                
                // Simulate AI response
                setTimeout(() => {
                    addMessage('I received your message: ' + message, 'ai');
                }, 1000);
            }
        }
    });

    function addMessage(text, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="message-content">${text}</div>
            <div class="message-time">${time}</div>
        `;
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Form submission handlers
    document.querySelector('.assistant-phone-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        
        // Get the phone number and assistant ID
        const phoneNumber = this.querySelector('#phoneCallNumber').value;
        const assistantId = this.querySelector('#assistantPhoneSelect').value;
        
        if (!phoneNumber || !assistantId) {
            addMessage('Please fill in all fields', 'system');
            return;
        }
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        // Send message to start assistant phone call
        ws.send(JSON.stringify({
            type: 'start_assistant_call',
            phone_number: phoneNumber,
            assistant_id: assistantId
        }));
        
        // Show room status card
        document.getElementById('roomStatusCard').style.display = 'block';
        
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        }, 1000);
    });

    document.querySelector('.assistant-chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        
        // Get the assistant ID and voice enabled status
        const assistantId = this.querySelector('#assistantChatSelect').value;
        const voiceEnabled = this.querySelector('#voiceEnabled').checked;
        
        if (!assistantId) {
            addMessage('Please select an assistant', 'system');
            return;
        }
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

        // Hide the form cards
        document.getElementById('formCardsRow').style.display = 'none';

        // Create a main content container for the chat interface
        const mainContent = document.createElement('div');
        mainContent.className = 'main-content-area';
        mainContent.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-outline-secondary back-button">
                    <i class="fas fa-arrow-left me-2"></i>Back to Forms
                </button>
                <h4 class="mb-0">Chat with Assistant</h4>
                <div style="width: 100px"></div>
            </div>
            <div id="richbot-client-container" class="richbot-container"></div>
        `;

        // Insert the main content after the form cards
        const formCardsRow = document.getElementById('formCardsRow');
        formCardsRow.parentNode.insertBefore(mainContent, formCardsRow.nextSibling);

        // Initialize RichbotClient
        const client = new RichbotClient('richbot-client-container', {
            wsUrl: `${window.appConfig.wsUrlAlt}/webclient`,
            apiToken: appState.apiToken,
            assistantId: assistantId,
            autoConnect: true,
            autoStartRecording: voiceEnabled,
            initialVolume: 1.0,
            showFormControls: true,
            showChatLog: true
        });

        // Listen for client events
        client.on('connected', () => {
            console.log('RichbotClient connected');
            button.disabled = false;
            button.innerHTML = originalText;
        });

        client.on('error', (error) => {
            console.error('RichbotClient error:', error);
            addMessage(`Error: ${error.message}`, 'system');
            button.disabled = false;
            button.innerHTML = originalText;
        });

        client.on('disconnected', () => {
            console.log('RichbotClient disconnected');
            mainContent.remove();
            document.getElementById('formCardsRow').style.display = 'flex';
        });

        // Add back button handler
        mainContent.querySelector('.back-button').addEventListener('click', () => {
            client.disconnect();
            mainContent.remove();
            document.getElementById('formCardsRow').style.display = 'flex';
        });
    });

    document.querySelector('.phone-call-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;

        // get the phone number from the form id phoneCallNumber
        const phoneCallNumber = this.querySelector('#phoneCallNumber').value;
        console.log('phoneCallNumber', phoneCallNumber);
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

        //send a message to the server to start the phone call
        ws.send(JSON.stringify({
            type: 'start_call',            
            phone_number: phoneCallNumber
        }));
        
        // Show room status card
        document.getElementById('roomStatusCard').style.display = 'block';
        
        
       
    });

    // Handle microphone controls
    const participantControls = document.querySelectorAll('.participant-controls .btn');
    participantControls.forEach(button => {
        button.addEventListener('click', function() {
            if (this.closest('.user')) {
                // Toggle web user's microphone
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');
                this.querySelector('i').className = isActive ? 'fas fa-microphone' : 'fas fa-microphone-slash';
                
                // Update UI to show microphone state
                if (isActive) {
                    addMessage('Microphone enabled', 'system');
                } else {
                    addMessage('Microphone disabled', 'system');
                }
            } else {
                // Toggle sending audio to specific participant
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');
                const participantName = this.closest('.participant-card').querySelector('h6').textContent;
                
                // Update UI to show audio routing state
                if (isActive) {
                    addMessage(`Sending audio to ${participantName}`, 'system');
                } else {
                    addMessage(`Stopped sending audio to ${participantName}`, 'system');
                }
            }
        });
    });

    // Handle bottom audio controls
    const audioControls = document.querySelector('.audio-controls');
    const muteMicBtn = audioControls.querySelector('button[title="Mute Microphone"]');
    const endCallBtn = audioControls.querySelector('button[title="End Call"]');
    const muteAudioBtn = audioControls.querySelector('button[title="Mute Audio"]');

    // Mute Microphone
    muteMicBtn.addEventListener('click', function() {
        this.classList.toggle('active');
        const isActive = this.classList.contains('active');
        const icon = this.querySelector('i');
        
        if (isActive) {
            icon.className = 'fas fa-microphone-slash';
            addMessage('Microphone muted', 'system');
        } else {
            icon.className = 'fas fa-microphone';
            addMessage('Microphone unmuted', 'system');
        }
    });

    // End Call
    endCallBtn.addEventListener('click', function() {
        this.classList.toggle('active');
        const isActive = this.classList.contains('active');
        
        if (isActive) {
            addMessage('Ending call...', 'system');
            // Simulate call ending
            setTimeout(() => {
                this.classList.remove('active');
                handleHangup();
            }, 1000);
        }
    });

    // Mute Audio
    muteAudioBtn.addEventListener('click', function() {
        this.classList.toggle('active');
        const isActive = this.classList.contains('active');
        const icon = this.querySelector('i');
        
        if (isActive) {
            icon.className = 'fas fa-volume-mute';
            addMessage('Audio muted', 'system');
        } else {
            icon.className = 'fas fa-volume-up';
            addMessage('Audio unmuted', 'system');
        }
    });

    // Add system message style
    const style = document.createElement('style');
    style.textContent = `
        .message.system {
            align-self: center;
            max-width: 100%;
        }
        .message.system .message-content {
            background: #f8f9fa;
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 0.5rem 1rem;
        }
    `;
    document.head.appendChild(style);

    // State management
    let currentMode = null;

    // Function to show/hide forms
    function toggleForms(show) {
        document.getElementById('formCardsRow').style.display = show ? 'flex' : 'none';
    }

    // Simple audio handling
    let isMuted = false;

    // Function to handle hangup
    function handleHangup() {
        // Reset audio context
        if (audioContext) {
            audioContext.close();
            audioContext = null;
        }
        
        // Reset UI state
        currentMode = null;
        document.getElementById('initialOptions').style.display = 'block';
        document.getElementById('activeParticipants').style.display = 'none';
        document.getElementById('chatArea').style.display = 'none';
        document.getElementById('roomName').textContent = '-';
        document.getElementById('roomStatusCard').style.display = 'none';
        
        // Show forms again
        toggleForms(true);
        
        // Add system message
        addMessage('Call ended', 'system');
    }

    // WebSocket connection state
    let ws = null;
    let isConnected = false;
    let currentRoom = null;
    let currentRoomMembers = [];
    let reconnectAttempts = 0;
    let isManualDisconnect = false;
    const MAX_RECONNECT_ATTEMPTS = 5;
    const RECONNECT_DELAY = 2000;

    // WebSocket connection functions
    function connectWebSocket() {
        if (ws) {
            ws.close();
        }

        isManualDisconnect = false;
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.hostname}:9502/webclient?api_token=${appState.apiToken}`;
        
        // Update connection details
        document.getElementById('connectionType').textContent = protocol === 'wss:' ? 'Secure WebSocket' : 'WebSocket';
        document.getElementById('connectionDetails').textContent = 'Connecting...';
        
        ws = new WebSocket(wsUrl);
        
        ws.onopen = function() {
            console.log('WebSocket connected');
            isConnected = true;
            updateConnectionStatus(true);
            reconnectAttempts = 0;
            
            // Update connection details
            document.getElementById('lastConnected').textContent = new Date().toLocaleString();
            document.getElementById('reconnectCount').textContent = '0';
            document.getElementById('connectionDetails').textContent = 'Connected';
            
            // Show/hide appropriate buttons
            document.getElementById('connectBtn').style.display = 'none';
            document.getElementById('disconnectBtn').style.display = 'inline-block';
            
           
        };
        
        ws.onclose = function() {
            console.log('WebSocket disconnected');
            isConnected = false;
            updateConnectionStatus(false);
            
            // Update connection details
            document.getElementById('connectionDetails').textContent = 'Disconnected';
            
            // Show/hide appropriate buttons
            document.getElementById('connectBtn').style.display = 'inline-block';
            document.getElementById('disconnectBtn').style.display = 'none';
            
            // Only attempt to reconnect if it wasn't a manual disconnect
            if (!isManualDisconnect && reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                document.getElementById('reconnectCount').textContent = reconnectAttempts;
                console.log(`Attempting to reconnect (${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})...`);
                setTimeout(connectWebSocket, RECONNECT_DELAY);
            } else if (isManualDisconnect) {
                document.getElementById('connectionDetails').textContent = 'Manually disconnected';
            } else {
                document.getElementById('connectionDetails').textContent = 'Max reconnection attempts reached';
            }
        };
        
        ws.onerror = function(error) {
            console.error('WebSocket error:', error);
            updateConnectionStatus(false);
            document.getElementById('connectionDetails').textContent = 'Connection error';
        };
        
        ws.onmessage = handleWebSocketMessage;
    }

    function disconnectWebSocket() {
        if (ws) {
            isManualDisconnect = true;
            // Leave current room if in one
            if (currentRoom) {
                ws.send(JSON.stringify({
                    type: 'leave',
                    room: currentRoom
                }));
            }
            
            ws.close();
            ws = null;
            isConnected = false;
            currentRoom = null;
            currentRoomMembers = [];
            updateConnectionStatus(false);
            
            // Update connection details
            document.getElementById('connectionDetails').textContent = 'Manually disconnected';
            document.getElementById('reconnectCount').textContent = '0';
            
            // Show/hide appropriate buttons
            document.getElementById('connectBtn').style.display = 'inline-block';
            document.getElementById('disconnectBtn').style.display = 'none';
            
            // Reset UI
            document.getElementById('roomName').textContent = '-';
            document.getElementById('participantsGrid').innerHTML = '';
        }
    }

    // Function to update connection status
    function updateConnectionStatus(connected) {
        isConnected = connected;
        const statusBadge = document.getElementById('connectionStatus');
        statusBadge.className = `badge ${connected ? 'bg-success' : 'bg-danger'}`;
        statusBadge.textContent = connected ? 'Connected' : 'Disconnected';
    }

    // Function to update participant status
    function updateParticipantStatus(fd, status) {
        const participant = document.querySelector(`[data-fd="${fd}"]`);
        if (participant) {
            const statusBadge = participant.querySelector('.participant-status');
            if (statusBadge) {
                statusBadge.className = `badge ${status === 'online' ? 'bg-success' : 'bg-danger'}`;
                statusBadge.textContent = status === 'online' ? 'Connected' : 'Offline';
            }
        }
    }

    // Function to populate assistant dropdowns
    function populateAssistantDropdowns() {
        const assistants = appState.data.assistants || [];
        const assistantPhoneSelect = document.getElementById('assistantPhoneSelect');
        const assistantChatSelect = document.getElementById('assistantChatSelect');
        
        // Clear existing options
        assistantPhoneSelect.innerHTML = '';
        assistantChatSelect.innerHTML = '';
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select an Assistant';
        assistantPhoneSelect.appendChild(defaultOption.cloneNode(true));
        assistantChatSelect.appendChild(defaultOption.cloneNode(true));
        
        // Add assistant options
        assistants.forEach(assistant => {
            const option = document.createElement('option');
            option.value = assistant.id;
            option.textContent = `${assistant.name} (${assistant.model})`;
            option.dataset.model = assistant.model;
            option.dataset.systemMessage = assistant.system_message;
            assistantPhoneSelect.appendChild(option.cloneNode(true));
            assistantChatSelect.appendChild(option.cloneNode(true));
        });
    }

    // Function to get selected assistant
    function getSelectedAssistant(type) {
        const select = document.getElementById(type === 'phone' ? 'assistantPhoneSelect' : 'assistantChatSelect');
        const assistantId = select.value;
        return appState.data.assistants.find(a => a.id === parseInt(assistantId));
    }

    // Function to create participant card
    function createParticipantCard(fd, type, name, assistant = null) {
        const card = document.createElement('div');
        card.className = 'participant-card';
        card.setAttribute('data-fd', fd);

        if (type == 'user') {
            status = 'Online';
        } else {
            status = 'Offline';
        }
        
        let icon, badgeColor, modelInfo = '';
        switch(type) {
            case 'openai':
                icon = 'fa-robot';
                badgeColor = 'bg-primary';
                if (assistant) {
                    const assistantData = appState.data.assistants.find(a => a.id === assistant);
                    if (assistantData) {
                        modelInfo = `<small class="text-muted">${assistantData.model.name}</small>`;
                    }
                }
                break;
            case 'twilio':
                icon = 'fa-phone';
                badgeColor = 'bg-success';
                break;
            default:
                icon = 'fa-user';
                badgeColor = 'bg-info';
        }

        card.innerHTML = `
            <div class="participant-avatar">
                <i class="fas ${icon}"></i>
            </div>
            <div class="participant-info">
                <h6>${name}</h6>
                ${modelInfo}
                <span class="badge ${badgeColor} participant-status">${status}</span>
            </div>
            <div class="participant-controls">
                ${type === 'user' ? `
                    <button class="btn btn-sm btn-outline-danger" title="Your microphone">
                        <i class="fas fa-microphone-slash"></i>
                    </button>
                ` : `
                    <button class="btn btn-sm btn-outline-primary" title="Send audio">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" title="Receive audio">
                        <i class="fas fa-volume-up"></i>
                    </button>
                `}
            </div>
        `;

        return card;
    }

    // Function to update participant card fd
    function updateParticipantCardFd(oldFd, newFd) {
        const card = document.querySelector(`[data-fd="${oldFd}"]`);
        if (card) {
            card.setAttribute('data-fd', newFd);
            return true;
        }
        return false;
    }

    // Function to update participants grid
    function updateParticipantsGrid(members) {
        const grid = document.getElementById('participantsGrid');
        grid.innerHTML = '';
        
        members.forEach(member => {
            const card = createParticipantCard(
                member.fd,
                member.type,
                member.name || member.fd,
                member.assistant
            );
            grid.appendChild(card);
        });
    }

    // WebSocket message handler
    function handleWebSocketMessage(event) {
        const data = JSON.parse(event.data);
        console.log('Received WebSocket message:', data);
        
        switch(data.type) {
            case 'welcome':
                console.log('Welcome message received:', data);
                break;

            case 'call_started':
                console.log('Call started:', data);
                currentRoom = data.room;
                document.getElementById('roomName').textContent = data.room;
                showPhoneCall(data.phone_number);
                break;

            case 'assistant_chat_started':
                console.log('Assistant chat started:', data);
                currentRoom = data.room;
                document.getElementById('roomName').textContent = data.room;
                
                // Update the assistant's fd if provided
                if (data.assistant_fd) {
                    const assistantMember = currentRoomMembers.find(m => m.type === 'openai');
                    if (assistantMember) {
                        assistantMember.fd = data.assistant_fd;
                        updateParticipantCardFd('assistant', data.assistant_fd);
                    }
                }
                
                // Update UI to show chat is active
                document.getElementById('chatArea').style.display = 'block';
                document.getElementById('roomStatusCard').style.display = 'block';
                break;

            case 'error':
                console.error('Error:', data.error);
                addMessage(`Error: ${data.error.message || 'Unknown error'}`, 'system');
                break;

            case 'response.created':
                console.log('Response created:', data);
                // Initialize response tracking
                if (!responseBuffers.has(data.response.id)) {
                    responseBuffers.set(data.response.id, {
                        text: '',
                        audio: [],
                        transcript: ''
                    });
                }
                break;

            case 'rate_limits.updated':
                console.log('Rate limits updated:', data.rate_limits);
                break;

            case 'response.output_item.added':
                console.log('Output item added:', data);
                break;

            case 'conversation.item.created':
                console.log('Conversation item created:', data);
                break;

            case 'response.content_part.added':
                console.log('Content part added:', data);
                break;

            case 'response.audio_transcript.delta':
                console.log('Audio transcript delta:', data);
                const transcriptBuffer = responseBuffers.get(data.response_id);
                if (transcriptBuffer) {
                    transcriptBuffer.transcript += data.delta;
                    // Update the transcript display in real-time
                    const transcriptContainer = document.querySelector(`#message-${data.response_id} .message-transcript`);
                    if (transcriptContainer) {
                        transcriptContainer.textContent = transcriptBuffer.transcript;
                    } else {
                        // Create new message container if it doesn't exist
                        createMessageContainer(data.response_id, 'ai');
                    }
                }
                break;

            case 'response.audio.delta':
                console.log('Audio delta received');
                const audioBuffer = responseBuffers.get(data.response_id);
                if (audioBuffer) {
                    audioBuffer.audio.push(data.delta);
                    // If audio playback is enabled, send to audio processor
                    if (!isMuted) {
                        handleOpenAIMediaData(data.delta);
                    }
                }
                break;

            case 'response.audio_transcript.done':
                console.log('Audio transcript complete:', data);
                const completeTranscript = responseBuffers.get(data.response_id)?.transcript || '';
                addMessage(completeTranscript, 'ai');
                break;

            case 'response.content_part.done':
                console.log('Content part done:', data);
                break;

            case 'response.output_item.done':
                console.log('Output item done:', data);
                break;

            case 'response.done':
                console.log('Response complete:', data);
                // Clean up response buffers
                if (responseBuffers.has(data.response.id)) {
                    responseBuffers.delete(data.response.id);
                }
                break;

            case 'joined':
                currentRoom = data.room;
                document.getElementById('roomName').textContent = data.room;
                currentRoomMembers = data.members;
                updateParticipantsGrid(currentRoomMembers);
                addMessage(`Joined room: ${data.room}`, 'system');
                break;
            
            case 'user_joined':
                const existingMember = currentRoomMembers.find(m => m.fd === data.user);
                if (!existingMember) {
                    const newMember = {
                        fd: data.user,
                        type: data.member_type,
                        name: data.member_name,
                        assistant: data.member_assistant
                    };
                    currentRoomMembers.push(newMember);
                    updateParticipantsGrid(currentRoomMembers);
                    addMessage(`${data.member_name || 'User'} joined the room`, 'system');
                }
                break;
            
            case 'user_left':
                const memberIndex = currentRoomMembers.findIndex(m => m.fd === data.user);
                if (memberIndex !== -1) {
                    currentRoomMembers.splice(memberIndex, 1);
                    updateParticipantsGrid(currentRoomMembers);
                    addMessage(`${data.member_name || 'User'} left the room`, 'system');
                }
                break;
            
            case 'left':
                currentRoom = null;
                document.getElementById('roomName').textContent = '-';
                currentRoomMembers = [];
                updateParticipantsGrid([]);
                addMessage('Left room', 'system');
                break;

            default:
                console.log('Unhandled message type:', data.type);
                break;
        }
    }

    // Function to create a message container with audio and transcript sections
    function createMessageContainer(responseId, type) {
        const container = document.createElement('div');
        container.id = `message-${responseId}`;
        container.className = `message ${type}`;
        
        container.innerHTML = `
            <div class="message-content">
                <div class="message-text"></div>
                <div class="message-audio">
                    <button class="btn btn-sm btn-primary play-button" style="display: none;">
                        <i class="fas fa-play"></i> Play
                    </button>
                    <span class="audio-status"></span>
                </div>
                <div class="message-transcript"></div>
            </div>
            <div class="message-time">${new Date().toLocaleTimeString()}</div>
        `;
        
        const chatMessages = document.querySelector('.chat-messages');
        chatMessages.appendChild(container);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        return container;
    }

    // Function to play audio message
    function playAudioMessage(responseId) {
        const audioChunks = audioBuffers.get(responseId);
        if (!audioChunks || audioChunks.length === 0) return;
        
        // Combine all base64 audio chunks
        const base64Audio = audioChunks.join('');
        
        // Convert base64 to ArrayBuffer
        const binaryString = window.atob(base64Audio);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        const audioBuffer = bytes.buffer;
        
        // Create audio context if it doesn't exist
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        // Decode and play the audio
        audioContext.decodeAudioData(audioBuffer)
            .then(decodedData => {
                const source = audioContext.createBufferSource();
                source.buffer = decodedData;
                source.connect(audioContext.destination);
                source.start(0);
            })
            .catch(err => console.error('Error playing audio:', err));
    }

    // Initialize Maps to store audio and transcript buffers
    const audioBuffers = new Map();
    const transcriptBuffers = new Map();

    // Event listeners for connection buttons
    document.getElementById('connectBtn').addEventListener('click', connectWebSocket);
    document.getElementById('disconnectBtn').addEventListener('click', disconnectWebSocket);

    // Update showAssistantPhoneCall function to include WebSocket join
    window.showAssistantPhoneCall = function(assistantId, phoneNumber) {
        const selectedAssistant = appState.data.assistants.find(a => a.id === parseInt(assistantId));
        if (!selectedAssistant) {
            addMessage('Assistant not found', 'system');
            return;
        }

        if (!isConnected) {
            addMessage('Please connect to the server first', 'system');
            return;
        }

        currentMode = 'assistantPhoneCall';
        document.getElementById('initialOptions').style.display = 'none';
        document.getElementById('activeParticipants').style.display = 'block';
        document.getElementById('chatArea').style.display = 'block';
        document.getElementById('roomName').textContent = `Assistant Call - ${selectedAssistant.name}`;
        
        // Hide forms
        toggleForms(false);
        
        // Send message to start assistant call
        ws.send(JSON.stringify({
            type: 'start_assistant_call',
            assistant_id: assistantId,
            phone_number: phoneNumber
        }));
        
        // Initialize with offline participants
        const initialMembers = [
            { fd: 'assistant', type: 'openai', name: selectedAssistant.name, assistant: selectedAssistant.id },
            { fd: 'phone', type: 'twilio', name: phoneNumber },
            { fd: 'user', type: 'user', name: 'Web User' }
        ];
        updateParticipantsGrid(initialMembers);
        
        // Add system message about the assistant
        addMessage(`Connected to ${selectedAssistant.name} (${selectedAssistant.model})`, 'system');
    };

    // Update showAssistantChat function to include WebSocket join
    window.showAssistantChat = function(assistant, voiceEnabled) {
        if (!assistant) {
            addMessage('Assistant not found', 'system');
            return;
        }

        if (!isConnected) {
            addMessage('Please connect to the server first', 'system');
            return;
        }

        currentMode = 'assistantChat';
        document.getElementById('initialOptions').style.display = 'none';
        document.getElementById('activeParticipants').style.display = 'block';
        document.getElementById('chatArea').style.display = 'block';
        document.getElementById('roomName').textContent = `Assistant Chat - ${assistant.name}`;
        
        // Hide forms
        toggleForms(false);
        
        // Join the room
        const roomName = `assistant-chat-${assistant.id}-${Date.now()}`;
        ws.send(JSON.stringify({
            type: 'start_assistant_chat',
            assistant_id: assistant.id,
            voice_enabled: voiceEnabled
        }));
        
        // Initialize with offline participants
        const initialMembers = [
            { fd: 'assistant', type: 'openai', name: assistant.name, assistant: assistant.id },
            { fd: 'user', type: 'user', name: 'Web User' }
        ];
        updateParticipantsGrid(initialMembers);
        
        // Add system message about the assistant
        addMessage(`Connected to ${assistant.name} (${assistant.model})`, 'system');
    };

    // Update showPhoneCall function to include WebSocket join
    window.showPhoneCall = function(phoneCallNumber) {
        if (!phoneCallNumber) {
            phoneCallNumber = document.getElementById('phoneCallNumber').value;
        }

        console.log('phoneCallNumber123123123', phoneCallNumber);
        if (!isConnected) {
            addMessage('Please connect to the server first', 'system');
            return;
        }

        currentMode = 'phoneCall';
        document.getElementById('initialOptions').style.display = 'none';
        document.getElementById('activeParticipants').style.display = 'block';
        document.getElementById('chatArea').style.display = 'none';
        document.getElementById('roomName').textContent = 'Phone Call';
        
        // Hide forms
        toggleForms(false);
        
        // Initialize with offline participants using 'twilio' as initial identifier
        const initialMembers = [
            { fd: 'twilio', type: 'twilio', name: phoneCallNumber },
            { fd: 'user', type: 'user', name: 'Web User' }
        ];
        updateParticipantsGrid(initialMembers);
    };

    // Initialize assistant dropdowns when the page loads
    populateAssistantDropdowns();

    // Auto-connect after page load
    setTimeout(() => {
        if (!isConnected) {
            console.log('[Client] Auto-connecting to WebSocket...');
            connectWebSocket();
        }
    }, 2000); // 2 second delay

    // Add Bootstrap collapse event listener for icon color change
    document.getElementById('serverInfoBody').addEventListener('show.bs.collapse', function () {
        document.querySelector('[data-bs-toggle="collapse"] i').classList.add('text-info');
    });

    document.getElementById('serverInfoBody').addEventListener('hide.bs.collapse', function () {
        document.querySelector('[data-bs-toggle="collapse"] i').classList.remove('text-info');
    });

    // Add device services tracking
    const deviceServices = new Map();

    // Function to handle device registration
    function handleDeviceRegister(deviceId, capabilities) {
        console.log('[Client] Device registered:', deviceId, capabilities);
        deviceServices.set(deviceId, {
            capabilities,
            status: 'registered',
            lastSeen: Date.now()
        });
        updateDeviceDisplay(deviceId);
    }

    // Function to handle device capabilities update
    function handleDeviceCapabilitiesUpdate(deviceId, capabilities) {
        console.log('[Client] Device capabilities updated:', deviceId, capabilities);
        const device = deviceServices.get(deviceId);
        if (device) {
            device.capabilities = capabilities;
            device.lastSeen = Date.now();
            updateDeviceDisplay(deviceId);
        }
    }

    // Function to handle device status update
    function handleDeviceStatusUpdate(deviceId, status) {
        console.log('[Client] Device status updated:', deviceId, status);
        const device = deviceServices.get(deviceId);
        if (device) {
            device.status = status;
            device.lastSeen = Date.now();
            updateDeviceDisplay(deviceId);
        }
    }

    // Function to handle device media
    function handleDeviceMedia(deviceId, mediaType, data) {
        console.log('[Client] Device media received:', deviceId, mediaType);
        const device = deviceServices.get(deviceId);
        if (!device) return;

        switch (mediaType) {
            case 'audio':
                handleDeviceAudio(deviceId, data);
                break;
            case 'video':
                handleDeviceVideo(deviceId, data);
                break;
            case 'screen':
                handleDeviceScreen(deviceId, data);
                break;
            default:
                console.warn('[Client] Unknown media type:', mediaType);
        }
    }

    // Function to handle device audio
    function handleDeviceAudio(deviceId, data) {
        // Convert base64 to binary data
        const binaryString = window.atob(data);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        
        // Add to audio queue
        audioQueue.push(bytes.buffer);
        processAudioQueue();
    }

    // Function to handle device video
    function handleDeviceVideo(deviceId, data) {
        // Create or get video element for this device
        let videoContainer = document.querySelector(`#device-${deviceId}-video`);
        if (!videoContainer) {
            videoContainer = document.createElement('div');
            videoContainer.id = `device-${deviceId}-video`;
            videoContainer.className = 'device-video-container';
            document.getElementById('deviceVideos').appendChild(videoContainer);
        }

        // Update video source
        const videoElement = videoContainer.querySelector('video') || document.createElement('video');
        videoElement.autoplay = true;
        videoElement.playsInline = true;
        videoElement.src = `data:video/webm;base64,${data}`;
        
        if (!videoContainer.contains(videoElement)) {
            videoContainer.appendChild(videoElement);
        }
    }

    // Function to handle device screen capture
    function handleDeviceScreen(deviceId, data) {
        // Create or get screen container for this device
        let screenContainer = document.querySelector(`#device-${deviceId}-screen`);
        if (!screenContainer) {
            screenContainer = document.createElement('div');
            screenContainer.id = `device-${deviceId}-screen`;
            screenContainer.className = 'device-screen-container';
            document.getElementById('deviceScreens').appendChild(screenContainer);
        }

        // Update screen image
        const imgElement = screenContainer.querySelector('img') || document.createElement('img');
        imgElement.src = `data:image/png;base64,${data}`;
        
        if (!screenContainer.contains(imgElement)) {
            screenContainer.appendChild(imgElement);
        }
    }

    // Function to update device display in UI
    function updateDeviceDisplay(deviceId) {
        const device = deviceServices.get(deviceId);
        if (!device) return;

        let deviceElement = document.querySelector(`#device-${deviceId}`);
        if (!deviceElement) {
            deviceElement = document.createElement('div');
            deviceElement.id = `device-${deviceId}`;
            deviceElement.className = 'device-card';
            document.getElementById('deviceList').appendChild(deviceElement);
        }

        // Update device card content
        deviceElement.innerHTML = `
            <div class="device-header">
                <h4>Device ${deviceId}</h4>
                <span class="device-status ${device.status}">${device.status}</span>
            </div>
            <div class="device-capabilities">
                ${device.capabilities.map(cap => `
                    <span class="capability-badge">${cap}</span>
                `).join('')}
            </div>
            <div class="device-controls">
                ${device.capabilities.includes('audio') ? `
                    <button class="btn btn-sm btn-primary" onclick="sendDeviceCommand('${deviceId}', 'toggle_audio')">
                        Toggle Audio
                    </button>
                ` : ''}
                ${device.capabilities.includes('video') ? `
                    <button class="btn btn-sm btn-primary" onclick="sendDeviceCommand('${deviceId}', 'toggle_video')">
                        Toggle Video
                    </button>
                ` : ''}
                ${device.capabilities.includes('screen') ? `
                    <button class="btn btn-sm btn-primary" onclick="sendDeviceCommand('${deviceId}', 'toggle_screen')">
                        Toggle Screen
                    </button>
                ` : ''}
            </div>
        `;
    }

    // Function to send commands to device
    function sendDeviceCommand(deviceId, command, parameters = {}) {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            console.error('[Client] WebSocket not connected');
            return;
        }

        ws.send(JSON.stringify({
            type: 'device_command',
            device_id: deviceId,
            command: command,
            parameters: parameters
        }));
    }

    // Add this function before the handleWebSocketMessage function
    async function textToSpeech(text) {
        try {
            const response = await fetch('/api/text-to-speech/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: JSON.stringify({
                    text: text,
                    voice: 'alloy',
                    model: 'tts-1'
                })
            });

            if (!response.ok) {
                throw new Error('Text-to-speech request failed');
            }

            // Create audio element
            const audio = new Audio();
            audio.src = URL.createObjectURL(await response.blob());
            await audio.play();
        } catch (error) {
            console.error('Text-to-speech error:', error);
        }
    }

    // Initialize response buffers Map
    const responseBuffers = new Map();

</script>

