<!-- WebRTC Dashboard -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>WebRTC Services</h2>
            <p class="text-muted">Manage WebRTC services and monitor their status</p>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                <button class="btn btn-primary" onclick="startServices()">
                    <i class="fas fa-play me-2"></i>Start All
                </button>
                <button class="btn btn-danger" onclick="stopServices()">
                    <i class="fas fa-stop me-2"></i>Stop All
                </button>
                <button class="btn btn-warning" onclick="restartServices()">
                    <i class="fas fa-sync me-2"></i>Restart All
                </button>
            </div>
        </div>
    </div>

    <!-- Active Rooms Monitor -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active Rooms</h5>
                    <button class="btn btn-primary" onclick="refreshRoomsStatus()">
                        <i class="fas fa-sync me-2"></i>Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Room ID</th>
                                    <th>Owner</th>
                                    <th>Participants</th>
                                    <th>Created</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="activeRoomsTable">
                                <tr>
                                    <td colspan="7" class="text-center">Loading rooms...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TURN Server Test -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">TURN Server Test</h5>
                    <button class="btn btn-primary" onclick="testTurnServer()">
                        <i class="fas fa-sync me-2"></i>Run Test
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Current Configuration</h6>
                            <pre class="bg-light p-3 rounded"><code id="turnConfig">Loading...</code></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Test Results</h6>
                            <div id="turnTestResults" class="bg-light p-3 rounded">
                                <div class="text-muted">Click "Run Test" to check TURN server connectivity</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Status Cards -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">WebRTC WebSocket Server</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary" onclick="startService('websocket')">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="stopService('websocket')">
                            <i class="fas fa-stop"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="restartService('websocket')">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Status:</span>
                        <span id="websocket-status" class="badge bg-secondary">Checking...</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>PID:</span>
                        <span id="websocket-pid">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Connected Clients:</span>
                        <span id="websocket-clients">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Coturn STUN/TURN Server</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary" onclick="startService('coturn')">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="stopService('coturn')">
                            <i class="fas fa-stop"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="restartService('coturn')">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Status:</span>
                        <span id="coturn-status" class="badge bg-secondary">Checking...</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>PID:</span>
                        <span id="coturn-pid">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Active Sessions:</span>
                        <span id="coturn-sessions">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WebRTC Widget -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">WebRTC Communication Widget</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="roomId" class="form-label">Room ID</label>
                                <input type="text" class="form-control" id="roomId" placeholder="Enter room ID">
                            </div>
                            <button class="btn btn-primary" onclick="joinRoom()">
                                <i class="fas fa-sign-in-alt me-2"></i>Join Room
                            </button>
                            <button class="btn btn-danger" onclick="leaveRoom()">
                                <i class="fas fa-sign-out-alt me-2"></i>Leave Room
                            </button>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div id="connection-status" class="text-muted">Not connected</div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Local Video</h6>
                                </div>
                                <div class="card-body">
                                    <video id="localVideo" autoplay muted playsinline class="w-100"></video>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleVideo()">
                                            <i class="fas fa-video"></i> Toggle Video
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleAudio()">
                                            <i class="fas fa-microphone"></i> Toggle Audio
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Remote Video</h6>
                                </div>
                                <div class="card-body">
                                    <video id="remoteVideo" autoplay playsinline class="w-100"></video>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// TURN Server Test Function
async function testTurnServer() {
    const resultsDiv = document.getElementById('turnTestResults');
    resultsDiv.innerHTML = '<div class="text-info">Testing TURN server connectivity...</div>';

    try {
        // Get fresh TURN credentials
        const response = await fetch('/api/webrtc/turn-credentials', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        const turnConfig = await response.json();
        document.getElementById('turnConfig').textContent = JSON.stringify(turnConfig, null, 2);

        // Create a temporary peer connection
        const pc = new RTCPeerConnection({
            iceServers: turnConfig.iceServers
        });

        // Create a data channel
        const channel = pc.createDataChannel('test');
        
        // Set up event handlers
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                resultsDiv.innerHTML += `<div class="text-success">ICE candidate received: ${event.candidate.type}</div>`;
            }
        };

        pc.oniceconnectionstatechange = () => {
            resultsDiv.innerHTML += `<div class="text-info">ICE connection state: ${pc.iceConnectionState}</div>`;
        };

        pc.onicegatheringstatechange = () => {
            resultsDiv.innerHTML += `<div class="text-info">ICE gathering state: ${pc.iceGatheringState}</div>`;
        };

        // Create and set local description
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        // Wait for ICE gathering to complete
        await new Promise(resolve => setTimeout(resolve, 5000));

        // Close the connection
        pc.close();

        resultsDiv.innerHTML += '<div class="text-success">TURN server test completed successfully!</div>';
    } catch (error) {
        resultsDiv.innerHTML += `<div class="text-danger">Error testing TURN server: ${error.message}</div>`;
    }
}

// Display current TURN configuration
async function updateTurnConfig() {
    try {
        const response = await fetch('/api/webrtc/turn-credentials', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        const turnConfig = await response.json();
        document.getElementById('turnConfig').textContent = JSON.stringify(turnConfig, null, 2);
    } catch (error) {
        document.getElementById('turnConfig').textContent = 'Error loading TURN configuration: ' + error.message;
    }
}

// Initialize dashboard
updateTurnConfig();

// WebRTC Client Class
class WebRTCClient {
    constructor() {
        this.ws = null;
        this.peerConnection = null;
        this.localStream = null;
        this.remoteStream = null;
        this.roomId = null;
        this.isInitiator = false;
        this.turnConfig = null;
    }

    async initialize() {
        try {
            // Fetch TURN credentials from server
            const response = await fetch('/api/webrtc/turn-credentials', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            this.turnConfig = await response.json();
            
            // Display current TURN configuration
            document.getElementById('turnConfig').textContent = JSON.stringify(this.turnConfig, null, 2);

            // Initialize media devices
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            document.getElementById('localVideo').srcObject = this.localStream;
            this.updateStatus('Local media initialized');
        } catch (error) {
            this.updateStatus('Error initializing: ' + error.message);
        }
    }

    connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.hostname}:9502`;
        
        this.ws = new WebSocket(wsUrl);
        
        this.ws.onopen = () => {
            this.updateStatus('WebSocket connected');
        };

        this.ws.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            
            switch (message.type) {
                case 'offer':
                    await this.handleOffer(message);
                    break;
                case 'answer':
                    await this.handleAnswer(message);
                    break;
                case 'candidate':
                    await this.handleCandidate(message);
                    break;
                case 'room_update':
                    this.handleRoomUpdate(message);
                    break;
            }
        };

        this.ws.onclose = () => {
            this.updateStatus('WebSocket disconnected');
        };

        this.ws.onerror = (error) => {
            this.updateStatus('WebSocket error: ' + error.message);
        };
    }

    async createPeerConnection() {
        if (!this.turnConfig) {
            throw new Error('TURN configuration not initialized');
        }

        this.peerConnection = new RTCPeerConnection({
            iceServers: this.turnConfig.iceServers
        });

        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.ws.send(JSON.stringify({
                    type: 'candidate',
                    candidate: event.candidate,
                    roomId: this.roomId
                }));
            }
        };

        this.peerConnection.ontrack = (event) => {
            this.remoteStream = event.streams[0];
            document.getElementById('remoteVideo').srcObject = this.remoteStream;
            this.updateStatus('Remote stream received');
        };

        this.localStream.getTracks().forEach(track => {
            this.peerConnection.addTrack(track, this.localStream);
        });
    }

    async joinRoom(roomId) {
        this.roomId = roomId;
        this.connectWebSocket();
        await this.createPeerConnection();

        this.ws.send(JSON.stringify({
            type: 'join',
            roomId: roomId
        }));

        this.updateStatus('Joining room: ' + roomId);
    }

    leaveRoom() {
        if (this.ws) {
            this.ws.send(JSON.stringify({
                type: 'leave',
                roomId: this.roomId
            }));
        }

        if (this.peerConnection) {
            this.peerConnection.close();
        }

        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }

        if (this.remoteStream) {
            this.remoteStream.getTracks().forEach(track => track.stop());
        }

        document.getElementById('localVideo').srcObject = null;
        document.getElementById('remoteVideo').srcObject = null;

        this.updateStatus('Left room');
    }

    async handleOffer(message) {
        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message));
        const answer = await this.peerConnection.createAnswer();
        await this.peerConnection.setLocalDescription(answer);

        this.ws.send(JSON.stringify({
            type: 'answer',
            answer: answer,
            roomId: this.roomId
        }));

        this.updateStatus('Received and answered offer');
    }

    async handleAnswer(message) {
        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message));
        this.updateStatus('Received answer');
    }

    async handleCandidate(message) {
        await this.peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
        this.updateStatus('Received ICE candidate');
    }

    handleRoomUpdate(message) {
        this.updateStatus('Room update: ' + message.status);
    }

    updateStatus(status) {
        document.getElementById('connection-status').textContent = status;
    }

    toggleVideo() {
        const videoTrack = this.localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            this.updateStatus('Video ' + (videoTrack.enabled ? 'enabled' : 'disabled'));
        }
    }

    toggleAudio() {
        const audioTrack = this.localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            this.updateStatus('Audio ' + (audioTrack.enabled ? 'enabled' : 'disabled'));
        }
    }
}

// Initialize WebRTC client
const webrtcClient = new WebRTCClient();
webrtcClient.initialize();

// Service Management Functions
async function updateServiceStatus() {
    try {
        const response = await fetch('/api/webrtc/status', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        const status = await response.json();

        // Update WebSocket status
        const wsStatus = document.getElementById('websocket-status');
        const wsPid = document.getElementById('websocket-pid');
        const wsClients = document.getElementById('websocket-clients');
        wsStatus.className = 'badge ' + (status.websocket.running ? 'bg-success' : 'bg-danger');
        wsStatus.textContent = status.websocket.running ? 'Running' : 'Stopped';
        wsPid.textContent = status.websocket.pid || '-';
        wsClients.textContent = status.websocket.clients || '0';

        // Update Coturn status
        const coturnStatus = document.getElementById('coturn-status');
        const coturnPid = document.getElementById('coturn-pid');
        const coturnSessions = document.getElementById('coturn-sessions');
        coturnStatus.className = 'badge ' + (status.coturn.running ? 'bg-success' : 'bg-danger');
        coturnStatus.textContent = status.coturn.running ? 'Running' : 'Stopped';
        coturnPid.textContent = status.coturn.pid || '-';
        coturnSessions.textContent = status.coturn.sessions || '0';
    } catch (error) {
        console.error('Error updating service status:', error);
    }
}

async function refreshRoomsStatus() {
    try {
        const response = await fetch('/api/webrtc/rooms', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        const rooms = await response.json();
        
        const tableBody = document.getElementById('activeRoomsTable');
        tableBody.innerHTML = '';
        
        if (rooms.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">No active rooms</td>
                </tr>
            `;
            return;
        }
        
        rooms.forEach(room => {
            const created = new Date(room.created_at * 1000).toLocaleString();
            const lastActivity = new Date(room.last_activity * 1000).toLocaleString();
            
            tableBody.innerHTML += `
                <tr>
                    <td>${room.room_id}</td>
                    <td>${room.owner_id}</td>
                    <td>
                        <span class="badge bg-primary">${room.participants.length}</span>
                        <button class="btn btn-sm btn-link" onclick="showParticipants('${room.room_id}')">
                            View
                        </button>
                    </td>
                    <td>${created}</td>
                    <td>${lastActivity}</td>
                    <td>
                        <span class="badge bg-${room.status === 'active' ? 'success' : 'warning'}">
                            ${room.status}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="closeRoom('${room.room_id}')">
                            Close
                        </button>
                    </td>
                </tr>
            `;
        });
    } catch (error) {
        console.error('Error refreshing rooms status:', error);
        showAlert('Error refreshing rooms status', 'danger');
    }
}

async function showParticipants(roomId) {
    try {
        const response = await fetch(`/api/webrtc/rooms/${roomId}/participants`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        const participants = await response.json();
        
        let participantsList = participants.map(p => `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">${p.user_id}</span>
                    <span class="badge bg-secondary ms-2">${p.status}</span>
                </div>
                <div>
                    ${p.user_id === participants[0].user_id ? '<span class="badge bg-success">Owner</span>' : ''}
                    <button class="btn btn-sm btn-danger ms-2" onclick="disconnectParticipant('${roomId}', ${p.fd})">
                        Disconnect
                    </button>
                </div>
            </div>
        `).join('');
        
        // Show modal with participants
        const modal = new bootstrap.Modal(document.createElement('div'));
        modal.element.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Room Participants</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group">
                            ${participantsList}
                        </div>
                    </div>
                </div>
            </div>
        `;
        modal.show();
    } catch (error) {
        console.error('Error showing participants:', error);
        showAlert('Error showing participants', 'danger');
    }
}

async function closeRoom(roomId) {
    if (!confirm(`Are you sure you want to close room ${roomId}?`)) {
        return;
    }
    
    try {
        const response = await fetch(`/api/webrtc/rooms/${roomId}/close`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            showAlert('Room closed successfully', 'success');
            refreshRoomsStatus();
        } else {
            showAlert('Failed to close room', 'danger');
        }
    } catch (error) {
        console.error('Error closing room:', error);
        showAlert('Error closing room', 'danger');
    }
}

async function disconnectParticipant(roomId, fd) {
    try {
        const response = await fetch(`/api/webrtc/rooms/${roomId}/participants/${fd}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            showAlert('Participant disconnected successfully', 'success');
            showParticipants(roomId); // Refresh participants list
        } else {
            showAlert('Failed to disconnect participant', 'danger');
        }
    } catch (error) {
        console.error('Error disconnecting participant:', error);
        showAlert('Error disconnecting participant', 'danger');
    }
}

// Service control functions
async function startService(service) {
    try {
        const response = await fetch(`/api/webrtc/services/${service}/start`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        if (response.ok) {
            updateServiceStatus();
            showAlert('Service started successfully', 'success');
        } else {
            showAlert('Failed to start service', 'danger');
        }
    } catch (error) {
        console.error('Error starting service:', error);
        showAlert('Error starting service', 'danger');
    }
}

async function stopService(service) {
    try {
        const response = await fetch(`/api/webrtc/services/${service}/stop`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        if (response.ok) {
            updateServiceStatus();
            showAlert('Service stopped successfully', 'success');
        } else {
            showAlert('Failed to stop service', 'danger');
        }
    } catch (error) {
        console.error('Error stopping service:', error);
        showAlert('Error stopping service', 'danger');
    }
}

async function restartService(service) {
    try {
        const response = await fetch(`/api/webrtc/services/${service}/restart`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        });
        if (response.ok) {
            updateServiceStatus();
            showAlert('Service restarted successfully', 'success');
        } else {
            showAlert('Failed to restart service', 'danger');
        }
    } catch (error) {
        console.error('Error restarting service:', error);
        showAlert('Error restarting service', 'danger');
    }
}

async function startServices() {
    await startService('websocket');
    await startService('coturn');
}

async function stopServices() {
    await stopService('websocket');
    await stopService('coturn');
}

async function restartServices() {
    await restartService('websocket');
    await restartService('coturn');
}

// WebRTC Functions
function joinRoom() {
    const roomId = document.getElementById('roomId').value;
    if (!roomId) {
        showAlert('Please enter a room ID', 'warning');
        return;
    }
    webrtcClient.joinRoom(roomId);
}

function leaveRoom() {
    webrtcClient.leaveRoom();
}

function toggleVideo() {
    webrtcClient.toggleVideo();
}

function toggleAudio() {
    webrtcClient.toggleAudio();
}

// Initialize dashboard
updateServiceStatus();
refreshRoomsStatus();

// Set up periodic updates
setInterval(updateServiceStatus, 5000);
setInterval(refreshRoomsStatus, 10000);
</script> 