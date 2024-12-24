<div id="manager-dashboard">
    <div id="manager-info">
        <h2>WebSocket Manager Dashboard</h2>
        <div id="manager-fd">Manager FD: Not Connected</div>
    </div>

    <!-- Connection Status -->
    <div id="status-panel">
        <div id="connection-status">Status: Disconnected</div>
        <button onclick="reconnectWebSocket()" id="reconnect-btn">Reconnect</button>
    </div>

    <!-- Active Clients -->
    <div id="clients-panel">
        <h3>Connected Clients</h3>
        <div id="client-list" class="list-container">
            <!-- Client list will be dynamically populated -->
        </div>
    </div>

    <!-- Active Chats -->
    <div id="chats-panel">
        <h3>Active Chats</h3>
        <div id="chat-list" class="list-container">
            <!-- Chat list will be dynamically populated -->
        </div>
    </div>

    <!-- Debug Panel -->
    <div id="debug-panel">
        <h3>Debug Logs</h3>
        <div id="debug-log" class="debug-container">
            <!-- Debug logs will be dynamically populated -->
        </div>
    </div>
</div>

<script>
let socket = null;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
let activeStreams = new Map();

function initializeManager() {
    connectWebSocket();
}

function connectWebSocket() {
    try {
        socket = new WebSocket(`wss://richbot9000.com:9501?token=${appState.apiToken}`);
        setupSocketListeners();
    } catch (error) {
        logDebug(`Connection error: ${error.message}`);
    }
}

function setupSocketListeners() {
    socket.addEventListener('open', () => {
        updateConnectionStatus('Connected');
        reconnectAttempts = 0;
        
        // Identify as Manager
        socket.send(JSON.stringify({
            event: 'connected',
            protocol: 'Manager'
        }));
    });

    socket.addEventListener('message', handleSocketMessage);
    socket.addEventListener('close', handleSocketClose);
    socket.addEventListener('error', (error) => {
        logDebug(`WebSocket error: ${error.message}`);
    });
}

function handleSocketMessage(event) {
    try {
        const data = JSON.parse(event.data);
        
        // Log all non-media messages
        if (data.event !== 'media') {
            logDebug(`Received: ${JSON.stringify(data)}`);
        }

        switch (data.event) {
            case 'connection_established':
                document.getElementById('manager-fd').textContent = `Manager FD: ${data.fd}`;
                break;

            case 'state_update':
                updateState(data);
                break;

            case 'client_connected':
                handleClientConnected(data);
                break;

            case 'client_disconnected':
                handleClientDisconnected(data);
                break;

            case 'chat_started':
                handleChatStarted(data);
                break;

            case 'chat_ended':
                handleChatEnded(data);
                break;

            case 'media':
                handleMediaMessage(data);
                break;
        }
    } catch (error) {
        logDebug(`Error handling message: ${error.message}`);
    }
}

function handleSocketClose() {
    updateConnectionStatus('Disconnected');
    attemptReconnect();
}

function attemptReconnect() {
    if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
        reconnectAttempts++;
        const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000);
        logDebug(`Reconnecting in ${delay/1000} seconds (attempt ${reconnectAttempts})`);
        setTimeout(connectWebSocket, delay);
    } else {
        logDebug('Max reconnect attempts reached');
        updateConnectionStatus('Disconnected (Max retries reached)');
    }
}

function updateState(data) {
    updateClientList(data.clients || {});
    updateChatList(data.activeChats || {});
}

function handleClientConnected(data) {
    addClient(data.clientId, data.clientData);
    logDebug(`Client connected: ${data.clientData.userName} (${data.clientId})`);
}

function handleClientDisconnected(data) {
    removeClient(data.clientId);
    logDebug(`Client disconnected: ${data.clientId}`);
}

function handleChatStarted(data) {
    addChat(data.chatId, data.chatData);
    logDebug(`Chat started: ${data.chatId}`);
}

function handleChatEnded(data) {
    removeChat(data.chatId);
    logDebug(`Chat ended: ${data.chatId}`);
}

function handleMediaMessage(data) {
    if (activeStreams.has(data.chatId)) {
        playAudioStream(data.media);
    }
}

function updateClientList(clients) {
    const clientList = document.getElementById('client-list');
    clientList.innerHTML = '';
    
    Object.entries(clients).forEach(([id, client]) => {
        const clientElement = createClientElement(id, client);
        clientList.appendChild(clientElement);
    });
}

function updateChatList(chats) {
    const chatList = document.getElementById('chat-list');
    chatList.innerHTML = '';
    
    Object.entries(chats).forEach(([id, chat]) => {
        const chatElement = createChatElement(id, chat);
        chatList.appendChild(chatElement);
    });
}

function createClientElement(clientId, clientData) {
    const div = document.createElement('div');
    div.className = 'client-item';
    div.innerHTML = `
        <div class="client-info">
            <span class="client-name">${clientData.userName || 'Anonymous'}</span>
            <span class="client-id">(FD: ${clientId})</span>
            <span class="client-type badge-${clientData.type.toLowerCase()}">${clientData.type}</span>
            <span class="client-status badge-${clientData.status.toLowerCase()}">${clientData.status}</span>
            ${clientData.chatId ? `<span class="chat-badge">Chat: ${clientData.chatId}</span>` : ''}
        </div>
        <div class="client-details">
            <div>Last Activity: ${new Date(clientData.lastActivity * 1000).toLocaleTimeString()}</div>
            <div>User ID: ${clientData.user_id}</div>
        </div>
    `;
    return div;
}

function createChatElement(chatId, chatData) {
    const div = document.createElement('div');
    div.className = 'chat-item';
    const isStreaming = activeStreams.has(chatId);
    
    const participantsList = chatData.participants.map(p => {
        if (p.fd) {
            return `<div class="participant">
                <span class="participant-role">${p.role}</span>
                <span class="participant-fd">FD: ${p.fd}</span>
            </div>`;
        } else if (p.assistant_id) {
            return `<div class="participant">
                <span class="participant-role">AI Assistant</span>
                <span class="participant-id">ID: ${p.assistant_id}</span>
            </div>`;
        }
    }).join('');

    div.innerHTML = `
        <div class="chat-header">
            <span class="chat-id">Chat ID: ${chatId}</span>
            <span class="chat-type badge-${chatData.type.toLowerCase()}">${chatData.type}</span>
            <span class="chat-status badge-${chatData.status.toLowerCase()}">${chatData.status}</span>
        </div>
        <div class="chat-details">
            <div class="chat-times">
                <div>Started: ${new Date(chatData.startTime * 1000).toLocaleTimeString()}</div>
                <div>Last Activity: ${new Date(chatData.lastActivity * 1000).toLocaleTimeString()}</div>
            </div>
            <div class="chat-participants">
                <h4>Participants:</h4>
                ${participantsList}
            </div>
        </div>
        <div class="chat-controls">
            <button onclick="toggleChatStream('${chatId}')" class="stream-btn ${isStreaming ? 'active' : ''}">
                ${isStreaming ? 'Stop Streaming' : 'Start Streaming'}
            </button>
        </div>
    `;
    return div;
}

function toggleChatStream(chatId) {
    if (activeStreams.has(chatId)) {
        stopChatStream(chatId);
    } else {
        startChatStream(chatId);
    }
}

function startChatStream(chatId) {
    socket.send(JSON.stringify({
        event: 'start_stream',
        chatId: chatId
    }));
    activeStreams.set(chatId, true);
    updateChatList(currentChats); // Refresh UI
}

function stopChatStream(chatId) {
    socket.send(JSON.stringify({
        event: 'stop_stream',
        chatId: chatId
    }));
    activeStreams.delete(chatId);
    updateChatList(currentChats); // Refresh UI
}

async function playAudioStream(mediaData) {
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        const audioBuffer = await audioContext.decodeAudioData(mediaData.buffer);
        const source = audioContext.createBufferSource();
        source.buffer = audioBuffer;
        source.connect(audioContext.destination);
        source.start();
    } catch (error) {
        logDebug(`Audio playback error: ${error.message}`);
    }
}

function updateConnectionStatus(status) {
    document.getElementById('connection-status').textContent = `Status: ${status}`;
}

function logDebug(message) {
    const debugLog = document.getElementById('debug-log');
    const timestamp = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = 'debug-entry';
    entry.innerHTML = `<span class="debug-timestamp">[${timestamp}]</span> ${message}`;
    debugLog.insertBefore(entry, debugLog.firstChild);

    // Keep only last 100 entries
    while (debugLog.children.length > 100) {
        debugLog.removeChild(debugLog.lastChild);
    }
}

function reconnectWebSocket() {
    if (socket) socket.close();
    reconnectAttempts = 0;
    connectWebSocket();
}

// Initialize the manager
initializeManager();
</script>

<style>
#manager-dashboard {
    max-width: 1200px;
    margin: 20px auto;
    font-family: Arial, sans-serif;
}

#manager-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

#status-panel {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.list-container {
    max-height: 400px;
    overflow-y: auto;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.client-item, .chat-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.client-info, .chat-info {
    display: flex;
    gap: 10px;
    align-items: center;
}

.client-id, .chat-id {
    color: #666;
    font-size: 0.9em;
}

.client-type, .chat-type {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 0.8em;
}

.chat-participants {
    font-size: 0.9em;
    color: #666;
}

.debug-container {
    font-family: monospace;
    font-size: 12px;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
}

.debug-entry {
    padding: 3px 0;
    border-bottom: 1px solid #eee;
}

.debug-timestamp {
    color: #666;
    margin-right: 8px;
}

button {
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
}

button:hover {
    background: #f5f5f5;
}

.stream-btn {
    background: #2196f3;
    color: white;
    border: none;
}

.stream-btn:hover {
    background: #1976d2;
}

.badge-client, .badge-manager, .badge-assistant {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    color: white;
}

.badge-client { background-color: #2196f3; }
.badge-manager { background-color: #4caf50; }
.badge-assistant { background-color: #ff9800; }

.badge-connected { background-color: #4caf50; }
.badge-in_chat { background-color: #2196f3; }
.badge-disconnected { background-color: #f44336; }

.chat-badge {
    background-color: #9c27b0;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.client-details, .chat-details {
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.chat-times {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
}

.chat-participants {
    margin-top: 10px;
}

.participant {
    display: flex;
    gap: 10px;
    align-items: center;
    margin: 5px 0;
}

.participant-role {
    font-weight: bold;
}

.stream-btn.active {
    background-color: #f44336;
}

.stream-btn.active:hover {
    background-color: #d32f2f;
}

.architecture-panel {
    margin-top: 40px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.file-structure {
    margin-top: 20px;
}

.file-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 15px 0;
}

.file-item {
    background: white;
    padding: 15px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.file-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.file-name {
    font-family: monospace;
    color: #2196f3;
}

.file-type {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.file-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.5;
}

.flow-diagram {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border-radius: 6px;
}

.flow-diagram pre {
    white-space: pre;
    font-family: monospace;
    line-height: 1.5;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow-x: auto;
}

code {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9em;
}
</style>

<div id="system-architecture" class="architecture-panel">
    <h3>System Architecture</h3>
    <div class="file-structure">
        <h4>Core Files</h4>
        <div class="file-list">
            <div class="file-item">
                <div class="file-header">
                    <span class="file-name">app/Console/Commands/RealtimeWebsocket.php</span>
                    <span class="file-type">Command</span>
                </div>
                <div class="file-description">
                    Main WebSocket server implementation. Handles server configuration, SSL setup, and delegates 
                    connection handling to ConnectionManager. Run with: <code>php artisan websocket:serve</code>
                </div>
            </div>

            <div class="file-item">
                <div class="file-header">
                    <span class="file-name">app/Services/ConnectionManager.php</span>
                    <span class="file-type">Service</span>
                </div>
                <div class="file-description">
                    Manages all WebSocket connections, handles message routing, and maintains connection state.
                    Uses OpenSwoole Tables for shared memory storage of clients and chats.
                </div>
            </div>

            <div class="file-item">
                <div class="file-header">
                    <span class="file-name">app/Services/AIRelay.php</span>
                    <span class="file-type">Service</span>
                </div>
                <div class="file-description">
                    Handles bi-directional communication between local WebSocket server and OpenAI's realtime API.
                    Creates separate coroutines for message relay in both directions.
                </div>
            </div>
        </div>

        <h4>Frontend Components</h4>
        <div class="file-list">
            <div class="file-item">
                <div class="file-header">
                    <span class="file-name">resources/views/webapp/websockets/_manager.blade.php</span>
                    <span class="file-type">View</span>
                </div>
                <div class="file-description">
                    Admin dashboard for monitoring WebSocket connections, active chats, and system status.
                    Provides real-time updates and connection management capabilities.
                </div>
            </div>

            <div class="file-item">
                <div class="file-header">
                    <span class="file-name">resources/views/webapp/websockets/_client.blade.php</span>
                    <span class="file-type">View</span>
                </div>
                <div class="file-description">
                    Client interface for connecting to WebSocket server, initiating AI chats,
                    and handling real-time audio/text communication.
                </div>
            </div>
        </div>

        <h4>Data Flow</h4>
        <div class="flow-diagram">
            <pre>
Client Browser ←→ WebSocket Server (RealtimeWebsocket)
                          ↓
                  ConnectionManager
                     ↙     ↘
                AIRelay  AIRelay (one per AI chat)
                   ↓        ↓
                OpenAI    OpenAI
            </pre>
        </div>

        <h4>Detailed Connection Flow</h4>
        <div class="flow-diagram">
            <pre>
1. Initial Connection Flow:
   Client → Enter Name → Click Connect
   ↓
   initializeClient()
   ↓
   new WebSocket(wss://richbot9000.com:9501) → ConnectionManager.handleNewConnection()
   ↓
   Authenticate Token → Register Client → Send Connection Established
   ↓
   Client Dashboard Displayed

2. Assistant Connection Flow:
   Client → Click "Connect" on Assistant
   ↓
   connectToAssistant(assistantId)
   ↓
   WebSocket.send(start_chat) → ConnectionManager.handleStartChat()
   ↓
   Create AIRelay Instance → Connect to OpenAI
   ↓
   Register Chat → Update Client Status → Notify Client
   ↓
   Initialize Audio Stream

3. Message Flow (Text):
   Client Types Message → sendMessage()
   ↓
   WebSocket → ConnectionManager.handleMessage()
   ↓
   AIRelay.handleLocalMessage()
   ↓
   OpenAI WebSocket
   ↓
   Response → AIRelay.handleOpenAIMessage()
   ↓
   ConnectionManager → Client WebSocket
   ↓
   handleSocketMessage() → Display Message

4. Audio Flow:
   Client Microphone → AudioContext Processing
   ↓
   PCM Audio Data → WebSocket (media event)
   ↓
   ConnectionManager → AIRelay
   ↓
   OpenAI WebSocket
   ↓
   AI Generated Audio → AIRelay
   ↓
   ConnectionManager → Client WebSocket
   ↓
   AudioContext Playback

5. Connection Management:
   ConnectionManager
   ├── Clients Table (OpenSwoole)
   ├── Chats Table
   ├── Stream Subscriptions
   └── Active AIRelays

6. Monitoring:
   Manager Dashboard
   ├── Connected Clients
   ├── Active Chats
   ├── Stream Status
   └── Debug Logs
        </pre>
        </div>
    </div>
</div>
