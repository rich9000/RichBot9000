
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <!-- Call Status Section -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Call Status</h2>
            <div class="flex items-center space-x-4">
                <div id="callStatus" class="text-lg font-semibold">Not Connected</div>
                <div id="callDuration" class="text-gray-600">00:00:00</div>
            </div>
        </div>

        <!-- Call Controls -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Call Controls</h2>
            <div class="flex space-x-4">
                <button id="startCall" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Start Call
                </button>
                <button id="endCall" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" disabled>
                    End Call
                </button>
                <button id="muteCall" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" disabled>
                    Mute
                </button>
            </div>
        </div>

        <!-- Audio Visualization -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Audio Visualization</h2>
            <div class="bg-gray-100 p-4 rounded">
                <canvas id="audioVisualizer" class="w-full h-32"></canvas>
            </div>
        </div>

        <!-- Real-time Transcript -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Transcript</h2>
            <div id="transcript" class="bg-gray-50 p-4 rounded h-64 overflow-y-auto">
                <!-- Transcript will be populated here -->
            </div>
        </div>

        <!-- System Messages -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">System Messages</h2>
            <div id="systemMessages" class="bg-gray-50 p-4 rounded h-32 overflow-y-auto">
                <!-- System messages will be populated here -->
            </div>
        </div>
    </div>
</div>


<script>
let ws;
let callStartTime;
let callTimer;
let isMuted = false;
let audioContext;
let analyser;
let dataArray;
let animationFrame;

// Initialize WebSocket connection
function initWebSocket() {
    ws = new WebSocket(`${window.appConfig.wsUrlAlt}/dashboard/${appState.apiToken}`);
    
    ws.onopen = () => {
        addSystemMessage('Connected to WebSocket server');
        document.getElementById('startCall').disabled = false;
    };

    ws.onclose = () => {
        addSystemMessage('Disconnected from WebSocket server');
        document.getElementById('startCall').disabled = true;
        document.getElementById('endCall').disabled = true;
        document.getElementById('muteCall').disabled = true;
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        handleWebSocketMessage(data);
    };
}

// Handle WebSocket messages
function handleWebSocketMessage(data) {
    switch(data.type) {
        case 'call_status':
            updateCallStatus(data.status);
            break;
        case 'transcript_delta':
            appendTranscript(data.delta);
            break;
        case 'transcript_complete':
            appendTranscript(data.transcript);
            break;
        case 'audio_data':
            updateAudioVisualizer(data.data);
            break;
        case 'system_message':
            addSystemMessage(data.message);
            break;
    }
}

// Update call status
function updateCallStatus(status) {
    const statusElement = document.getElementById('callStatus');
    statusElement.textContent = status;
    
    if (status === 'in-progress') {
        startCallTimer();
        document.getElementById('endCall').disabled = false;
        document.getElementById('muteCall').disabled = false;
    } else if (status === 'completed' || status === 'failed') {
        stopCallTimer();
        document.getElementById('endCall').disabled = true;
        document.getElementById('muteCall').disabled = true;
    }
}

// Call timer functions
function startCallTimer() {
    callStartTime = Date.now();
    callTimer = setInterval(updateCallDuration, 1000);
}

function stopCallTimer() {
    clearInterval(callTimer);
}

function updateCallDuration() {
    const duration = Math.floor((Date.now() - callStartTime) / 1000);
    const hours = Math.floor(duration / 3600);
    const minutes = Math.floor((duration % 3600) / 60);
    const seconds = duration % 60;
    
    document.getElementById('callDuration').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

// Transcript handling
function appendTranscript(text) {
    const transcript = document.getElementById('transcript');
    transcript.innerHTML += `<div class="mb-2">${text}</div>`;
    transcript.scrollTop = transcript.scrollHeight;
}

// Audio visualization
function initAudioVisualizer() {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    analyser = audioContext.createAnalyser();
    analyser.fftSize = 256;
    dataArray = new Uint8Array(analyser.frequencyBinCount);
    
    const canvas = document.getElementById('audioVisualizer');
    const ctx = canvas.getContext('2d');
    
    function draw() {
        animationFrame = requestAnimationFrame(draw);
        analyser.getByteFrequencyData(dataArray);
        
        ctx.fillStyle = 'rgb(200, 200, 200)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        const barWidth = (canvas.width / dataArray.length) * 2.5;
        let barHeight;
        let x = 0;
        
        for(let i = 0; i < dataArray.length; i++) {
            barHeight = dataArray[i] / 2;
            ctx.fillStyle = `rgb(${barHeight + 100},50,50)`;
            ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
            x += barWidth + 1;
        }
    }
    
    draw();
}

function updateAudioVisualizer(audioData) {
    if (!audioContext || !analyser) return;
    
    const buffer = audioContext.createBuffer(1, audioData.length, 8000);
    buffer.copyToChannel(new Float32Array(audioData), 0);
    
    const source = audioContext.createBufferSource();
    source.buffer = buffer;
    source.connect(analyser);
    source.start();
}

// System messages
function addSystemMessage(message) {
    const systemMessages = document.getElementById('systemMessages');
    systemMessages.innerHTML += `<div class="text-sm text-gray-600">${message}</div>`;
    systemMessages.scrollTop = systemMessages.scrollHeight;
}

// Event listeners
document.getElementById('startCall').addEventListener('click', () => {
    ws.send(JSON.stringify({
        type: 'start_call',
        phone_number: prompt('Enter phone number:')
    }));
});

document.getElementById('endCall').addEventListener('click', () => {
    ws.send(JSON.stringify({
        type: 'end_call'
    }));
});

document.getElementById('muteCall').addEventListener('click', () => {
    isMuted = !isMuted;
    ws.send(JSON.stringify({
        type: 'mute_call',
        muted: isMuted
    }));
    document.getElementById('muteCall').textContent = isMuted ? 'Unmute' : 'Mute';
});

// Initialize

    initWebSocket();
    initAudioVisualizer();

</script>
