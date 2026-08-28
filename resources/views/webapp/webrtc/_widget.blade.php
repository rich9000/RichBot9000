<!-- WebRTC Widget -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">WebRTC Communication</h5>
                </div>
                <div class="card-body">
                    <!-- Room Configuration -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="roomId" class="form-label">Room ID</label>
                                <input type="text" class="form-control" id="roomId" placeholder="Enter room ID">
                            </div>
                            <div class="mb-3">
                                <label for="maxParticipants" class="form-label">Max Participants</label>
                                <input type="number" class="form-control" id="maxParticipants" value="10" min="2" max="20">
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
                                <label class="form-label">Room Status</label>
                                <div id="room-status" class="text-muted">Not in a room</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Connection Status</label>
                                <div id="connection-status" class="text-muted">Not connected</div>
                            </div>
                        </div>
                    </div>

                    <!-- Participants List -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Room Participants</h6>
                                </div>
                                <div class="card-body">
                                    <div id="participants-list" class="list-group">
                                        <!-- Participants will be added here dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Video Streams -->
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
        this.userId = appState.userId || 'anonymous';
        this.participants = new Map();
        this.isRoomOwner = false;
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
        
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                this.updateStatus('WebSocket connected');
                resolve();
            };

            this.ws.onmessage = async (event) => {
                const message = JSON.parse(event.data);
                
                switch (message.type) {
                    case 'welcome':
                        this.updateStatus('Connected to signaling server');
                        break;
                    case 'joined':
                        await this.handleRoomJoined(message);
                        break;
                    case 'peer-joined':
                        await this.handlePeerJoined(message);
                        break;
                    case 'peer-left':
                        this.handlePeerLeft(message);
                        break;
                    case 'offer':
                        await this.handleOffer(message);
                        break;
                    case 'answer':
                        await this.handleAnswer(message);
                        break;
                    case 'ice-candidate':
                        await this.handleCandidate(message);
                        break;
                    case 'error':
                        this.handleError(message);
                        break;
                }
            };

            this.ws.onclose = () => {
                this.updateStatus('WebSocket disconnected');
                this.handleDisconnect();
            };

            this.ws.onerror = (error) => {
                this.updateStatus('WebSocket error: ' + error.message);
                reject(error);
            };
        });
    }

    async createPeerConnection(targetPeerId = null) {
        if (!this.turnConfig) {
            throw new Error('TURN configuration not initialized');
        }

        if (this.peerConnection) {
            this.peerConnection.close();
        }

        this.peerConnection = new RTCPeerConnection({
            iceServers: this.turnConfig.iceServers
        });

        this.peerConnection.onicecandidate = (event) => {
            if (event.candidate && targetPeerId) {
                this.ws.send(JSON.stringify({
                    type: 'ice-candidate',
                    room_id: this.roomId,
                    target_fd: targetPeerId,
                    data: event.candidate
                }));
            }
        };

        this.peerConnection.ontrack = (event) => {
            this.remoteStream = event.streams[0];
            document.getElementById('remoteVideo').srcObject = this.remoteStream;
            this.updateStatus('Remote stream received');
        };

        this.peerConnection.oniceconnectionstatechange = () => {
            this.updateStatus('ICE Connection State: ' + this.peerConnection.iceConnectionState);
        };

        this.localStream.getTracks().forEach(track => {
            this.peerConnection.addTrack(track, this.localStream);
        });

        return this.peerConnection;
    }

    async joinRoom(roomId) {
        try {
            this.roomId = roomId;
            await this.connectWebSocket();

            const maxParticipants = parseInt(document.getElementById('maxParticipants').value) || 10;
            
            if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    type: 'join',
                    room_id: roomId,
                    user_id: this.userId,
                    settings: {
                        max_participants: maxParticipants
                    },
                    client_info: {
                        browser: navigator.userAgent,
                        timestamp: Date.now()
                    }
                }));
                this.updateRoomStatus('Joining room: ' + roomId);
            } else {
                throw new Error('WebSocket is not connected');
            }
        } catch (error) {
            this.updateStatus('Error joining room: ' + error.message);
            throw error;
        }
    }

    async handleRoomJoined(message) {
        this.updateRoomStatus('Joined room: ' + message.room_id);
        this.isRoomOwner = message.room.owner_id === this.userId;
        
        // Initialize participants list
        message.room.participants.forEach(participant => {
            if (participant.user_id !== this.userId) {
                this.participants.set(participant.fd, participant);
                this.updateParticipantsList();
            }
        });

        // Create peer connection if there are other participants
        if (message.room.participants.length > 1) {
            const otherPeer = [...this.participants.values()][0];
            await this.createPeerConnection(otherPeer.fd);
            // If we're not the room owner, wait for offer
            if (!this.isRoomOwner) {
                this.updateStatus('Waiting for connection from room owner...');
            } else {
                // As room owner, create and send offer to the first peer
                await this.createAndSendOffer(otherPeer.fd);
            }
        }
    }

    async handlePeerJoined(message) {
        const peer = message.peer;
        this.participants.set(peer.fd, peer);
        this.updateParticipantsList();
        
        // If we're the room owner, initiate connection with the new peer
        if (this.isRoomOwner) {
            await this.createPeerConnection(peer.fd);
            await this.createAndSendOffer(peer.fd);
        }
    }

    async createAndSendOffer(peerId) {
        try {
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);
            
            this.ws.send(JSON.stringify({
                type: 'offer',
                room_id: this.roomId,
                target_fd: peerId,
                data: offer
            }));
            
            this.updateStatus('Sent connection offer to peer');
        } catch (error) {
            this.updateStatus('Error creating offer: ' + error.message);
        }
    }

    async handleOffer(message) {
        try {
            await this.createPeerConnection(message.sender_fd);
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message.data));
            
            const answer = await this.peerConnection.createAnswer();
            await this.peerConnection.setLocalDescription(answer);
            
            this.ws.send(JSON.stringify({
                type: 'answer',
                room_id: this.roomId,
                target_fd: message.sender_fd,
                data: answer
            }));
            
            this.updateStatus('Sent connection answer to peer');
        } catch (error) {
            this.updateStatus('Error handling offer: ' + error.message);
        }
    }

    async handleAnswer(message) {
        try {
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message.data));
            this.updateStatus('Received answer from peer');
        } catch (error) {
            this.updateStatus('Error handling answer: ' + error.message);
        }
    }

    async handleCandidate(message) {
        try {
            if (this.peerConnection) {
                await this.peerConnection.addIceCandidate(new RTCIceCandidate(message.data));
            }
        } catch (error) {
            this.updateStatus('Error handling ICE candidate: ' + error.message);
        }
    }

    handlePeerLeft(message) {
        const peer = message.peer;
        this.participants.delete(peer.fd);
        this.updateParticipantsList();
        
        if (peer.was_owner && message.new_owner === this.userId) {
            this.isRoomOwner = true;
            this.updateRoomStatus('You are now the room owner');
        }
        
        this.updateStatus(`Peer left: ${peer.user_id}`);
    }

    handleError(message) {
        this.updateStatus('Error: ' + message.message);
        showAlert(message.message, 'danger');
    }

    handleDisconnect() {
        this.participants.clear();
        this.updateParticipantsList();
        this.updateRoomStatus('Disconnected from room');
        
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
    }

    leaveRoom() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'leave',
                room_id: this.roomId
            }));
        }

        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }

        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }

        if (this.remoteStream) {
            this.remoteStream.getTracks().forEach(track => track.stop());
        }

        document.getElementById('localVideo').srcObject = null;
        document.getElementById('remoteVideo').srcObject = null;

        this.participants.clear();
        this.updateParticipantsList();
        this.updateRoomStatus('Left room');
        this.updateStatus('Disconnected');
    }

    updateParticipantsList() {
        const listElement = document.getElementById('participants-list');
        listElement.innerHTML = '';

        this.participants.forEach((participant, fd) => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <div>
                    <span class="fw-bold">${participant.user_id}</span>
                    <span class="badge bg-secondary ms-2">${participant.status}</span>
                </div>
                <div>
                    ${fd === this.userId ? '<span class="badge bg-primary">You</span>' : ''}
                    ${participant.user_id === this.roomOwner ? '<span class="badge bg-success">Owner</span>' : ''}
                </div>
            `;
            listElement.appendChild(item);
        });

        if (this.participants.size === 0) {
            listElement.innerHTML = '<div class="list-group-item text-muted">No participants</div>';
        }
    }

    updateStatus(status) {
        document.getElementById('connection-status').textContent = status;
    }

    updateRoomStatus(status) {
        document.getElementById('room-status').textContent = status;
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
</script> 