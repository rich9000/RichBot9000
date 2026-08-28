<!-- BareWebsocketServerV2 Process Control -->
<div class="container mb-3">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">BareWebsocketServer</h5>
                <small>Relay: <code>bare:assistant-v2</code> · Connect: <code>/dashboard/{room}?token={api_token}</code></small>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span id="serverProcessBadge" class="badge bg-secondary">Checking...</span>
                <button id="startServerBtn" class="btn btn-sm btn-success" onclick="controlBareServer('start')">
                    <i class="fas fa-play"></i> Start
                </button>
                <button id="stopServerBtn" class="btn btn-sm btn-danger" onclick="controlBareServer('stop')">
                    <i class="fas fa-stop"></i> Stop
                </button>
                <button class="btn btn-sm btn-warning" onclick="controlBareServer('restart')">
                    <i class="fas fa-sync"></i> Restart
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="refreshServerStatus()">
                    <i class="fas fa-info-circle"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="serverProcessAlert" class="alert d-none mb-3" role="alert"></div>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="text-muted small">Status</div>
                    <div id="serverProcessStatus">-</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">PID</div>
                    <div id="serverProcessPid">-</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Uptime</div>
                    <div id="serverProcessUptime">-</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Listen</div>
                    <div id="serverProcessListen">-</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Command</div>
                    <div id="serverProcessCmd" class="small font-monospace text-break">-</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Log file</div>
                    <div id="serverProcessLogFile" class="small text-break">-</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Alternate serverv2</div>
                    <div id="serverV2Status">-</div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-12">
                    <div class="text-muted small mb-1">Connection paths</div>
                    <pre id="serverConnectPaths" class="bg-light p-2 rounded small mb-0"></pre>
                </div>
            </div>
            <h6 class="mb-2">OpenAI Relays (<code>bare:assistant-v2</code>)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>PID</th>
                            <th>Room</th>
                            <th>Assistant</th>
                            <th>Conversation</th>
                            <th>Uptime</th>
                        </tr>
                    </thead>
                    <tbody id="relayTableBody">
                        <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Connection Status Indicator -->
<div class="container mb-3">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
                    <div id="connectionStatus" class="me-3">
            <span class="status-indicator status-disconnected"></span>
            <span id="connectionText">Disconnected</span>
        </div>
                    <div id="connectionDetails" class="text-muted small"></div>
                </div>
                <div class="d-flex align-items-center">
                    <button id="refreshDataBtn" class="btn btn-sm btn-outline-primary me-2" onclick="refreshDashboardData()">
            <i class="fas fa-sync-alt"></i> Refresh Data
        </button>
                    <button class="btn btn-sm btn-outline-info" onclick="showConnectionInfo()">
                        <i class="fas fa-info-circle"></i> Connection Info
        </button>
                </div>
            </div>
            <div id="connectionInfo" class="mt-2 small text-muted" style="display: none;">
                <strong>Target Server:</strong> <span id="targetServerUrl"></span><br>
                <strong>Connection Type:</strong> Dashboard (bare:server + assistant-v2)<br>
                <strong>Path:</strong> /dashboard/{device-id}?token={api-token}<br>
                <strong>Status query:</strong> request_server_data / get_all_clients<br>
                <strong>Protocol:</strong> Secure WebSocket (WSS)<br>
                <div id="connectionDiagnostics" class="mt-1"></div>
            </div>
        </div>
    </div>
</div>

<!-- Command History -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Command History</h5>
        </div>
        <div class="card-body">
            <div id="commandHistory" class="command-history">
                <!-- Messages will be logged here -->
            </div>
        </div>
    </div>
</div>

<!-- Connected Devices Table -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Connected Bare Devices                
            </h5>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-2" id="toggleInactiveDevices">
                    <i class="fas fa-eye-slash"></i> Hide Inactive
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="refreshDashboardData()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>FD</th>
                            <th>Device ID</th>
                            <th>Type</th>
                            <th>Last Seen</th>
                            <th>Services</th>
                            <th>Room</th>
                            <th>Stream SID</th>
                            <th>API Token</th>
                        </tr>
                    </thead>
                    <tbody id="deviceTableBody">
                        <!-- Devices will be listed here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Server Stats Table -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header">
                    <h6 class="mb-0">Server Statistics</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Total Connections</th>
                            <th>Active Devices</th>
                            <th>Active Rooms</th>
                            <th>Active Streams</th>
                            <th>WebClients</th>
                            <th>OpenAI Relays</th>
                            <th>Twilio</th>
                            <th>Dashboards</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="totalConnections">0</td>
                            <td id="activeDevices">0</td>
                            <td id="activeRooms">0</td>
                            <td id="activeStreams">0</td>
                            <td id="webclientCount">0</td>
                            <td id="openaiCount">0</td>
                            <td id="twilioCount">0</td>
                            <td id="dashboardCount">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Active Rooms Table -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Active Rooms</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshDashboardData()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Room Name</th>
                            <th>Created</th>
                            <th>Last Activity</th>
                            <th>Client Count</th>
                            <th>Clients</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="roomsTableBody">
                        <!-- Rooms will be listed here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Connections -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Dashboard Connections</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>FD</th>
                            <th>Device ID</th>
                            <th>Connected At</th>
                            <th>Room</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="dashboardTableBody">
                        <!-- Dashboard connections will be listed here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Controls -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Dashboard Controls</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Broadcast Message</h6>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="broadcastMessageInput" placeholder="Enter broadcast message...">
                        <button class="btn btn-outline-primary" onclick="sendBroadcast()">
                            <i class="fas fa-broadcast-tower"></i> Broadcast
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>System Commands</h6>
                    <div class="btn-group">
                        <button class="btn btn-outline-info btn-sm" onclick="sendSystemCommand('status_check')">
                            <i class="fas fa-check-circle"></i> Status Check
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="sendSystemCommand('health_check')">
                            <i class="fas fa-heartbeat"></i> Health Check
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="sendSystemCommand('ping_all')">
                            <i class="fas fa-ping-pong-paddle-ball"></i> Ping All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Remote Richbots Dashboard -->
<div class="container">
            <h1>Bare WebSocket Dashboard</h1>
    <table class="table" id="richbotsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Remote ID</th>
                <th>Status</th>
                <th>Last Seen</th>
                <th>Latest Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\App\Models\RemoteRichbot::all() as $richbot)
                <tr data-richbot-id="{{ $richbot->id }}">
                    <td>{{ $richbot->name }}</td>
                    <td>{{ $richbot->remote_richbot_id }}</td>
                    <td>{{ $richbot->status }}</td>
                    <td>{{ $richbot->last_seen }}</td>
                    <td>
                        <?php $image = $richbot->media()->where('type', 'image')->latest()->first(); ?>
                        @if($image)
                            <img style="max-width: 200px;" src="/storage/{{ $image->file_path }}" alt="Latest Image"/>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-primary view-details-btn" data-richbot-id="{{ $richbot->id }}">View</button>
                    </td>
                </tr>
                <!-- Expandable Details Row -->
                <tr class="details-row" id="details-{{ $richbot->id }}" style="display: none;">
                    <td colspan="6">
                        <!-- Placeholder for AJAX-loaded content -->
                        <div id="details-content-{{ $richbot->id }}"></div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="row mt-4">
    <div class="col-md-12">
        @include('webapp.conversations._file_browser')
    </div>
</div> 
    <style>
    .device-list {
        max-height: 600px;
        overflow-y: auto;
    }
    .device-card {
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        }
    .device-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    .device-header {
        padding: 0.75rem;
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
        border-radius: 0.25rem 0.25rem 0 0;
    }
    .device-header h5 {
        display: flex;
        align-items: center;
        margin: 0;
    }
    .device-header .status-indicator {
        width: 12px;
        height: 12px;
            border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        flex-shrink: 0;
        }
    .device-header .status-indicator.status-active {
            background-color: #28a745;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
        }
    .device-header .status-indicator.status-inactive {
            background-color: #dc3545;
        box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
    }
    .device-body {
        padding: 0.75rem;
    }
    .device-footer {
        padding: 0.75rem;
        border-top: 1px solid #dee2e6;
        background-color: #f8f9fa;
        border-radius: 0 0 0.25rem 0.25rem;
        }
        .capability-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem;
            background-color: #e9ecef;
            border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    .device-command {
        margin-top: 0.5rem;
    }
    .device-command select {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    .device-command button {
        width: 100%;
        margin-top: 0.25rem;
    }
    .last-seen {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-connected {
        background-color: #28a745;
    }
    .status-disconnected {
        background-color: #dc3545;
    }
    .status-active {
        background-color: #28a745;
    }
    .status-inactive {
        background-color: #dc3545;
    }
    .command-history {
        height: 300px;
        overflow-y: auto;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
    }
    #connectionStatus {
        display: flex;
        align-items: center;
        font-weight: 500;
    }
    #connectionDetails {
        margin-left: 1rem;
    }
    #refreshDataBtn {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
    }
    #refreshDataBtn i {
        margin-right: 0.25rem;
    }
    #refreshDataBtn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
    /* Image Gallery Styles */
    .image-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        padding: 1rem;
    }
    
    .image-card {
        position: relative;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .image-card:hover {
        transform: scale(1.05);
    }
    
    .image-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 0.25rem;
    }
    
    .image-count {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.875rem;
    }
    
    /* Carousel Modal Styles */
    .carousel-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 1050;
    }
    
    .carousel-content {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .carousel-image {
        max-width: 90%;
        max-height: 90vh;
        object-fit: contain;
    }
    
    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        padding: 1rem;
        cursor: pointer;
        font-size: 1.5rem;
    }
    
    .carousel-prev {
        left: 1rem;
    }
    
    .carousel-next {
        right: 1rem;
    }
    
    .carousel-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
    }
</style>

    <script>
    // WebSocket Connection and Device Management
        let ws;
    let selectedDevice = null;
    let clientsData = {}; // Store V2 clients data
    let reconnectAttempts = 0;
    const MAX_RECONNECT_ATTEMPTS = 5;
    const RECONNECT_DELAY = 5000;
    let lastDataUpdate = null;
    const DATA_UPDATE_INTERVAL = 10000; // Update every 30 seconds
    const FIVE_MINUTES = 5 * 60 * 1000; // 5 minutes in milliseconds
    let showInactiveDevices = false;
    let dashboardDeviceId = null; // Store the dashboard device ID
    let receivedImages = new Map(); // Store images by device ID
    let currentCarouselIndex = 0;
    let currentDeviceImages = [];

    function updateConnectionStatus(connected, details = '') {
        const statusIndicator = document.querySelector('#connectionStatus .status-indicator');
        const statusText = document.getElementById('connectionText');
        const statusDetails = document.getElementById('connectionDetails');

        if (connected) {
            statusIndicator.className = 'status-indicator status-connected';
            statusText.textContent = 'Connected';
            statusDetails.textContent = details;
            reconnectAttempts = 0;
        } else {
            statusIndicator.className = 'status-indicator status-disconnected';
            statusText.textContent = 'Disconnected';
            statusDetails.textContent = details;
        }
    }

    function bareServerHeaders() {
        return {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
    }

    function showServerProcessAlert(message, type = 'info') {
        const alertEl = document.getElementById('serverProcessAlert');
        if (!alertEl) return;
        alertEl.className = `alert alert-${type} mb-3`;
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');
    }

    function renderServerStatus(data) {
        const server = data.server || {};
        const config = data.config || {};
        const running = !!server.running;
        const badge = document.getElementById('serverProcessBadge');
        const startBtn = document.getElementById('startServerBtn');
        const stopBtn = document.getElementById('stopServerBtn');

        if (badge) {
            badge.className = `badge ${running ? 'bg-success' : 'bg-danger'}`;
            badge.textContent = running ? 'Running' : 'Stopped';
        }
        if (startBtn) startBtn.disabled = running;
        if (stopBtn) stopBtn.disabled = !running;

        document.getElementById('serverProcessStatus').textContent = running ? 'Running (bare:server)' : 'Stopped';
        document.getElementById('serverProcessPid').textContent = server.pid || '-';
        document.getElementById('serverProcessUptime').textContent = server.uptime || '-';
        document.getElementById('serverProcessListen').textContent = config.url || '-';
        document.getElementById('serverProcessCmd').textContent = server.cmdline || server.command || '-';
        const logEl = document.getElementById('serverProcessLogFile');
        if (logEl) logEl.textContent = server.log_file || '-';
        const v2El = document.getElementById('serverV2Status');
        if (v2El) {
            v2El.textContent = (data.serverv2 && data.serverv2.running) ? 'Running (port conflict risk)' : 'Not running';
        }

        const paths = config.connect_paths || {};
        const pathText = Object.entries(paths)
            .map(([type, path]) => `${type.padEnd(14)} ${config.url || ''}${path}`)
            .join('\n');
        document.getElementById('serverConnectPaths').textContent = pathText || 'No paths available';

        const relayBody = document.getElementById('relayTableBody');
        const relays = data.relays || [];
        if (!relays.length) {
            relayBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No V2 relays running</td></tr>';
        } else {
            relayBody.innerHTML = relays.map((relay) => {
                const parsed = relay.parsed || {};
                return `<tr>
                    <td>${relay.pid}</td>
                    <td>${parsed.room || '-'}</td>
                    <td>${parsed.assistant_id || '-'}</td>
                    <td>${parsed.conversation_id || '-'}</td>
                    <td>${relay.etime || '-'}</td>
                </tr>`;
            }).join('');
        }

        if (data.serverv2 && data.serverv2.running) {
            showServerProcessAlert('bare:serverv2 is also running. Port 9502 can only have one server.', 'warning');
        }
    }

    async function refreshServerStatus() {
        try {
            const response = await fetch('/api/bare-websocket/status', {
                headers: bareServerHeaders()
            });
            const data = await response.json();
            renderServerStatus(data);
            return data;
        } catch (error) {
            console.error('Failed to load bare websocket status', error);
            showServerProcessAlert('Could not load server status: ' + error.message, 'danger');
            return null;
        }
    }

    async function controlBareServer(action) {
        const startBtn = document.getElementById('startServerBtn');
        const stopBtn = document.getElementById('stopServerBtn');
        if (startBtn) startBtn.disabled = true;
        if (stopBtn) stopBtn.disabled = true;
        showServerProcessAlert(`${action} in progress...`, 'info');

        try {
            const response = await fetch(`/api/bare-websocket/${action}`, {
                method: 'POST',
                headers: bareServerHeaders()
            });
            const data = await response.json();
            renderServerStatus(data);
            showServerProcessAlert(data.message || `Server ${action} complete`, data.success ? 'success' : 'danger');

            if (action === 'start' || action === 'restart') {
                setTimeout(() => {
                    reconnectAttempts = 0;
                    if (ws) {
                        try { ws.close(); } catch (e) {}
                    }
                    connectWebSocket();
                }, 1200);
            }
        } catch (error) {
            showServerProcessAlert(`Failed to ${action} server: ${error.message}`, 'danger');
            refreshServerStatus();
        }
    }

    function connectWebSocket() {
        dashboardDeviceId = 'dashboard-' + Math.random().toString(36).substr(2, 9);
        
        // Build connection URL with detailed logging
        const protocol = 'wss://';
        const host = window.appConfig.domain;
        const port = window.appConfig.wsPortAlt;
        const path = `/dashboard/${dashboardDeviceId}`;
        const token = appState.apiToken || 'NO_TOKEN';
        const fullUrl = `${protocol}${host}:${port}${path}?token=${token}`;
        
        console.log('=== WebSocket Connection Details ===');
        console.log('Dashboard Device ID:', dashboardDeviceId);
        console.log('Protocol:', protocol);
        console.log('Host:', host);
        console.log('Port:', port);
        console.log('Path:', path);
        console.log('API Token:', token ? `${token.substring(0, 10)}...` : 'MISSING');
        console.log('Full URL:', fullUrl);
        console.log('=====================================');
        
        logMessage(`Attempting connection to ${host}:${port} as ${dashboardDeviceId}`);
        logMessage(`Connection URL: ${protocol}${host}:${port}${path}`);
        logMessage(`API Token: ${token ? 'Present (' + token.length + ' chars)' : 'MISSING!'}`);
        
        // Connect to V2 server using proper dashboard path
        ws = new WebSocket(fullUrl);
        
        ws.onopen = () => {
            updateConnectionStatus(true, `Connected as ${dashboardDeviceId} to ${host}:${port}`);
            logMessage(`✅ Connected to V2 WebSocket server at ${host}:${port}`);
            logMessage(`📡 Connection established with dashboard ID: ${dashboardDeviceId}`);
            
            // Request initial data from V2 server
            requestAllClients();
            requestAllRooms();
            
            // Start periodic data updates
            startPeriodicUpdates();
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            console.log('Received V2 message:', data);
            handleWebSocketMessage(data);
        };

        ws.onclose = (event) => {
            const reason = event.reason || 'Unknown reason';
            const code = event.code || 'No code';
            const wasClean = event.wasClean ? 'Clean' : 'Unclean';
            
            console.log('=== WebSocket Connection Closed ===');
            console.log('Close Code:', code);
            console.log('Close Reason:', reason);
            console.log('Was Clean:', wasClean);
            console.log('Reconnect Attempts:', reconnectAttempts);
            console.log('===================================');
            
            updateConnectionStatus(false, reconnectAttempts < MAX_RECONNECT_ATTEMPTS ? 
                `Reconnecting... (Attempt ${reconnectAttempts + 1}/${MAX_RECONNECT_ATTEMPTS})` : 
                'Connection failed. Please refresh the page.');
            
            logMessage(`❌ Disconnected from WebSocket server (Code: ${code}, Reason: ${reason})`);
            logMessage(`🔄 Connection was ${wasClean.toLowerCase()}`);
            
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                logMessage(`⏳ Retrying connection in ${RECONNECT_DELAY/1000} seconds... (${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})`);
                setTimeout(connectWebSocket, RECONNECT_DELAY);
            } else {
                logMessage(`🚫 Max reconnection attempts reached. Please refresh the page.`);
            }
        };

        ws.onerror = (error) => {
            console.error('=== WebSocket Error ===');
            console.error('Error Event:', error);
            console.error('WebSocket State:', ws.readyState);
            console.error('Expected States: CONNECTING=0, OPEN=1, CLOSING=2, CLOSED=3');
            console.error('======================');
            
            updateConnectionStatus(false, 'Connection error occurred');
            logMessage(`🔥 WebSocket error occurred (State: ${ws.readyState})`);
            
            if (error.message) {
                logMessage(`Error details: ${error.message}`);
            }
        };
    }

    function requestAllClients() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'request_server_data' }));
            ws.send(JSON.stringify({ type: 'get_all_clients' }));
        }
    }

    function requestAllRooms() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'get_all_rooms' }));
        }
    }

    function requestRoomStatus(room) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ 
                type: 'get_room_status',
                room: room 
            }));
        }
    }

    function startPeriodicUpdates() {
        setInterval(() => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                requestAllClients();
                requestAllRooms();
            }
        }, DATA_UPDATE_INTERVAL);
    }

    function normalizeClients(clients) {
        if (!clients) return {};
        if (Array.isArray(clients)) {
            const map = {};
            clients.forEach((client) => {
                const fd = client.fd;
                map[fd] = {
                    ...client,
                    name: client.device_id || client.name || '',
                    room: client.room_id || client.room || '',
                    connected_at: client.last_seen || client.connected_at || 0,
                    last_seen: client.last_seen || client.connected_at || 0,
                };
            });
            return map;
        }
        const map = {};
        Object.entries(clients).forEach(([fd, client]) => {
            map[fd] = {
                ...client,
                fd: client.fd || fd,
                name: client.device_id || client.name || '',
                room: client.room_id || client.room || '',
                connected_at: client.last_seen || client.connected_at || 0,
                last_seen: client.last_seen || client.connected_at || 0,
            };
        });
        return map;
    }

    function handleAllClientsResponse(data) {
        console.log('Received all clients data:', data);
        lastDataUpdate = Date.now();
        updateConnectionStatus(true, `Last update: ${new Date().toLocaleTimeString()}`);
        
        const clients = normalizeClients(data.clients);
        clientsData = clients;
        
        updateDeviceTable(clients);
        updateDashboardTable(clients);
        updateDeviceSelect(clients);
        
        const clientArray = Object.values(clients);
        document.getElementById('totalConnections').textContent = clientArray.length;
        document.getElementById('activeDevices').textContent = clientArray.filter(c => c.type !== 'dashboard').length;
        document.getElementById('activeStreams').textContent = clientArray.filter(c => c.type === 'twilio' || c.type === 'twilio_inbound').length;
        document.getElementById('webclientCount').textContent = clientArray.filter(c => c.type === 'webclient').length;
        document.getElementById('openaiCount').textContent = clientArray.filter(c => c.type === 'openai').length;
        document.getElementById('twilioCount').textContent = clientArray.filter(c => c.type === 'twilio' || c.type === 'twilio_inbound').length;
        document.getElementById('dashboardCount').textContent = clientArray.filter(c => c.type === 'dashboard').length;

        if (data.rooms) {
            handleAllRoomsResponse({ rooms: data.rooms });
        }
    }

    function handleAllRoomsResponse(data) {
        console.log('Received all rooms data:', data);
        const rooms = data.rooms || {};
        document.getElementById('activeRooms').textContent = Object.keys(rooms).length;
        
        // Store rooms data for reference
        window.roomsData = rooms;
        
        updateRoomsTable(rooms);
    }

    function handleRoomStatusResponse(data) {
        console.log('Received room status:', data);
        logMessage(`Room ${data.room}: ${JSON.stringify(data.room_data)}`);
    }

    function handleBroadcastMessage(data) {
        logMessage(`Broadcast from ${data.source}: ${data.content}`);
    }

    function handleControlMessage(data) {
        logMessage(`Control command: ${data.action} from ${data.source}`);
    }

    function handleSystemMessage(data) {
        logMessage(`System: ${data.action} - ${JSON.stringify(data.data)}`);
    }

    function handleCommandMessage(data) {
        logMessage(`Command: ${data.command} from ${data.source}`);
    }

    function handleStatusMessage(data) {
        logMessage(`Status update: ${JSON.stringify(data)}`);
    }

    function handleMediaMessage(data) {
        logMessage(`Media received from ${data.source || 'unknown'}`);
        // Handle media/image data if needed
        if (data.device_id && data.data) {
            handleImageResponse(data);
        }
    }

    function updateDeviceTable(clients) {
        const deviceTableBody = document.getElementById('deviceTableBody');
        deviceTableBody.innerHTML = ''; // Clear existing rows
        
        if (!clients || Object.keys(clients).length === 0) {
            deviceTableBody.innerHTML = '<tr><td colspan="9" class="text-center">No devices connected</td></tr>';
            return;
        }

        let hasVisibleDevices = false;
        
        Object.entries(clients).forEach(([fd, client]) => {
            // Skip dashboard connections
            if (client.type === 'dashboard') {
                return;
            }
            
            const lastSeen = (client.last_seen || client.connected_at || 0) * 1000;
            const timeSinceConnection = lastSeen ? Date.now() - lastSeen : 0;
            const isActive = !lastSeen || timeSinceConnection < FIVE_MINUTES;
            
            if (!isActive && !showInactiveDevices) {
                return;
            }
            
            hasVisibleDevices = true;
            const services = Array.isArray(client.services)
                ? client.services.join(', ')
                : (client.services || '-');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <span class="status-indicator ${isActive ? 'status-active' : 'status-inactive'}"></span>
                </td>
                <td>${fd}</td>
                <td>${client.device_id || client.name || 'N/A'}</td>
                <td><span class="badge bg-secondary">${client.type || 'Unknown'}</span></td>
                <td>${lastSeen ? formatLastSeen(lastSeen) : '-'}</td>
                <td>${services}</td>
                <td>${client.room || client.room_id || 'None'}</td>
                <td>${client.stream_sid || client.call_sid || '-'}</td>
                <td>${client.api_token ? String(client.api_token).slice(0, 8) + '…' : '-'}</td>
            `;
            
            deviceTableBody.appendChild(row);
        });

        if (!hasVisibleDevices) {
            deviceTableBody.innerHTML = '<tr><td colspan="9" class="text-center">No active devices connected</td></tr>';
        }
    }

    function updateDashboardTable(clients) {
        const dashboardTableBody = document.getElementById('dashboardTableBody');
        dashboardTableBody.innerHTML = ''; // Clear existing rows
        
        if (!clients || Object.keys(clients).length === 0) {
            dashboardTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No dashboard connections</td></tr>';
            return;
        }

        let hasDashboardConnections = false;
        
        Object.entries(clients).forEach(([fd, client]) => {
            // Only show dashboard connections
            if (client.type !== 'dashboard') {
                return;
            }
            
            hasDashboardConnections = true;
            const lastSeen = (client.last_seen || client.connected_at || 0) * 1000;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${fd}</td>
                <td>${client.device_id || client.name || `dashboard-${fd}`}</td>
                <td>${lastSeen ? formatLastSeen(lastSeen) : '-'}</td>
                <td>${client.room || client.room_id || 'None'}</td>
                <td><span class="badge bg-success">Connected</span></td>
            `;
            
            dashboardTableBody.appendChild(row);
        });

        if (!hasDashboardConnections) {
            dashboardTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No dashboard connections</td></tr>';
        }
    }

    function updateRoomsTable(rooms) {
        const roomsTableBody = document.getElementById('roomsTableBody');
        roomsTableBody.innerHTML = ''; // Clear existing rows
        
        if (!rooms || Object.keys(rooms).length === 0) {
            roomsTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No active rooms</td></tr>';
            return;
        }

        Object.entries(rooms).forEach(([roomName, roomData]) => {
            const createdAt = roomData.created_at ? new Date(roomData.created_at * 1000) : new Date();
            const lastActivity = roomData.last_activity ? new Date(roomData.last_activity * 1000) : new Date();
            
            // Parse clients array
            let clientsArray = [];
            try {
                const parsed = roomData.clients ? JSON.parse(roomData.clients) : [];
                clientsArray = Array.isArray(parsed) ? parsed : Object.keys(parsed || {});
            } catch (e) {
                clientsArray = [];
            }
            
            // Get client details for this room
            const roomClients = clientsArray.map(fd => {
                const client = clientsData[fd];
                return client ? `${client.type}(${fd})` : `Unknown(${fd})`;
            }).join(', ');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${roomName}</td>
                <td>${formatLastSeen(createdAt.getTime())}</td>
                <td>${formatLastSeen(lastActivity.getTime())}</td>
                <td>${clientsArray.length}</td>
                <td>${roomClients || 'None'}</td>
                <td>
                    <span class="badge ${roomData.status === 'active' ? 'bg-success' : 'bg-secondary'}">${roomData.status || 'unknown'}</span>
                    <button class="btn btn-sm btn-outline-info ms-1" onclick="requestRoomStatus('${roomName}')">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                </td>
            `;
            
            roomsTableBody.appendChild(row);
        });
    }

    function updateDeviceSelect(clients) {
        const deviceSelect = document.getElementById('remoteRichbotDeviceSelect');
        if (!deviceSelect) return;
        deviceSelect.innerHTML = '<option value="">Choose a device...</option>';
        
        Object.entries(clients).forEach(([fd, client]) => {
            if (client.type !== 'dashboard') {
                const option = document.createElement('option');
                option.value = fd;
                option.textContent = `${client.name || client.user_id || fd} (${client.type})`;
                deviceSelect.appendChild(option);
            }
        });
    }

    function handleWebSocketMessage(data) {
        console.log('Handling V2 message:', data);
        switch(data.type) {
            case 'server_data':
            case 'all_clients':
                handleAllClientsResponse(data);
                break;
            case 'all_rooms':
                handleAllRoomsResponse(data);
                break;
            case 'room_status':
                handleRoomStatusResponse(data);
                break;
            case 'broadcast':
                handleBroadcastMessage(data);
                break;
            case 'control':
                handleControlMessage(data);
                break;
            case 'system':
                handleSystemMessage(data);
                break;
            case 'command':
                handleCommandMessage(data);
                break;
            case 'status':
                handleStatusMessage(data);
                break;
            case 'error':
                logMessage(`Server error: ${data.message || 'Unknown error'}`);
                break;
            case 'device_command_response':
                if (data.command === 'capture_image') {
                    handleImageResponse(data);
                }
                break;
            case 'media':
                handleMediaMessage(data);
                break;
            case 'heartbeat':
                // Respond to heartbeat
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({ type: 'heartbeat', timestamp: Date.now() }));
                }
                break;
            default:
                logMessage(`Received unknown V2 message type: ${data.type}`);
            }
        }



    function formatLastSeen(timestamp) {
        const now = Date.now();
        const diff = now - timestamp;
        
        if (diff < 60000) { // Less than 1 minute
            return 'Just now';
        } else if (diff < 3600000) { // Less than 1 hour
            const minutes = Math.floor(diff / 60000);
            return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
        } else if (diff < 86400000) { // Less than 1 day
            const hours = Math.floor(diff / 3600000);
            return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
        } else {
            return new Date(timestamp).toLocaleString();
        }
    }

    function generateCommandOptions(services) {
        let options = [];
        
        services.forEach(service => {
            switch(service.name) {
                case 'video':
                    options.push(
                        '<option value="start_video">Start Video</option>',
                        '<option value="stop_video">Stop Video</option>',
                        '<option value="capture_image">Capture Image</option>'
                    );
                    break;
                case 'audio':
                    options.push(
                        '<option value="start_audio">Start Audio</option>',
                        '<option value="stop_audio">Stop Audio</option>',
                        '<option value="capture_audio">Capture Audio</option>'
                    );
                    break;
                case 'screen':
                    options.push(
                        '<option value="start_screen">Start Screen</option>',
                        '<option value="stop_screen">Stop Screen</option>',
                        '<option value="capture_screen">Capture Screen</option>'
                    );
                    break;
            }
        });
        
        return options.join('');
    }

    function sendCommand(deviceId, command) {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            logMessage('Cannot send command: WebSocket not connected');
            return;
        }

        const parameters = {};
        
        // Add specific parameters based on command type
        switch (command) {
            case 'capture_image':
            case 'capture_audio':
            case 'capture_screen':
                parameters.quality = 'high';
                break;
            case 'start_video':
            case 'start_audio':
            case 'start_screen':
                parameters.format = 'webm';
                parameters.bitrate = '2Mbps';
                break;
        }
        
        // Use V2 command message format
            ws.send(JSON.stringify({
            type: 'command',
            command: command,
            params: parameters,
            target: deviceId,
            source: 'dashboard'
        }));
        
        logMessage(`Sent V2 command "${command}" to device ${deviceId}`);
    }

    function sendBroadcastMessage(message) {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            logMessage('Cannot send broadcast: WebSocket not connected');
            return;
        }

        ws.send(JSON.stringify({
            type: 'broadcast',
            content: message,
            source: 'dashboard'
        }));
        
        logMessage(`Broadcast message sent: ${message}`);
    }

    function sendControlCommand(action, params = {}) {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            logMessage('Cannot send control: WebSocket not connected');
            return;
        }
        
        ws.send(JSON.stringify({
            type: 'control',
            action: action,
            params: params,
            source: 'dashboard'
        }));
        
        logMessage(`Control command sent: ${action}`);
        }
        
    function refreshDashboardData() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            requestAllClients();
            requestAllRooms();
            logMessage('Refreshing dashboard data...');
        } else {
            logMessage('Cannot refresh: WebSocket not connected');
        }
    }

    function updateDeviceStatus(deviceId, status) {
        // This function is no longer needed with V2 as status updates come through regular data refreshes
        console.log(`Device ${deviceId} status update: ${status}`);
        logMessage(`Device ${deviceId} status updated to ${status}`);
    }

    function updateDeviceCapabilities(deviceId, capabilities) {
        // This function is no longer needed with V2 as capabilities come through regular data refreshes
        console.log(`Device ${deviceId} capabilities update:`, capabilities);
        logMessage(`Device ${deviceId} capabilities updated`);
        }



    function handleDeviceCommandSelect(select, deviceId) {
        const command = select.value;
        if (!command) return;
        
        // Store the selected command for this device
        if (!window.deviceCommands) window.deviceCommands = {};
        window.deviceCommands[deviceId] = command;
    }

    function sendDeviceCommand(deviceId) {
        const command = window.deviceCommands?.[deviceId];
        if (!command) {
            logMessage('Please select a command first');
                return;
            }

        const parameters = {};
        
        // Add specific parameters based on command type
        switch (command) {
            case 'capture_image':
            case 'capture_audio':
            case 'capture_screen':
                parameters.quality = 'high';
                break;
            case 'start_video':
            case 'start_audio':
            case 'start_screen':
                parameters.format = 'webm';
                parameters.bitrate = '2Mbps';
                break;
        }
        
        ws.send(JSON.stringify({
            type: 'device_command',
            device_id: deviceId,
            command: command,
            parameters: parameters
        }));
        
        logMessage(`Sent command "${command}" to device ${deviceId}`);
    }

    function sendWebSocketCommand() {
        const deviceId = document.getElementById('deviceSelect').value;
        const command = document.getElementById('commandInput').value;
        const params = document.getElementById('commandParams').value;

        if (!deviceId || !command) {
            logMessage('Please select a device and enter a command');
            return;
        }

        let paramsObj = {};
        try {
            if (params) {
                paramsObj = JSON.parse(params);
            }
        } catch (e) {
            logMessage('Invalid JSON parameters');
            return;
        }

        ws.send(JSON.stringify({
            type: 'device_command',
            device_id: deviceId,
            command: command,
            parameters: paramsObj
        }));

        logMessage(`Sent command "${command}" to device ${deviceId}`);
    }

    function logMessage(message) {
        const history = document.getElementById('commandHistory');
        const entry = document.createElement('div');
        entry.className = 'mb-2';
        entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        history.appendChild(entry);
        history.scrollTop = history.scrollHeight;
    }

    function handleDeviceMedia(data) {
        logMessage(`Received media from device ${data.device_id}: ${data.media_type}`);
    }

    function handleImageResponse(data) {
        const deviceId = data.device_id;
        if (!receivedImages.has(deviceId)) {
            receivedImages.set(deviceId, []);
        }
        
        const images = receivedImages.get(deviceId);
        images.push({
            data: data.data,
            format: data.format,
            timestamp: Date.now()
        });
        
        // Update the image display for this device
        updateDeviceImageDisplay(deviceId);
        
        // Log the receipt of the image
        logMessage(`Received image from device ${deviceId}`);
    }
    
    function updateDeviceImageDisplay(deviceId) {


        console.log('updateDeviceImageDisplay',deviceId);
        console.log('receivedImages',receivedImages);
        console.log('devices',devices);


        
        const images = receivedImages.get(deviceId) || [];
        const deviceRow = document.querySelector(`tr[data-device-id="${deviceId}"]`);
        
        if (!deviceRow) return;
        
        // Find or create the image cell
        let imageCell = deviceRow.querySelector('.device-image-cell');
        if (!imageCell) {
            imageCell = document.createElement('td');
            imageCell.className = 'device-image-cell';
            deviceRow.insertBefore(imageCell, deviceRow.lastElementChild);
        }
        
        // Update the image cell content
        imageCell.innerHTML = `
            <div class="image-gallery">
                ${images.slice(-4).map((img, index) => `
                    <div class="image-card" onclick="showCarousel('${deviceId}', ${index})">
                        <img src="data:image/${img.format};base64,${img.data}" alt="Device Image">
                        ${images.length > 4 && index === 3 ? 
                            `<div class="image-count">+${images.length - 4}</div>` : ''}
                    </div>
                `).join('')}
            </div>
            <button class="btn btn-primary mt-2" onclick="showCarousel('${deviceId}', 0)">
                <i class="fas fa-images"></i> View All Images
                        </button>
        `;
    }
    
    function showCarousel(deviceId, startIndex) {
        const images = receivedImages.get(deviceId) || [];
        if (images.length === 0) return;
        
        currentDeviceImages = images;
        currentCarouselIndex = startIndex;
        
        // Create and show the carousel modal
        const modal = document.createElement('div');
        modal.className = 'carousel-modal';
        modal.innerHTML = `
            <div class="carousel-content">
                <button class="carousel-close" onclick="closeCarousel()">&times;</button>
                <button class="carousel-nav carousel-prev" onclick="carouselPrev()">&lt;</button>
                <img class="carousel-image" src="data:image/${images[startIndex].format};base64,${images[startIndex].data}" alt="Full size image">
                <button class="carousel-nav carousel-next" onclick="carouselNext()">&gt;</button>
                    </div>
                `;
        
        document.body.appendChild(modal);
        modal.style.display = 'block';
        
        // Add keyboard navigation
        document.addEventListener('keydown', handleCarouselKeyPress);
    }
    
    function closeCarousel() {
        const modal = document.querySelector('.carousel-modal');
        if (modal) {
            modal.remove();
            document.removeEventListener('keydown', handleCarouselKeyPress);
        }
    }
    
    function carouselPrev() {
        currentCarouselIndex = (currentCarouselIndex - 1 + currentDeviceImages.length) % currentDeviceImages.length;
        updateCarouselImage();
    }
    
    function carouselNext() {
        currentCarouselIndex = (currentCarouselIndex + 1) % currentDeviceImages.length;
        updateCarouselImage();
    }
    
    function updateCarouselImage() {
        const img = currentDeviceImages[currentCarouselIndex];
        const carouselImg = document.querySelector('.carousel-image');
        if (carouselImg) {
            carouselImg.src = `data:image/${img.format};base64,${img.data}`;
        }
    }
    
    function handleCarouselKeyPress(event) {
        switch(event.key) {
            case 'ArrowLeft':
                carouselPrev();
                break;
            case 'ArrowRight':
                carouselNext();
                break;
            case 'Escape':
                closeCarousel();
                break;
        }
    }

    // Initialize WebSocket connection
    console.log('=== Dashboard Initialization ===');
    console.log('Dashboard script loaded');
    console.log('appState.apiToken:', appState.apiToken ? 'Present' : 'MISSING');
    console.log('Starting WebSocket connection...');
    console.log('================================');
    
    logMessage('🚀 Dashboard V2 initializing...');
    logMessage('🔧 Checking API token availability...');
    
    if (!appState.apiToken) {
        logMessage('⚠️ WARNING: API token is missing! Connection may fail.');
        logMessage('Please ensure you are logged in and have a valid API token.');
    } else {
        logMessage(`✅ API token found (${appState.apiToken.length} characters)`);
    }
    
    connectWebSocket();
    refreshServerStatus();
    setInterval(refreshServerStatus, 15000);

    document.addEventListener('click', function(event) {
        if (event.target && event.target.id.startsWith('add-trigger-btn-')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            showAddTriggerForm(richbotId);
        }
    });

    function showAddTriggerForm(richbotId) {
        // Implement a modal or form for adding triggers
        // For this example, we'll use simple prompts

        const type = prompt('Enter trigger type (e.g., image_notify, image_alarm, audio_note):');
        const promptText = prompt('Enter prompt (e.g., "Notify me if a dog is sitting on the couch"):');
        const action = prompt('Enter action (e.g., notify, alarm, email):');

        if (type && promptText && action) {
            const data = {
                type: type,
                prompt: promptText,
                action: action,
            };

            fetch(`/api/remote-richbot/${richbotId}/triggers`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
                .then(response => response.json())
                .then(trigger => {
                    alert('Trigger added successfully!');
                    // Reload triggers
                    const detailsContent = document.getElementById('details-content-' + richbotId);
                    loadRichbotDetails(richbotId, detailsContent);
                })
                .catch(err => {
                    console.error('Failed to add trigger:', err);
                    alert('Failed to add trigger.');
                });
        }
    }

    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('edit-trigger-btn')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            const triggerId = event.target.getAttribute('data-trigger-id');
            showEditTriggerForm(richbotId, triggerId);
        }
    });

    function showEditTriggerForm(richbotId, triggerId) {
        // Fetch the existing trigger data
        fetch(`/api/remote-richbot/${richbotId}/triggers/${triggerId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(trigger => {
                const type = prompt('Edit trigger type:', trigger.type);
                const promptText = prompt('Edit prompt:', trigger.prompt);
                const action = prompt('Edit action:', trigger.action);

                if (type && promptText && action) {
                    const data = {
                        type: type,
                        prompt: promptText,
                        action: action,
                    };

                    fetch(`/api/remote-richbot/${richbotId}/triggers/${triggerId}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': 'Bearer ' + appState.apiToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data),
                    })
                        .then(response => response.json())
                        .then(updatedTrigger => {
                            alert('Trigger updated successfully!');
                            // Reload triggers
                            const detailsContent = document.getElementById('details-content-' + richbotId);
                            loadRichbotDetails(richbotId, detailsContent);
                        })
                        .catch(err => {
                            console.error('Failed to update trigger:', err);
                            alert('Failed to update trigger.');
                        });
                }
            })
            .catch(err => {
                console.error('Failed to fetch trigger:', err);
            });
    }
    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('delete-trigger-btn')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            const triggerId = event.target.getAttribute('data-trigger-id');
            deleteTrigger(richbotId, triggerId);
        }
    });

    function deleteTrigger(richbotId, triggerId) {
        if (confirm('Are you sure you want to delete this trigger?')) {
            fetch(`/api/remote-richbot/${richbotId}/triggers/${triggerId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                },
            })
                .then(() => {
                    alert('Trigger deleted successfully!');
                    // Reload triggers
                    const detailsContent = document.getElementById('details-content-' + richbotId);
                    loadRichbotDetails(richbotId, detailsContent);
                })
                .catch(err => {
                    console.error('Failed to delete trigger:', err);
                    alert('Failed to delete trigger.');
                });
        }
    }

    // Event listener for the "View" buttons
    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('view-details-btn')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            toggleDetailsRow(richbotId);
        }
    });

    // Function to toggle the details row
    function toggleDetailsRow(richbotId) {
        const detailsRow = document.getElementById('details-' + richbotId);
        const detailsContent = document.getElementById('details-content-' + richbotId);

        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = '';
            // Load details via AJAX if not already loaded
            if (!detailsContent.innerHTML.trim()) {
                loadRichbotDetails(richbotId, detailsContent);
            }
        } else {
            detailsRow.style.display = 'none';
        }
    }

    function loadRichbotDetails(richbotId, container) {
        fetch(`/api/remote-richbot/${richbotId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                console.log('data',data);
                // Build the HTML content
                const content = buildDetailsContent(data);
                console.log('content',content);
                container.innerHTML = content;
                // Add event listener for the command form submission
                const commandForm = container.querySelector(`#command-form-${richbotId}`);
                commandForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    sendCommand(richbotId, commandForm);
                });
            })
            .catch(err => {
                console.error('Failed to load richbot details:', err);
            });
    }
    function buildDetailsContent(data) {
        // Build the tabs
        let content = `
    <ul class="nav nav-tabs" id="richbotTab-${data.id}" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="media-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#media-${data.id}" type="button" role="tab">Media</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="commands-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#commands-${data.id}" type="button" role="tab">Commands</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="events-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#events-${data.id}" type="button" role="tab">Event Logs</button>
        </li>
<li class="nav-item" role="presentation">
            <button class="nav-link" id="triggers-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#triggers-${data.id}" type="button" role="tab">Triggers</button>
        </li>
    </ul>
    <div class="tab-content" id="richbotTabContent-${data.id}">
        <!-- Media Tab -->
        <div class="tab-pane fade show active" id="media-${data.id}" role="tabpanel">
            <div class="media-gallery row">
                ${data.media.map(media => {
            if (media.type === 'image') {
                return `<div class="col-md-3 mb-3"><img src="/storage/${media.file_path}" alt="Image" class="img-thumbnail"></div>`;
            } else if (media.type === 'audio') {
                return `<div class="col-md-3 mb-3">
                            <audio controls>
                                <source src="/storage/${media.file_path}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>`;
            }
            return '';
        }).join('')}
            </div>
        </div>
        <!-- Commands Tab -->
        <div class="tab-pane fade" id="commands-${data.id}" role="tabpanel">
            <h3>Send Command</h3>
            <form id="command-form-${data.id}">
                <div class="form-group">
                    <label for="command-${data.id}">Command</label>
                    <select name="command" id="command-${data.id}" class="form-control command-select" required>
                        <option value="" disabled selected>Select a command</option>
                        <option value="take_picture">Take Picture</option>
                        <option value="move">Move</option>
                        <option value="send_data">Send Data</option>
                        <option value="speak_text">Speak Text</option>
                        <option value="play_url">Play URL</option>
                    </select>
                </div>
                <div class="form-group parameters-group" id="parameters-group-${data.id}">
                    <!-- Parameters will be dynamically loaded here based on the selected command -->
                </div>
                <button type="submit" class="btn btn-success mt-2">Send Command</button>
            </form>
            <h3 class="mt-4">Command History</h3>
            <ul class="list-group">
                ${data.commands.map(command => `
                    <li class="list-group-item">
                        ${command.created_at} - <strong>${command.command}</strong>: ${JSON.stringify(command.parameters)}
                    </li>
                `).join('')}
            </ul>
        </div>
        <!-- Event Logs Tab -->
        <div class="tab-pane fade" id="events-${data.id}" role="tabpanel">
            <h3>Event Logs</h3>
            <ul class="list-group">
                ${data.events.map(event => `
                    <li class="list-group-item">
                        ${event.created_at} - ${event.event_type}: ${JSON.stringify(event.details)}
                    </li>
                `).join('')}
            </ul>
        </div>
      <!-- Triggers Tab -->
<div class="tab-pane fade" id="triggers-${data.id}" role="tabpanel">
            <h3>Media Triggers</h3>
            <button class="btn btn-primary mb-2" data-richbot-id="${data.id}" id="add-trigger-btn-${data.id}">Add Trigger</button>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Prompt</th>
                        <th>Action</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="trigger-list-${data.id}">
                    ${data.media_triggers.map(trigger => `
                        <tr data-trigger-id="${trigger.id}">
                            <td>${trigger.type}</td>
                            <td>${trigger.prompt}</td>
                            <td>${trigger.action}</td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-trigger-btn" data-richbot-id="${data.id}" data-trigger-id="${trigger.id}">Edit</button>
                                <button class="btn btn-sm btn-danger delete-trigger-btn" data-richbot-id="${data.id}" data-trigger-id="${trigger.id}">Delete</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    </div>
    `;

        return content;
    }

    // Event listener for the dynamic form changes based on selected command
    document.addEventListener('change', function(event) {
        if (event.target && event.target.classList.contains('command-select')) {
            const richbotId = event.target.id.split('-')[1];
            const selectedCommand = event.target.value;
            console.log('selectedCommand',selectedCommand);
            console.log('richbotId',richbotId);
            const parametersGroup = document.getElementById(`parameters-group-${richbotId}`);

            if (!parametersGroup) {
                console.error('Parameters group not found for richbot:', richbotId);
                return;
            }

            // Clear the parameters group
            parametersGroup.innerHTML = '';

            // Add dynamic fields based on the selected command
            if (selectedCommand === 'take_picture') {
                parametersGroup.innerHTML = `
                <label>Resolution</label>
                <select name="parameters[resolution]" class="form-control">
                    <option value="1080p">1080p</option>
                    <option value="720p">720p</option>
                </select>
            `;
            } else if (selectedCommand === 'move') {
                parametersGroup.innerHTML = `
                <label>Direction</label>
                <select name="parameters[direction]" class="form-control">
                    <option value="forward">Forward</option>
                    <option value="backward">Backward</option>
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                </select>
                <label>Speed</label>
                <input type="number" name="parameters[speed]" class="form-control" placeholder="Enter speed (1-10)">
            `;
            } else if (selectedCommand === 'send_data') {
                parametersGroup.innerHTML = `
                <label>Data</label>
                <input type="text" name="parameters[data]" class="form-control" placeholder="Enter the data">
            `;
            } else if (selectedCommand === 'speak_text') {
                parametersGroup.innerHTML = `
                <label>Text to Speak</label>
                <input type="text" name="parameters[text]" class="form-control" placeholder="Enter the text to speak">
            `;
            } else if (selectedCommand === 'play_url') {
                parametersGroup.innerHTML = `
                <label>URL to Play</label>
                <input type="url" name="parameters[url]" class="form-control" placeholder="Enter the URL to play">
            `;
            }
        }
    });

    function sendCommand(richbotId, command) {
        if (!command) {
            console.error('No command provided');
            return;
        }

        const parameters = {};
        
        // Add specific parameters based on command type
        switch (command) {
            case 'capture_image':
            case 'capture_audio':
            case 'capture_screen':
                parameters.quality = 'high';
                break;
            case 'start_video':
            case 'start_audio':
            case 'start_screen':
                parameters.format = 'webm';
                parameters.bitrate = '2Mbps';
                break;
        }
        
        ws.send(JSON.stringify({
            type: 'device_command',
            device_id: richbotId,
            command: command,
            parameters: parameters
        }));
        
        logMessage(`Sent command "${command}" to device ${richbotId}`);
    }

    document.getElementById('toggleInactiveDevices').addEventListener('click', function() {
        showInactiveDevices = !showInactiveDevices;
        this.innerHTML = showInactiveDevices ? 
            '<i class="fas fa-eye"></i> Show Inactive' : 
            '<i class="fas fa-eye-slash"></i> Hide Inactive';
        // Trigger a refresh to update the view with current filter
        refreshDashboardData();
    });

    function viewDeviceDetails(deviceId) {
        // Create a modal to show device details
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'deviceDetailsModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Device Details: ${deviceId}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="deviceDetailsContent">
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Load device details
        loadDeviceDetails(deviceId);
        
        // Clean up modal when closed
        modal.addEventListener('hidden.bs.modal', function () {
            document.body.removeChild(modal);
        });
    }

    function loadDeviceDetails(deviceFd) {
        const device = clientsData[deviceFd];
        if (!device) {
            console.error('Device not found:', deviceFd);
            const content = document.getElementById('deviceDetailsContent');
            content.innerHTML = '<div class="alert alert-warning">Device not found. Please refresh the page to update the device list.</div>';
            return;
        }

        const content = document.getElementById('deviceDetailsContent');
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Client Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>File Descriptor:</th>
                            <td>${deviceFd}</td>
                        </tr>
                        <tr>
                            <th>Name/ID:</th>
                            <td>${device.name || device.user_id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Connected At:</th>
                            <td>${formatLastSeen(device.connected_at * 1000)}</td>
                        </tr>
                        <tr>
                            <th>Room:</th>
                            <td>${device.room || 'None'}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>${device.type || 'Unknown'}</td>
                        </tr>
                        <tr>
                            <th>Assistant ID:</th>
                            <td>${device.assistant_id || 'N/A'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Actions</h6>
                    <div class="mb-3">
                        <label class="form-label">Send Message:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="messageInput-${deviceFd}" placeholder="Enter message...">
                            <button class="btn btn-outline-secondary" onclick="sendMessageToDevice('${deviceFd}')">Send</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Actions:</label>
                        <div class="btn-group d-block">
                            <button class="btn btn-outline-info btn-sm" onclick="requestRoomStatus('${device.room}')">Get Room Status</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function sendMessageToDevice(deviceFd) {
        const messageInput = document.getElementById(`messageInput-${deviceFd}`);
        const message = messageInput.value.trim();
        
        if (!message) {
            alert('Please enter a message');
            return;
        }

                 sendBroadcastMessage(`Message to ${deviceFd}: ${message}`);
         messageInput.value = '';
         logMessage(`Sent message to device ${deviceFd}: ${message}`);
     }

     function sendBroadcast() {
         const messageInput = document.getElementById('broadcastMessageInput');
         const message = messageInput.value.trim();
         
         if (!message) {
             alert('Please enter a broadcast message');
             return;
         }

         sendBroadcastMessage(message);
         messageInput.value = '';
     }

     function sendSystemCommand(command) {
         sendControlCommand(command, {
             timestamp: Date.now(),
             source: 'dashboard'
         });
     }

     function showConnectionInfo() {
         const infoDiv = document.getElementById('connectionInfo');
         const diagnosticsDiv = document.getElementById('connectionDiagnostics');
         
         if (infoDiv.style.display === 'none') {
             // Update diagnostics with current info
             const token = appState.apiToken || 'NOT_SET';
             const currentDeviceId = dashboardDeviceId || 'NOT_GENERATED';
             const wsState = ws ? ws.readyState : 'NO_WEBSOCKET';
             const stateNames = ['CONNECTING', 'OPEN', 'CLOSING', 'CLOSED'];
             const stateName = typeof wsState === 'number' ? stateNames[wsState] : wsState;
             
             diagnosticsDiv.innerHTML = `
                 <strong>Current Status:</strong><br>
                 Device ID: ${currentDeviceId}<br>
                 WebSocket State: ${wsState} (${stateName})<br>
                 API Token: ${token ? `Present (${token.length} chars)` : 'Missing'}<br>
                 Last Update: ${lastDataUpdate ? new Date(lastDataUpdate).toLocaleTimeString() : 'Never'}<br>
                 Reconnect Attempts: ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS}<br>
                 Auto-refresh Interval: ${DATA_UPDATE_INTERVAL/1000}s
             `;
             
             infoDiv.style.display = 'block';
             logMessage('📊 Connection diagnostics displayed');
         } else {
             infoDiv.style.display = 'none';
        }
    }
    </script>
