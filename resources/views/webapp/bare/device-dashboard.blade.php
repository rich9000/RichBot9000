
    <style>
        .device-card {
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .device-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .command-history {
            height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-active {
            background-color: #28a745;
        }
        .status-inactive {
            background-color: #dc3545;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Device List -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Connected Devices</h5>
                    </div>
                    <div class="card-body">
                        <div id="deviceList">
                            <!-- Devices will be listed here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Command Interface -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Command Interface</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="deviceSelect" class="form-label">Select Device</label>
                            <select class="form-select" id="deviceSelect">
                                <option value="">Choose a device...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="commandInput" class="form-label">Command</label>
                            <input type="text" class="form-control" id="commandInput" placeholder="Enter command...">
                        </div>
                        <div class="mb-3">
                            <label for="commandParams" class="form-label">Parameters (JSON)</label>
                            <textarea class="form-control" id="commandParams" rows="3" placeholder='{"param1": "value1"}'></textarea>
                        </div>
                        <button class="btn btn-primary" onclick="sendCommand()">Send Command</button>
                        <div class="mt-3">
                            <h6>Command History</h6>
                            <div id="commandHistory" class="command-history"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let ws;
        let selectedDevice = null;
        let devices = new Map();

        function connect() {
            ws = new WebSocket(`wss://${window.location.host}/bare/remote-richbot`);
            
            ws.onopen = () => {
                logMessage('Connected to WebSocket server');
                // Register as dashboard
                ws.send(JSON.stringify({
                    type: 'device_register',
                    device_id: 'dashboard-' + Math.random().toString(36).substr(2, 9),
                    capabilities: ['command', 'monitor']
                }));
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                handleMessage(data);
            };

            ws.onclose = () => {
                logMessage('Disconnected from WebSocket server');
                setTimeout(connect, 5000);
            };
        }

        function handleMessage(data) {
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
            }
        }

        function updateDeviceStatus(deviceId, status) {
            const deviceCard = document.getElementById(`device-${deviceId}`);
            if (deviceCard) {
                const statusIndicator = deviceCard.querySelector('.status-indicator');
                statusIndicator.className = `status-indicator status-${status === 'active' ? 'active' : 'inactive'}`;
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
            const deviceList = document.getElementById('deviceList');
            const deviceSelect = document.getElementById('deviceSelect');
            
            deviceList.innerHTML = '';
            deviceSelect.innerHTML = '<option value="">Choose a device...</option>';
            
            devices.forEach((device, id) => {
                // Add to device list
                const card = document.createElement('div');
                card.className = 'card device-card';
                card.id = `device-${id}`;
                card.innerHTML = `
                    <div class="card-body">
                        <h6 class="card-title">
                            <span class="status-indicator status-${device.status === 'active' ? 'active' : 'inactive'}"></span>
                            ${device.name || id}
                        </h6>
                        <p class="card-text">
                            <small class="text-muted">Capabilities: ${device.capabilities.join(', ')}</small>
                        </p>
                    </div>
                `;
                deviceList.appendChild(card);

                // Add to select
                const option = document.createElement('option');
                option.value = id;
                option.textContent = device.name || id;
                deviceSelect.appendChild(option);
            });
        }

        function sendCommand() {
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
            // Handle media data from devices
            logMessage(`Received media from device ${data.device_id}: ${data.media_type}`);
        }

        // Initialize connection
        connect();
    </script>
