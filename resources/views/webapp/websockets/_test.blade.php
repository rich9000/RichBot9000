<!-- WebSocket Test Page -->
<div class="container-fluid">
    <div class="row">
        <!-- Left Sidebar - Room Info & Participants -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Connection</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2" id="connection-status">Disconnected</span>
                        <button class="btn btn-sm" id="connection-toggle" onclick="toggleConnection()">Connect</button>
                    </div>
                </div>
                <div class="card-body">
                                <div class="input-group">
                        <input type="text" class="form-control" id="room-name" placeholder="Enter room name">
                        <button class="btn btn-primary" onclick="joinRoom()">Join</button>
                        <button class="btn btn-danger" onclick="leaveRoom()">Leave</button>
                                </div>
                            </div>
                        </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0" id="current-room-title">Current Room</h5>
                </div>
                <div class="card-body">
                    <div id="participants-list" class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">
                        No participants
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Available Rooms</h5>
                </div>
                <div class="card-body">
                    <div id="rooms-list" class="bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto;">
                        No rooms available
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Commands</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/help')">Help</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/stats')">Show Stats</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/ping')">Ping Server</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/list')">List Rooms</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/who')">Who's Here</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
                        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Chat Messages</h5>
                            </div>
                <div class="card-body">
                    <div id="messages" class="bg-light p-2 rounded mb-3" style="height: 400px; overflow-y: auto;">
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="message-input" placeholder="Type a message or command (e.g. /help)">
                        <button class="btn btn-primary" onclick="sendMessage()">Send</button>
                    </div>
                        </div>
                    </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Media Streams</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Local Media</h6>
                                    <div>
                                        <button id="share-camera-btn" class="btn btn-sm btn-outline-primary" onclick="startMediaStream('camera')">
                                            <i class="fas fa-video"></i> Share Camera
                                        </button>
                                        <button id="share-desktop-btn" class="btn btn-sm btn-outline-primary ms-2" onclick="startMediaStream('desktop')">
                                            <i class="fas fa-desktop"></i> Share Desktop
                                        </button>
                                        <button id="toggle-audio-btn" class="btn btn-sm btn-outline-primary ms-2" onclick="toggleAudio()">
                                            <i class="fas fa-microphone"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="local-streams" class="d-flex flex-wrap gap-2">
                                        <!-- Local streams will be added here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Remote Streams</h6>
                                </div>
                                <div class="card-body">
                                    <div id="remote-streams" class="d-flex flex-wrap gap-2">
                                        <!-- Remote streams will be added here -->
                                    </div>
                                </div>
                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

        <!-- Right Sidebar - Debug & Monitoring -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Debug Information</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleDebugArea()">
                        <i class="fas fa-chevron-down" id="debug-toggle-icon"></i>
                    </button>
                </div>
                <div class="card-body collapse" id="debug-area">
                    <div class="mb-3">
                        <label class="form-label">WebSocket URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="ws-url" :value="window.appConfig?.wsUrlAlt || 'wss://richbot9000.com:9502'" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Connection Stats</label>
                        <pre id="connection-stats" class="bg-light p-2 rounded">No stats available</pre>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Server Monitor</label>
                        <div id="monitoring-content" class="bg-light p-2 rounded">
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Active Clients:</span>
                                    <span id="monitor-clients">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Active Rooms:</span>
                                    <span id="monitor-rooms">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Messages:</span>
                                    <span id="monitor-messages">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Server Uptime:</span>
                                    <span id="monitor-uptime">0s</span>
                                </div>
                            </div>
                            <div class="border-top border-secondary pt-2 mt-2 small">
                                <div id="monitor-events" style="height: 100px; overflow-y: auto;">
                                    <!-- Real-time events will be added here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                            <div class="card">
                                <div class="card-header">
                    <h5 class="mb-0">Add to Room</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                        <label class="form-label">Select Assistant</label>
                        <div class="input-group">
                            <select class="form-select" id="assistant-select">
                                <option value="">Choose an assistant...</option>
                            </select>
                            <button class="btn btn-primary" onclick="addAssistantToRoom()">Add to Room</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Add Phone Call</label>
                        <div class="input-group">
                            <input type="tel" class="form-control" id="phone-number" placeholder="Enter phone number">
                            <button class="btn btn-primary" onclick="initiatePhoneCall()">Call</button>
                        </div>
                        <div id="call-status" class="alert d-none mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * WebSocket Test Page -->
<div class="container-fluid">
    <div class="row">
        <!-- Left Sidebar - Room Info & Participants -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Connection</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2" id="connection-status">Disconnected</span>
                        <button class="btn btn-sm" id="connection-toggle" onclick="toggleConnection()">Connect</button>
                    </div>
                </div>
                <div class="card-body">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="room-name" placeholder="Enter room name">
                                            <button class="btn btn-primary" onclick="joinRoom()">Join</button>
                                            <button class="btn btn-danger" onclick="leaveRoom()">Leave</button>
                                        </div>
                                    </div>
                                    </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0" id="current-room-title">Current Room</h5>
                                </div>
                <div class="card-body">
                    <div id="participants-list" class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">
                        No participants
                            </div>
                        </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Available Rooms</h5>
                </div>
                <div class="card-body">
                    <div id="rooms-list" class="bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto;">
                        No rooms available
                    </div>
                </div>
            </div>

                            <div class="card">
                                <div class="card-header">
                    <h5 class="mb-0">Quick Commands</h5>
                                </div>
                                <div class="card-body">
                    <div class="list-group">
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/help')">Help</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/stats')">Show Stats</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/ping')">Ping Server</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/list')">List Rooms</button>
                        <button class="list-group-item list-group-item-action" onclick="sendCommand('/who')">Who's Here</button>
                                </div>
                            </div>
                        </div>
                    </div>

        <!-- Main Content Area -->
        <div class="col-md-6">
            <div class="card mb-4">
                                <div class="card-header">
                    <h5 class="mb-0">Chat Messages</h5>
                                </div>
                                <div class="card-body">
                    <div id="messages" class="bg-light p-2 rounded mb-3" style="height: 400px; overflow-y: auto;">
                                    </div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="message-input" placeholder="Type a message or command (e.g. /help)">
                                            <button class="btn btn-primary" onclick="sendMessage()">Send</button>
                                        </div>
                                    </div>
                                </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Media Streams</h5>
                            </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Local Media</h6>
                                    <div>
                                        <button id="share-camera-btn" class="btn btn-sm btn-outline-primary" onclick="startMediaStream('camera')">
                                            <i class="fas fa-video"></i> Share Camera
                                        </button>
                                        <button id="share-desktop-btn" class="btn btn-sm btn-outline-primary ms-2" onclick="startMediaStream('desktop')">
                                            <i class="fas fa-desktop"></i> Share Desktop
                                        </button>
                                        <button id="toggle-audio-btn" class="btn btn-sm btn-outline-primary ms-2" onclick="toggleAudio()">
                                            <i class="fas fa-microphone"></i>
                                        </button>
                        </div>
                                </div>
                                <div class="card-body">
                                    <div id="local-streams" class="d-flex flex-wrap gap-2">
                                        <!-- Local streams will be added here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Remote Streams</h6>
                                </div>
                                <div class="card-body">
                                    <div id="remote-streams" class="d-flex flex-wrap gap-2">
                                        <!-- Remote streams will be added here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Debug & Monitoring -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Debug Information</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleDebugArea()">
                        <i class="fas fa-chevron-down" id="debug-toggle-icon"></i>
                    </button>
                </div>
                <div class="card-body collapse" id="debug-area">
                    <div class="mb-3">
                        <label class="form-label">WebSocket URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="ws-url" :value="window.appConfig?.wsUrlAlt || 'wss://richbot9000.com:9502'" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Connection Stats</label>
                        <pre id="connection-stats" class="bg-light p-2 rounded">No stats available</pre>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Server Monitor</label>
                        <div id="monitoring-content" class="bg-light p-2 rounded">
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Active Clients:</span>
                                    <span id="monitor-clients">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Active Rooms:</span>
                                    <span id="monitor-rooms">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Messages:</span>
                                    <span id="monitor-messages">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Server Uptime:</span>
                                    <span id="monitor-uptime">0s</span>
                                </div>
                            </div>
                            <div class="border-top border-secondary pt-2 mt-2 small">
                                <div id="monitor-events" style="height: 100px; overflow-y: auto;">
                                    <!-- Real-time events will be added here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add to Room</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Select Assistant</label>
                        <div class="input-group">
                            <select class="form-select" id="assistant-select">
                                <option value="">Choose an assistant...</option>
                            </select>
                            <button class="btn btn-primary" onclick="addAssistantToRoom()">Add to Room</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Add Phone Call</label>
                        <div class="input-group">
                            <input type="tel" class="form-control" id="phone-number" placeholder="Enter phone number">
                            <button class="btn btn-primary" onclick="initiatePhoneCall()">Call</button>
                        </div>
                        <div id="call-status" class="alert d-none mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * WebSocket Message Types Documentation
 * 
 * Client -> Server Messages:
 * -------------------------
 * Room Management:
 * - join: Join a room
 *   { type: 'join', room: string }
 *   Triggers: handleJoinRoom() on server
 * 
 * - leave: Leave current room
 *   { type: 'leave', room: string }
 *   Triggers: handleLeaveRoom() on server
 * 
 * Media Streaming:
 * - media_ready: Signal media stream readiness
 *   { type: 'media_ready' }
 *   Triggers: handleMediaReady() on server
 * 
 * - media_offer: Send WebRTC offer
 *   { type: 'media_offer', streamId: string, streamType: string, sdp: object }
 *   Triggers: handleMediaOffer() on server
 * 
 * - media_answer: Send WebRTC answer
 *   { type: 'media_answer', streamId: string, target_fd: number, sdp: object }
 *   Triggers: handleMediaAnswer() on server
 * 
 * - media_ice: Send ICE candidate
 *   { type: 'media_ice', streamId: string, target_fd?: number, candidate: object }
 *   Triggers: handleMediaIce() on server
 * 
 * Utility Commands:
 * - list_rooms: Request room list
 *   { type: 'list_rooms' }
 *   Triggers: handleListRooms() on server
 * 
 * - who: Request room members
 *   { type: 'who', room: string }
 *   Triggers: handleWhoInRoom() on server
 * 
 * - ping: Check connection
 *   { type: 'ping', time: number }
 *   Triggers: handlePing() on server
 * 
 * - stats: Request statistics
 *   { type: 'stats' }
 *   Triggers: handleStats() on server
 * 
 * - help: Request command help
 *   { type: 'help' }
 *   Triggers: handleHelp() on server
 * 
 * Server -> Client Messages:
 * -------------------------
 * Connection Events:
 * - welcome: Initial connection response
 *   { type: 'welcome', session_id: string, message: string, commands: object }
 *   Handled by: handleMessage() -> welcome case
 * 
 * Room Events:
 * - joined: Room join confirmation
 *   { type: 'joined', room: string }
 *   Handled by: handleMessage() -> joined case
 * 
 * - left: Room leave confirmation
 *   { type: 'left', room: string }
 *   Handled by: handleMessage() -> left case
 * 
 * - user_joined: New user in room notification
 *   { type: 'user_joined', user: number, room: string }
 *   Handled by: handleMessage() -> user_joined case
 * 
 * - user_left: User left room notification
 *   { type: 'user_left', user: number, room: string, new_owner: boolean }
 *   Handled by: handleMessage() -> user_left case
 * 
 * Media Events:
 * - peer_media_ready: Peer media stream ready notification
 *   { type: 'peer_media_ready', peer_fd: number }
 *   Handled by: handleMessage() -> peer_media_ready case
 * 
 * - media_offer: Incoming WebRTC offer
 *   { type: 'media_offer', streamId: string, streamType: string, from_fd: number, sdp: object }
 *   Handled by: handleMediaOffer()
 * 
 * - media_answer: Incoming WebRTC answer
 *   { type: 'media_answer', streamId: string, from_fd: number, sdp: object }
 *   Handled by: handleMediaAnswer()
 * 
 * - media_ice: Incoming ICE candidate
 *   { type: 'media_ice', streamId: string, from_fd: number, candidate: object }
 *   Handled by: handleMediaIce()
 * 
 * - stream_ended: Stream end notification
 *   { type: 'stream_ended', streamId: string, from_fd: number }
 *   Handled by: handleMessage() -> stream_ended case
 * 
 * Utility Responses:
 * - room_list: List of available rooms
 *   { type: 'room_list', rooms: Array<{name: string, members: number}> }
 *   Handled by: handleMessage() -> room_list case
 * 
 * - room_members: List of room members
 *   { type: 'room_members', room: string, members: Array<number> }
 *   Handled by: handleMessage() -> room_members case
 * 
 * - pong: Connection check response
 *   { type: 'pong', time: number }
 *   Handled by: handleMessage() -> pong case
 * 
 * - stats: Server statistics
 *   { type: 'stats', client: object, server: object }
 *   Handled by: handleMessage() -> stats case
 * 
 * - error: Error notification
 *   { type: 'error', message: string }
 *   Handled by: handleMessage() -> error case
 * 
 * Session Management:
 * - reconnected: Reconnection success
 *   { type: 'reconnected', session_id: string, room_id: string, media_ready: number }
 *   Handled by: handleMessage() -> reconnected case
 */

let ws = null;
let currentRoom = null;
let messageHistory = [];
let localStream = null;
let remoteStream = null;
let maxMessages = 100;
let peerConnection = null;
let streams = new Map();
let isAudioMuted = false;
const userAudioStreams = new Map();
let activeStreams = {
    camera: false,
    desktop: false
};
let sessionId = localStorage.getItem('ws_session_id');
let reconnectAttempts = 0;
let maxReconnectAttempts = 5;
let reconnectDelay = 1000; // Start with 1 second delay
let currentRoomMembers = [];

// Audio context and processing
let audioContext = null;
let audioWorklet = null;
let audioQueue = [];
let isPlaying = false;
let audioInitialized = false;

// Audio processing configuration
const AUDIO_BUFFER_SIZE = 4096;
const OUTPUT_SAMPLE_RATE = 48000; // Higher quality output sample rate
let audioBufferQueue = new Map(); // Buffer queue per user

function updateMonitoringStats(stats) {
    if (stats.server) {
        document.getElementById('monitor-clients').textContent = stats.server.active_clients || 0;
        document.getElementById('monitor-rooms').textContent = stats.server.active_rooms || 0;
        document.getElementById('monitor-messages').textContent = stats.server.total_messages || 0;
        document.getElementById('monitor-uptime').textContent = formatUptime(stats.server.uptime || 0);
    }
}

function addMonitoringEvent(event) {
    const eventsDiv = document.getElementById('monitor-events');
    const eventElement = document.createElement('div');
    eventElement.className = 'monitor-event mb-1';
    const time = new Date().toLocaleTimeString();
    eventElement.innerHTML = `<small class="text-muted">${time}</small> ${event}`;
    eventsDiv.insertBefore(eventElement, eventsDiv.firstChild);
    
    // Keep only last 50 events
    while (eventsDiv.children.length > 50) {
        eventsDiv.removeChild(eventsDiv.lastChild);
    }
}

function formatUptime(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (days > 0) return `${days}d ${hours}h`;
    if (hours > 0) return `${hours}h ${minutes}m`;
    if (minutes > 0) return `${minutes}m ${secs}s`;
    return `${secs}s`;
}

function toggleDebugArea() {
    const debugArea = document.getElementById('debug-area');
    const icon = document.getElementById('debug-toggle-icon');
    
    if (debugArea.classList.contains('show')) {
        debugArea.classList.remove('show');
        icon.className = 'fas fa-chevron-down';
    } else {
        debugArea.classList.add('show');
        icon.className = 'fas fa-chevron-up';
    }
}

async function initializeMedia() {
    try {
        // Get list of available devices
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        
        // If multiple cameras, try each one until one works
        if (videoDevices.length > 0) {
            for (const device of videoDevices) {
                try {
                    localStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            deviceId: { exact: device.deviceId }
                        },
                        audio: true
                    });
                    addMessage('System', `Using camera: ${device.label || 'Camera ' + device.deviceId}`);
                    break;
                } catch (deviceError) {
                    continue;
                }
            }
            
            if (!localStream) {
                throw new Error('No working camera found');
            }
        } else {
            // Fall back to default behavior if no devices found
        localStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        }
    } catch (error) {
        try {
            // If that fails, try just video
            localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });
        } catch (videoError) {
            try {
                // If video fails, try just audio
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: false,
                    audio: true
                });
            } catch (audioError) {
                addMessage('System', 'Error initializing media: ' + audioError.message, 'danger');
                return;
            }
        }
    }
        
    // Create a container for the local video
    const container = createVideoElement('localVideo', true);
    container.querySelector('.stream-label').textContent = 'Local Camera';
    document.getElementById('local-streams').appendChild(container);
        
    // Set the video source
    const videoElement = document.getElementById('localVideo');
    if (videoElement) {
        videoElement.srcObject = localStream;
        
        // Add error handling for the video element
        videoElement.onerror = (error) => {
            addMessage('System', 'Video element error: ' + error.message, 'danger');
        };
        
        // Add loadedmetadata handler
        videoElement.onloadedmetadata = () => {
            addMessage('System', 'Local media initialized successfully');
        };
    }
}

function connect() {
    const wsUrl = document.getElementById('ws-url').value;
    const url = new URL(wsUrl);
    
    // Add session ID to URL if we have one
    if (sessionId) {
        url.searchParams.set('session', sessionId);
    }
    
    ws = new WebSocket(url.toString());
    
    ws.onopen = () => {
        updateStatus('Connected', 'success');
        addMessage('System', 'Connected to server');
        reconnectAttempts = 0; // Reset reconnect attempts on successful connection
        reconnectDelay = 1000; // Reset reconnect delay
        initializeMedia(); // Initialize media after connection
        
   
    };
    
    ws.onclose = () => {
        updateStatus('Disconnected', 'secondary');
        addMessage('System', 'Disconnected from server');
        handleDisconnect();
    };
    
    ws.onerror = (error) => {
        updateStatus('Error', 'danger');
        addMessage('System', 'WebSocket error: ' + error.message);
    };
    
    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        handleMessage(data);
    };
}

function disconnect() {
    if (ws) {
        // Stop all streams
        streams.forEach((info, streamId) => {
            stopStream(streamId);
        });
        ws.close();
    }
}

function stopMedia() {
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        document.getElementById('localVideo').srcObject = null;
    }
    if (remoteStream) {
        remoteStream.getTracks().forEach(track => track.stop());
        document.getElementById('remoteVideo').srcObject = null;
    }
    localStream = null;
    remoteStream = null;
}

function toggleVideo() {
    if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            addMessage('System', 'Video ' + (videoTrack.enabled ? 'enabled' : 'disabled'));
        }
    }
}

function toggleAudio() {
    let audioTracks = [];
    
    // Collect all audio tracks from all active streams
    streams.forEach((streamInfo) => {
        if (streamInfo.stream) {
            audioTracks.push(...streamInfo.stream.getAudioTracks());
        }
    });

    if (audioTracks.length > 0) {
        isAudioMuted = !isAudioMuted;
        audioTracks.forEach(track => {
            track.enabled = !isAudioMuted;
        });
        updateMediaButtons();
        addMessage('System', `Audio ${isAudioMuted ? 'muted' : 'unmuted'}`);
    } else {
        addMessage('System', 'No active audio tracks found', 'warning');
    }
}

async function shareScreen() {
    try {
        const screenStream = await navigator.mediaDevices.getDisplayMedia({
            video: true,
            audio: true
        });
        
        // Replace video track
        if (localStream) {
            const oldVideoTrack = localStream.getVideoTracks()[0];
            if (oldVideoTrack) {
                oldVideoTrack.stop();
            }
            localStream.removeTrack(oldVideoTrack);
            localStream.addTrack(screenStream.getVideoTracks()[0]);
        } else {
            localStream = screenStream;
        }
        
        document.getElementById('localVideo').srcObject = localStream;
        addMessage('System', 'Screen sharing started');
        
        // Handle screen sharing stop
        screenStream.getVideoTracks()[0].onended = () => {
            initializeMedia(); // Switch back to camera
            addMessage('System', 'Screen sharing stopped');
        };
    } catch (error) {
        addMessage('System', 'Error sharing screen: ' + error.message, 'danger');
    }
}

function updateStatus(status, type) {
    const statusEl = document.getElementById('connection-status');
    const toggleBtn = document.getElementById('connection-toggle');
    
    statusEl.className = `badge bg-${type} me-2`;
    statusEl.textContent = status;
    
    if (status === 'Connected') {
        toggleBtn.className = 'btn btn-sm btn-danger';
        toggleBtn.textContent = 'Disconnect';
    } else {
        toggleBtn.className = 'btn btn-sm btn-primary';
        toggleBtn.textContent = 'Connect';
    }
}

function toggleConnection() {
    if (ws && ws.readyState === WebSocket.OPEN) {
        disconnect();
    } else {
        connect();
    }
}

function addMessage(from, message, type = 'info') {
    const messagesDiv = document.getElementById('messages');
    const messageEl = document.createElement('div');
    messageEl.className = `message message-${type} mb-2`;
    
    const timestamp = new Date().toLocaleTimeString();
    messageEl.innerHTML = `
        <small class="text-muted">${timestamp}</small>
        <strong class="ms-2">${from}:</strong>
        <span class="ms-2">${message}</span>
    `;
    
    messagesDiv.appendChild(messageEl);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    messageHistory.push({ from, message, timestamp, type });
    if (messageHistory.length > maxMessages) {
        messageHistory.shift();
        messagesDiv.removeChild(messagesDiv.firstChild);
    }
}

function handleMessage(data) {
    try {
        // Add event to monitoring
        switch(data.type) {
            case 'joined':
                addMonitoringEvent(`User joined room: ${data.room}`);
                break;
            case 'left':
                addMonitoringEvent(`User left room: ${data.room}`);
                break;
            case 'user_joined':
                addMonitoringEvent(`User ${data.user} joined`);
                break;
            case 'user_left':
                addMonitoringEvent(`User ${data.user} left`);
                break;
            case 'stats':
                updateMonitoringStats(data);
                break;
        }
        
    switch (data.type) {
        case 'welcome':
            // Store session ID from welcome message
            if (data.session_id) {
                sessionId = data.session_id;
                localStorage.setItem('ws_session_id', sessionId);
            }
            addMessage('Server', data.message);
            if (data.commands) {
                addMessage('Server', 'Available commands:', 'info');
                Object.entries(data.commands).forEach(([cmd, desc]) => {
                    addMessage('Server', `${cmd}: ${desc}`, 'info');
                });
            }
            break;
            
        case 'joined':
            currentRoom = data.room;
            addMessage('System', `Joined room: ${data.room}`, 'success');
            currentRoomMembers = data.members || [];
            updateRoomInfo(currentRoomMembers);
            break;
            
        case 'left':
            currentRoom = null;
            currentRoomMembers = [];
            addMessage('System', `Left room: ${data.room}`, 'info');
            updateRoomInfo('Not in a room');
            break;
            
        case 'user_joined':
            addMessage('System', `User ${data.user} joined room ${data.room}`, 'info');
            // Get current members list from the participants-list element
            const currentMembers = Array.isArray(currentRoomMembers) ? currentRoomMembers : [];
            
            // Create new member object from the join data
            const newMember = {
                fd: data.user,
                type: data.member_type || 'user',
                name: data.member_name || `User ${data.user}`,
                assistant: data.member_assistant
            };
            
            // Check if member already exists
            const memberExists = currentMembers.some(m => m.fd === newMember.fd);
            if (!memberExists) {
                currentRoomMembers = [...currentMembers, newMember];
                updateRoomInfo(currentRoomMembers);
            }
            break;
            
        case 'user_left':
            addMessage('System', `User ${data.user} left room ${data.room}`, 'info');
            // Remove the user from the current members list
            currentRoomMembers = currentRoomMembers.filter(m => m.fd !== data.user);
            updateRoomInfo(currentRoomMembers);
            break;
            
        case 'message':
            addMessage(`User ${data.from}`, data.message, 'chat');
            break;
            
            case 'transcript_delta':
                if (data.delta) {
                    // Add transcript delta to messages with a special style
                    addMessage('Transcript', data.delta, 'transcript-delta');
                }
                break;
            
            case 'transcript_complete':
                if (data.transcript) {
                    // Add complete transcript to messages
                    addMessage('Transcript Complete', data.transcript, 'transcript');
                }
            break;
            
        case 'room_list':
                updateRoomsList(data.rooms);
            break;
            
        case 'room_members':
                updateRoomInfo(data.members.map(member => `User ${member}`).join('<br>'));
            break;
            
        case 'pong':
            const latency = Date.now() - data.time;
            addMessage('Server', `Pong! Latency: ${latency}ms`, 'success');
            break;
            
        case 'error':
            addMessage('Error', data.message, 'danger');
            break;
            
        case 'media_offer':
            handleMediaOffer(data);
            break;
            
        case 'media_answer':
            handleMediaAnswer(data);
            break;
            
        case 'media_ice':
            handleMediaIce(data);
            break;
            
        case 'stream_ended':
            const container = document.getElementById(`remote-${data.streamId}`)?.parentElement;
            if (container) {
                container.remove();
                addMessage('System', `Remote stream ended: ${data.streamId}`);
            }
            break;
            
        case 'reconnected':
            addMessage('System', 'Successfully reconnected with previous session', 'success');
            // Restore room if we were in one
            if (data.room_id) {
                currentRoom = data.room_id;
                updateRoomInfo(`Reconnected to room: ${data.room_id}`);
            }
            break;
            
            case 'call_status':
                showCallStatus(data.message, data.status);
                break;
            
            case 'media_data':
                handleMediaData(data);
            break;
        }
    } catch (error) {
        addMessage('System', 'Error handling message: ' + error.message, 'danger');
    }
}

function handleMediaData(data) {
    if (!data.data) {
        console.error('No audio data received');
        return;
    }

    // Initialize audio context if needed
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }

    // Get or create audio stream for this user
    let userAudio = userAudioStreams.get(data.from_fd);
    if (!userAudio) {
        userAudio = {
            muted: false,
            gainNode: audioContext.createGain(),
            compressor: audioContext.createDynamicsCompressor(),
            lowpass: audioContext.createBiquadFilter()
        };

        // Set up audio processing chain for Twilio
        if (data.source === 'twilio') {
            // Configure lowpass filter
            userAudio.lowpass.type = 'lowpass';
            userAudio.lowpass.frequency.value = 4000; // Cut off at 4kHz for 8kHz audio
            userAudio.lowpass.Q.value = 0.7; // Moderate resonance

            // Configure compressor for voice
            userAudio.compressor.threshold.value = -24;
            userAudio.compressor.knee.value = 30;
            userAudio.compressor.ratio.value = 12;
            userAudio.compressor.attack.value = 0.003;
            userAudio.compressor.release.value = 0.25;

            // Connect the processing chain
            userAudio.gainNode.connect(userAudio.compressor);
            userAudio.compressor.connect(userAudio.lowpass);
            userAudio.lowpass.connect(audioContext.destination);
        } else {
            // Simple chain for OpenAI
            userAudio.gainNode.connect(audioContext.destination);
        }

        userAudioStreams.set(data.from_fd, userAudio);
    }

    // Skip if muted
    if (userAudio.muted) {
        return;
    }

    try {
        // Convert base64 to binary data
        const binaryString = atob(data.data);
        let audioData;

        if (data.source === 'openai') {
            // OpenAI sends 24kHz PCM data - simple decode
            audioData = new Int16Array(binaryString.length / 2);
            for (let i = 0; i < binaryString.length; i += 2) {
                const low = binaryString.charCodeAt(i);
                const high = binaryString.charCodeAt(i + 1);
                audioData[i/2] = (high << 8) | low;
            }
        } else {
            // Twilio sends 8kHz μ-law data - needs special processing
            audioData = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                audioData[i] = binaryString.charCodeAt(i);
            }
            // Convert μ-law to PCM
            const pcmData = new Int16Array(audioData.length);
            for (let i = 0; i < audioData.length; i++) {
                pcmData[i] = ulawToLinear(audioData[i]);
            }
            audioData = pcmData;
        }

        // Convert to normalized float32 (-1 to 1)
        const float32Data = new Float32Array(audioData.length);
        for (let i = 0; i < audioData.length; i++) {
            float32Data[i] = audioData[i] / 32768.0;
        }

        // Create audio buffer with appropriate sample rate
        const sampleRate = data.source === 'openai' ? 24000 : 8000;
        const audioBuffer = audioContext.createBuffer(1, float32Data.length, sampleRate);
        audioBuffer.getChannelData(0).set(float32Data);

        // Create and configure source
        const source = audioContext.createBufferSource();
        source.buffer = audioBuffer;
        
        // Adjust gain based on source
        userAudio.gainNode.gain.value = data.source === 'twilio' ? 2.5 : 0.8; // Increased gain for Twilio
        
        // Connect and play
        source.connect(userAudio.gainNode);
        source.start(0);

        console.log(`Playing audio from ${data.source}:`, {
            sampleRate,
            samples: float32Data.length,
            duration: audioBuffer.duration
        });
    } catch (error) {
        console.error('Error processing audio data:', error);
    }
}

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

function resampleAudio(audioData, originalSampleRate, targetSampleRate) {
    if (originalSampleRate === targetSampleRate) {
        return audioData;
    }

    const ratio = targetSampleRate / originalSampleRate;
    const newLength = Math.round(audioData.length * ratio);
    const result = new Float32Array(newLength);
    
    for (let i = 0; i < newLength; i++) {
        const originalIndex = i / ratio;
        const index1 = Math.floor(originalIndex);
        const index2 = Math.min(index1 + 1, audioData.length - 1);
        const fraction = originalIndex - index1;
        
        // Linear interpolation
        result[i] = audioData[index1] * (1 - fraction) + audioData[index2] * fraction;
    }
    
    return result;
}

function updateUserVolume(userId, value) {
    const userAudio = userAudioStreams.get(userId);
    if (userAudio) {
        userAudio.gainNode.gain.value = parseFloat(value);
    }
}

function toggleUserAudio(userId) {
    let userAudio = userAudioStreams.get(userId);
    
    // If no audio stream exists yet, create one
    if (!userAudio) {
        userAudio = {
            muted: false,
            gainNode: audioContext.createGain()
        };
        userAudioStreams.set(userId, userAudio);
        userAudio.gainNode.connect(audioContext.destination);
    }
    
    // Toggle mute state
    userAudio.muted = !userAudio.muted;
    userAudio.gainNode.gain.value = userAudio.muted ? 0 : 1;
    
    // Update button UI
    const button = document.querySelector(`button.mute-button[onclick="toggleUserAudio(${userId})"]`);
    if (button) {
        const icon = button.querySelector('i');
        icon.className = userAudio.muted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
        button.classList.toggle('btn-outline-secondary', !userAudio.muted);
        button.classList.toggle('btn-secondary', userAudio.muted);
        button.title = userAudio.muted ? 'Unmute user' : 'Mute user';
        
        // Find the participant name
        const participantName = button.closest('.participant-item')?.querySelector('.participant-info')?.textContent.trim() || `User ${userId}`;
        
        // Add message to chat
        addMessage('System', `${userAudio.muted ? 'Muted' : 'Unmuted'} ${participantName}`, 'info');
    }
}

function handleMedia(data) {
    console.log('Received media offer:', data);
}

function handleMediaAnswer(data) {
    console.log('Received media answer:', data);
}

function updateRoomInfo(members) {
    const titleEl = document.getElementById('current-room-title');
    const participantsEl = document.getElementById('participants-list');
    
    if (currentRoom) {
        titleEl.textContent = `Room: ${currentRoom}`;
        
        // Update participants list with mute buttons
        if (Array.isArray(members)) {
            participantsEl.innerHTML = members.map(member => {
                const isMuted = userAudioStreams.get(member.fd)?.muted || false;
                const memberType = member.type || 'user';
                const memberName = member.name || `User ${member.fd}`;
                
                return `
                    <div class="participant-item">
                        <div class="participant-info">
                            <span class="participant-type ${memberType}">${memberType}</span>
                            <span>${memberName}</span>
                            ${member.assistant ? `<small class="text-muted">(${member.assistant})</small>` : ''}
                        </div>
                        <button class="btn ${isMuted ? 'btn-secondary' : 'btn-outline-secondary'} mute-button" 
                                onclick="toggleUserAudio(${member.fd})" 
                                title="${isMuted ? 'Unmute' : 'Mute'} user">
                            <i class="fas ${isMuted ? 'fa-volume-mute' : 'fa-volume-up'}"></i>
                        </button>
                    </div>
                `;
            }).join('') || 'No participants';
        } else {
            participantsEl.innerHTML = 'No participants';
        }
    } else {
        titleEl.textContent = 'Current Room';
        participantsEl.innerHTML = 'No participants';
    }
}

function updateRoomsList(rooms) {
    const roomsList = document.getElementById('rooms-list');
    if (!rooms || rooms.length === 0) {
        roomsList.innerHTML = 'No rooms available';
        return;
    }

    const roomsHtml = rooms.map(room => `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>${room.name}</span>
            <span class="badge bg-secondary">${room.members} members</span>
        </div>
    `).join('');
    
    roomsList.innerHTML = roomsHtml;
}

function updateStats(stats) {
    const statsEl = document.getElementById('connection-stats');
    statsEl.textContent = JSON.stringify(stats, null, 2);
}

function sendMessage() {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
        addMessage('Error', 'Not connected to server', 'danger');
        return;
    }
    
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (message) {
        if (message.startsWith('/')) {
            handleCommand(message);
        } else {
            ws.send(JSON.stringify({
                type: 'message',
                room: currentRoom,
                message: message
            }));
            addMessage('You', message, 'chat');
        }
        input.value = '';
    }
}

function sendCommand(command) {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
        addMessage('Error', 'Not connected to server', 'danger');
        return;
    }

    switch (command) {
        case '/help':
            ws.send(JSON.stringify({ type: 'help' }));
            break;
        case '/stats':
            ws.send(JSON.stringify({ type: 'stats' }));
            break;
        case '/ping':
            ws.send(JSON.stringify({
                type: 'ping',
                time: Date.now()
            }));
            break;
        case '/list':
            ws.send(JSON.stringify({ type: 'list_rooms' }));
            break;
        case '/who':
            if (currentRoom) {
                ws.send(JSON.stringify({ 
                    type: 'who',
                    room: currentRoom
                }));
            } else {
                addMessage('System', 'You are not in a room', 'warning');
            }
            break;
        default:
            addMessage('System', `Unknown command: ${command}`, 'error');
    }
}

function joinRoom(roomName = null) {
    if (!roomName) {
        roomName = document.getElementById('room-name').value.trim();
    }
    
    if (roomName) {
        ws.send(JSON.stringify({
            type: 'join',
            room: roomName
        }));
    } else {
        addMessage('System', 'Please enter a room name', 'error');
    }
}

function leaveRoom() {
    if (currentRoom) {
        // Stop all streams
        streams.forEach((info, streamId) => {
            stopStream(streamId);
        });
        ws.send(JSON.stringify({
            type: 'leave',
            room: currentRoom
        }));
    }
}

function listRooms() {
    ws.send(JSON.stringify({ type: 'list_rooms' }));
}

function whoInRoom() {
    if (currentRoom) {
        ws.send(JSON.stringify({
            type: 'who',
            room: currentRoom
        }));
    } else {
        addMessage('System', 'You are not in a room', 'info');
    }
}

function copyUrl() {
    const urlInput = document.getElementById('ws-url');
    urlInput.select();
    document.execCommand('copy');
    showAlert('URL copied to clipboard', 'success');
}

// Handle Enter key in message input
document.getElementById('message-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Show initial WebSocket URL
document.getElementById('ws-url').value = `wss://${window.location.hostname}:9502`;

function createVideoElement(id, muted = false) {
    const container = document.createElement('div');
    container.className = 'stream-container';
    container.style.width = '300px';
    
    const video = document.createElement('video');
    video.id = id;
    video.className = 'w-100 rounded';
    video.autoplay = true;
    video.playsinline = true;
    video.muted = muted;
    
    const label = document.createElement('div');
    label.className = 'stream-label';
    label.textContent = id;
    
    container.appendChild(video);
    container.appendChild(label);
    return container;
}

function updateMediaButtons() {
    // Update camera button
    const cameraBtn = document.getElementById('share-camera-btn');
    if (activeStreams.camera) {
        cameraBtn.classList.remove('btn-outline-primary');
        cameraBtn.classList.add('btn-success');
        cameraBtn.innerHTML = '<i class="fas fa-video"></i> Camera Shared';
        cameraBtn.disabled = true;
    } else {
        cameraBtn.classList.remove('btn-success');
        cameraBtn.classList.add('btn-outline-primary');
        cameraBtn.innerHTML = '<i class="fas fa-video"></i> Share Camera';
        cameraBtn.disabled = false;
    }

    // Update desktop button
    const desktopBtn = document.getElementById('share-desktop-btn');
    if (activeStreams.desktop) {
        desktopBtn.classList.remove('btn-outline-primary');
        desktopBtn.classList.add('btn-success');
        desktopBtn.innerHTML = '<i class="fas fa-desktop"></i> Screen Shared';
        desktopBtn.disabled = true;
    } else {
        desktopBtn.classList.remove('btn-success');
        desktopBtn.classList.add('btn-outline-primary');
        desktopBtn.innerHTML = '<i class="fas fa-desktop"></i> Share Desktop';
        desktopBtn.disabled = false;
    }

    // Update audio button
    const audioBtn = document.getElementById('toggle-audio-btn');
    if (isAudioMuted) {
        audioBtn.classList.remove('btn-outline-primary');
        audioBtn.classList.add('btn-danger');
        audioBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
    } else {
        audioBtn.classList.remove('btn-danger');
        audioBtn.classList.add('btn-outline-primary');
        audioBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    }
}

async function startMediaStream(type = 'camera') {
    if (!ws || !currentRoom) {
        addMessage('System', 'Must be connected and in a room to stream', 'warning');
        return;
    }

    if (activeStreams[type]) {
        addMessage('System', `${type} is already being shared`, 'warning');
        return;
    }

    try {
        // Get media stream based on type
        let stream;
        if (type === 'desktop') {
            stream = await navigator.mediaDevices.getDisplayMedia({
                video: true,
                audio: true
            });
        } else {
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
        }

        // Create unique ID for this stream
        const streamId = `${type}-${Date.now()}`;
        
        // Add to local display
        const container = createVideoElement(`local-${streamId}`, true);
        container.querySelector('.stream-label').textContent = `Local ${type}`;
        document.getElementById('local-streams').appendChild(container);
        document.getElementById(`local-${streamId}`).srcObject = stream;

        // Create peer connection for this stream
        const peerConnection = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        });
        
        // Add stream to peer connection
        stream.getTracks().forEach(track => {
            peerConnection.addTrack(track, stream);
        });

        // Handle incoming stream
        peerConnection.ontrack = (event) => {
            const remoteStreamId = `remote-${streamId}`;
            if (!document.getElementById(remoteStreamId)) {
                const container = createVideoElement(remoteStreamId);
                container.querySelector('.stream-label').textContent = `Remote ${type}`;
                document.getElementById('remote-streams').appendChild(container);
                document.getElementById(remoteStreamId).srcObject = event.streams[0];
            }
        };

        // Handle ICE candidates
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                ws.send(JSON.stringify({
                    type: 'media_ice',
                    streamId: streamId,
                    candidate: event.candidate
                }));
            }
        };

        // Handle connection state changes
        peerConnection.onconnectionstatechange = () => {
            addMessage('System', `Connection state for ${type}: ${peerConnection.connectionState}`);
        };

        // Create and send offer
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);

        ws.send(JSON.stringify({
            type: 'media_offer',
            streamId: streamId,
            streamType: type,
            sdp: peerConnection.localDescription
        }));

        // Store stream info
        streams.set(streamId, {
            stream: stream,
            connection: peerConnection,
            type: type
        });

        // Update active streams and buttons
        activeStreams[type] = true;
        updateMediaButtons();

        // Handle stream end
        stream.getTracks().forEach(track => {
            track.onended = () => {
                stopStream(streamId);
            };
        });

        addMessage('System', `Started ${type} stream`);
    } catch (error) {
        addMessage('System', `Error starting ${type} stream: ${error.message}`, 'danger');
        activeStreams[type] = false;
        updateMediaButtons();
    }
}

function stopStream(streamId) {
    const streamInfo = streams.get(streamId);
    if (streamInfo) {
        // Stop all tracks
        if (streamInfo.stream) {
            streamInfo.stream.getTracks().forEach(track => track.stop());
        }
        
        // Close peer connection
        if (streamInfo.connection) {
            streamInfo.connection.close();
        }
        
        // Remove video elements
        const localContainer = document.getElementById(`local-${streamId}`)?.parentElement;
        if (localContainer) {
            localContainer.remove();
        }
        const remoteContainer = document.getElementById(`remote-${streamId}`)?.parentElement;
        if (remoteContainer) {
            remoteContainer.remove();
        }
        
        // Remove from streams map
        streams.delete(streamId);
        
        // Update active streams state
        activeStreams[streamInfo.type] = false;
        updateMediaButtons();
        
        // Notify others
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'stream_ended',
                streamId: streamId
            }));
        }
        
        addMessage('System', `Stopped stream: ${streamId}`);
    }
}

async function handleMediaOffer(data) {
    try {
        // Create new peer connection for this stream
        const peerConnection = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        });
        
        // Handle incoming stream
        peerConnection.ontrack = (event) => {
            const remoteStreamId = `remote-${data.streamId}`;
            if (!document.getElementById(remoteStreamId)) {
                const container = createVideoElement(remoteStreamId);
                container.querySelector('.stream-label').textContent = `Remote ${data.streamType}`;
                document.getElementById('remote-streams').appendChild(container);
                document.getElementById(remoteStreamId).srcObject = event.streams[0];
                addMessage('System', `Received ${data.streamType} stream`);
            }
        };

        // Handle ICE candidates
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                ws.send(JSON.stringify({
                    type: 'media_ice',
                    streamId: data.streamId,
                    target_fd: data.from_fd,
                    candidate: event.candidate
                }));
            }
        };

        // Handle connection state changes
        peerConnection.onconnectionstatechange = () => {
            addMessage('System', `Connection state for remote ${data.streamType}: ${peerConnection.connectionState}`);
        };

        // Set remote description and create answer
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);

        // Send answer back
        ws.send(JSON.stringify({
            type: 'media_answer',
            streamId: data.streamId,
            target_fd: data.from_fd,
            sdp: peerConnection.localDescription
        }));

        // Store stream info
        streams.set(data.streamId, {
            connection: peerConnection,
            type: data.streamType
        });

        addMessage('System', `Answered ${data.streamType} stream offer`);
    } catch (error) {
        addMessage('System', 'Error handling offer: ' + error.message, 'danger');
    }
}

async function handleMediaAnswer(data) {
    try {
        const streamInfo = streams.get(data.streamId);
        if (streamInfo && streamInfo.connection) {
            await streamInfo.connection.setRemoteDescription(new RTCSessionDescription(data.sdp));
            addMessage('System', `Received answer for ${streamInfo.type} stream`);
        }
    } catch (error) {
        addMessage('System', 'Error handling answer: ' + error.message, 'danger');
    }
}

async function handleMediaIce(data) {
    try {
        const streamInfo = streams.get(data.streamId);
        if (streamInfo && streamInfo.connection && data.candidate) {
            await streamInfo.connection.addIceCandidate(new RTCIceCandidate(data.candidate));
        }
    } catch (error) {
        addMessage('System', 'Error handling ICE candidate: ' + error.message, 'danger');
    }
}

function handleDisconnect() {
    // Stop all streams
    streams.forEach((info, streamId) => {
        stopStream(streamId);
    });

    // Attempt to reconnect if we have a session and haven't exceeded max attempts
    if (sessionId && reconnectAttempts < maxReconnectAttempts) {
        updateStatus('Reconnecting...', 'warning');
        addMessage('System', `Attempting to reconnect (${reconnectAttempts + 1}/${maxReconnectAttempts})...`);
        
        // Exponential backoff for reconnect delay
        setTimeout(() => {
            reconnectAttempts++;
            connect();
        }, reconnectDelay);
        
        // Increase delay for next attempt (max 30 seconds)
        reconnectDelay = Math.min(reconnectDelay * 2, 30000);
    } else if (reconnectAttempts >= maxReconnectAttempts) {
        updateStatus('Reconnection Failed', 'danger');
        addMessage('System', 'Maximum reconnection attempts reached. Please try connecting manually.', 'danger');
        sessionId = null;
        localStorage.removeItem('ws_session_id');
    }
}

function populateAssistantSelect() {
    const select = document.getElementById('assistant-select');
    select.innerHTML = '<option value="">Choose an assistant...</option>';
    
    if (appState.data && appState.data.assistants) {
        appState.data.assistants.forEach(assistant => {


            console.log('assistant', assistant);


            if (assistant.interactive === true && assistant.type === 'assistant') {
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = `${assistant.name} (${assistant.model.name})`;
                option.title = assistant.system_message; // Add tooltip with system message
                select.appendChild(option);
            }
        });
    }
}

function addAssistantToRoom() {
    const select = document.getElementById('assistant-select');
    const assistantId = select.value;
    
    if (!assistantId) {
        addMessage('System', 'Please select an assistant first', 'warning');
        return;
    }
    
    if (!currentRoom) {
        addMessage('System', 'Please join a room first', 'warning');
        return;
    }
    
    const assistant = appState.data.assistants.find(a => a.id == assistantId);
    if (!assistant) {
        addMessage('System', 'Selected assistant not found', 'error');
        return;
    }
    
    // Send WebSocket message to add assistant to room
    ws.send(JSON.stringify({
        type: 'add_assistant',
        room: currentRoom,
        assistant_id: assistantId
    }));
    
    addMessage('System', `Adding ${assistant.name} to the room...`, 'info');
    select.value = ''; // Reset selection
}

function initiatePhoneCall() {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
        showCallStatus('Not connected to server', 'danger');
        return;
    }

    if (!currentRoom) {
        showCallStatus('Please join a room first', 'warning');
        return;
    }

    const phoneNumber = document.getElementById('phone-number').value.trim();
    if (!phoneNumber) {
        showCallStatus('Please enter a phone number', 'warning');
        return;
    }

    // Send phone call request to server
    ws.send(JSON.stringify({
        type: 'initiate_call',
        room: currentRoom,
        phone_number: phoneNumber
    }));

    showCallStatus('Initiating call...', 'info');
}

function showCallStatus(message, type) {
    const statusDiv = document.getElementById('call-status');
    statusDiv.className = `alert alert-${type}`;
    statusDiv.textContent = message;
    statusDiv.classList.remove('d-none');
}

// Initialize button states
updateMediaButtons();
populateAssistantSelect();

// Auto-refresh stats every 10 seconds
setInterval(() => {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'stats' }));
    }
}, 10000);

// Auto-refresh rooms list every 30 seconds
setInterval(() => {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'list_rooms' }));
    }
}, 30000);

async function initializeAudio() {
    if (audioInitialized) {
        console.log('Audio already initialized');
        return;
    }

    try {
        // Create audio context with error handling
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        if (!audioContext) {
            throw new Error('Failed to create AudioContext');
        }

        // Resume audio context (needed for some browsers)
        if (audioContext.state === 'suspended') {
            await audioContext.resume();
        }

        // Load and initialize the audio worklet
        try {
            console.log('Loading audio worklet module...');
            await audioContext.audioWorklet.addModule('/webapp_public/audio-processor.js');
            console.log('Audio worklet module loaded successfully');

            // Create audio worklet node after module is loaded
            try {
                console.log('Creating AudioWorkletNode...');
                audioWorklet = new AudioWorkletNode(audioContext, 'audio-processor');
                console.log('AudioWorkletNode created successfully');

                // Handle audio data from worklet
                audioWorklet.port.onmessage = (event) => {
                    if (event.data.type === 'audio_data') {
                        // Convert μ-law data to base64 and send via WebSocket
                        const base64Data = btoa(String.fromCharCode.apply(null, event.data.data));
                        if (ws && ws.readyState === WebSocket.OPEN) {
                            ws.send(JSON.stringify({
                                type: 'media',
                                data: base64Data
                            }));
                        }
                    }
                };

                // Connect worklet to destination
                audioWorklet.connect(audioContext.destination);
                
                console.log('Audio system initialized successfully');
                audioInitialized = true;

                // Send media_ready message to server
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'media_ready'                
                    }));
                    addMessage('System', 'Media system ready', 'success');
                }
            } catch (nodeError) {
                throw new Error('Failed to create AudioWorkletNode: ' + nodeError.message);
            }
        } catch (moduleError) {
            throw new Error('Failed to load audio worklet module: ' + moduleError.message);
        }
    } catch (error) {
        console.error('Failed to initialize audio system:', error);
        addMessage('System', 'Failed to initialize audio system: ' + error.message, 'danger');
        audioInitialized = false;
        audioContext = null;
        audioWorklet = null;
        
        // Attempt recovery after a short delay
        setTimeout(() => {
            if (!audioInitialized) {
                console.log('Attempting audio system recovery...');
                initializeAudio().catch(console.error);
            }
        }, 2000);
    }
}

// Initialize audio when document is ready and on user interaction

    // Try to initialize audio on page load with a slight delay
    setTimeout(() => {
        initializeAudio().catch(console.error);
    }, 1000);

    // Also initialize on first user interaction (required by some browsers)
    const initOnInteraction = async () => {
        try {
            await initializeAudio();
        } catch (error) {
            console.error('Failed to initialize audio on interaction:', error);
        }
        document.removeEventListener('click', initOnInteraction);
        document.removeEventListener('touchstart', initOnInteraction);
    };
    
    document.addEventListener('click', initOnInteraction);
    document.addEventListener('touchstart', initOnInteraction);

async function playNextInQueue() {
    if (isPlaying || audioQueue.length === 0) {
        return;
    }

    isPlaying = true;
    const audioData = audioQueue.shift();

    try {
        if (!audioContext) {
            await initializeAudio();
        }

        // Convert μ-law to linear PCM
        const samples = new Float32Array(audioData.data.length);
        for (let i = 0; i < audioData.data.length; i++) {
            samples[i] = ulawToLinear(audioData.data[i]) / 32768.0;
        }

        // Create audio buffer with appropriate sample rate
        const sampleRate = audioData.source === 'openai' ? 24000 : 8000;
        const audioBuffer = audioContext.createBuffer(1, samples.length, sampleRate);
        audioBuffer.getChannelData(0).set(samples);

        // Create audio processing nodes
        const source = audioContext.createBufferSource();
        source.buffer = audioBuffer;

        const gainNode = audioContext.createGain();
        gainNode.gain.value = audioData.source === 'twilio' ? 2.0 : 0.8;

        const compressor = audioContext.createDynamicsCompressor();
        compressor.threshold.value = -24;
        compressor.knee.value = 30;
        compressor.ratio.value = 12;
        compressor.attack.value = 0.003;
        compressor.release.value = 0.25;

        const lowpass = audioContext.createBiquadFilter();
        lowpass.type = 'lowpass';
        lowpass.frequency.value = audioData.source === 'twilio' ? 4000 : 8000;
        lowpass.Q.value = 0.7;

        // Connect the audio processing chain
        source.connect(gainNode);
        gainNode.connect(compressor);
        compressor.connect(lowpass);
        lowpass.connect(audioData.gainNode);

        // Handle completion
        source.onended = () => {
            isPlaying = false;
            playNextInQueue();
        };

        // Start playback
        source.start();

    } catch (error) {
        console.error('Error playing audio:', error);
        isPlaying = false;
        playNextInQueue(); // Try next item in queue
    }
}

</script>

<style>
.message {
    padding: 8px;
    border-radius: 4px;
    margin-bottom: 8px;
}

.message-info {
    background-color: #f8f9fa;
}

.message-success {
    background-color: #d4edda;
}

.message-danger {
    background-color: #f8d7da;
}

.message-chat {
    background-color: #e9ecef;
}

.message-command {
    background-color: #cff4fc;
}

.message-transcript-delta {
    background-color: #e3f2fd;
    font-style: italic;
}

.message-transcript {
    background-color: #bbdefb;
    font-weight: 500;
}

video {
    background-color: #000;
    border-radius: 8px;
}

.stream-container {
    position: relative;
    margin-bottom: 1rem;
}

.stream-label {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    background: rgba(0,0,0,0.5);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

#monitor-events {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.3) transparent;
}

#monitor-events::-webkit-scrollbar {
    width: 6px;
}

#monitor-events::-webkit-scrollbar-track {
    background: transparent;
}

#monitor-events::-webkit-scrollbar-thumb {
    background-color: rgba(255,255,255,0.3);
    border-radius: 3px;
}

#participants-list, #rooms-list {
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.2) transparent;
}

#participants-list::-webkit-scrollbar,
#rooms-list::-webkit-scrollbar {
    width: 6px;
}

#participants-list::-webkit-scrollbar-track,
#rooms-list::-webkit-scrollbar-track {
    background: transparent;
}

#participants-list::-webkit-scrollbar-thumb,
#rooms-list::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

#messages {
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.2) transparent;
}

#messages::-webkit-scrollbar {
    width: 6px;
}

#messages::-webkit-scrollbar-track {
    background: transparent;
}

#messages::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.bg-light {
    background-color: #f8f9fa !important;
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    color: white;
}

.badge {
    font-weight: 500;
}

.input-group .form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}

#debug-area {
    transition: all 0.3s ease;
}

#debug-area.show {
    display: block;
}

#debug-toggle-icon {
    transition: transform 0.3s ease;
}

#debug-area.show #debug-toggle-icon {
    transform: rotate(180deg);
}

#connection-toggle {
    transition: all 0.3s ease;
}

#connection-toggle.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

#connection-toggle.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

#rooms-list .badge {
    font-size: 0.75rem;
}

#participants-list, #rooms-list {
    font-size: 0.875rem;
}

.participant-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #eee;
}

.participant-item:last-child {
    border-bottom: none;
}

.participant-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.participant-type {
    font-size: 0.8em;
    padding: 0.2em 0.5em;
    border-radius: 3px;
}

.participant-type.twilio {
    background-color: #F22F46;
    color: white;
}

.participant-type.openai {
    background-color: #10A37F;
    color: white;
}

.participant-type.user {
    background-color: #6C757D;
    color: white;
}

.mute-button {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.mute-button i {
    width: 1rem;
    text-align: center;
}
</style> 