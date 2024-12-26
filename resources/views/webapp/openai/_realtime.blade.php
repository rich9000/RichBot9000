<div id="realtime-chat">
    <div id="connection-form">
        <input type="text" id="instructions" placeholder="Enter chat instructions" value="You are a helpful AI assistant.">
        <select id="voice-select">
            <option value="alloy">Alloy</option>
            <option value="echo">Echo</option>
            <option value="fable">Fable</option>
            <option value="onyx">Onyx</option>
            <option value="nova">Nova</option>
            <option value="shimmer">Shimmer</option>
        </select>
        <button onclick="connect()" id="connect-button">Connect</button>
    </div>

    <div id="chat-interface" style="display: none;">
        <div id="connection-info">
            <div id="status">Disconnected</div>
            <div id="session-display">Session: Not Connected</div>
        </div>

        <div id="chat-messages"></div>

        <div id="input-area">
            <button id="mic-toggle" onclick="toggleMicrophone()">
                Enable Mic
            </button>
            <textarea id="message-input" placeholder="Type your message..."></textarea>
            <button onclick="sendMessage()" id="send-button">Send</button>
        </div>

        <div id="message-log"></div>
    </div>
</div>

<script>
let socket = null;
let socketReady = false;
let sessionId = null;
let audioContext = null;
let mediaStream = null;
let isStreaming = false;
let processorNode = null;
let audioBuffer = ''; // For collecting audio deltas
let isOpenAIConnected = false;
let isResponseActive = false;
const BUFFER_SIZE = 4096;
const TARGET_SAMPLE_RATE = 24000;
let sharedAudioContext = null;  // Add this for shared context

// Add rate limiting constants
const AUDIO_CHUNK_INTERVAL = 10; // Reduced from previous value
let lastAudioSendTime = 0;
let audioQueue = [];

async function connect(connectionId, connectionData) {
    try {
        await getAudioContext();
        
        if (!connectionData.instructions) {
            alert('Please enter instructions');
            return;
        }

        try {
            document.getElementById('connect-button').disabled = true;
            document.getElementById('mic-toggle').disabled = true;
            document.getElementById('send-button').disabled = true;
            
            socket = new WebSocket(`wss://richbot9000.com:9501?token=${appState.apiToken}&connection_id=${connectionId}`);

            await new Promise((resolve, reject) => {
                socket.addEventListener('open', () => {
                    socketReady = true;
                    resolve();
                });
                socket.addEventListener('error', reject);
            });

            socket.addEventListener('message', handleSocketMessage);
            socket.addEventListener('close', handleSocketClose);
            socket.addEventListener('error', handleSocketError);

            document.getElementById('connection-form').style.display = 'none';
            document.getElementById('chat-interface').style.display = 'block';

            updateStatus('Connected to WebSocket');
            handleSocketOpen(connectionData.instructions, connectionData.voice);

        } catch (error) {
            logMessage(`Connection error: ${error.message}`, 'error');
            enableInterface();
            socket = null;
            socketReady = false;
        }
    } catch (error) {
        logMessage(`Connection error: ${error.message}`, 'error');
        enableInterface();
        socket = null;
        socketReady = false;
    }
}

function handleSocketOpen(instructions, voice) {
    updateStatus('Connected');
    const startMessage = {
        event: 'start',
        sessionId: sessionId,
        instructions: instructions,
        voice: voice,
        streaming: true
    };
    sendWebSocketMessage(startMessage);
    logMessage('WebSocket connected, streaming enabled');
}

function handleSocketMessage(event) {
    try {
        const data = JSON.parse(event.data);
        console.log('📩 Received:', {
            event: data.event,
            type: data.data?.type,
            content: data.data?.content || data.data?.delta || null
        });

        switch (data.event) {
            case 'status_update':
                updateStatus(data.status);
                if (data.status === 'connected') {
                    isOpenAIConnected = true;
                    enableInterface(true);
                }
                break;
                
            case 'error':
                console.error('Error:', data);
                showErrorMessage(data.message);
                isOpenAIConnected = false;
                break;
                
            case 'openai_message':
                handleOpenAIMessage(data.data);
                break;
                
            default:
                console.log('Unhandled event type:', data.event);
        }
    } catch (error) {
        console.error('Error handling message:', error);
    }
}

function handleOpenAIMessage(data) {
    console.log('Processing OpenAI message:', data.type);
    
    switch (data.type) {
        case 'response.done':
            isResponseActive = false;
            break;
            
        case 'response.created':
            isResponseActive = true;
            break;
            
        case 'session.created':
            console.log('Session created');
            isOpenAIConnected = true;
            // Start recording when session is created
            startRecording().then(() => {
                logMessage('Audio recording started');
                document.getElementById('mic-toggle').textContent = 'Disable Mic';
            }).catch(error => {
                logMessage(`Microphone error: ${error.message}`, 'error');
            });
            break;
            
        case 'session.updated':
            console.log('Session updated:', data.session);
            break;
            
        case 'response.content_part.added':
            if (data.content?.text) {
                appendMessage('assistant', data.content.text);
            }
            break;
            
        case 'response.audio.delta':
            if (data.delta) {
                audioBuffer += data.delta;
                console.log('Audio buffer updated, length:', audioBuffer.length);
            }
            break;
            
        case 'response.audio.done':
            if (audioBuffer) {
                console.log('Playing complete audio buffer');
                playAudio(audioBuffer);
                audioBuffer = '';
            }
            break;

        case 'response.audio_transcript.delta':
            if (data.delta) {
                appendTranscript(data.delta);
                console.log('Transcript delta:', data.delta);
            }
            break;
            
        case 'error':
            console.error('OpenAI Error:', data);
            showErrorMessage(data.error?.message || 'Unknown OpenAI error');
            break;
            
        default:
            console.log('Unhandled message type:', data.type, data);
    }
}

function handleSocketClose() {
    updateStatus('Disconnected');
    stopRecording();
    logMessage('Connection closed');
}

function handleSocketError(error) {
    logMessage(`WebSocket error: ${error.message}`, 'error');
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message || !socket || socket.readyState !== WebSocket.OPEN) {
        showErrorMessage('Connection not ready. Please wait or reconnect.');
        return;
    }
    
    const messageData = {
        event: 'message',
        type: 'text',
        message: message
    };

    sendWebSocketMessage(messageData);
    appendMessage('user', message);
    input.value = '';
}

function appendMessage(role, content) {
    const messagesDiv = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${role}-message`;
    messageDiv.textContent = content;
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function updateStatus(message) {
    document.getElementById('status').textContent = message;
    logMessage(`Status: ${message}`);
}

function generateSessionId() {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

function logMessage(message, type = 'info') {
    const messageLog = document.getElementById('message-log');
    const timestamp = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = `log-entry log-${type}`;
    entry.innerHTML = `<span class="log-timestamp">[${timestamp}]</span> ${message}`;
    messageLog.insertBefore(entry, messageLog.firstChild);

    while (messageLog.children.length > 50) {
        messageLog.removeChild(messageLog.lastChild);
    }
}

// Add these utility functions for WAV handling
function createWAVHeader(dataLength) {
    const buffer = new ArrayBuffer(44);
    const view = new DataView(buffer);
    
    // "RIFF" chunk descriptor
    writeString(view, 0, 'RIFF');
    view.setUint32(4, 36 + dataLength, true);
    writeString(view, 8, 'WAVE');
    
    // "fmt " sub-chunk
    writeString(view, 12, 'fmt ');
    view.setUint32(16, 16, true); // fmt chunk size
    view.setUint16(20, 1, true); // audio format (PCM)
    view.setUint16(22, 1, true); // num channels
    view.setUint32(24, TARGET_SAMPLE_RATE, true); // sample rate
    view.setUint32(28, TARGET_SAMPLE_RATE * 2, true); // byte rate
    view.setUint16(32, 2, true); // block align
    view.setUint16(34, 16, true); // bits per sample
    
    // "data" sub-chunk
    writeString(view, 36, 'data');
    view.setUint32(40, dataLength, true);
    
    return buffer;
}

function writeString(view, offset, string) {
    for (let i = 0; i < string.length; i++) {
        view.setUint8(offset + i, string.charCodeAt(i));
    }
}

function base64ToArrayBuffer(base64) {
    const binaryString = atob(base64);
    const bytes = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

// Update getAudioContext function
async function getAudioContext() {
    if (!sharedAudioContext) {
        // Create with browser's default sample rate
        sharedAudioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    return sharedAudioContext;
}

// Update playAudio function to handle OpenAI's audio format
async function playAudio(base64Data) {
    try {
        const audioContext = await getAudioContext();
        
        // Convert base64 to Int16Array
        const binaryString = atob(base64Data);
        const pcmData = new Int16Array(binaryString.length / 2);
        
        // Convert binary string to Int16Array (handle endianness)
        for (let i = 0; i < binaryString.length; i += 2) {
            // Little-endian format
            const low = binaryString.charCodeAt(i);
            const high = binaryString.charCodeAt(i + 1);
            pcmData[i/2] = (high << 8) | low;
        }

        // Convert Int16Array to Float32Array (normalize to [-1, 1])
        const float32Data = new Float32Array(pcmData.length);
        for (let i = 0; i < pcmData.length; i++) {
            // Normalize to [-1, 1]
            float32Data[i] = pcmData[i] / 32768.0;
        }

        // Create audio buffer with OpenAI's sample rate
        const audioBuffer = audioContext.createBuffer(1, float32Data.length, 24000);
        audioBuffer.getChannelData(0).set(float32Data);

        // Create and play source
        const source = audioContext.createBufferSource();
        source.buffer = audioBuffer;
        source.connect(audioContext.destination);
        source.start();
        
        console.log('Playing audio:', {
            duration: audioBuffer.duration,
            sampleRate: audioBuffer.sampleRate,
            samples: float32Data.length,
            min: Math.min(...float32Data),
            max: Math.max(...float32Data)
        });

    } catch (error) {
        console.error('Audio playback error:', error);
        console.error('Audio data length:', base64Data?.length);
        logMessage(`Audio playback error: ${error.message}`, 'error');
    }
}

function decodeMulaw(mulawData) {
    const BIAS = 0x84;
    const CLIP = 32635;
    
    const decoded = new Float32Array(mulawData.length);
    
    for (let i = 0; i < mulawData.length; i++) {
        let sample = mulawData[i] ^ 0xFF; // Invert all bits
        
        sample &= 0x7F; // Strip sign bit
        
        let value = (sample << 4) + 8; // Add bias
        
        if (sample < 16) {
            value += (sample + 1) << 3;
        }
        
        if (mulawData[i] & 0x80) { // If sign bit was set
            value = -value;
        }
        
        decoded[i] = value / 32768.0; // Normalize to [-1, 1]
    }
    
    return decoded;
}

function resample(audioData, fromSampleRate, toSampleRate) {
    const ratio = toSampleRate / fromSampleRate;
    const newLength = Math.round(audioData.length * ratio);
    const result = new Float32Array(newLength);
    
    for (let i = 0; i < newLength; i++) {
        const position = i / ratio;
        const index = Math.floor(position);
        const fraction = position - index;
        
        const sample1 = audioData[index] || 0;
        const sample2 = audioData[Math.min(index + 1, audioData.length - 1)] || 0;
        
        result[i] = sample1 + fraction * (sample2 - sample1);
    }
    
    return result;
}

// Update startRecording
async function startRecording() {
    try {
        // Start capturing audio at 24kHz
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: {
                sampleRate: TARGET_SAMPLE_RATE,
                channelCount: 1,
                echoCancellation: true,
                noiseSuppression: true
            }
        });

        const audioContext = new AudioContext({sampleRate: TARGET_SAMPLE_RATE});
        const source = audioContext.createMediaStreamSource(stream);
        const processor = audioContext.createScriptProcessor(BUFFER_SIZE, 1, 1);

        processor.onaudioprocess = (event) => {
            if (!socket || !socketReady || !isOpenAIConnected) return;

            try {
                const audioData = event.inputBuffer.getChannelData(0);
                const pcmData = convertFloat32ToPCM16(audioData);
                const base64Audio = btoa(String.fromCharCode.apply(null, new Uint8Array(pcmData.buffer)));

                const audioMessage = {
                    event: 'message',
                    type: 'audio',
                    audio: base64Audio,
                    sampleRate: TARGET_SAMPLE_RATE
                };

                socket.send(JSON.stringify(audioMessage));

                // Log occasionally for debugging
                if (Math.random() < 0.01) {
                    console.log('Audio sent:', {
                        bufferSize: BUFFER_SIZE,
                        pcmLength: pcmData.length,
                        base64Length: base64Audio.length,
                        sampleRate: TARGET_SAMPLE_RATE
                    });
                }
            } catch (error) {
                console.error('Audio processing error:', error);
            }
        };

        source.connect(processor);
        processor.connect(audioContext.destination);
        mediaStream = stream;
        processorNode = processor;
        isStreaming = true;
        
        console.log('Started audio streaming:', {
            contextSampleRate: audioContext.sampleRate,
            bufferSize: BUFFER_SIZE,
            targetSampleRate: TARGET_SAMPLE_RATE
        });

    } catch (error) {
        console.error('Failed to start recording:', error);
        throw error;
    }
}

function stopRecording() {
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
    }
    if (processorNode) {
        processorNode.disconnect();
    }
    isStreaming = false;
    document.getElementById('playback-button').disabled = true;
}

let currentTranscript = '';

function appendTranscript(text) {
    const messagesDiv = document.getElementById('chat-messages');
    let transcriptDiv = messagesDiv.querySelector('.current-transcript');
    
    if (!transcriptDiv) {
        transcriptDiv = document.createElement('div');
        transcriptDiv.className = 'message user-message current-transcript';
        messagesDiv.appendChild(transcriptDiv);
    }
    
    transcriptDiv.textContent += text;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function convertFloat32ToPCM16(float32Data) {
    const pcm16Data = new Int16Array(float32Data.length);
    for (let i = 0; i < float32Data.length; i++) {
        const s = Math.max(-1, Math.min(1, float32Data[i]));
        pcm16Data[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
    }
    return pcm16Data;
}

function toggleMicrophone() {
    const micButton = document.getElementById('mic-toggle');
    
    if (isStreaming) {
        stopRecording();
        micButton.textContent = 'Enable Mic';
    } else {
        startRecording().then(() => {
            micButton.textContent = 'Disable Mic';
        }).catch(error => {
            logMessage(`Microphone error: ${error.message}`, 'error');
        });
    }
}

// Add a helper function for sending websocket messages
function sendWebSocketMessage(message) {
    if (!socket || !socketReady) {
        console.error('Socket not ready');
        return false;
    }

    try {
        const messageStr = JSON.stringify(message);
        console.log('🔵 Outgoing WebSocket message:', {
            type: message.type,
            event: message.event,
            size: messageStr.length
        });
        socket.send(messageStr);
        return true;
    } catch (error) {
        console.error('WebSocket send error:', error);
        logMessage(`Failed to send message: ${error.message}`, 'error');
        return false;
    }
}

// Add a function to request OpenAI status
function checkOpenAIStatus() {
    sendWebSocketMessage({
        event: 'status',
        type: 'check',
        target: 'openai'
    });
    logMessage('Requesting OpenAI connection status', 'info');
}

// Add a button to the interface
function addStatusButton() {
    const connectionInfo = document.getElementById('connection-info');
    const statusButton = document.createElement('button');
    statusButton.textContent = 'Check OpenAI';
    statusButton.onclick = checkOpenAIStatus;
    connectionInfo.appendChild(statusButton);
}

// Add function to enable/disable interface
function enableInterface(connected = true) {
    document.getElementById('connect-button').disabled = false;
    document.getElementById('mic-toggle').disabled = !connected;
    document.getElementById('send-button').disabled = !connected;
    
    if (connected) {
        startRecording().catch(error => {
            logMessage(`Microphone error: ${error.message}`, 'error');
        });
    }
}

// Add function to show error messages
function showErrorMessage(message) {
    const messagesDiv = document.getElementById('chat-messages');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'message error-message';
    errorDiv.textContent = `Error: ${message}`;
    messagesDiv.appendChild(errorDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Add resampler function
function createResampledBuffer(audioContext, originalBuffer, targetSampleRate) {
    const originalSampleRate = audioContext.sampleRate;
    const originalLength = originalBuffer.length;
    const resampledLength = Math.round(originalLength * targetSampleRate / originalSampleRate);
    const resampledBuffer = new Float32Array(resampledLength);
    
    for (let i = 0; i < resampledLength; i++) {
        const position = (i * originalSampleRate) / targetSampleRate;
        const index = Math.floor(position);
        const fraction = position - index;
        
        const sample1 = originalBuffer[index] || 0;
        const sample2 = originalBuffer[Math.min(index + 1, originalLength - 1)] || 0;
        
        resampledBuffer[i] = sample1 + fraction * (sample2 - sample1);
    }
    
    return resampledBuffer;
}

// Update sendAudioMessage with rate limiting
function sendAudioMessage(audioData) {
    try {
        if (!audioData || audioData.length === 0) {
            return;
        }

        const now = Date.now();
        if (now - lastAudioSendTime < AUDIO_CHUNK_INTERVAL) {
            // Queue the audio data if we're sending too fast
            audioQueue.push(audioData);
            return;
        }

        // Process queued audio first
        if (audioQueue.length > 0) {
            const combinedAudio = new Float32Array(audioQueue.reduce((acc, curr) => acc + curr.length, 0) + audioData.length);
            let offset = 0;
            audioQueue.forEach(chunk => {
                combinedAudio.set(chunk, offset);
                offset += chunk.length;
            });
            combinedAudio.set(audioData, offset);
            audioQueue = [];
            audioData = combinedAudio;
        }

        const pcmData = convertFloat32ToPCM16(audioData);
        const base64Audio = btoa(String.fromCharCode.apply(null, new Uint8Array(pcmData.buffer)));

        const audioMessage = {
            event: 'message',
            type: 'audio',
            audio: base64Audio
        };

        socket.send(JSON.stringify(audioMessage));
        lastAudioSendTime = now;

        // Log occasionally
        if (Math.random() < 0.01) {
            console.log('Audio sent:', {
                originalLength: audioData.length,
                pcmLength: pcmData.length,
                base64Length: base64Audio.length,
                queueLength: audioQueue.length,
                sampleRate: TARGET_SAMPLE_RATE
            });
        }
    } catch (error) {
        console.error('Error sending audio:', error);
    }
}

// Update handleAudioProcess to use smaller chunks
function handleAudioProcess(event) {
    if (!socket || !socketReady || !isOpenAIConnected) return;

    try {
        const inputData = event.inputBuffer.getChannelData(0);
        if (!inputData || inputData.length === 0) {
            return;
        }

        // Split into smaller chunks if needed
        const CHUNK_SIZE = 1024; // Process smaller chunks
        for (let i = 0; i < inputData.length; i += CHUNK_SIZE) {
            const chunk = inputData.slice(i, i + CHUNK_SIZE);
            const resampledData = createResampledBuffer(audioContext, chunk, TARGET_SAMPLE_RATE);
            if (resampledData && resampledData.length > 0) {
                sendAudioMessage(resampledData);
            }
        }
    } catch (error) {
        console.error('Audio processing error:', error);
    }
}

socket.addEventListener('message', function(event) {
    try {
        const data = JSON.parse(event.data);
        console.log(' Received WebSocket message:', {
            event: data.event,
            type: data.data?.type,
            fullData: data
        });
        
        // Existing message handling...
        handleSocketMessage(event);
        
    } catch (error) {
        console.error('Error processing message:', error);
    }
});

function populateAssistantsList() {
    const assistantsList = document.getElementById('assistants-list');
    assistantsList.innerHTML = '<h3>Available Assistants</h3>';
    
    // Filter interactive assistants
    const interactiveAssistants = appState.data.assistants.filter(a => 
        a.interactive && a.type === 'assistant'
    );
    
    interactiveAssistants.forEach(assistant => {
        const assistantCard = document.createElement('div');
        assistantCard.className = 'assistant-card';
        assistantCard.innerHTML = `
            <div class="assistant-header">
                <h4>${assistant.name}</h4>
                <span class="model-badge">${assistant.model.name}</span>
            </div>
            <div class="assistant-description">
                ${assistant.system_message.substring(0, 150)}...
            </div>
            <div class="assistant-tools">
                <strong>Tools (${assistant.tools.length}):</strong>
                <div class="tools-list">
                    ${assistant.tools.map(tool => 
                        `<span class="tool-badge" title="${tool.description}">${tool.name}</span>`
                    ).join('')}
                </div>
            </div>
            <button onclick="connectToAssistant(${assistant.id}, '${assistant.name}')" 
                    class="connect-button">
                Connect
            </button>
        `;
        assistantsList.appendChild(assistantCard);
    });
}

// Add this CSS to style the assistants list
<style>
.assistant-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background: white;
}

.assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.model-badge {
    background: #2196f3;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.assistant-description {
    color: #666;
    margin-bottom: 10px;
    font-size: 0.9em;
}

.tools-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}

.tool-badge {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8em;
    cursor: help;
}

.connect-button {
    width: 100%;
    margin-top: 10px;
    padding: 8px;
    background: #4caf50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.connect-button:hover {
    background: #45a049;
}
</style>

<script>
// Initialize the assistants list when the page loads
document.addEventListener('DOMContentLoaded', () => {
    populateAssistantsList();
});

function connectToAssistant(assistantId, assistantName) {
    const assistant = appState.data.assistants.find(a => a.id === assistantId);
    if (!assistant) return;

    connect(null, {
        instructions: assistant.system_message,
        voice: document.getElementById('voice-select').value,
        assistant_id: assistantId
    });
}
</script>
