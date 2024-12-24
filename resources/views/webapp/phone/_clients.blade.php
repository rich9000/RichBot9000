<div id="clients-panel">
    <div class="connection-controls">
        <h3>Start New Connection</h3>
        <select id="connection-type">
            <option value="realtime">Realtime</option>
            <option value="openai" disabled>OpenAI API</option>
            <option value="ollama" disabled>Ollama</option>
        </select>

        <select id="target-type">
            <option value="assistant">AI Assistant</option>
            <option value="user">User</option>
        </select>

        <select id="target-select" class="hidden">
            <!-- Populated dynamically -->
        </select>

        <div id="assistant-options" class="hidden">
            <input type="text" id="instructions" placeholder="Enter chat instructions" value="You are a helpful AI assistant.">
            <select id="voice-select">
                <option value="alloy">Alloy</option>
                <option value="echo">Echo</option>
                <option value="fable">Fable</option>
                <option value="onyx">Onyx</option>
                <option value="nova">Nova</option>
                <option value="shimmer">Shimmer</option>
            </select>
        </div>

        <button onclick="initiateConnection()" id="connect-button">Connect</button>
    </div>

    <div class="active-connections">
        <h3>Active Connections</h3>
        <div id="connections-list"></div>
    </div>

    <div class="available-users">
        <h3>Available Users</h3>
        <div id="users-list"></div>
    </div>
</div>

<script>
// Create AudioWorklet for audio processing
const audioContext = new (window.AudioContext || window.webkitAudioContext)();
let audioWorkletNode;

async function initAudioProcessing() {
    try {
        await audioContext.audioWorklet.addModule('/js/audio-processor.js');
        audioWorkletNode = new AudioWorkletNode(audioContext, 'audio-processor');
        
        // Configure audio processing
        audioWorkletNode.port.onmessage = (event) => {
            if (event.data.type === 'audio') {
                // Send processed audio data to WebSocket
                if (connectionManager.ws && connectionManager.ws.readyState === WebSocket.OPEN) {
                    connectionManager.ws.send(JSON.stringify({
                        event: 'media',
                        type: 'audio',
                        format: 'pcm16',
                        sampleRate: audioContext.sampleRate,
                        data: event.data.buffer
                    }));
                }
            }
        };
    } catch (error) {
        console.error('Failed to initialize AudioWorklet:', error);
    }
}

let connectionManager = {
    activeConnections: new Map(),
    activeUsers: new Map(),
    currentConnection: null,
    ws: null,

    async init() {
        await initAudioProcessing();
        this.setupWebSocket();
        this.setupEventListeners();
    },

    setupWebSocket() {
        this.ws = new WebSocket(`wss://richbot9000.com:9501?token=${appState.apiToken}`);
        
        this.ws.onopen = () => {
            console.log('WebSocket connected');
            // Request initial connections list
            this.ws.send(JSON.stringify({
                action: 'get_connections'
            }));
        };

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            console.log('WebSocket message:', data);

            switch (data.action) {
                case 'connections_update':
                    this.updateConnectionsDisplay(data.connections);
                    this.updateUsersDisplay(data.users);
                    break;
                    
                case 'status_update':
                    this.handleStatusUpdate(data);
                    break;

                case 'error':
                    showErrorMessage(data.message);
                    break;
            }
        };

        this.ws.onclose = () => {
            console.log('WebSocket disconnected');
            // Attempt to reconnect after delay
            setTimeout(() => this.setupWebSocket(), 5000);
        };
    },

    setupEventListeners() {
        document.getElementById('target-type').addEventListener('change', (e) => {
            const targetSelect = document.getElementById('target-select');
            const assistantOptions = document.getElementById('assistant-options');
            
            targetSelect.innerHTML = '';
            
            if (e.target.value === 'assistant') {
                // Filter only interactive assistants
                const interactiveAssistants = appState.data.assistants.filter(a => 
                    a.interactive && a.type === 'assistant'
                );
                
                interactiveAssistants.forEach(assistant => {
                    const option = document.createElement('option');
                    option.value = assistant.id;
                    option.textContent = assistant.name;
                    
                    // Add assistant details as data attributes
                    option.dataset.model = assistant.model.name;
                    option.dataset.description = assistant.system_message;
                    
                    targetSelect.appendChild(option);
                });
                
                assistantOptions.classList.remove('hidden');
                
                // Update instructions with selected assistant's system message
                if (targetSelect.options.length > 0) {
                    document.getElementById('instructions').value = 
                        targetSelect.options[0].dataset.description;
                }
            } else {
                this.activeUsers.forEach((user, id) => {
                    if (user.status === 'available') {
                        const option = document.createElement('option');
                        option.value = id;
                        option.textContent = user.name;
                        targetSelect.appendChild(option);
                    }
                });
                assistantOptions.classList.add('hidden');
            }
            
            targetSelect.classList.remove('hidden');
        });

        // Add change listener for assistant selection
        document.getElementById('target-select').addEventListener('change', (e) => {
            if (document.getElementById('target-type').value === 'assistant') {
                const selectedOption = e.target.options[e.target.selectedIndex];
                document.getElementById('instructions').value = 
                    selectedOption.dataset.description;
            }
        });
    },

    updateConnectionsDisplay(connections) {
        const list = document.getElementById('connections-list');
        list.innerHTML = '';
        
        connections.forEach(conn => {
            const div = document.createElement('div');
            div.className = 'connection-item';
            div.innerHTML = `
                <span class="connection-type">${conn.type}</span>
                <span class="connection-target">${conn.target_id}</span>
                <span class="connection-status">${conn.status}</span>
                <button onclick="connectionManager.disconnect('${conn.connection_id}')">
                    Disconnect
                </button>
            `;
            list.appendChild(div);
            this.activeConnections.set(conn.connection_id, conn);
        });
    },

    updateUsersDisplay(users) {
        const list = document.getElementById('users-list');
        list.innerHTML = '';
        
        users.forEach(user => {
            const div = document.createElement('div');
            div.className = 'user-item';
            div.innerHTML = `
                <span class="user-name">${user.name}</span>
                <span class="user-status ${user.status}">${user.status}</span>
            `;
            list.appendChild(div);
            this.activeUsers.set(user.user_id, user);
        });
    },

    async initiateConnection() {
        const type = document.getElementById('connection-type').value;
        const targetType = document.getElementById('target-type').value;
        const targetId = document.getElementById('target-select').value;
        
        let connectionData = {
            action: 'start_chat',
            type,
            target_type: targetType,
            target_id: targetId
        };

        if (targetType === 'assistant') {
            connectionData.instructions = document.getElementById('instructions').value;
            connectionData.voice = document.getElementById('voice-select').value;
        }

        this.ws.send(JSON.stringify(connectionData));
    },

    disconnect(connectionId) {
        this.ws.send(JSON.stringify({
            action: 'end_chat',
            connection_id: connectionId
        }));
    },

    handleStatusUpdate(data) {
        if (data.status === 'connected_to_ai') {
            // Initialize chat interface
            connect(data.session_id, {
                instructions: document.getElementById('instructions').value,
                voice: document.getElementById('voice-select').value
            });
        }
    }
};

// Initialize connection manager
connectionManager.init();

</script>

<style>
#clients-panel {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.connection-controls {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.connection-controls select,
.connection-controls input {
    margin: 10px 0;
    padding: 8px;
    width: 100%;
}

.active-connections,
.available-users {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.connection-item,
.user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.hidden {
    display: none;
}

button {
    padding: 8px 16px;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.connection-status,
.user-status {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 0.9em;
}

.connection-status.connected { background: #4caf50; }
.connection-status.connecting { background: #ff9800; }
.connection-status.chatting_with_ai { background: #2196f3; }
.connection-status.disconnected { background: #f44336; }

.user-status.connected { background: #4caf50; }
.user-status.available { background: #8bc34a; }
.user-status.in_chat { background: #2196f3; }
.user-status.disconnected { background: #f44336; }
</style> 

<script type="text/worklet">
// Audio processor worklet code
class AudioProcessor extends AudioWorkletProcessor {
    constructor() {
        super();
        this.bufferSize = 2048;
        this.buffer = new Float32Array(this.bufferSize);
        this.bufferIndex = 0;
    }

    process(inputs, outputs, parameters) {
        const input = inputs[0];
        if (input.length > 0) {
            const channel = input[0];
            
            // Process audio data
            for (let i = 0; i < channel.length; i++) {
                this.buffer[this.bufferIndex++] = channel[i];
                
                if (this.bufferIndex >= this.bufferSize) {
                    // Convert to 16-bit PCM
                    const pcm16 = new Int16Array(this.bufferSize);
                    for (let j = 0; j < this.bufferSize; j++) {
                        pcm16[j] = Math.max(-32768, Math.min(32767, this.buffer[j] * 32768));
                    }
                    
                    // Send processed buffer to main thread
                    this.port.postMessage({
                        type: 'audio',
                        buffer: pcm16.buffer
                    }, [pcm16.buffer]);
                    
                    // Reset buffer
                    this.buffer = new Float32Array(this.bufferSize);
                    this.bufferIndex = 0;
                }
            }
        }
        return true;
    }
}

registerProcessor('audio-processor', AudioProcessor);
</script> 