<!-- the current file is -->
 <div class="container">
    <h1>Remote Richbots Dashboard</h1>
    <h5>webapp.remote_richbot._richbots.blade.php</h5>
 </div>


<!-- Connection Status Indicator -->
<div class="container mb-3">
    <div class="d-flex align-items-center">
        <div id="connectionStatus" class="me-2">
            <span class="status-indicator status-disconnected"></span>
            <span id="connectionText">Disconnected</span>
        </div>
        <div id="connectionDetails" class="text-muted small me-3"></div>
        <button id="refreshDataBtn" class="btn btn-sm btn-outline-primary" onclick="requestServerData()">
            <i class="fas fa-sync-alt"></i> Refresh Data
        </button>
    </div>
</div>

<!-- Connected Devices Table -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Connected Devices</h5>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-2" id="toggleInactiveDevices">
                    <i class="fas fa-eye-slash"></i> Hide Inactive
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="requestServerData()">
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
                            <th>Latest Image</th>
                            <th>Actions</th>
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
            <h5 class="card-title mb-0">Server Statistics</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Total Connections</th>
                            <th>Total Messages</th>
                            <th>Active Devices</th>
                            <th>Active Rooms</th>
                            <th>Active Streams</th>
                            <th>Active Calls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="totalConnections">0</td>
                            <td id="totalMessages">0</td>
                            <td id="activeDevices">0</td>
                            <td id="activeRooms">0</td>
                            <td id="activeStreams">0</td>
                            <td id="activeCalls">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Command Interface -->
<div class="container mb-4">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Command Interface</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="remoteRichbotDeviceSelect" class="form-label">Select Device</label>
                        <select class="form-select" id="remoteRichbotDeviceSelect">
                            <option value="">Choose a device...</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="commandInput" class="form-label">Command</label>
                        <input type="text" class="form-control" id="commandInput" placeholder="Enter command...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="commandParams" class="form-label">Parameters (JSON)</label>
                        <textarea class="form-control" id="commandParams" rows="1" placeholder='{"param1": "value1"}'></textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-primary" onclick="sendWebSocketCommand()">Send Command</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Command History</h6>
                    <div id="commandHistory" class="command-history"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Existing Richbots Table -->
<div class="container">
    <h1>Remote Richbots Dashboard</h1>
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
    let devices = new Map();
    let reconnectAttempts = 0;
    const MAX_RECONNECT_ATTEMPTS = 5;
    const RECONNECT_DELAY = 5000;
    let lastDataUpdate = null;
    const DATA_UPDATE_INTERVAL = 30000; // Update every 30 seconds
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

    function connectWebSocket() {
        dashboardDeviceId = 'dashboard-' + Math.random().toString(36).substr(2, 9);
        console.log('Connecting with device ID:', dashboardDeviceId);
        
        ws = new WebSocket(`${window.appConfig.wsUrlAlt}/remote-richbot-manager/${dashboardDeviceId}`);
        
        ws.onopen = () => {
            updateConnectionStatus(true, `Connected as ${dashboardDeviceId}`);
            logMessage('Connected to WebSocket server');
            // Register as dashboard
            ws.send(JSON.stringify({
                type: 'device_register',
                device_id: dashboardDeviceId,
                capabilities: ['command', 'monitor']
            }));
            // Request initial server data
            requestServerData();
            // Start periodic data updates
            startPeriodicUpdates();
        };

        ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log('Raw WebSocket message:', event.data);
                handleWebSocketMessage(data);
            } catch (error) {
                console.error('Error parsing message:', error);
                logMessage('Error parsing message: ' + error.message);
            }
        };

        ws.onclose = () => {
            updateConnectionStatus(false, reconnectAttempts < MAX_RECONNECT_ATTEMPTS ? 
                `Reconnecting... (Attempt ${reconnectAttempts + 1}/${MAX_RECONNECT_ATTEMPTS})` : 
                'Connection failed. Please refresh the page.');
            logMessage('Disconnected from WebSocket server');
            
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                setTimeout(connectWebSocket, RECONNECT_DELAY);
            }
        };

        ws.onerror = (error) => {
            console.error('WebSocket error:', error);
            updateConnectionStatus(false, 'Connection error occurred');
            logMessage('WebSocket error: ' + error.message);
        };
    }

    function requestServerData() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            const refreshBtn = document.getElementById('refreshDataBtn');
            refreshBtn.disabled = true;
            
            // Add spinning animation to the icon
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            
            console.log('Requesting server data with device ID:', dashboardDeviceId);
            ws.send(JSON.stringify({
                type: 'request_server_data',
                device_id: dashboardDeviceId
            }));
            
            // Reset button state after 2 seconds
            setTimeout(() => {
                refreshBtn.disabled = false;
                icon.classList.remove('fa-spin');
            }, 2000);
        } else {
            console.error('Cannot refresh data: WebSocket not connected');
            logMessage('Cannot refresh data: WebSocket not connected');
        }
    }

    function startPeriodicUpdates() {
        setInterval(() => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                requestServerData();
            }
        }, DATA_UPDATE_INTERVAL);
    }

    function handleWebSocketMessage(data) {
        console.log('Handling message:', data);
        switch(data.type) {
            case 'device_registered':
                logMessage('Dashboard registered successfully');
                break;
            case 'device_status_update':
                updateDeviceStatus(data.device_id, data.status);
                break;
            case 'device_capabilities_updated':
                updateDeviceCapabilities(data.device_id, data.capabilities);
                break;
            case 'device_error':
                logMessage(`Error from device ${data.device_id}: ${data.error}`);
                break;
            case 'device_media':
                handleDeviceMedia(data);
                break;
            case 'server_data':
                handleServerData(data);
                break;
            case 'error':
                logMessage(`Server error: ${data.message || 'Unknown error'}`);
                break;
            case 'device_command_response':
                if (data.command === 'capture_image') {
                    handleImageResponse(data);
                }
                break;
            default:
                logMessage(`Received unknown message type: ${data.type}`);
        }
    }

    function handleServerData(data) {
        console.log('Processing server data:', data);
        
        // Update last data update time
        lastDataUpdate = Date.now();
        updateConnectionStatus(true, `Last update: ${new Date().toLocaleTimeString()}`);
        
        // Update stats
        if (data.data && data.data.stats) {
            const stats = data.data.stats;
            document.getElementById('totalConnections').textContent = stats.total_connections || 0;
            document.getElementById('totalMessages').textContent = stats.total_messages || 0;
            document.getElementById('activeDevices').textContent = Object.keys(data.data.clients || {}).length;
            document.getElementById('activeRooms').textContent = (data.data.rooms || []).length;
            document.getElementById('activeStreams').textContent = stats.total_media_streams || 0;
            document.getElementById('activeCalls').textContent = (data.data.rooms || []).filter(room => room.name && room.name.startsWith('call-')).length;
        }
        
        // Update devices table and store devices in Map
        if (data.data && data.data.clients) {
            const deviceTableBody = document.getElementById('deviceTableBody');
            const deviceSelect = document.getElementById('remoteRichbotDeviceSelect');
            
            deviceTableBody.innerHTML = '';
            deviceSelect.innerHTML = '<option value="">Choose a device...</option>';
            
            // Clear existing devices Map
            devices.clear();
            
            const clients = new Map(Object.entries(data.data.clients));
            
            if (clients.size === 0) {
                deviceTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No devices connected</td></tr>';
                return;
            }

            let hasVisibleDevices = false;
            
            clients.forEach((client, fd) => {
                // Store device in Map
                devices.set(client.device_id || fd, client);
                
                const lastSeen = client.last_seen * 1000;
                const timeSinceLastSeen = Date.now() - lastSeen;
                const isActive = timeSinceLastSeen < FIVE_MINUTES;
                
                if (!isActive && !showInactiveDevices) {
                    return;
                }
                
                hasVisibleDevices = true;
                
                // Get services from the client data
                const services = typeof client.services === 'string' ? 
                    Object.entries(JSON.parse(client.services))
                        .filter(([_, value]) => value === true)
                        .map(([name]) => ({ name })) : 
                    client.services;
                const serviceNames = services.map(service => service.name).join(', ');
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <span class="status-indicator ${isActive ? 'status-active' : 'status-inactive'}"></span>
                    </td>
                    <td>${fd}</td>
                    <td>${client.device_id || 'N/A'}</td>
                    <td>${client.type || 'Unknown'}</td>
                    <td>${formatLastSeen(lastSeen)}</td>
                    <td>
                        ${services.map(service => `
                            <span class="capability-badge" title="${service.description || ''}">
                                ${service.name}
                            </span>
                        `).join('')}
                    </td>
                    <td>${client.room_id || 'None'}</td>
                    <td>
                        ${(() => {
                            const deviceImages = receivedImages.get(client.device_id || fd) || [];
                            if (deviceImages.length > 0) {
                                const latestImage = deviceImages[deviceImages.length - 1];
                                return `
                                    <div class="latest-image-container" style="cursor: pointer;" onclick="showCarousel('${client.device_id || fd}', ${deviceImages.length - 1})">
                                        <img src="data:image/${latestImage.format};base64,${latestImage.data}" 
                                             alt="Latest Image" 
                                             style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                                        ${deviceImages.length > 1 ? 
                                            `<div class="image-count" style="position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; border-radius: 10px; font-size: 12px;">
                                                ${deviceImages.length}
                                            </div>` : ''}
                                    </div>
                                `;
                            }
                            return '<span class="text-muted">No images</span>';
                        })()}
                    </td>
                    <td>
                        <div class="btn-group">
                            <select class="form-select form-select-sm command-select" data-device-id="${client.device_id || fd}">
                                <option value="">Select command...</option>
                                ${generateCommandOptions(services)}
                            </select>
                            <button class="btn btn-sm btn-info" onclick="viewDeviceDetails('${client.device_id || fd}')" title="View Details">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                deviceTableBody.appendChild(row);
                
                // Add to select
                const option = document.createElement('option');
                option.value = client.device_id || fd;
                option.textContent = `${client.device_id || fd} (${serviceNames})`;
                deviceSelect.appendChild(option);
                
                // Add event listener for command selection
                const commandSelect = row.querySelector('.command-select');
                commandSelect.addEventListener('change', function() {
                    const command = this.value;
                    if (command) {
                        sendCommand(client.device_id || fd, command);
                        this.value = ''; // Reset selection
                    }
                });
            });

            if (!hasVisibleDevices) {
                deviceTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No active devices connected</td></tr>';
            }
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
        
        ws.send(JSON.stringify({
            type: 'device_command',
            device_id: deviceId,
            command: command,
            parameters: parameters
        }));
        
        logMessage(`Sent command "${command}" to device ${deviceId}`);
    }

    function updateDeviceStatus(deviceId, status) {
        const deviceCard = document.getElementById(`device-${deviceId}`);
        if (deviceCard) {
            const statusIndicator = deviceCard.querySelector('.status-indicator');
            const currentTime = Date.now();
            const device = devices.get(deviceId);
            
            if (device) {
                const isActive = (currentTime - (device.last_seen * 1000)) < FIVE_MINUTES;
                statusIndicator.className = `status-indicator status-${isActive ? 'active' : 'inactive'}`;
            }
        }
    }

    function updateDeviceCapabilities(deviceId, capabilities) {
        const device = devices.get(deviceId);
        if (device) {
            device.capabilities = capabilities;
            updateDeviceList();
        }
    }

    function updateDeviceList() {
        console.log('Updating device list with:', devices); // Debug log
        const deviceList = document.getElementById('remoteRichbotDeviceList');
        const deviceSelect = document.getElementById('remoteRichbotDeviceSelect');
        console.log('Device list:', deviceList);
        console.log('Device select:', deviceSelect);
        
        if (!deviceList) {
            console.error('Device list container not found!');
            return;
        }
        
        deviceList.innerHTML = '';
        deviceSelect.innerHTML = '<option value="">Choose a device...</option>';
        
        if (devices.size === 0) {
            deviceList.innerHTML = '<div class="text-muted">No devices connected</div>';
            return;
        }
        
        const currentTime = Date.now();
        let hasVisibleDevices = false;
        
        devices.forEach((device, id) => {
            console.log('Processing device:', id, device); // Debug log
            
            // Check if device is active (seen within last 5 minutes)
            const lastSeen = device.last_seen * 1000; // Convert to milliseconds
            const timeSinceLastSeen = currentTime - lastSeen;
            const isActive = timeSinceLastSeen < FIVE_MINUTES;
            
            // Skip inactive devices if they're hidden
            if (!isActive && !showInactiveDevices) {
                return;
            }
            
            hasVisibleDevices = true;
            
            // Create device card
            const card = document.createElement('div');
            card.className = 'device-card';
            card.id = `device-${id}`;
            
            // Format last seen time
            const lastSeenFormatted = new Date(lastSeen).toLocaleString();
            
            // Create command options based on capabilities
            const commandOptions = generateCommandOptions(JSON.parse(device.services || '[]'));
            
            card.innerHTML = `
                <div class="device-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <span class="status-indicator status-${isActive ? 'active' : 'inactive'} me-2">●</span>
                            ${id}
                        </h6>
                        <span class="last-seen">Last seen: ${lastSeenFormatted}</span>
                    </div>
                </div>
                <div class="device-body">
                    <div class="capabilities mb-2">
                        ${(JSON.parse(device.services || '[]')).map(service => `
                            <span class="capability-badge">${service}</span>
                        `).join('')}
                    </div>
                    <div class="device-command">
                        <select class="form-select" onchange="handleDeviceCommandSelect(this, '${id}')">
                            <option value="">Select command...</option>
                            ${commandOptions}
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="sendDeviceCommand('${id}')">
                            Send Command
                        </button>
                    </div>
                </div>
            `;
            
            deviceList.appendChild(card);

            // Add to select
            const option = document.createElement('option');
            option.value = id;
            option.textContent = id;
            deviceSelect.appendChild(option);
        });

        if (!hasVisibleDevices) {
            deviceList.innerHTML = '<div class="text-center text-muted">No active devices connected</div>';
        }
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
    connectWebSocket();

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
        updateDeviceList();
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

    function loadDeviceDetails(deviceId) {
        const device = devices.get(deviceId);
        if (!device) {
            console.error('Device not found:', deviceId);
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
                            <th>Device ID:</th>
                            <td>${deviceId}</td>
                        </tr>
                        <tr>
                            <th>Last Seen:</th>
                            <td>${formatLastSeen(device.last_seen * 1000)}</td>
                        </tr>
                        <tr>
                            <th>Room:</th>
                            <td>${device.room_id || 'None'}</td>
                        </tr>
                        <tr>
                            <th>File Descriptor:</th>
                            <td>${device.fd || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>${device.type || 'Unknown'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Services</h6>
                    <div id="servicesList">
                        ${(typeof device.services === 'string' ? 
                            Object.entries(JSON.parse(device.services))
                                .filter(([_, value]) => value === true)
                                .map(([name]) => name) : 
                            device.services.map(service => service.name)
                        ).map(service => `
                            <span class="capability-badge">${service}</span>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        
        // Request detailed device information
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'request_device_details',
                device_id: deviceId
            }));
        }
    }
</script>
