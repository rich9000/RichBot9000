<!DOCTYPE html>
<div id="client-dashboard">
    <!-- Status Bar -->
    <div class="status-bar">
        <span class="badge bg-secondary" id="status">Disconnected</span>
        <span id="fd-display"></span>
    </div>

    <!-- Main Content -->
    <div class="connection-panels">
        <!-- Assistants Panel -->
        <div class="panel">
            <h3>Available Assistants</h3>
            <div id="assistants-list" class="list-container"></div>
            </div>

        <!-- Active Chat -->
        <div id="active-connection" class="d-none">
            <div class="chat-container">
                <div class="chat-header">
                    <div>
                        <span id="chat-title" class="chat-title"></span>
                        <small id="connection-details" class="text-muted"></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="autoEnableMic">
                            <label class="form-check-label small" for="autoEnableMic">
                                Auto-enable mic
                            </label>
                        </div>
                        <div class="response-indicator me-3">
                            <i class="fas fa-comment text-muted" id="response-indicator"></i>
                        </div>
                        <div class="audio-status">
                            <span id="mic-status">Mic: Muted</span>
                            <span id="speaker-status">Speaker: Active</span>
                        </div>
                    </div>
                </div>

                <div id="chat-messages"></div>

                <div class="input-area">
                    <div class="input-group">
                        <input type="text" id="message-input" class="form-control" placeholder="Type your message...">
                        <button onclick="sendMessage()" class="btn btn-primary">Send</button>
                    </div>
                    
        <div class="audio-controls">
                        <button id="toggle-mic" class="btn btn-outline-primary" onclick="toggleMic()">
                            <i class="fas fa-microphone"></i>
            </button>
                        <button id="toggle-speaker" class="btn btn-outline-primary" onclick="toggleSpeaker()">
                            <i class="fas fa-volume-up"></i>
            </button>
                        <input type="range" id="pitch-control" min="0.5" max="2" step="0.1" value="1.0" 
                               class="form-range" style="width: 100px;" onchange="setPitchFactor(this.value)">
                        <label for="pitch-control" class="form-label small">Pitch</label>
            </div>




        </div>
            </div>
        </div>
    </div>

    <!-- Debug Log -->
    <div class="accordion" id="debugAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#debugLog">
                    Debug Log
                </button>
            </h2>
            <div id="debugLog" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div id="message-log"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global state
let socket = null;

// Initialize missing appState properties
appState.currentConnection = null;
appState.audio = {
    isMicMuted: true,  // Start with mic muted
    isSpeakerMuted: false,  // Start with speaker on
    autoEnableMic: false,   // Start with auto-enable off
    pitchFactor: 1.0,
    context: null,
    gainNode: null,
    micStream: null,
    micSource: null,
    micProcessor: null,
    mediaHandler: null,
    uploadQueue: [],
    isProcessingQueue: false,
    playbackQueue: [],  // Queue for audio deltas
    isPlaying: false,   // Flag to track if we're currently playing audio
    socket: null        // WebSocket connection
};

class MediaHandler {
    constructor() {
        this.isRecording = false;
        this.processorNode = null;
        this.mediaStream = null;
        this.BUFFER_SIZE = 16384;
        this.audioBuffer = new Float32Array(0);
        this.source = null;
    }

    async startRecording() {
        try {
            // Check if we're in a secure context
            if (!window.isSecureContext) {
                throw new Error('Media devices require a secure context (HTTPS or localhost). Please use HTTPS or access via localhost.');
            }

            // Check if mediaDevices is available
            if (!navigator.mediaDevices) {
                throw new Error('Media devices are not available in this browser. Please ensure you are using HTTPS or localhost.');
            }

            // Ensure any previous recording is fully stopped
            this.stopRecording();

            // Get microphone input at 16kHz
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    sampleRate: 16000,
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true
                }
            });

            // Use the shared AudioContext from appState
            if (!appState.audio.context) {
                appState.audio.context = new (window.AudioContext || window.webkitAudioContext)();
            }

            this.source = appState.audio.context.createMediaStreamSource(stream);
            this.processorNode = appState.audio.context.createScriptProcessor(this.BUFFER_SIZE, 1, 1);

            this.processorNode.onaudioprocess = (event) => {
                if (!this.isRecording || appState.audio.isMicMuted) return;

                const inputData = event.inputBuffer.getChannelData(0);
                
                // Convert to 24kHz using linear interpolation
                const ratio = 24000 / appState.audio.context.sampleRate;
                const newLength = Math.floor(inputData.length * ratio);
                const resampledData = new Float32Array(newLength);

                for (let i = 0; i < newLength; i++) {
                    const position = i / ratio;
                    const index = Math.floor(position);
                    const fraction = position - index;
                    
                    const sample1 = inputData[index] || 0;
                    const sample2 = inputData[Math.min(index + 1, inputData.length - 1)] || 0;
                    
                    resampledData[i] = sample1 + fraction * (sample2 - sample1);
                }

                // Append to buffer
                const newBuffer = new Float32Array(this.audioBuffer.length + resampledData.length);
                newBuffer.set(this.audioBuffer);
                newBuffer.set(resampledData, this.audioBuffer.length);
                this.audioBuffer = newBuffer;

                // Only send when we have accumulated enough data and mic is not muted
                if (this.audioBuffer.length >= 48000 && !appState.audio.isMicMuted) {
                    // Convert to PCM16
                    const pcmData = new Int16Array(this.audioBuffer.length);
                    for (let i = 0; i < this.audioBuffer.length; i++) {
                        const s = Math.max(-1, Math.min(1, this.audioBuffer[i]));
                        pcmData[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
                    }

                    // Convert to base64 string
                    const base64Audio = btoa(String.fromCharCode.apply(null, new Uint8Array(pcmData.buffer)));

                    // Only send if socket is ready and mic is not muted
                    if (appState.audio.socket?.readyState === WebSocket.OPEN && !appState.audio.isMicMuted) {
                        appState.audio.socket.send(JSON.stringify({
                            event: 'message',
                            type: 'audio',
                            data: {
                                audio: base64Audio,
                                timestamp: Date.now(),
                                format: 'pcm16',
                                sampleRate: 24000
                            }
                        }));

                        // Log occasionally
                        if (Math.random() < 0.1) {
                            console.log('Audio processed:', {
                                inputSamples: inputData.length,
                                outputSamples: pcmData.length,
                                sampleRate: `${appState.audio.context.sampleRate}Hz -> 24kHz`,
                                min: Math.min(...pcmData),
                                max: Math.max(...pcmData)
                            });
                        }
                    }

                    // Reset buffer
                    this.audioBuffer = new Float32Array(0);
                }
            };

            this.source.connect(this.processorNode);
            this.processorNode.connect(appState.audio.context.destination);
            this.mediaStream = stream;
            this.isRecording = true;

            console.log('Started recording at native sample rate, resampling to 24kHz');
            return true;
        } catch (error) {
            console.error('Failed to start recording:', error);
            this.cleanup();
            return false;
        }
    }

    stopRecording() {
        this.isRecording = false;
        this.cleanup();
    }

    cleanup() {
        // Stop all tracks in the media stream
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => {
                track.stop();
                this.mediaStream.removeTrack(track);
            });
        }

        // Disconnect and cleanup audio nodes
        if (this.source) {
            this.source.disconnect();
            this.source = null;
        }

        if (this.processorNode) {
            this.processorNode.disconnect();
            this.processorNode = null;
        }

        // Clear references
        this.mediaStream = null;
        this.audioBuffer = new Float32Array(0);
        
        // Log cleanup
        console.log('Audio resources cleaned up');
    }
}

async function initializeAudio() {
    try {
        if (!navigator.mediaDevices) {
            logMessage('MediaDevices API not available - this browser might not be secure or lacks permission', 'error');
            return false;
        }

        // Create MediaHandler if it doesn't exist
        if (!appState.audio.mediaHandler) {
            appState.audio.mediaHandler = new MediaHandler();
        }

        logMessage('Audio system initialized successfully', 'info');
        return true;

    } catch (error) {
        logMessage(`Audio setup error: ${error.message}`, 'error');
        console.error('Audio setup error:', error);
        return false;
    }
}

// Initialize the assistants list
async function loadAssistants() {
    try {
        // Load available assistants from ollama endpoint
        const response = await fetch('https://richbot9000.local/api/ollama_assistants', {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        appState.data = data;
        updateAssistantsList();
    } catch (error) {
        logMessage(`Failed to load assistants: ${error.message}`, 'error');
    }
}

// Initialize the client
async function initializeClient() {
    logMessage('Initializing client...', 'info');
    await loadAssistants();
}

// Connect to assistant
async function connectToAssistant(assistantId) {
    logMessage(`Connecting to assistant: ${assistantId}`, 'info');
    
    // Initialize audio first but don't start recording
    const audioInitialized = await initializeAudio();
    if (audioInitialized) {
        // Start with mic muted
        appState.audio.isMicMuted = true;
        updateAudioStatus('mic', 'muted');
        updateAudioStatus('speaker', 'active');
        
        // Initialize mic button state
        const micButton = document.getElementById('toggle-mic');
        if (micButton) {
            micButton.classList.remove('btn-primary');
            micButton.classList.add('btn-outline-primary');
            micButton.querySelector('i').classList.remove('audio-active');
            micButton.setAttribute('data-toggled', 'false'); // Track if mic has been toggled
        }
        
        // Don't start recording yet - we'll do that when unmuted
        appState.audio.mediaHandler = new MediaHandler();
    } else {
        logMessage('Chat will proceed in text-only mode. Click the microphone icon to try audio setup again.', 'info');
    }

    // Create WebSocket connection
    if (!appState.audio.socket || appState.audio.socket.readyState !== WebSocket.OPEN) {
        const wsUrl = `wss://richbot9000.local:9501/app/${encodeURIComponent(appState.apiToken)}${assistantId ? '/' + encodeURIComponent(assistantId) : ''}`;
        
        logMessage(`Connecting to WebSocket: ${wsUrl}`, 'info');
        appState.audio.socket = new WebSocket(wsUrl);
        setupSocketListeners();

        // Send start chat message once connected
        appState.audio.socket.addEventListener('open', () => {
            const startChatMessage = {
                type: 'start_chat',
                assistant_id: assistantId
            };
            
            console.log('🟢 Starting chat:', startChatMessage);
            logMessage('WebSocket connected, starting chat', 'info');
            appState.audio.socket.send(JSON.stringify(startChatMessage));
        });
    }
}

function toggleMic() {
    const micButton = document.getElementById('toggle-mic');
    const hasBeenToggled = micButton.getAttribute('data-toggled') === 'true';
    
    if (!hasBeenToggled) {
        micButton.setAttribute('data-toggled', 'true');
    }
    
    appState.audio.isMicMuted = !appState.audio.isMicMuted;
    updateAudioStatus('mic', appState.audio.isMicMuted ? 'muted' : 'active');
    
    logMessage(`Toggling mic: ${appState.audio.isMicMuted ? 'muted' : 'unmuted'}`, 'info');
    
    if (!appState.audio.isMicMuted) {
        // Only start recording when unmuted
        if (!appState.audio.mediaHandler) {
            logMessage('Creating new MediaHandler', 'info');
            appState.audio.mediaHandler = new MediaHandler();
        }
        
        if (!appState.audio.mediaHandler.isRecording) {
            logMessage('Starting audio recording...', 'info');
            appState.audio.mediaHandler.startRecording()
                .then(success => {
                    if (success) {
                        logMessage('Audio recording started successfully', 'info');
                    } else {
                        logMessage('Failed to start audio recording', 'error');
                        // Reset mic state if recording fails
                        appState.audio.isMicMuted = true;
                        updateAudioStatus('mic', 'muted');
                    }
                })
                .catch(error => {
                    logMessage(`Error starting audio recording: ${error.message}`, 'error');
                    // Reset mic state on error
                    appState.audio.isMicMuted = true;
                    updateAudioStatus('mic', 'muted');
                });
        }
    } else {
        // Stop recording when muted
        if (appState.audio.mediaHandler) {
            logMessage('Stopping audio recording...', 'info');
            appState.audio.mediaHandler.stopRecording();
            // Clear the media handler to ensure complete cleanup
            appState.audio.mediaHandler = null;
        }
    }
}

function toggleSpeaker() {
    appState.audio.isSpeakerMuted = !appState.audio.isSpeakerMuted;
    updateAudioStatus('speaker', appState.audio.isSpeakerMuted ? 'muted' : 'active');
    
    if (appState.audio.gainNode) {
        appState.audio.gainNode.gain.value = appState.audio.isSpeakerMuted ? 0 : 1;
    }
}

// Update socket listeners to use local socket variable
function setupSocketListeners() {
    logMessage('Setting up socket listeners', 'info');
    
    appState.audio.socket.addEventListener('open', () => {
        logMessage('WebSocket connection established', 'info');
        updateStatus('Connected');
    });

    appState.audio.socket.addEventListener('message', (event) => {
        try {


            console.log('📥 Received (message event):', event);

            const data = JSON.parse(event.data);
            // Don't log media messages to avoid cluttering the log
       //     if (!(data.event?.includes('media') || data.type?.includes('audio'))) {
                console.log('📥 Received:', data);
          //  }
          handleMessage(event);
            //handleSocketMessage(data);  // Pass the parsed data directly
        } catch (error) {
            logMessage(`Error parsing WebSocket message: ${error.message}`, 'error');
            console.error('WebSocket message error:', error);
        }
    });
    
    appState.audio.socket.addEventListener('close', (event) => {
        logMessage(`WebSocket closed with code: ${event.code}, reason: ${event.reason || 'No reason provided'}`, 'error');
        appState.audio.socket = null; // Clear the socket reference
        handleSocketClose();
    });
    
    appState.audio.socket.addEventListener('error', (error) => {
        logMessage(`WebSocket error: ${error.message || 'Connection error'}`, 'error');
        console.error('WebSocket error:', error);
    });
}

async function setupAudioStream() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Audio input not supported');
        }

        appState.audio.micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Create and load the audio worklet
        await audioContext.audioWorklet.addModule('/webapp_public/audio-processor.js');
        
        appState.audio.micSource = audioContext.createMediaStreamSource(appState.audio.micStream);
        appState.audio.micProcessor = new AudioWorkletNode(audioContext, 'audio-processor');
        
        appState.audio.micSource.connect(appState.audio.micProcessor);
        appState.audio.micProcessor.connect(audioContext.destination);
        
        // Handle audio data from the worklet
        appState.audio.micProcessor.port.onmessage = (event) => {
            if (!appState.audio.isMicMuted && appState.currentConnection) {
                appState.audio.socket.send(JSON.stringify({
                    event: 'media',
                    type: 'audio',
                    data: event.data,
                    chat_id: appState.currentConnection.chatId
                }));
            }
        };
        
        return true;
    } catch (error) {
        logMessage(`Audio setup error: ${error.message}`, 'error');
        return false;
    }
}

// Add new function to handle audio queue
async function processAudioQueue() {
    if (appState.audio.isPlaying || appState.audio.playbackQueue.length === 0) {
        return;
    }

    appState.audio.isPlaying = true;
    const audioData = appState.audio.playbackQueue.shift();

    try {
        if (!appState.audio.context) {
            appState.audio.context = new (window.AudioContext || window.webkitAudioContext)();
            appState.audio.gainNode = appState.audio.context.createGain();
            appState.audio.gainNode.connect(appState.audio.context.destination);
        }

        // Create WAV header for 24kHz mono PCM
        const wavHeader = new ArrayBuffer(44);
        const view = new DataView(wavHeader);
        
        // "RIFF" chunk descriptor
        view.setUint32(0, 0x52494646, false); // "RIFF"
        view.setUint32(4, 36 + audioData.length * 2, true); // File size
        view.setUint32(8, 0x57415645, false); // "WAVE"
        
        // "fmt " sub-chunk
        view.setUint32(12, 0x666D7420, false); // "fmt "
        view.setUint32(16, 16, true); // Subchunk1Size (16 for PCM)
        view.setUint16(20, 1, true); // AudioFormat (1 for PCM)
        view.setUint16(22, 1, true); // NumChannels (1 for mono)
        view.setUint32(24, 24000, true); // SampleRate (24kHz)
        view.setUint32(28, 24000 * 2, true); // ByteRate
        view.setUint16(32, 2, true); // BlockAlign
        view.setUint16(34, 16, true); // BitsPerSample (16)
        
        // "data" sub-chunk
        view.setUint32(36, 0x64617461, false); // "data"
        view.setUint32(40, audioData.length * 2, true); // Subchunk2Size
        
        // Convert base64 to binary data
        const binaryString = atob(audioData);
        const audioArray = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            audioArray[i] = binaryString.charCodeAt(i);
        }
        
        // Combine WAV header with audio data
        const completeAudio = new Uint8Array(wavHeader.byteLength + audioArray.length);
        completeAudio.set(new Uint8Array(wavHeader), 0);
        completeAudio.set(audioArray, wavHeader.byteLength);
        
        // Decode and play
        const audioBuffer = await appState.audio.context.decodeAudioData(completeAudio.buffer);
        const source = appState.audio.context.createBufferSource();
        source.buffer = audioBuffer;
        source.playbackRate.value = appState.audio.pitchFactor;
        
        source.connect(appState.audio.gainNode);
        
        // Handle the end of this audio chunk
        source.onended = () => {
            appState.audio.isPlaying = false;
            processAudioQueue(); // Process next chunk if available
        };
        
        source.start(0);
        
    } catch (error) {
        logMessage(`Audio playback error: ${error.message}`, 'error');
        console.error('Audio playback error:', error);
        appState.audio.isPlaying = false;
        processAudioQueue(); // Try next chunk even if this one failed
    }
}

// Update the playAudioMessage function to use the queue
function playAudioMessage(audioData) {
    // Add to queue
    appState.audio.playbackQueue.push(audioData);
    
    // Start processing if not already playing
    if (!appState.audio.isPlaying) {
        processAudioQueue();
    }
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (message && appState.audio.socket?.readyState === WebSocket.OPEN) {
        // Stop any playing audio first
        stopAllAudio();
        
        const messageData = {
            type: 'text',
            data: {
                content: message,
                timestamp: Date.now()
            }
        };
        
        console.log('🔵 Sending message:', messageData);
        logMessage(`Sending message: ${message}`, 'debug');
        
        try {
            appState.audio.socket.send(JSON.stringify(messageData));
            input.value = '';
            
            // Add message to chat immediately for better UX
            addMessageToChat('You', message, 'text', 'sent');
        } catch (error) {
            logMessage(`Failed to send message: ${error.message}`, 'error');
        }
    }
}

// Add event listener for Enter key in message input
document.getElementById('message-input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        sendMessage();
    }
});

function updateAssistantsList() {
    const assistantsList = document.getElementById('assistants-list');
    assistantsList.innerHTML = '';

    if (!appState.data || !appState.data.assistants) {
        logMessage('No assistants data available', 'error');
        return;
    }

    logMessage(`Received ${appState.data.assistants.length} assistants`, 'info');

    appState.data.assistants.forEach(assistant => {
        if (assistant.interactive !== false) {  // Only show interactive assistants
            logMessage(`Processing assistant: ${assistant.id} - ${assistant.name}`, 'debug');
        const div = document.createElement('div');
            div.className = 'assistant-card';
            
            // Create capabilities badges based on tools
            const capabilities = [];
            if (assistant.tools.some(tool => tool.name === 'sms_rich' || tool.name === 'email_rich')) {
                capabilities.push('Communication');
            }
            if (assistant.tools.some(tool => tool.name === 'update_display')) {
                capabilities.push('Interactive');
            }
            capabilities.push('Text Chat'); // All assistants support text chat

        div.innerHTML = `
            <div class="assistant-header">
                    <span class="assistant-name">${assistant.name}</span>
                    <span class="model-badge">${assistant.model?.name || 'Unknown'}</span>
            </div>
                <div class="assistant-description">${assistant.system_message?.split('\n')[0] || 'No description available'}</div>
                <div class="capabilities">
                    ${capabilities.map(cap => `
                        <span class="capability-badge">
                            <i class="fas fa-${cap === 'Communication' ? 'comment-dots' : 
                                           cap === 'Interactive' ? 'desktop' : 
                                           'comment'}"></i>
                            ${cap}
                        </span>
                    `).join('')}
                    </div>
                <div class="d-flex justify-content-end">
                    <button onclick="connectToAssistant('${assistant.id}')" class="btn btn-primary btn-sm">
                        <i class="fas fa-plug me-1"></i>Connect
                </button>
                </div>
        `;
        assistantsList.appendChild(div);
        }
    });
}

function addMessageToChat(sender, content, type = 'text', messageType = 'received', isDelta = false) {
    const chatMessages = document.getElementById('chat-messages');
    
    // For deltas, try to append to the last message if it's from the same sender
    if (isDelta && chatMessages.lastElementChild) {
        const lastMessage = chatMessages.lastElementChild;
        if (lastMessage.classList.contains(messageType)) {
            const contentDiv = lastMessage.querySelector('.content');
            if (contentDiv) {
                contentDiv.textContent += content;
                chatMessages.scrollTop = chatMessages.scrollHeight;
                return;
            }
        }
    }
    
    // Create new message if not a delta or couldn't append
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${messageType}`;
    
    let messageContent = content;
    if (type === 'audio') {
        messageContent = `<i class="fas fa-microphone text-muted"></i> ${content}`;
    }

    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="sender small text-muted mb-1">${sender}</div>
            <div class="content">${messageContent}</div>
        </div>
    `;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    logMessage(`Added ${type} message from ${sender} to chat`, 'debug');
}

function updateAudioStatus(type, status) {
    const element = document.getElementById(`${type}-status`);
    const icon = document.querySelector(`#toggle-${type} i`);
    const button = document.getElementById(`toggle-${type}`);
    
    if (status === 'active') {
        element.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)}: Active`;
        icon.classList.add('audio-active');
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
    } else {
        element.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)}: Muted`;
        icon.classList.remove('audio-active');
        button.classList.add('btn-outline-primary');
        button.classList.remove('btn-primary');
    }
}

function showChat() {
    const activeConnection = document.getElementById('active-connection');
    activeConnection.classList.remove('d-none');
    activeConnection.style.display = 'block';
    document.querySelector('.panel').style.display = 'none';
}

function hideChat() {
    const activeConnection = document.getElementById('active-connection');
    activeConnection.classList.add('d-none');
    activeConnection.style.display = 'none';
    document.querySelector('.panel').style.display = 'block';
    document.getElementById('chat-messages').innerHTML = '';
}

function handleChatStarted(data) {
    appState.currentConnection = {
        chatId: data.chatId,
        assistantId: data.assistantId,
        assistantName: data.assistantName
    };

    // Update UI
    document.getElementById('chat-title').textContent = `${data.assistantName} Chat`;
    document.getElementById('connection-details').textContent = `Chat ID: ${data.chatId}`;
    showChat();
    updateStatus('In Chat');
}

function handleChatEnded() {
    if (appState.audio.micStream) {
        appState.audio.micStream.getTracks().forEach(track => track.stop());
        appState.audio.micStream = null;
    }
    
    hideChat();
    // Show assistants panel again
    document.querySelector('.panel').style.display = 'block';
        updateStatus('Connected');
    appState.currentConnection = null;
}   

function updateStatus(status) {
    const statusElement = document.getElementById('status');
    statusElement.textContent = status;
    
    // Update badge color based on status
    statusElement.className = 'badge ' + 
        (status === 'Connected' ? 'bg-success' : 
         status === 'In Chat' ? 'bg-primary' : 
         'bg-secondary');
}

function logMessage(message, type = 'info') {
    const logContainer = document.getElementById('message-log');
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.innerHTML = `
        <span class="timestamp">${new Date().toLocaleTimeString()}</span>
        <span class="type">[${type.toUpperCase()}]</span>
        <span class="message">${message}</span>
    `;
    logContainer.appendChild(entry);
    logContainer.scrollTop = logContainer.scrollHeight;
}

function handleSocketClose() {
    updateStatus('Disconnected');
    logMessage('WebSocket connection closed', 'error');
    
    // Clean up existing connection
    if (appState.currentConnection) {
        handleChatEnded();
    }
    
    // Attempt to reconnect after a delay
    setTimeout(() => {
        if (!appState.audio.socket || appState.audio.socket.readyState === WebSocket.CLOSED) {
            logMessage('Attempting to reconnect...', 'info');
            initializeClient();
        }
    }, 5000);
}

function setPitchFactor(value) {
    appState.audio.pitchFactor = parseFloat(value);
    logMessage(`Pitch factor set to: ${value}`, 'info');
}

// Initialize immediately since DOM is already loaded
initializeClient();

// Socket message handler
function handleSocketMessage(data) {
    try {


console.log('🔵 Handling socket message:', data,data.type);

        switch (data.type) {
            case 'status':
                switch (data.status) {
                    case 'connected':
                        updateStatus('Connected');
                        logMessage('Connected to server', 'info');
                        break;
                    case 'chat_ready':
                        showChat();
                        updateStatus('In Chat');
                        logMessage('Chat session ready', 'info');
                        break;
                }
                break;

            case 'text':
                // Add text message to chat
                addMessageToChat(data.sender === 'assistant' ? 'Assistant' : 'You', 
                              data.content, 'text', 
                              data.sender === 'assistant' ? 'received' : 'sent');
                break;

            case 'audio':
                // Play audio if speaker is not muted
                if (!appState.audio.isSpeakerMuted) {
                    audioHandler.processAudioMessage(data);
                }
                // If there's a transcript, show it
                if (data.transcript) {
                    addMessageToChat('Assistant', data.transcript, 'text', 'received');
                }
                break;

            case 'error':
                logMessage(`Error: ${data.message}`, 'error');
                const chatMessages = document.getElementById('chat-messages');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = data.message;
                chatMessages.appendChild(errorDiv);
                break;

            default:
                logMessage(`Unhandled message type: ${data.type}`, 'debug');
        }
    } catch (error) {
        logMessage(`Message handling error: ${error.message}`, 'error');
        console.error('Message handling error:', error);
    }
}

// Chat event handlers
function handleChatStarted(data) {
    appState.currentConnection = {
        chatId: data.chatId,
        assistantId: data.assistantId,
        assistantName: data.assistantName
    };

    // Update UI
    document.getElementById('chat-title').textContent = `${data.assistantName} Chat`;
    document.getElementById('connection-details').textContent = `Chat ID: ${data.chatId}`;
    document.getElementById('active-connection').classList.remove('d-none');
    document.getElementById('status').textContent = 'Connected';
    document.getElementById('status').className = 'badge bg-success';

    // Hide assistants list
    document.querySelector('.panel').style.display = 'none';
}

function handleChatEnded(data) {
    // Clean up audio
    if (appState.audio.mediaHandler) {
        appState.audio.mediaHandler.stopRecording('audio');
    }

    // Reset connection state
    appState.currentConnection = null;

    // Update UI
    document.getElementById('active-connection').classList.add('d-none');
    document.getElementById('status').textContent = 'Disconnected';
    document.getElementById('status').className = 'badge bg-secondary';
    document.querySelector('.panel').style.display = 'block';

    logMessage('Chat ended', 'info');
}

async function initializeAudio() {
    try {
        if (!navigator.mediaDevices) {
            logMessage('MediaDevices API not available - this browser might not be secure or lacks permission', 'error');
            return false;
        }

        // Create MediaHandler if it doesn't exist
        if (!appState.audio.mediaHandler) {
            appState.audio.mediaHandler = new MediaHandler();
        }

        // Initialize audio context for playback
        appState.audio.context = new (window.AudioContext || window.webkitAudioContext)();
        appState.audio.gainNode = appState.audio.context.createGain();
        appState.audio.gainNode.connect(appState.audio.context.destination);

        logMessage('Audio system initialized successfully', 'info');
        return true;

    } catch (error) {
        logMessage(`Audio setup error: ${error.message}`, 'error');
        console.error('Audio setup error:', error);
        return false;
    }
}

function reconnectToAssistant() {
    if (!appState.currentConnection) {
        logMessage('No active connection to reconnect', 'error');
        return;
    }

    logMessage('Attempting to reconnect...', 'info');
    
    // Close existing connection if any
    if (appState.audio.socket && appState.audio.socket.readyState === WebSocket.OPEN) {
        appState.audio.socket.close();
    }

    // Reconnect with same assistant
    connectToAssistant(appState.currentConnection.assistantId);
}

// Update initial UI state for mic

    const micButton = document.getElementById('toggle-mic');
    const micStatus = document.getElementById('mic-status');
    if (micButton) {
        micButton.classList.remove('btn-outline-primary');
        micButton.classList.add('btn-outline-danger');
        micButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
    }
    if (micStatus) {
        micStatus.textContent = 'Mic: Muted';
    }


// Audio handling
class AudioHandler {
    constructor() {
        // Use the shared AudioContext from appState
        this.audioContext = appState.audio.context;
        this.gainNode = appState.audio.gainNode;
        this.audioQueue = [];
        this.isPlaying = false;
        this.currentBuffer = null;
        this.currentResponse = null;
    }

    // Convert g711_ulaw to PCM
    ulaw2linear(ulawByte) {
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

    async processAudioMessage(message) {
        try {
            // Track current response
            if (this.currentResponse !== message.response_id) {
                this.currentResponse = message.response_id;
                this.currentBuffer = null;
            }

            // Decode base64 to bytes
            const rawData = atob(message.data);
            const bytes = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; i++) {
                bytes[i] = rawData.charCodeAt(i);
            }

            // Convert ulaw to PCM
            const pcmData = new Int16Array(bytes.length);
            for (let i = 0; i < bytes.length; i++) {
                pcmData[i] = this.ulaw2linear(bytes[i]);
            }

            // Create audio buffer (g711_ulaw is always 8kHz mono)
            const audioBuffer = this.audioContext.createBuffer(1, pcmData.length, 8000);
            const channelData = audioBuffer.getChannelData(0);
            
            // Convert Int16 to Float32 (-1.0 to 1.0)
            for (let i = 0; i < pcmData.length; i++) {
                channelData[i] = pcmData[i] / 32768.0;
            }

            // Queue the audio
            this.audioQueue.push(audioBuffer);
            
            // Start playing if not already playing
            if (!this.isPlaying) {
                await this.playNextInQueue();
            }
        } catch (error) {
            console.error('Error processing audio:', error);
            logMessage(`Audio processing error: ${error.message}`, 'error');
        }
    }

    async playNextInQueue() {
        if (this.audioQueue.length === 0) {
            this.isPlaying = false;
            return;
        }

        this.isPlaying = true;
        const audioBuffer = this.audioQueue.shift();

        return new Promise((resolve) => {
            const source = this.audioContext.createBufferSource();
            source.buffer = audioBuffer;
            
            // Apply pitch control if set
            if (appState.audio.pitchFactor !== 1.0) {
                source.playbackRate.value = appState.audio.pitchFactor;
            }

            // Connect through the shared gain node
            source.connect(this.gainNode);
            
            source.onended = () => {
                this.playNextInQueue();
                resolve();
            };
            
            source.start(0);
        });
    }
}

// Initialize audio handler
let audioHandler = null;
initializeAudio().then(() => {
    audioHandler = new AudioHandler();
});


/*
// WebSocket message handling
socket.onmessage = async function(event) {
    const message = JSON.parse(event.data);
    console.log('📥 Received: ', message);

    switch (message.type) {
        case 'audio':
            await audioHandler.processAudioMessage(message);
            break;
        // ... rest of the message handling
    }
};
*/
// State management
let currentResponseId = null;
let audioBuffers = new Map(); // Store audio chunks by response_id
let transcripts = new Map();  // Store transcripts by response_id
let isAssistantResponding = false;

function updateConnectionStatus(data) {
    const statusEl = document.getElementById('status');
    if (statusEl) {
        const isConnected = data.session_id && data.model;
        statusEl.textContent = isConnected ? 'Connected' : 'Disconnected';
        statusEl.className = `badge ${isConnected ? 'bg-success' : 'bg-secondary'}`;
        
        if (isConnected) {
            showChat();
        }
    }
}

function handleSessionUpdate(data) {
    console.log('Session update:', data);
    updateConnectionStatus(data);
}

function showChat() {
    const activeConnection = document.getElementById('active-connection');
    if (activeConnection) {
        activeConnection.classList.remove('d-none');
        activeConnection.style.display = 'block';
        
        // Hide assistants list
        const panel = document.querySelector('.panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }
}

// Keep track of transcripts by response ID
const transcriptBuffers = new Map();

function handleMessage(event) {
    try {
        const data = JSON.parse(event.data);
        console.log('📥 Received message:', data);

        switch (data.type) {
            case 'assistant_text_delta':
                // Handle incremental text updates
                addMessageToChat('Assistant', data.data.delta, 'text', 'received', true);
                break;

            case 'assistant_audio_delta':
                // Queue audio chunk for playback
                if (!appState.audio.isSpeakerMuted) {
                    playAudioMessage(data.data.delta);
                }
                // Create or update message container
                const container = createMessageContainer(data.data.response_id);
                const audioContainer = container.querySelector('.audio-container');
                if (audioContainer) {
                    const playButton = audioContainer.querySelector('.play-button');
                    const audioStatus = audioContainer.querySelector('.audio-status');
                    playButton.classList.remove('d-none');
                    audioStatus.textContent = 'Receiving audio...';
                }
                break;

            case 'assistant_audio_transcript_delta':
                // Handle incremental transcript updates
                const transcriptDelta = data.data.delta;
                if (!transcriptBuffers.has(data.data.response_id)) {
                    transcriptBuffers.set(data.data.response_id, '');
                }
                const currentTranscript = transcriptBuffers.get(data.data.response_id) + transcriptDelta;
                transcriptBuffers.set(data.data.response_id, currentTranscript);
                updateTranscriptDisplay(data.data.response_id, currentTranscript);
                break;

            case 'assistant_audio_transcript':
                // Handle complete transcript
                const transcript = data.data.transcript;
                transcriptBuffers.set(data.data.response_id, transcript);
                updateTranscriptDisplay(data.data.response_id, transcript);
                break;

            case 'assistant_response_complete':
                // Handle response completion
                handleResponseComplete(data.data);
                break;

            case 'response.created':
                // Handle new response creation
                handleResponseCreated(data.data);
                break;

            case 'error':
                // Handle errors
                logMessage(`Error from server: ${data.data.error}`, 'error');
                break;

            case 'session_update':
                // Handle session updates
                handleSessionUpdate(data.data);
                break;

            case 'conversation_update':
                // Handle conversation updates
                handleConversationUpdate(data.data);
                break;


            case 'function_call_output':
                handleFunctionCallOutput(data.data);
                break;

            case 'function_call':
                handleFunctionCall(data.data);
                break;

            default:
                console.log('Unhandled message type:', data.type,data);
                break;
        }
    } catch (error) {
        console.error('Error handling message:', error);
        logMessage(`Error handling message: ${error.message}`, 'error');
    }
}

function updateTranscriptDisplay(responseId, text) {
    const container = createMessageContainer(responseId);
    if (container) {
        const transcriptArea = container.querySelector('.transcript-area');
        if (transcriptArea) {
            transcriptArea.textContent = text;
            
            // Scroll into view if near bottom
            const messagesContainer = document.getElementById('chat-messages');
            const isNearBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 100;
            if (isNearBottom) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
    }
}

function handleResponseCreated(data) {
    console.log('Response created:', data);
    currentResponseId = data.response_id;
    isAssistantResponding = true;
    updateMicState(false); // Disable mic while assistant is responding
    
    // Show response indicator
    const indicator = document.getElementById('response-indicator');
    if (indicator) {
        indicator.classList.add('active');
    }
}

function handleConversationUpdate(data) {
    console.log('Conversation update:', data);
    // Create or update message container for this response
    if (data.item && data.item.role === 'assistant') {
        createMessageContainer(currentResponseId);
    }
}

// G.711 u-law to linear PCM conversion
function ulaw2linear(ulawByte) {
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

function playAudioChunk(pcmData) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffer = audioContext.createBuffer(1, pcmData.length, 24000);
    const channelData = audioBuffer.getChannelData(0);
    
    // Convert Int16 to Float32 (-1.0 to 1.0)
    for (let i = 0; i < pcmData.length; i++) {
        channelData[i] = pcmData[i] / 32768.0;
    }
    
    const source = audioContext.createBufferSource();
    source.buffer = audioBuffer;
    source.connect(audioContext.destination);
    source.start(0);
}

function handleAudioDelta(data) {
    console.log('Received audio delta for response:', data.response_id);
    
    // Create or get container
    const container = createMessageContainer(data.response_id);
    const audioContainer = container.querySelector('.audio-container');
    const playButton = audioContainer.querySelector('.play-button');
    const audioStatus = audioContainer.querySelector('.audio-status');
    
    // Show play button if hidden
    playButton.classList.remove('d-none');
    
    // Store audio chunk
    if (!audioBuffers.has(data.response_id)) {
        audioBuffers.set(data.response_id, []);
        audioStatus.textContent = 'Receiving audio...';
    }
    
    // Store for complete audio
    audioBuffers.get(data.response_id).push(data.delta);
    
    // Try to play chunk immediately if not muted
    if (!appState.audio.isSpeakerMuted) {
        try {
            playAudioMessage(data.delta);
        } catch (error) {
            console.error('Error playing audio chunk:', error);
        }
    }
}

function handleResponseComplete(data) {
    console.log('Response complete:', data);
    isAssistantResponding = false;
    
    // Only re-enable mic if auto-enable is checked
    const autoEnableMic = document.getElementById('autoEnableMic');
    if (autoEnableMic && autoEnableMic.checked) {
        updateMicState(true);
    }
    
    // Hide response indicator
    const indicator = document.getElementById('response-indicator');
    if (indicator) {
        indicator.classList.remove('active');
    }
    
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`);
    if (container) {
        const audioStatus = container.querySelector('.audio-status');
        if (audioStatus) {
            audioStatus.textContent = 'Audio ready';
        }
        
        // Enable play button for complete audio
        if (audioBuffers.has(data.response_id)) {
            const playButton = container.querySelector('.play-button');
            const stopButton = container.querySelector('.stop-button');
            if (playButton && stopButton) {
                playButton.classList.remove('d-none');
                stopButton.classList.remove('d-none');
                
                playButton.onclick = () => {
                    // Stop any other playing audio first
                    stopAllAudio();
                    
                    // Update button states
                    playButton.classList.remove('btn-outline-secondary');
                    playButton.classList.add('btn-success');
                    playButton.querySelector('i').className = 'fas fa-pause';
                    stopButton.classList.remove('d-none');
                    
                    const audioChunks = audioBuffers.get(data.response_id);
                    audioChunks.forEach(chunk => playAudioMessage(chunk));
                };
                
                stopButton.onclick = () => {
                    stopAllAudio();
                };
            }
        }
    }
}

function updateMicState(enabled) {
    appState.audio.isMicMuted = !enabled;
    updateAudioStatus('mic', enabled ? 'active' : 'muted');
}

// Remove any duplicate event listeners and keep only one handleMessage
//appState.audio.socket.removeEventListener('message', handleMessage);
//appState.audio.socket.addEventListener('message', handleMessage);

function createAudioBlob(audioChunks) {
    // Convert base64 to binary data
    const binaryString = atob(audioChunks.join(''));
    const audioArray = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
        audioArray[i] = binaryString.charCodeAt(i);
    }
    
    // Create WAV header
    const wavHeader = new ArrayBuffer(44);
    const view = new DataView(wavHeader);
    
    // "RIFF" chunk descriptor
    view.setUint32(0, 0x52494646, false); // "RIFF"
    view.setUint32(4, 36 + audioArray.length * 2, true); // File size
    view.setUint32(8, 0x57415645, false); // "WAVE"
    
    // "fmt " sub-chunk
    view.setUint32(12, 0x666D7420, false); // "fmt "
    view.setUint32(16, 16, true); // Subchunk1Size (16 for PCM)
    view.setUint16(20, 1, true); // AudioFormat (1 for PCM)
    view.setUint16(22, 1, true); // NumChannels (1 for mono)
    view.setUint32(24, 24000, true); // SampleRate (24kHz)
    view.setUint32(28, 24000 * 2, true); // ByteRate
    view.setUint16(32, 2, true); // BlockAlign
    view.setUint16(34, 16, true); // BitsPerSample (16)
    
    // "data" sub-chunk
    view.setUint32(36, 0x64617461, false); // "data"
    view.setUint32(40, audioArray.length * 2, true); // Subchunk2Size
    
    // Combine WAV header with audio data
    const completeAudio = new Uint8Array(wavHeader.byteLength + audioArray.length);
    completeAudio.set(new Uint8Array(wavHeader), 0);
    completeAudio.set(audioArray, wavHeader.byteLength);
    
    return new Blob([completeAudio], { type: 'audio/wav' });
}

function addWavHeader(samples) {
    const wavHeader = new ArrayBuffer(44);
    const view = new DataView(wavHeader);
    
    // RIFF identifier
    writeString(view, 0, 'RIFF');
    // file length
    view.setUint32(4, 32 + samples.length * 2, true);
    // RIFF type
    writeString(view, 8, 'WAVE');
    // format chunk identifier
    writeString(view, 12, 'fmt ');
    // format chunk length
    view.setUint32(16, 16, true);
    // sample format (raw)
    view.setUint16(20, 1, true);
    // channel count
    view.setUint16(22, 1, true);
    // sample rate (changed to 24000 to match incoming audio)
    view.setUint32(24, 24000, true);
    // byte rate (sample rate * block align)
    view.setUint32(28, 24000 * 2, true);
    // block align
    view.setUint16(32, 2, true);
    // bits per sample
    view.setUint16(34, 16, true);
    // data chunk identifier
    writeString(view, 36, 'data');
    // data chunk length
    view.setUint32(40, samples.length * 2, true);
    
    const combinedBuffer = new Uint8Array(wavHeader.byteLength + samples.length);
    combinedBuffer.set(new Uint8Array(wavHeader), 0);
    combinedBuffer.set(samples, wavHeader.byteLength);
    
    return combinedBuffer;
}

function writeString(view, offset, string) {
    for (let i = 0; i < string.length; i++) {
        view.setUint8(offset + i, string.charCodeAt(i));
    }
}

function addAudioPlayerToMessage(responseId, audioUrl) {
    const container = document.querySelector(`[data-response-id="${responseId}"]`);
    if (!container) {
        console.error('Message container not found for response:', responseId);
        return;
    }

    const audioContainer = container.querySelector('.audio-container');
    if (audioContainer) {
        const audioPlayer = document.createElement('div');
        audioPlayer.className = 'audio-player';
        audioPlayer.innerHTML = `
            <button class="btn btn-sm btn-outline-secondary" onclick="playFullAudio(this, '${audioUrl}')">
                <i class="fas fa-play"></i>
            </button>
        `;
        
        // Create hidden audio element for full playback
        const audio = document.createElement('audio');
        audio.style.display = 'none';
        audio.src = audioUrl;
        audioPlayer.appendChild(audio);
        
        audioContainer.appendChild(audioPlayer);
    }
}

function playFullAudio(button, audioUrl) {
    const audio = button.parentElement.querySelector('audio');
    const icon = button.querySelector('i');
    
    if (audio.paused) {
        audio.play();
        icon.className = 'fas fa-pause';
    } else {
        audio.pause();
        audio.currentTime = 0;
        icon.className = 'fas fa-play';
    }
    
    audio.onended = () => {
        icon.className = 'fas fa-play';
    };
}

function createMessageContainer(responseId) {
    let container = document.querySelector(`[data-response-id="${responseId}"]`);
    if (!container) {
        container = document.createElement('div');
        container.className = 'message-container assistant-message p-3 mb-3';
        container.setAttribute('data-response-id', responseId);
        container.innerHTML = `
            <div class="message-content">
                <div class="message-text mb-2"></div>
                <div class="transcript-area" style="font-style: italic; color: #666;"></div>
                <div class="function-calls-area"></div>
                <div class="audio-container d-flex align-items-center mt-2">
                    <button class="btn btn-sm btn-outline-secondary me-2 d-none play-button">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="btn btn-sm btn-danger me-2 d-none stop-button">
                        <i class="fas fa-stop"></i>
                    </button>
                    <div class="audio-status small text-muted"></div>
                </div>
            </div>
        `;
        
        const messagesContainer = document.getElementById('chat-messages');
        if (messagesContainer) {
            messagesContainer.appendChild(container);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
    return container;
}

function handleTranscriptUpdate(data) {
    console.log('Transcript update:', data);
    
    // Create message container if it doesn't exist
    if (!document.querySelector(`[data-response-id="${data.response_id}"]`)) {
        createMessageContainer(data.response_id);
    }
    
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`);
    if (container) {
        const textDiv = container.querySelector('.message-text');
        if (textDiv) {
            if (data.transcript) {
                // Full transcript
                textDiv.textContent = data.transcript;
            } else if (data.delta) {
                // Delta update - append to existing text
                textDiv.textContent += data.delta;
            }
            
            // Scroll into view
            container.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }
    } else {
        console.error('Container not found for response:', data.response_id);
    }
}

// Add some CSS
const style = document.createElement('style');
style.textContent = `
    .audio-player {
        display: inline-block;
        margin-top: 5px;
    }
    .audio-player button {
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: 50%;
    }
    .audio-player button i {
        font-size: 0.8em;
    }
`;
document.head.appendChild(style);

function stopAllAudio() {
    // Stop any currently playing audio
    if (appState.audio.playbackQueue.length > 0) {
        appState.audio.playbackQueue = [];
    }
    if (appState.audio.isPlaying) {
        if (appState.audio.context) {
            appState.audio.context.close().then(() => {
                appState.audio.context = new (window.AudioContext || window.webkitAudioContext)();
                appState.audio.gainNode = appState.audio.context.createGain();
                appState.audio.gainNode.connect(appState.audio.context.destination);
            });
        }
        appState.audio.isPlaying = false;
    }
    
    // Reset all play/stop buttons
    document.querySelectorAll('.play-button').forEach(button => {
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
        button.querySelector('i').className = 'fas fa-play';
    });
    document.querySelectorAll('.stop-button').forEach(button => {
        button.classList.add('d-none');
    });
}

function handleFunctionCall(data) {
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`) || createMessageContainer(data.response_id);
    const functionCallsArea = container.querySelector('.function-calls-area');
    
    const functionCallElement = document.createElement('div');
    functionCallElement.className = 'function-call-container mt-2 p-2 border rounded';
    
    // Format the function call details
    const functionName = data.name;
    const args = JSON.stringify(data.arguments, null, 2);
    const result = JSON.stringify(data.result, null, 2);
    
    functionCallElement.innerHTML = `
        <div class="function-call-header d-flex align-items-center" style="cursor: pointer;" onclick="toggleFunctionDetails(this)">
            <i class="fas fa-code me-2"></i>
            <span class="function-name">${functionName}</span>
            <i class="fas fa-chevron-down ms-auto"></i>
        </div>
        <div class="function-details mt-2" style="display: none;">
            <div class="arguments mb-2">
                <div class="text-muted small">Arguments:</div>
                <pre class="bg-light p-2 rounded"><code>${args}</code></pre>
            </div>
            <div class="result">
                <div class="text-muted small">Result:</div>
                <pre class="bg-light p-2 rounded"><code>${result}</code></pre>
            </div>
        </div>
    `;
    
    functionCallsArea.appendChild(functionCallElement);
    container.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

function handleFunctionCallOutput(data) {
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`) || createMessageContainer(data.response_id);
    const functionCallsArea = container.querySelector('.function-calls-area');
    
    const functionCallElement = document.createElement('div');
    functionCallElement.className = 'function-call-container mt-2 p-2 border rounded';
    
    // Format the function call details
    const functionName = data.name;
    const args = JSON.stringify(data.arguments, null, 2);
    const result = JSON.stringify(data.result, null, 2);
    
    functionCallElement.innerHTML = `
        <div class="function-call-header d-flex align-items-center" style="cursor: pointer;" onclick="toggleFunctionDetails(this)">
            <i class="fas fa-code me-2"></i>
            <span class="function-name">${functionName}</span>
            <i class="fas fa-chevron-down ms-auto"></i>
        </div>
        <div class="function-details mt-2" style="display: none;">
            <div class="arguments mb-2">
                <div class="text-muted small">Arguments:</div>
                <pre class="bg-light p-2 rounded"><code>${args}</code></pre>
            </div>
            <div class="result">
                <div class="text-muted small">Result:</div>
                <pre class="bg-light p-2 rounded"><code>${result}</code></pre>
            </div>
        </div>
    `;
    
    functionCallsArea.appendChild(functionCallElement);
    container.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

function toggleFunctionDetails(header) {
    const details = header.nextElementSibling;
    const chevron = header.querySelector('.fa-chevron-down');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        details.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}

// Add to the existing style block
const additionalStyles = `
    .function-call-container {
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }
    
    .function-call-container:hover {
        background-color: #e9ecef;
    }
    
    .function-call-header {
        padding: 8px;
        user-select: none;
    }
    
    .function-call-header i {
        transition: transform 0.2s ease;
    }
    
    .function-name {
        font-family: monospace;
        color: #0d6efd;
    }
    
    .function-details pre {
        margin: 0;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .function-details code {
        font-size: 0.85em;
    }
`;

// Append the new styles
document.head.appendChild(document.createElement('style')).textContent += additionalStyles;
</script>

<style>
/* Container styles */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Status bar styles */
.status-bar {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Assistant card styles */
.assistant-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.assistant-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.assistant-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
}

.model-badge {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #495057;
}

.assistant-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 12px;
    line-height: 1.4;
}

.capabilities {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.capability-badge {
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #495057;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Chat container styles */
.chat-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chat-header {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#chat-messages {
    height: 400px;
    overflow-y: auto;
    padding: 15px;
}

.message {
    margin-bottom: 15px;
    max-width: 80%;
}

.message.received {
    margin-right: auto;
}

.message.sent {
    margin-left: auto;
}

.message-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 12px;
}

.message.sent .message-content {
    background: #007bff;
    color: white;
}

.message.sent .sender {
    color: #dee2e6 !important;
}

/* Input area styles */
.input-area {
    padding: 15px;
    border-top: 1px solid #e0e0e0;
    background: #f8f9fa;
}

/* Audio controls styles */
.audio-controls {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    align-items: center;
}

.audio-active {
    color: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* Debug log styles */
.debug-log {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    font-family: monospace;
    font-size: 0.9rem;
}

.log-entry {
    margin-bottom: 5px;
    padding: 5px;
    border-radius: 4px;
}

.log-entry.debug { color: #6c757d; }
.log-entry.info { color: #0d6efd; }
.log-entry.error { color: #dc3545; }

.connection-status {
    padding: 5px 10px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.connection-status.connected {
    background: #4CAF50;
    color: white;
}

.connection-status.disconnected {
    background: #f44336;
    color: white;
}

.mic-button {
    /* ... existing mic button styles ... */
}

.mic-button.muted {
    opacity: 0.5;
    cursor: not-allowed;
}

.message-container {
    margin: 10px 0;
    padding: 10px;
    border-radius: 8px;
    background: #f5f5f5;
}

.assistant-message {
    background: #e3f2fd;
}

.audio-player {
    margin-top: 10px;
    display: flex;
    align-items: center;
}

.play-button {
    background: #2196F3;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.play-button:hover {
    background: #1976D2;
}

.response-indicator i {
    transition: color 0.3s ease;
}

.response-indicator i.active {
    color: #28a745 !important;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>

