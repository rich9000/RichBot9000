<!-- WebSocket RTC System Information -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">WebSocket RTC System Overview</h5>
                </div>
                <div class="card-body">
                    <p class="lead">
                        The WebSocket RTC system enables real-time video and audio communication between users through WebRTC technology, 
                        facilitated by a custom WebSocket signaling server. This system supports peer-to-peer connections with STUN/TURN 
                        server fallback for NAT traversal.
                    </p>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Implementation Status:</strong> ✅ = Implemented, 🚧 = Partially Implemented, ❌ = Planned
                    </div>

                    <h6 class="mt-4">Key Components</h6>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <strong>WebSocket Signaling Server</strong> 🚧
                            <p>Custom OpenSwoole-based WebSocket server handling client connections, room management, and signaling.</p>
                            <ul>
                                <li>✅ Basic WebSocket server setup with SSL</li>
                                <li>✅ Basic room creation and joining</li>
                                <li>✅ Enhanced room state management</li>
                                <li>✅ Participant tracking and notifications</li>
                                <li>✅ Room ownership and transfer</li>
                                <li>✅ Periodic health checks</li>
                                <li>🚧 Advanced error recovery</li>
                            </ul>
                        </li>
                        <li class="mb-3">
                            <strong>STUN/TURN Server</strong> ✅
                            <p>Coturn server implementation for NAT traversal and relay capabilities.</p>
                            <ul>
                                <li>✅ TURN server configuration</li>
                                <li>✅ Dynamic credential generation</li>
                                <li>✅ ICE server integration</li>
                                <li>✅ Connection testing capabilities</li>
                            </ul>
                        </li>
                        <li class="mb-3">
                            <strong>WebRTC Client</strong> 🚧
                            <p>JavaScript client implementation managing peer connections, media streams, and signaling communication.</p>
                            <ul>
                                <li>✅ Media device initialization</li>
                                <li>✅ Basic peer connection setup</li>
                                <li>✅ Enhanced room management</li>
                                <li>✅ Participant list management</li>
                                <li>✅ Media controls (audio/video toggle)</li>
                                <li>🚧 Advanced connection recovery</li>
                                <li>🚧 Comprehensive error handling</li>
                            </ul>
                        </li>
                    </ul>

                    <h6 class="mt-4">Connection Flow Implementation Status</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Step</th>
                                    <th>Features</th>
                                    <th>Status</th>
                                    <th>Implementation Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1. Initialization</td>
                                    <td>
                                        <ul>
                                            <li>TURN Credential Request</li>
                                            <li>Media Device Setup</li>
                                            <li>Local Stream Creation</li>
                                            <li>Connection Testing</li>
                                        </ul>
                                    </td>
                                    <td>✅</td>
                                    <td>
                                        <ul>
                                            <li>Implemented in WebRTCController::getTurnCredentials()</li>
                                            <li>Handled in WebRTCClient.initialize()</li>
                                            <li>Using getUserMedia() with proper error handling</li>
                                            <li>TURN server connectivity testing</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2. Room Join</td>
                                    <td>
                                        <ul>
                                            <li>WebSocket Connection</li>
                                            <li>Room Management</li>
                                            <li>Participant Notifications</li>
                                            <li>Room Configuration</li>
                                        </ul>
                                    </td>
                                    <td>✅</td>
                                    <td>
                                        <ul>
                                            <li>✅ Enhanced WebSocket connection</li>
                                            <li>✅ Comprehensive room management</li>
                                            <li>✅ Participant tracking and notifications</li>
                                            <li>✅ Room ownership management</li>
                                            <li>✅ Maximum participants limit</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3. Signaling</td>
                                    <td>
                                        <ul>
                                            <li>SDP Exchange</li>
                                            <li>ICE Candidate Exchange</li>
                                            <li>Connection State Management</li>
                                            <li>Room State Updates</li>
                                        </ul>
                                    </td>
                                    <td>🚧</td>
                                    <td>
                                        <ul>
                                            <li>✅ Enhanced offer/answer exchange</li>
                                            <li>✅ ICE candidate relay</li>
                                            <li>✅ Room state notifications</li>
                                            <li>🚧 Connection state recovery</li>
                                            <li>🚧 Advanced error handling</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>4. Media Exchange</td>
                                    <td>
                                        <ul>
                                            <li>Stream Management</li>
                                            <li>Connection Monitoring</li>
                                            <li>Cleanup Processes</li>
                                            <li>Media Controls</li>
                                        </ul>
                                    </td>
                                    <td>🚧</td>
                                    <td>
                                        <ul>
                                            <li>✅ Enhanced media streaming</li>
                                            <li>✅ Media device controls</li>
                                            <li>✅ Automatic cleanup</li>
                                            <li>🚧 Stream recovery</li>
                                            <li>🚧 Quality monitoring</li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mt-4">New Features</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Room Management</h6>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li>✅ Enhanced Room State
                                            <ul>
                                                <li>Room status tracking</li>
                                                <li>Participant limits</li>
                                                <li>Activity timestamps</li>
                                                <li>Owner management</li>
                                            </ul>
                                        </li>
                                        <li>✅ Participant Management
                                            <ul>
                                                <li>Join/Leave notifications</li>
                                                <li>Role-based permissions</li>
                                                <li>User identification</li>
                                                <li>Connection status tracking</li>
                                            </ul>
                                        </li>
                                        <li>✅ Administrative Controls
                                            <ul>
                                                <li>Room closure</li>
                                                <li>Participant removal</li>
                                                <li>Room monitoring</li>
                                                <li>Service management</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">System Monitoring</h6>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li>✅ Service Status Dashboard
                                            <ul>
                                                <li>WebSocket server status</li>
                                                <li>TURN server status</li>
                                                <li>Connected clients tracking</li>
                                                <li>Active sessions monitoring</li>
                                            </ul>
                                        </li>
                                        <li>✅ Health Checks
                                            <ul>
                                                <li>Periodic status updates</li>
                                                <li>Connection verification</li>
                                                <li>Resource monitoring</li>
                                                <li>Error logging</li>
                                            </ul>
                                        </li>
                                        <li>✅ Diagnostic Tools
                                            <ul>
                                                <li>TURN server testing</li>
                                                <li>Connection quality checks</li>
                                                <li>Debug information</li>
                                                <li>Error tracking</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mt-4">Planned Improvements</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Server-Side</h6>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li>❌ Advanced Error Recovery
                                            <ul>
                                                <li>Automatic service recovery</li>
                                                <li>State reconciliation</li>
                                                <li>Failover mechanisms</li>
                                            </ul>
                                        </li>
                                        <li>❌ Load Balancing
                                            <ul>
                                                <li>Multiple server support</li>
                                                <li>Session distribution</li>
                                                <li>Resource optimization</li>
                                            </ul>
                                        </li>
                                        <li>❌ Analytics
                                            <ul>
                                                <li>Usage statistics</li>
                                                <li>Performance metrics</li>
                                                <li>Error analysis</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Client-Side</h6>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li>❌ Advanced Media Features
                                            <ul>
                                                <li>Screen sharing</li>
                                                <li>Recording capabilities</li>
                                                <li>Quality selection</li>
                                            </ul>
                                        </li>
                                        <li>❌ Enhanced UI/UX
                                            <ul>
                                                <li>Grid view for multiple participants</li>
                                                <li>Chat integration</li>
                                                <li>File sharing</li>
                                            </ul>
                                        </li>
                                        <li>❌ Mobile Support
                                            <ul>
                                                <li>Responsive design</li>
                                                <li>Battery optimization</li>
                                                <li>Network adaptation</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <h6 class="mb-2">Current Limitations</h6>
                        <ul class="mb-0">
                            <li>Limited automatic reconnection capabilities</li>
                            <li>Basic error recovery mechanisms</li>
                            <li>No support for screen sharing</li>
                            <li>Limited mobile device optimization</li>
                            <li>No recording capabilities</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Message Types Documentation</h5>
                </div>
                <div class="card-body">
                    <h6>Client -> Server Messages</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Payload</th>
                                    <th>Description</th>
                                    <th>Handler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>join</code></td>
                                    <td>
<pre>{
  type: 'join',
  room_id: string,
  user_id: string,
  settings: {
    max_participants: number
  },
  client_info: object
}</pre>
                                    </td>
                                    <td>Join or create a room with specified settings</td>
                                    <td><code>handleJoinRoom()</code></td>
                                </tr>
                                <tr>
                                    <td><code>leave</code></td>
                                    <td>
<pre>{
  type: 'leave',
  room_id: string
}</pre>
                                    </td>
                                    <td>Leave current room</td>
                                    <td><code>handleLeaveRoom()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_ready</code></td>
                                    <td>
<pre>{
  type: 'media_ready'
}</pre>
                                    </td>
                                    <td>Signal that local media is ready</td>
                                    <td><code>handleMediaReady()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_offer</code></td>
                                    <td>
<pre>{
  type: 'media_offer',
  streamId: string,
  streamType: string,
  sdp: object,
  target_fd: number
}</pre>
                                    </td>
                                    <td>Send WebRTC offer to peer</td>
                                    <td><code>handleMediaOffer()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_answer</code></td>
                                    <td>
<pre>{
  type: 'media_answer',
  streamId: string,
  target_fd: number,
  sdp: object
}</pre>
                                    </td>
                                    <td>Send WebRTC answer to peer</td>
                                    <td><code>handleMediaAnswer()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_ice</code></td>
                                    <td>
<pre>{
  type: 'media_ice',
  streamId: string,
  target_fd?: number,
  candidate: object
}</pre>
                                    </td>
                                    <td>Send ICE candidate to peer</td>
                                    <td><code>handleMediaIce()</code></td>
                                </tr>
                                <tr>
                                    <td><code>message</code></td>
                                    <td>
<pre>{
  type: 'message',
  room: string,
  message: string
}</pre>
                                    </td>
                                    <td>Send chat message to room</td>
                                    <td><code>handleChatMessage()</code></td>
                                </tr>
                                <tr>
                                    <td><code>list_rooms</code></td>
                                    <td>
<pre>{
  type: 'list_rooms'
}</pre>
                                    </td>
                                    <td>Request list of available rooms</td>
                                    <td><code>handleListRooms()</code></td>
                                </tr>
                                <tr>
                                    <td><code>who</code></td>
                                    <td>
<pre>{
  type: 'who',
  room: string
}</pre>
                                    </td>
                                    <td>Request list of room members</td>
                                    <td><code>handleWhoInRoom()</code></td>
                                </tr>
                                <tr>
                                    <td><code>reconnect</code></td>
                                    <td>
<pre>{
  type: 'reconnect',
  session_id: string
}</pre>
                                    </td>
                                    <td>Attempt to reconnect with session</td>
                                    <td><code>handleReconnect()</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mt-4">Server -> Client Messages</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Payload</th>
                                    <th>Description</th>
                                    <th>Client Handler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>welcome</code></td>
                                    <td>
<pre>{
  type: 'welcome',
  session_id: string,
  message: string,
  commands: object
}</pre>
                                    </td>
                                    <td>Initial connection response</td>
                                    <td><code>handleMessage() -> welcome</code></td>
                                </tr>
                                <tr>
                                    <td><code>joined</code></td>
                                    <td>
<pre>{
  type: 'joined',
  room_id: string,
  room: {
    owner_id: string,
    created_at: number,
    settings: object,
    participants: array
  }
}</pre>
                                    </td>
                                    <td>Room join confirmation with details</td>
                                    <td><code>handleRoomJoined()</code></td>
                                </tr>
                                <tr>
                                    <td><code>peer-joined</code></td>
                                    <td>
<pre>{
  type: 'peer-joined',
  room_id: string,
  peer: {
    fd: number,
    user_id: string,
    status: string
  }
}</pre>
                                    </td>
                                    <td>New peer joined notification</td>
                                    <td><code>handlePeerJoined()</code></td>
                                </tr>
                                <tr>
                                    <td><code>peer-left</code></td>
                                    <td>
<pre>{
  type: 'peer-left',
  room_id: string,
  peer: {
    fd: number,
    user_id: string,
    was_owner: boolean
  },
  new_owner: string
}</pre>
                                    </td>
                                    <td>Peer left notification</td>
                                    <td><code>handlePeerLeft()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_offer</code></td>
                                    <td>
<pre>{
  type: 'media_offer',
  room_id: string,
  data: object,
  sender_fd: number,
  sender_id: string
}</pre>
                                    </td>
                                    <td>WebRTC offer from peer</td>
                                    <td><code>handleOffer()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_answer</code></td>
                                    <td>
<pre>{
  type: 'media_answer',
  room_id: string,
  data: object,
  sender_fd: number,
  sender_id: string
}</pre>
                                    </td>
                                    <td>WebRTC answer from peer</td>
                                    <td><code>handleAnswer()</code></td>
                                </tr>
                                <tr>
                                    <td><code>media_ice</code></td>
                                    <td>
<pre>{
  type: 'media_ice',
  room_id: string,
  data: object,
  sender_fd: number,
  sender_id: string
}</pre>
                                    </td>
                                    <td>ICE candidate from peer</td>
                                    <td><code>handleCandidate()</code></td>
                                </tr>
                                <tr>
                                    <td><code>error</code></td>
                                    <td>
<pre>{
  type: 'error',
  message: string
}</pre>
                                    </td>
                                    <td>Error notification</td>
                                    <td><code>handleError()</code></td>
                                </tr>
                                <tr>
                                    <td><code>reconnected</code></td>
                                    <td>
<pre>{
  type: 'reconnected',
  session_id: string,
  room_id: string,
  media_ready: number
}</pre>
                                    </td>
                                    <td>Reconnection success</td>
                                    <td><code>handleMessage() -> reconnected</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 