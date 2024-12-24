<div id="connection-form">
    <div id="assistants-list">
        <h3>Available Assistants</h3>
        <div class="assistants-grid">
            <!-- Populated dynamically -->
        </div>
    </div>
</div>

<div id="audio-controls" style="display: none;">
    <div id="connection-info">
        <div id="status">Disconnected</div>
        <div id="fd-display">FD: Not Connected</div>
    </div>
    <div id="call-info"></div>
    <button id="mic-button" onclick="toggleMicrophone()" disabled>Enable Microphone</button>
    <div id="message-log"></div>
</div>

<style>
.assistants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.assistant-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.assistant-header h4 {
    margin: 0;
    color: #333;
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
    margin: 10px 0;
    font-size: 0.9em;
    max-height: 100px;
    overflow-y: auto;
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
    transition: background 0.3s;
}

.connect-button:hover {
    background: #45a049;
}
</style>

<script>

    populateAssistantsList();


function populateAssistantsList() {
    const assistantsGrid = document.querySelector('.assistants-grid');
    assistantsGrid.innerHTML = '';
    
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
                ${assistant.system_message}
            </div>
            <div class="assistant-tools">
                <strong>Tools (${assistant.tools.length}):</strong>
                <div class="tools-list">
                    ${assistant.tools.map(tool => 
                        `<span class="tool-badge" title="${tool.description}">${tool.name}</span>`
                    ).join('')}
                </div>
            </div>
            <button onclick="connectToAssistant(${assistant.id})" class="connect-button">
                Connect to ${assistant.name}
            </button>
        `;
        assistantsGrid.appendChild(assistantCard);
    });
}

function connectToAssistant(assistantId) {
    const assistant = appState.data.assistants.find(a => a.id === assistantId);
    if (!assistant) {
        logMessage('Assistant not found', 'error');
        return;
    }

    // Hide the connection form and show audio controls
    document.getElementById('connection-form').style.display = 'none';
    document.getElementById('audio-controls').style.display = 'block';

    // Initialize WebSocket connection with assistant ID
    sessionId = generateSessionId();
    
    try {
        if (socket) {
            socket.close();
            socket = null;
        }

        socket = new WebSocket(`wss://richbot9000.com:9501?assistant=${assistantId}&token=${appState.apiToken}`);
        
        socket.addEventListener('open', () => {
            handleSocketOpen(assistant);
            logMessage(`Connected to assistant: ${assistant.name}`);
        });
        
        socket.addEventListener('message', handleSocketMessage);
        
        socket.addEventListener('close', (event) => {
            handleSocketClose(event);
            // Attempt to reconnect after a delay
            setTimeout(() => {
                if (document.getElementById('audio-controls').style.display !== 'none') {
                    logMessage('Attempting to reconnect...', 'info');
                    connectToAssistant(assistantId);
                }
            }, 5000);
        });

        socket.addEventListener('error', (error) => {
            logMessage(`WebSocket error: ${error.message}`, 'error');
            updateStatus('Connection Error');
        });

    } catch (error) {
        logMessage(`Connection error: ${error.message}`, 'error');
        updateStatus('Connection Failed');
    }
}

// Global variables
let socket = null;
let audioContext = null;
let mediaStream = null;
let isStreaming = false;
let processorNode = null;
let sessionId = null;

// Initialize WebSocket connection
async function initializeClient() {
    // Remove auto-connection
    // Will be called by connect() function instead
}

// Add new connect function
async function connect() {
    const userName = document.getElementById('user-name').value.trim();
    console.log('Connecting with user name:', userName);
    if (!userName) {
        alert('Please enter your name');
        return;
    }

    // Store user name
    window.userName = userName;

    // Hide connection form and show audio controls
    document.getElementById('connection-form').style.display = 'none';
    document.getElementById('audio-controls').style.display = 'block';

    try {
        sessionId = generateSessionId();
        socket = new WebSocket('wss://richbot9000.com:9501?phone='+userName+'&token='+ appState.apiToken);

        socket.addEventListener('open', handleSocketOpen);
        socket.addEventListener('message', handleSocketMessage);
        socket.addEventListener('close', handleSocketClose);

    } catch (error) {
        console.error('Initialization error:', error);
        updateStatus(`Error: ${error.message}`);
    }
}

function handleSocketOpen(assistant) {
    updateStatus('Connected');
    socket.send(JSON.stringify({
        event: 'connected',
        protocol: 'Client',
        sessionId: sessionId,
        userData: {
            assistantId: assistant.id,
            assistantName: assistant.name,
            instructions: assistant.system_message
        }
    }));
    document.getElementById('mic-button').disabled = false;
    logMessage('WebSocket connection established');
}

async function handleSocketMessage(event) {
    try {
        const data = JSON.parse(event.data);
        
        // Log every message received
        logMessage(`Received: ${JSON.stringify(data)}`, 'received');
        console.log('Received message:', data);

        switch (data.event) {
            case 'connection_established':
                document.getElementById('fd-display').textContent = `FD: ${data.fd}`;
                logMessage(`Connection established with FD: ${data.fd}`);
                break;

            case 'call_started':
                updateStatus('Call Connected');
                document.getElementById('call-info').textContent = `Call ID: ${data.callId}`;
                await startStreaming();
                break;

            case 'call_ended':
                updateStatus('Call Ended');
                document.getElementById('call-info').textContent = '';
                stopStreaming();
                break;

            case 'media':
                if (data.media?.payload) {
                    await playAudio(data.media.payload);
                }
                break;

            case 'stream_start_request':

                console.log('Stream start requested by manager');

                updateStatus('Streaming to manager');
                logMessage('Stream start requested by manager');
                if (!isStreaming) {
                    await startStreaming();
                }
                break;

            case 'stream_stop_request':
                updateStatus('Stream stopped by manager');
                logMessage('Stream stop requested by manager');
                if (isStreaming) {
                    stopStreaming();
                }
                break;

            default:
                console.warn(`Unhandled event: ${data.event}`);
        }
    } catch (error) {
        logMessage(`Error: ${error.message}`, 'error');
        console.error('Error handling message:', error);
    }
}

function handleSocketClose(event) {
    updateStatus('Disconnected');
    document.getElementById('mic-button').disabled = true;
    stopStreaming();
    
    logMessage(`Connection closed. Code: ${event.code}, Reason: ${event.reason}`, 'error');
    
    if (event.code === 1006) {
        logMessage('Abnormal closure, will attempt to reconnect', 'info');
    }
}

async function toggleMicrophone() {
    if (isStreaming) {
        stopStreaming();
    } else {
        await startStreaming();
    }
}

async function startStreaming() {
    if (isStreaming) return;

    try {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        mediaStream = await navigator.mediaDevices.getUserMedia({
            audio: {
                channelCount: 1,
                echoCancellation: true,
                noiseSuppression: true,
                sampleRate: 48000,
            },
        });

        const input = audioContext.createMediaStreamSource(mediaStream);
        processorNode = audioContext.createScriptProcessor(2048, 1, 1);

        processorNode.onaudioprocess = (event) => {
            if (!socket || socket.readyState !== WebSocket.OPEN || !isStreaming) return;

            const inputData = event.inputBuffer.getChannelData(0);
            const pcmData = convertFloat32ToPCM16(inputData);

            socket.send(JSON.stringify({
                event: 'media',
                media: {
                    payload: btoa(String.fromCharCode.apply(null, new Uint8Array(pcmData.buffer))),
                    timestamp: Date.now(),
                },
            }));
        };

        input.connect(processorNode);
        processorNode.connect(audioContext.destination);

        isStreaming = true;
        document.getElementById('mic-button').textContent = 'Disable Microphone';
        updateStatus('Streaming Active');
    } catch (error) {
        console.error('Streaming error:', error);
        updateStatus(`Streaming Error: ${error.message}`);
    }
}

function stopStreaming() {
    if (mediaStream) mediaStream.getTracks().forEach(track => track.stop());
    if (processorNode) processorNode.disconnect();
    if (audioContext) audioContext.close();

    mediaStream = null;
    processorNode = null;
    audioContext = null;

    isStreaming = false;
    document.getElementById('mic-button').textContent = 'Enable Microphone';
    updateStatus('Connected');
}

async function playAudio(base64Data) {
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        // Convert base64 to Uint8Array (mulaw data)
        const audioData = new Uint8Array(atob(base64Data).split('').map(c => c.charCodeAt(0)));
        
        // Decode mulaw to PCM
        const decoded = decodeMulaw(audioData);
        
        // Resample to 48kHz for better quality
        const resampledData = resampleAudio(decoded, 8000, 48000);
        
        // Create and play audio buffer (using resampled 48kHz audio)
        const audioBuffer = audioContext.createBuffer(1, resampledData.length, 48000);
        audioBuffer.getChannelData(0).set(resampledData);
        
        // Create audio processing chain
        const source = audioContext.createBufferSource();
        
        // Add a biquad filter for better frequency response
        const filter = audioContext.createBiquadFilter();
        filter.type = 'lowshelf';
        filter.frequency.value = 1000;
        filter.gain.value = 3.0;

        // Add a compressor to normalize volume
        const compressor = audioContext.createDynamicsCompressor();
        compressor.threshold.value = -24;
        compressor.knee.value = 30;
        compressor.ratio.value = 12;
        compressor.attack.value = 0.003;
        compressor.release.value = 0.25;
        
        // Connect the audio processing chain
        source.buffer = audioBuffer;
        source.connect(filter);
        filter.connect(compressor);
        compressor.connect(audioContext.destination);
        
        source.start();
    } catch (error) {
        console.error('Audio playback error:', error);
    }
}

function resampleAudio(audioData, originalSampleRate, targetSampleRate) {
    const ratio = targetSampleRate / originalSampleRate;
    const newLength = Math.round(audioData.length * ratio);
    const result = new Float32Array(newLength);
    
    // Linear interpolation resampling
    for (let i = 0; i < newLength; i++) {
        const position = i / ratio;
        const index = Math.floor(position);
        const decimal = position - index;
        
        const sample1 = audioData[index] || 0;
        const sample2 = audioData[index + 1] || sample1;
        
        // Interpolate between samples
        result[i] = sample1 + (sample2 - sample1) * decimal;
    }
    
    return result;
}

// Enhanced mulaw decoder with better precision
function decodeMulaw(mulawData) {
    const MULAW_DECODE_TABLE = new Float32Array([
        -32124,-31100,-30076,-29052,-28028,-27004,-25980,-24956,
        -23932,-22908,-21884,-20860,-19836,-18812,-17788,-16764,
        -15996,-15484,-14972,-14460,-13948,-13436,-12924,-12412,
        -11900,-11388,-10876,-10364,-9852,-9340,-8828,-8316,
        -7932,-7676,-7420,-7164,-6908,-6652,-6396,-6140,
        -5884,-5628,-5372,-5116,-4860,-4604,-4348,-4092,
        -3900,-3772,-3644,-3516,-3388,-3260,-3132,-3004,
        -2876,-2748,-2620,-2492,-2364,-2236,-2108,-1980,
        -1884,-1820,-1756,-1692,-1628,-1564,-1500,-1436,
        -1372,-1308,-1244,-1180,-1116,-1052,-988,-924,
        -876,-844,-812,-780,-748,-716,-684,-652,
        -620,-588,-556,-524,-492,-460,-428,-396,
        -372,-356,-340,-324,-308,-292,-276,-260,
        -244,-228,-212,-196,-180,-164,-148,-132,
        -120,-112,-104,-96,-88,-80,-72,-64,
        -56,-48,-40,-32,-24,-16,-8,0,
        32124,31100,30076,29052,28028,27004,25980,24956,
        23932,22908,21884,20860,19836,18812,17788,16764,
        15996,15484,14972,14460,13948,13436,12924,12412,
        11900,11388,10876,10364,9852,9340,8828,8316,
        7932,7676,7420,7164,6908,6652,6396,6140,
        5884,5628,5372,5116,4860,4604,4348,4092,
        3900,3772,3644,3516,3388,3260,3132,3004,
        2876,2748,2620,2492,2364,2236,2108,1980,
        1884,1820,1756,1692,1628,1564,1500,1436,
        1372,1308,1244,1180,1116,1052,988,924,
        876,844,812,780,748,716,684,652,
        620,588,556,524,492,460,428,396,
        372,356,340,324,308,292,276,260,
        244,228,212,196,180,164,148,132,
        120,112,104,96,88,80,72,64,
        56,48,40,32,24,16,8,0
    ]);
    
    const decoded = new Float32Array(mulawData.length);
    let prevSample = 0; // For simple noise reduction
    
    for (let i = 0; i < mulawData.length; i++) {
        // Get the basic decoded value
        let sample = MULAW_DECODE_TABLE[mulawData[i]] / 32768.0;
        
        // Simple noise reduction using moving average
        sample = 0.85 * sample + 0.15 * prevSample;
        prevSample = sample;
        
        decoded[i] = sample;
    }
    
    return decoded;
}

function convertFloat32ToPCM16(float32Array) {
    const pcmData = new Int16Array(float32Array.length);
    for (let i = 0; i < float32Array.length; i++) {
        const s = Math.max(-1, Math.min(1, float32Array[i]));
        pcmData[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
    }
    return pcmData;
}

function updateStatus(message) {
    document.getElementById('status').textContent = message;
    logMessage(`Status: ${message}`);
}

function generateSessionId() {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

// Add message logging function
function logMessage(message, type = 'info') {
    const messageLog = document.getElementById('message-log');
    const timestamp = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = `log-entry log-${type}`;
    entry.innerHTML = `<span class="log-timestamp">[${timestamp}]</span> ${message}`;
    messageLog.insertBefore(entry, messageLog.firstChild);

    // Keep only the last 50 messages
    while (messageLog.children.length > 50) {
        messageLog.removeChild(messageLog.lastChild);
    }
}

</script>
