
<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Start Bare Call</h5>
        </div>
        <div class="card-body">
            <form id="bareCallForm">
                <div class="mb-3">
                    <label class="form-label">Your Phone Number</label>
                    <input type="tel" class="form-control" id="yourPhoneNumber" placeholder="+1 (555) 123-4567" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Additional Phone Numbers</label>
                    <div id="additionalPhones">
                        <div class="input-group mb-2">
                            <input type="tel" class="form-control" placeholder="+1 (555) 123-4567">
                            <button type="button" class="btn btn-outline-danger remove-phone">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addPhoneButton">
                        <i class="fas fa-plus"></i> Add Another Number
                    </button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Contact (Optional)</label>
                    <select class="form-select" id="contactSelect">
                        <option value="">Select a Contact</option>
                        <!-- Contacts will be loaded dynamically -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Assistant (Optional)</label>
                    <select class="form-select" id="assistantSelect">
                        <option value="">Select an Assistant</option>
                        <!-- Assistants will be loaded dynamically -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Pipeline (Optional)</label>
                    <select class="form-select" id="pipelineSelect">
                        <option value="">Select a Pipeline</option>
                        <!-- Pipelines will be loaded dynamically -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Conversation Path (Optional)</label>
                    <select class="form-select" id="conversationPathSelect">
                        <option value="">Select a Conversation Path</option>
                        <!-- Conversation paths will be loaded dynamically -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room Name (Optional)</label>
                    <input type="text" class="form-control" id="room" placeholder="Enter room name">
                    <small class="form-text text-muted">Leave blank to generate a unique room name</small>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="interactiveMode">
                    <label class="form-check-label" for="interactiveMode">Start in Interactive Mode</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="addMonitor">
                    <label class="form-check-label" for="addMonitor">Add Monitor</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="recordCall">
                    <label class="form-check-label" for="recordCall">Record Call</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-phone me-2"></i>Start Group Call
                </button>
            </form>
        </div>
    </div>

    <!-- Call Status Card -->
    <div id="callStatus" class="card mb-4" style="display: none;">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Call Status</h5>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-phone-volume me-2"></i>
                <span id="callStatusText">Initializing call...</span>
            </div>
            <div class="progress mb-3" style="height: 20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 100%"></div>
            </div>
            <button id="endCallButton" class="btn btn-danger" style="display: none;">
                <i class="fas fa-phone-slash me-2"></i>End Call
            </button>
        </div>
    </div>
</div>

<script>
// Bare Call management
const BareCall = {
    init() {
        this.loadAssistants();
        this.loadContacts();
        this.loadPipelines();
        this.loadConversationPaths();
        this.attachEventListeners();
    },

    loadAssistants() {
        const select = document.getElementById('assistantSelect');
        select.innerHTML = '<option value="">Select an Assistant</option>';
        
        if (appState.data?.assistants) {
            appState.data.assistants.forEach(assistant => {
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = `${assistant.name}${assistant.model ? ` (${assistant.model.name})` : ''}`;
                select.appendChild(option);
            });
        }
    },

    loadContacts() {
        const select = document.getElementById('contactSelect');
        fetch('/api/contacts', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Select a Contact</option>';
            // Handle DataTables format
            const contacts = data.data || [];
            contacts.forEach(contact => {
                const option = document.createElement('option');
                option.value = contact.id;
                option.textContent = `${contact.name} (${contact.phone})`;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading contacts:', error));
    },

    loadPipelines() {
        const select = document.getElementById('pipelineSelect');
        fetch('/api/pipelines', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Select a Pipeline</option>';
            // Handle regular array format
            const pipelines = Array.isArray(data) ? data : [];
            pipelines.forEach(pipeline => {
                const option = document.createElement('option');
                option.value = pipeline.id;
                option.textContent = pipeline.name;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading pipelines:', error));
    },

    loadConversationPaths() {
        const select = document.getElementById('conversationPathSelect');
        fetch('/api/conversation-paths', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Select a Conversation Path</option>';
            // Handle regular array format
            const paths = Array.isArray(data) ? data : [];
            paths.forEach(path => {
                const option = document.createElement('option');
                option.value = path.id;
                option.textContent = path.name;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading conversation paths:', error));
    },

    attachEventListeners() {
        document.getElementById('bareCallForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.startCall();
        });

        document.getElementById('endCallButton').addEventListener('click', () => {
            this.endCall();
        });

        // Add phone number button
        document.getElementById('addPhoneButton').addEventListener('click', () => {
            const phoneDiv = document.createElement('div');
            phoneDiv.className = 'input-group mb-2';
            phoneDiv.innerHTML = `
                <input type="tel" class="form-control" placeholder="+1 (555) 123-4567">
                <button type="button" class="btn btn-outline-danger remove-phone">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.getElementById('additionalPhones').appendChild(phoneDiv);
        });

        // Remove phone number button
        document.getElementById('additionalPhones').addEventListener('click', (e) => {
            if (e.target.closest('.remove-phone')) {
                e.target.closest('.input-group').remove();
            }
        });

        // Contact selection
        document.getElementById('contactSelect').addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            if (selectedOption.value) {
                const phoneNumber = selectedOption.textContent.match(/\((.*?)\)/)[1];
                // Add the contact's phone number as an additional number
                const phoneDiv = document.createElement('div');
                phoneDiv.className = 'input-group mb-2';
                phoneDiv.innerHTML = `
                    <input type="tel" class="form-control" value="${phoneNumber}" readonly>
                    <button type="button" class="btn btn-outline-danger remove-phone">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                document.getElementById('additionalPhones').appendChild(phoneDiv);
            }
        });
    },

    async startCall() {
        const yourPhoneNumber = document.getElementById('yourPhoneNumber').value;
        const additionalPhones = Array.from(document.querySelectorAll('#additionalPhones input'))
            .map(input => input.value)
            .filter(phone => phone.trim() !== '');
        const assistantId = document.getElementById('assistantSelect').value;
        const contactId = document.getElementById('contactSelect').value;
        const pipelineId = document.getElementById('pipelineSelect').value;
        const conversationPathId = document.getElementById('conversationPathSelect').value;
        const room = document.getElementById('room').value;
        const interactiveMode = document.getElementById('interactiveMode').checked;
        const addMonitor = document.getElementById('addMonitor').checked;
        const recordCall = document.getElementById('recordCall').checked;

        if (!yourPhoneNumber) {
            alert('Please enter your phone number');
            return;
        }

        // Hide form and show call status
        document.getElementById('bareCallForm').closest('.card').style.display = 'none';
        const callStatus = document.getElementById('callStatus');
        callStatus.style.display = 'block';

        try {
            const response = await fetch('/api/bare/call/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: JSON.stringify({
                    phone_number: yourPhoneNumber,
                    additional_phones: additionalPhones,
                    assistant_id: assistantId,
                    contact_id: contactId,
                    pipeline_id: pipelineId,
                    conversation_path_id: conversationPathId,
                    room: room,
                    interactive_mode: interactiveMode,
                    add_monitor: addMonitor,
                    record_call: recordCall
                })
            });

            if (!response.ok) {
                throw new Error('Failed to initiate call');
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }

            this.currentCallId = result.data.call_sid;
            
            // Show end call button
            document.getElementById('endCallButton').style.display = 'block';
            
            // Update status
            document.getElementById('callStatusText').textContent = 'Call in progress...';

            // Start polling for call status
            this.startStatusPolling();

        } catch (error) {
            console.error('Call initiation error:', error);
            document.getElementById('callStatusText').textContent = 'Failed to start call: ' + error.message;
            setTimeout(() => this.resetInterface(), 3000);
        }
    },

    async endCall() {
        if (!this.currentCallId) return;

        try {
            const response = await fetch(`/api/bare/call/${this.currentCallId}/end`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to end call');
            }

            document.getElementById('callStatusText').textContent = 'Call ended';
            setTimeout(() => this.resetInterface(), 2000);

        } catch (error) {
            console.error('Call end error:', error);
            document.getElementById('callStatusText').textContent = 'Failed to end call: ' + error.message;
        }
    },

    startStatusPolling() {
        if (this.statusInterval) clearInterval(this.statusInterval);
        
        this.statusInterval = setInterval(async () => {
            if (!this.currentCallId) {
                clearInterval(this.statusInterval);
                return;
            }

            try {
                const response = await fetch(`/api/bare/call/${this.currentCallId}/status`, {
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`
                    }
                });

                if (!response.ok) throw new Error('Failed to get call status');
                
                const data = await response.json();
                
                // Update status display
                document.getElementById('callStatusText').textContent = data.status;
                
                // If call is finished, reset interface
                if (['completed', 'failed', 'no-answer'].includes(data.status)) {
                    clearInterval(this.statusInterval);
                    setTimeout(() => this.resetInterface(), 2000);
                }

            } catch (error) {
                console.error('Status polling error:', error);
            }
        }, 2000);
    },

    resetInterface() {
        // Clear current call data
        this.currentCallId = null;
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }

        // Reset UI
        document.getElementById('bareCallForm').closest('.card').style.display = 'block';
        document.getElementById('callStatus').style.display = 'none';
        document.getElementById('endCallButton').style.display = 'none';
        document.getElementById('bareCallForm').reset();
    }
};

// Initialize when document is ready
BareCall.init();
</script>


