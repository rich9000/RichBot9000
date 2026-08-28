class RichbotClient {
    constructor(targetElementId, config = {}) {
        this.config = {
            wsUrl: config.wsUrl || (window.appConfig?.wsUrlAlt ? `${window.appConfig.wsUrlAlt}/webclient` : 'wss://richbot9000.com:9502/webclient'),
            apiToken: config.apiToken || '',
            assistantId: config.assistantId || null,
            autoConnect: config.autoConnect || false,
            autoStartRecording: config.autoStartRecording || false,
            initialVolume: config.initialVolume || 1.0,
            showFormControls: config.showFormControls || true,
            showChatLog: config.showChatLog || true,
            ...config
        };

        this.targetElement = document.getElementById(targetElementId);
        if (!this.targetElement) {
            throw new Error(`Target element with id "${targetElementId}" not found`);
        }

        // WebSocket state
        this.socket = null;
        this.isConnected = false;
        this.currentRoom = null;
        this.roomMembers = new Map();

        // Audio state - Initialize with mic off, speaker on
        this.mediaRecorder = null;
        this.isRecording = false;
        this.audioContext = null;
        this.audioQueue = [];
        this.isPlaying = false;
        this.lastPlayTime = 0;
        this.audioGainNode = null;
        this.isMicMuted = true;  // Start with mic muted
        this.isSpeakerMuted = false;  // Start with speaker unmuted
        this.audioContextInitialized = false;

        // Message state
        this.messageCount = 0;
        this.isWaitingForResponse = false;
        this.currentMessage = '';
        this.currentTranscript = '';
        this.currentToolCall = null;
        this.currentResponseId = null;
        this.responseBuffers = new Map();

        // Add transcript buffer to track deltas by response ID
        this.transcriptBuffers = new Map();
        this.currentResponseId = null;
        this.currentTranscript = '';

        // Debug state
        this.debugMessages = [];
        this.debugFilter = '';
        this.debugMessageCount = 0;
        this.availableTools = [];

        // Initialize the client
        this.initialize();
    }

    initialize() {
        this.createClientStructure();
        this.attachEventListeners();
        
        if (this.config.autoConnect) {
            this.connect();
        }
    }

    createClientStructure() {
        // Get assistant info from appState
        const assistantId = this.config.assistantId;
        let assistantName = 'Assistant';
        let assistantModel = '';
        
        if (window.appState && window.appState.data && window.appState.data.assistants) {
            const assistant = window.appState.data.assistants.find(a => a.id === parseInt(assistantId));
            if (assistant) {
                assistantName = assistant.name;
                assistantModel = assistant.model.name;
            }
        }

        console.log('Creating client structure:', {
            targetElementId: this.targetElement.id,
            showFormControls: this.config.showFormControls,
            showChatLog: this.config.showChatLog
        });

        const html = `
            <div class="richbot-client">
                <div class="card">
                    <!-- Combined Header -->
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0">${assistantName}</h5>
                            <span class="badge bg-dark ms-2">${assistantModel}</span>
                            <button class="btn btn-link text-white ms-2 info-toggle" id="${this.getId('info-toggle')}" title="Toggle Session Info">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <span class="badge bg-info ms-2" id="${this.getId('room-badge')}" style="display: none;">
                                Room: <span id="${this.getId('room-name')}">-</span>
                            </span>
                            <span class="badge bg-secondary ms-2" id="${this.getId('modalities-badge')}" style="display: none;">
                                <i class="fas fa-microphone-alt me-1"></i><span id="${this.getId('modalities-text')}">-</span>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Audio Controls -->
                            <div class="d-flex align-items-center me-3">
                                <div class="audio-control-group d-flex align-items-center gap-2">
                                    <div class="audio-pill mic-pill">
                                        <div class="form-check form-switch d-flex align-items-center">
                                            <input class="form-check-input me-1" type="checkbox" id="${this.getId('mic-toggle')}" ${!this.isMicMuted ? 'checked' : ''}>
                                            <label class="form-check-label text-white" for="${this.getId('mic-toggle')}">
                                                <i class="fas fa-microphone" id="${this.getId('mic-indicator')}"></i>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="audio-pill speaker-pill">
                                        <div class="form-check form-switch d-flex align-items-center">
                                            <input class="form-check-input me-1" type="checkbox" id="${this.getId('speaker-toggle')}" ${!this.isSpeakerMuted ? 'checked' : ''}>
                                            <label class="form-check-label text-white" for="${this.getId('speaker-toggle')}">
                                                <i class="fas fa-volume-up" id="${this.getId('speaker-indicator')}"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-secondary" id="${this.getId('connection-status')}">Disconnected</span>
                            <button class="btn btn-sm btn-light" id="${this.getId('connect-btn')}">
                                <i class="fas fa-plug"></i> Connect
                            </button>
                        </div>
                    </div>

                    <!-- Info Section (Collapsible) -->
                    <div id="${this.getId('session-info')}" style="display: none;">
                        <div class="card-body border-bottom">
                            <div class="session-info">
                                <!-- Room Participants -->
                                <div class="participants-section mb-3" id="${this.getId('participants-section')}" style="display: none;">
                                    <strong>Room Participants:</strong>
                                    <div class="participants-grid mt-2" id="${this.getId('participants-grid')}">
                                        <!-- Participants will be added here -->
                                    </div>
                                </div>
                                <h6 class="border-bottom pb-2 mb-3">Session Information</h6>
                                <!-- Rest of session info content -->
                                ${this.getSessionInfoContent()}
                            </div>
                        </div>
                    </div>

                    <!-- Chat Section -->
                    <div class="card-body" id="${this.getId('chat-section')}" style="display: none;">
                        <div class="chat-messages" id="${this.getId('messages')}">
                            <!-- Messages will be added here -->
                        </div>
                        ${this.config.showFormControls ? `
                        <div class="chat-input mt-3">
                            <div class="input-group">
                                <textarea class="form-control" id="${this.getId('message-input')}" 
                                    rows="2" placeholder="Type your message..."></textarea>
                                <button class="btn btn-primary" id="${this.getId('send-btn')}" ${this.isWaitingForResponse ? 'disabled' : ''}>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <!-- System Controls -->
                            <div class="system-controls mt-2 d-flex gap-2">
                                <select class="form-select form-select-sm" id="${this.getId('vad-mode')}" style="width: auto;">
                                    <option value="server">Server VAD</option>
                                    <option value="semantic">Semantic VAD</option>
                                    <option value="none">No VAD</option>
                                </select>
                                <button class="btn btn-sm btn-secondary" id="${this.getId('update-vad-btn')}">
                                    Update VAD
                                </button>
                                <button class="btn btn-sm btn-success" id="${this.getId('create-response-btn')}">
                                    Create Response
                                </button>
                            </div>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div id="${this.getId('tool-status')}" class="tool-status" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tools me-2"></i>
                                    <span class="tool-name"></span>
                                </div>
                            </div>
                            <span class="badge bg-secondary" id="${this.getId('message-count')}">0 messages</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.targetElement.innerHTML = html;
        console.log('Client structure created:', {
            chatSectionId: this.getId('chat-section'),
            messagesId: this.getId('messages'),
            chatSection: document.getElementById(this.getId('chat-section')),
            messages: document.getElementById(this.getId('messages'))
        });

        this.addStyles();

        // Add info toggle handler
        document.getElementById(this.getId('info-toggle'))?.addEventListener('click', () => {
            const infoSection = document.getElementById(this.getId('session-info'));
            const isVisible = infoSection.style.display !== 'none';
            infoSection.style.display = isVisible ? 'none' : 'block';
            
            // Update icon
            const icon = document.getElementById(this.getId('info-toggle')).querySelector('i');
            icon.className = isVisible ? 'fas fa-info-circle' : 'fas fa-info-circle fa-rotate-180';
        });

        this.updateMicStatus();
        this.updateSpeakerStatus();
    }

    getSessionInfoContent() {
        return `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <strong>Model:</strong> <span id="${this.getId('model')}">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Voice:</strong> <span id="${this.getId('voice')}">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Modalities:</strong> <span id="${this.getId('modalities')}">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Turn Detection:</strong>
                        <ul class="list-unstyled ms-3 mb-0" id="${this.getId('turn-detection')}">
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <strong>Audio Format:</strong>
                        <ul class="list-unstyled ms-3 mb-0">
                            <li>Input: <span id="${this.getId('input-format')}">-</span></li>
                            <li>Output: <span id="${this.getId('output-format')}">-</span></li>
                        </ul>
                    </div>
                    <div class="mb-2">
                        <strong>Temperature:</strong> <span id="${this.getId('temperature')}">-</span>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <strong>Available Tools:</strong>
                <div class="tools-list mt-2" id="${this.getId('tools-list')}" style="max-height: 200px; overflow-y: auto;">
                </div>
            </div>
        `;
    }

    getExistingStructure() {
        return `
            <!-- Audio Controls -->
            <div class="audio-controls card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Audio Controls</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="mic-controls">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="${this.getId('mic-toggle')}" ${!this.isMicMuted ? 'checked' : ''}>
                                <label class="form-check-label" for="${this.getId('mic-toggle')}">
                                    Microphone
                                </label>
                            </div>
                            <div class="mic-indicator" id="${this.getId('mic-indicator')}">
                                <i class="fas fa-microphone"></i>
                            </div>
                        </div>
                        <div class="speaker-controls">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="${this.getId('speaker-toggle')}" ${!this.isSpeakerMuted ? 'checked' : ''}>
                                <label class="form-check-label" for="${this.getId('speaker-toggle')}">
                                    Speaker
                                </label>
                            </div>
                            <div class="speaker-indicator active" id="${this.getId('speaker-indicator')}">
                                <i class="fas fa-volume-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Section -->
            <div class="chat-section card mb-3" id="${this.getId('chat-section')}" style="display: none;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Chat</h5>
                </div>
                <div class="card-body">
                    <div class="chat-messages" id="${this.getId('messages')}">
                        <!-- Messages will be added here -->
                    </div>
                    <div class="chat-input mt-3">
                        <div class="input-group">
                            <textarea class="form-control" id="${this.getId('message-input')}" 
                                rows="2" placeholder="Type your message..."></textarea>
                            <button class="btn btn-primary" id="${this.getId('send-btn')}" ${this.isWaitingForResponse ? 'disabled' : ''}>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-end">
                        <span class="badge bg-secondary" id="${this.getId('message-count')}">0 messages</span>
                    </div>
                </div>
            </div>

            <!-- Tool Status -->
            <div class="tool-status card mb-3" id="${this.getId('tool-status')}" style="display: none;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tools me-2"></i>
                        <span class="tool-name"></span>
                    </div>
                </div>
            </div>
        `;
    }

    addStyles() {
        const styleId = 'richbot-client-styles';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                ${this.getExistingStyles()}
                
                .audio-control-group {
                    display: flex;
                    align-items: center;
                }

                .audio-control-group .form-check {
                    min-height: auto;
                    margin: 0;
                    padding: 0;
                }

                .audio-control-group .form-check-input {
                    margin: 0 4px;
                    height: 1rem;
                    width: 2rem;
                    cursor: pointer;
                }

                .audio-pill {
                    padding: 0.25rem 0.5rem;
                    border-radius: 2rem;
                    display: flex;
                    align-items: center;
                }

                .mic-pill {
                    background-color: #dc3545;
                    transition: background-color 0.3s ease;
                }

                .mic-pill.active {
                    background-color: #28a745;
                }

                .speaker-pill {
                    background-color: #dc3545;
                    transition: background-color 0.3s ease;
                }

                .speaker-pill.active {
                    background-color: #28a745;
                }

                .audio-control-group i {
                    transition: all 0.3s ease;
                    font-size: 1rem;
                    color: white;
                }

                #${this.getId('mic-indicator')}.active,
                #${this.getId('speaker-indicator')}.active {
                    color: white;
                }

                #${this.getId('mic-indicator')},
                #${this.getId('speaker-indicator')} {
                    color: rgba(255, 255, 255, 0.75);
                }

                .form-check-label {
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                }

                .form-check-input:checked {
                    background-color: white;
                    border-color: white;
                }
            `;
            document.head.appendChild(style);
        }
    }

    getExistingStyles() {
        return `
            .richbot-client {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            .participants-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .participant-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1rem;
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .participant-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #e9ecef;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .participant-info {
                flex: 1;
            }

            .chat-messages {
                height: 300px;
                overflow-y: auto;
                padding: 1rem;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .message {
                max-width: 80%;
                padding: 0.75rem 1rem;
                border-radius: 8px;
                word-break: break-word;
            }

            .message.user {
                background: #e3f2fd;
                align-self: flex-end;
            }

            .message.assistant {
                background: #f5f5f5;
                align-self: flex-start;
            }

            .message.system {
                background: #fff3cd;
                align-self: center;
                font-style: italic;
            }

            .mic-indicator, .speaker-indicator {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-top: 0.5rem;
                background: #dc3545;
                color: white;
                transition: all 0.3s ease;
            }

            .mic-indicator.active, .speaker-indicator.active {
                background: #198754;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }

            .tool-status {
                transition: all 0.3s ease;
            }

            .tool-status.active {
                background: #e8f5e9;
                border-color: #4caf50;
            }
        `;
    }

    getId(elementName) {
        return `richbot-${this.targetElement.id}-${elementName}`;
    }

    attachEventListeners() {
        // Connection controls
        const connectBtn = document.getElementById(this.getId('connect-btn'));
        if (connectBtn) {
            connectBtn.addEventListener('click', () => {
                if (!this.isConnected) {
                    this.connect();
                } else {
                    this.disconnect();
                }
            });
        }

        // Audio controls
        const micToggle = document.getElementById(this.getId('mic-toggle'));
        if (micToggle) {
            micToggle.addEventListener('change', (e) => {
                this.toggleMicrophone(e.target.checked);
            });
        }

        const speakerToggle = document.getElementById(this.getId('speaker-toggle'));
        if (speakerToggle) {
            speakerToggle.addEventListener('change', (e) => {
                this.toggleSpeaker(e.target.checked);
            });
        }

        // Chat controls - only attach if form controls are enabled
        if (this.config.showFormControls) {
            const sendBtn = document.getElementById(this.getId('send-btn'));
            if (sendBtn) {
                sendBtn.addEventListener('click', () => {
                    this.sendMessage();
                });
            }

            const messageInput = document.getElementById(this.getId('message-input'));
            if (messageInput) {
                messageInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
            }

            // Add VAD update handler
            const updateVadBtn = document.getElementById(this.getId('update-vad-btn'));
            if (updateVadBtn) {
                updateVadBtn.addEventListener('click', () => {
                    const vadMode = document.getElementById(this.getId('vad-mode')).value;
                    this.sendSystemUpdate('vad_mode', vadMode);
                });
            }

            // Add create response handler
            const createResponseBtn = document.getElementById(this.getId('create-response-btn'));
            if (createResponseBtn) {
                createResponseBtn.addEventListener('click', () => {
                    this.createResponse();
                });
            }
        }

        // Debug area event listeners
        const debugFilter = document.getElementById('richbot-debug-filter');
        const clearDebugFilter = document.getElementById('richbot-clear-debug-filter');

        if (debugFilter) {
            debugFilter.addEventListener('input', (e) => {
                this.debugFilter = e.target.value;
                this.updateDebugDisplay();
            });
        }

        if (clearDebugFilter) {
            clearDebugFilter.addEventListener('click', () => {
                this.debugFilter = '';
                debugFilter.value = '';
                this.updateDebugDisplay();
            });
        }
    }

    // WebSocket Methods
    async connect() {
        if (this.isConnected) {
            this.disconnect();
            return;
        }

        this.updateConnectionStatus('connecting');
        const wsUrl = `${this.config.wsUrl}/${this.config.assistantId || ''}?token=${this.config.apiToken}`;
        
        try {
            this.socket = new WebSocket(wsUrl);
            
            this.socket.onopen = () => {
                console.log('WebSocket connection established');
                this.updateConnectionStatus('connected');
                this.emit('connected');
                
                if (this.config.autoStartRecording) {
                    setTimeout(() => this.startRecording(), 500);
                }
            };

            this.socket.onmessage = (event) => {
                this.handleSocketMessage(event);
            };

            this.socket.onclose = () => {
                this.isConnected = false;
                this.updateConnectionStatus('disconnected');
                this.stopRecording();
                this.emit('disconnected');
            };

            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.updateConnectionStatus('error');
                this.emit('error', error);
            };
        } catch (error) {
            console.error('Error connecting to WebSocket:', error);
            this.updateConnectionStatus('error');
            this.emit('error', error);
        }
    }

    disconnect() {
        if (this.socket) {
            this.socket.close();
        }
        this.stopRecording();
        this.updateConnectionStatus('disconnected');
    }

    // Room Management Methods
    joinRoom(roomId) {
        if (!this.isConnected) {
            this.addMessage('Not connected to server', 'system');
            return;
        }

        this.socket.send(JSON.stringify({
            type: 'join',
            room: roomId
        }));
    }

    leaveRoom() {
        if (this.currentRoom) {
            this.socket.send(JSON.stringify({
                type: 'leave',
                room: this.currentRoom
            }));
            this.currentRoom = null;
            this.roomMembers.clear();
            this.updateRoomStatus();
        }
    }

    updateRoomStatus() {
        const roomBadge = document.getElementById(this.getId('room-badge'));
        const roomName = document.getElementById(this.getId('room-name'));
        const participantsSection = document.getElementById(this.getId('participants-section'));

        if (this.currentRoom) {
            roomBadge.style.display = 'inline';
            roomName.textContent = this.currentRoom;
            participantsSection.style.display = 'block';
            this.updateParticipantsGrid();
        } else {
            roomBadge.style.display = 'none';
            roomName.textContent = '-';
            participantsSection.style.display = 'none';
        }
    }

    updateParticipantsGrid() {
        const grid = document.getElementById(this.getId('participants-grid'));
        grid.innerHTML = '';

        this.roomMembers.forEach((member, fd) => {
            const card = this.createParticipantCard(member);
            grid.appendChild(card);
        });
    }

    createParticipantCard(member) {
        const card = document.createElement('div');
        card.className = 'participant-card';
        card.setAttribute('data-fd', member.fd);

        let icon, badgeColor;
        switch(member.type) {
            case 'openai':
                icon = 'fa-robot';
                badgeColor = 'bg-primary';
                break;
            case 'twilio':
                icon = 'fa-phone';
                badgeColor = 'bg-success';
                break;
            default:
                icon = 'fa-user';
                badgeColor = 'bg-info';
        }

        card.innerHTML = `
            <div class="participant-avatar">
                <i class="fas ${icon}"></i>
            </div>
            <div class="participant-info">
                <h6 class="mb-1">${member.name || member.fd}</h6>
                <span class="badge ${badgeColor}">${member.type}</span>
                ${member.assistant ? `<small class="d-block text-muted">Assistant ID: ${member.assistant}</small>` : ''}
            </div>
        `;

        return card;
    }

    // Audio Methods
    async initializeAudioContext() {
        if (this.audioContextInitialized) {
            return;
        }

        try {
            // Create AudioContext
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Resume if suspended
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }

            // Create gain node for volume control
            this.audioGainNode = this.audioContext.createGain();
            this.audioGainNode.gain.value = this.config.initialVolume;
            this.audioGainNode.connect(this.audioContext.destination);

            this.audioContextInitialized = true;
            console.log('AudioContext initialized:', {
                sampleRate: this.audioContext.sampleRate,
                state: this.audioContext.state
            });
        } catch (error) {
            console.error('Failed to initialize AudioContext:', error);
            throw error;
        }
    }

    async startRecording() {
        if (this.isRecording || this.isMicMuted) {
            console.log('Recording blocked:', {
                isRecording: this.isRecording,
                isMicMuted: this.isMicMuted
            });
            return;
        }

        try {
            // Ensure AudioContext is initialized
            await this.initializeAudioContext();

            // Get microphone stream
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                } 
            });

            // Create audio processing nodes
            const source = this.audioContext.createMediaStreamSource(stream);
            const targetSampleRate = 24000;
            console.log('Audio processing setup:', {
                sourceSampleRate: this.audioContext.sampleRate,
                targetSampleRate: targetSampleRate,
                needsResampling: this.audioContext.sampleRate !== targetSampleRate
            });

            const resamplingLength = Math.round(2048 * targetSampleRate / this.audioContext.sampleRate);
            const processor = this.audioContext.createScriptProcessor(2048, 1, 1);
            
            // Connect nodes
            source.connect(processor);
            processor.connect(this.audioContext.destination);
            
            processor.onaudioprocess = async (e) => {
                if (!this.isRecording || this.isMicMuted) {
                    return;
                }

                const inputData = e.inputBuffer.getChannelData(0);
                
                let resampledData;
                if (this.audioContext.sampleRate !== targetSampleRate) {
                    // Resample audio to target sample rate
                    const offlineCtx = new OfflineAudioContext(1, resamplingLength, targetSampleRate);
                    const bufferSource = offlineCtx.createBufferSource();
                    const tempBuffer = this.audioContext.createBuffer(1, inputData.length, this.audioContext.sampleRate);
                    tempBuffer.getChannelData(0).set(inputData);
                    bufferSource.buffer = tempBuffer;
                    bufferSource.connect(offlineCtx.destination);
                    bufferSource.start();
                    const renderedBuffer = await offlineCtx.startRendering();
                    resampledData = renderedBuffer.getChannelData(0);
                } else {
                    resampledData = inputData;
                }
                
                // Convert Float32 to PCM16
                const pcm16Data = new Int16Array(resampledData.length);
                for (let i = 0; i < resampledData.length; i++) {
                    const s = Math.max(-1, Math.min(1, resampledData[i]));
                    pcm16Data[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
                }
                
                // Convert to base64
                const base64Audio = btoa(String.fromCharCode(...new Uint8Array(pcm16Data.buffer)));
                
                // Send to WebSocket if connected
                if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                    const audioMessage = {
                        type: 'input_audio_buffer.append',
                        audio: base64Audio,
                        format: {
                            type: 'pcm16',
                            sample_rate: targetSampleRate,
                            channels: 1
                        }
                    };

                    try {
                        this.socket.send(JSON.stringify(audioMessage));
                    } catch (error) {
                        console.error('Error sending audio:', error);
                        this.stopRecording();
                    }
                }
            };
            
            // Store recorder components for cleanup
            this.mediaRecorder = {
                stream,
                processor,
                source,
                stop: () => {
                    try {
                        stream.getTracks().forEach(track => track.stop());
                        processor.disconnect();
                        source.disconnect();
                        console.log('Recording stopped and cleaned up');
                    } catch (error) {
                        console.error('Error cleaning up recorder:', error);
                    }
                }
            };
            
            this.isRecording = true;
            this.updateMicStatus();
            this.emit('recordingStarted');
            console.log('Recording started successfully');
            
        } catch (error) {
            console.error('Error starting recording:', error);
            this.isRecording = false;
            this.updateMicStatus();
            this.emit('error', error);
        }
    }

    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            try {
                // Only send cancel message if we have an active response and we're waiting for it
                if (this.currentResponseId && this.isWaitingForResponse) {
                    const cancelMessage = {
                        type: 'response.cancel',
                        response_id: this.currentResponseId
                    };
                    this.socket.send(JSON.stringify(cancelMessage));
                    console.log('Sending response cancel:', {
                        responseId: this.currentResponseId,
                        isWaiting: this.isWaitingForResponse
                    });
                }
                
                this.mediaRecorder.stop();
                this.mediaRecorder = null;
                this.isRecording = false;
                this.updateMicStatus();
                this.emit('recordingStopped');
                console.log('Recording stopped');
            } catch (error) {
                console.error('Error stopping recording:', error);
                this.emit('error', error);
            }
        }
    }

    toggleMicrophone(enabled) {
        this.isMicMuted = !enabled;
        if (enabled && !this.isRecording) {
            this.startRecording();
        } else if (!enabled && this.isRecording) {
            this.stopRecording();
        }
        this.updateMicStatus();
    }

    toggleSpeaker(enabled) {
        this.isSpeakerMuted = !enabled;
        if (this.audioGainNode) {
            const volume = enabled ? this.config.initialVolume : 0;
            this.audioGainNode.gain.setTargetAtTime(volume, this.audioContext?.currentTime || 0, 0.1);
            console.log('Speaker toggled:', {
                enabled: enabled,
                volume: volume
            });
        }
        this.updateSpeakerStatus();
    }

    updateMicStatus() {
        const micIndicator = document.getElementById(this.getId('mic-indicator'));
        const micToggle = document.getElementById(this.getId('mic-toggle'));
        const micPill = micToggle?.closest('.mic-pill');
        
        if (micIndicator) {
            micIndicator.className = `fas ${!this.isMicMuted ? 'fa-microphone' : 'fa-microphone-slash'} ${!this.isMicMuted ? 'active' : ''}`;
        }
        if (micToggle) {
            micToggle.checked = !this.isMicMuted;
        }
        if (micPill) {
            micPill.classList.toggle('active', !this.isMicMuted);
        }
    }

    updateSpeakerStatus() {
        const speakerIndicator = document.getElementById(this.getId('speaker-indicator'));
        const speakerToggle = document.getElementById(this.getId('speaker-toggle'));
        const speakerPill = speakerToggle?.closest('.speaker-pill');
        
        if (speakerIndicator) {
            speakerIndicator.className = `fas ${!this.isSpeakerMuted ? 'fa-volume-up' : 'fa-volume-xmark'} ${!this.isSpeakerMuted ? 'active' : ''}`;
        }
        if (speakerToggle) {
            speakerToggle.checked = !this.isSpeakerMuted;
        }
        if (speakerPill) {
            speakerPill.classList.toggle('active', !this.isSpeakerMuted);
        }
    }

    convertToPCM16(float32Data) {
        const pcm16Data = new Int16Array(float32Data.length);
        for (let i = 0; i < float32Data.length; i++) {
            const s = Math.max(-1, Math.min(1, float32Data[i]));
            pcm16Data[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
        }
        return pcm16Data;
    }

    // Event handling
    #eventListeners = {};

    on(event, callback) {
        if (!this.#eventListeners[event]) {
            this.#eventListeners[event] = [];
        }
        this.#eventListeners[event].push(callback);
    }

    emit(event, data) {
        if (this.#eventListeners[event]) {
            this.#eventListeners[event].forEach(callback => callback(data));
        }
    }

    // Status Updates
    updateConnectionStatus(status) {
        const statusBadge = document.getElementById(this.getId('connection-status'));
        const connectBtn = document.getElementById(this.getId('connect-btn'));
        const chatSection = document.getElementById(this.getId('chat-section'));
        const roomBadge = document.getElementById(this.getId('room-badge'));
        const participantsSection = document.getElementById(this.getId('participants-section'));
        
        if (!statusBadge || !connectBtn) return;

        // Reset classes
        statusBadge.className = 'badge';
        
        switch(status) {
            case 'connected':
                statusBadge.classList.add('bg-success');
                statusBadge.textContent = 'Connected';
                connectBtn.innerHTML = '<i class="fas fa-plug-circle-xmark"></i> Disconnect';
                connectBtn.classList.remove('btn-light');
                connectBtn.classList.add('btn-outline-light');
                this.isConnected = true;
                if (chatSection) chatSection.style.display = 'block';
                if (roomBadge) roomBadge.style.display = 'inline';
                if (participantsSection) participantsSection.style.display = 'block';
                break;

            case 'disconnected':
                statusBadge.classList.add('bg-danger');
                statusBadge.textContent = 'Disconnected';
                connectBtn.innerHTML = '<i class="fas fa-plug"></i> Connect';
                connectBtn.classList.remove('btn-outline-light');
                connectBtn.classList.add('btn-light');
                this.isConnected = false;
                if (chatSection) chatSection.style.display = 'none';
                if (roomBadge) roomBadge.style.display = 'none';
                if (participantsSection) participantsSection.style.display = 'none';
                break;

            case 'connecting':
                statusBadge.classList.add('bg-warning');
                statusBadge.textContent = 'Connecting...';
                connectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                connectBtn.classList.remove('btn-outline-light');
                connectBtn.classList.add('btn-light');
                if (chatSection) chatSection.style.display = 'none';
                break;

            case 'processing':
                statusBadge.classList.add('bg-info');
                statusBadge.textContent = 'Processing';
                // Keep chat section visible during processing
                if (chatSection) chatSection.style.display = 'block';
                break;

            case 'ready':
                statusBadge.classList.add('bg-success');
                statusBadge.textContent = 'Connected';
                // Keep chat section visible when ready
                if (chatSection) chatSection.style.display = 'block';
                break;

            case 'error':
                statusBadge.classList.add('bg-danger');
                statusBadge.textContent = 'Error';
                connectBtn.innerHTML = '<i class="fas fa-plug"></i> Reconnect';
                connectBtn.classList.remove('btn-outline-light');
                connectBtn.classList.add('btn-light');
                if (chatSection) chatSection.style.display = 'none';
                break;
        }
    }

    // Message Methods
    addMessage(content, type = 'assistant') {
        console.log('Adding message:', { content, type });
        const messagesDiv = document.getElementById(this.getId('messages'));
        if (!messagesDiv) {
            console.error('Messages div not found:', this.getId('messages'));
            return;
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = content;

        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        this.messageCount++;
        const messageCountElement = document.getElementById(this.getId('message-count'));
        if (messageCountElement) {
            messageCountElement.textContent = `${this.messageCount} messages`;
        }

        console.log('Message added:', {
            messageCount: this.messageCount,
            messagesDiv: messagesDiv,
            messageDiv: messageDiv
        });
    }

    sendMessage() {
        const input = document.getElementById(this.getId('message-input'));
        const message = input.value.trim();

        if (!message || !this.isConnected || this.isWaitingForResponse) return;

        const messageData = {
            type: 'message',
            content: message
        };

        this.socket.send(JSON.stringify(messageData));
        this.addDebugMessage(messageData, 'outgoing');

        this.addMessage(message, 'user');
        input.value = '';
        this.isWaitingForResponse = true;
        document.getElementById(this.getId('send-btn')).disabled = true;
    }

    handleSocketMessage(event) {
        try {
            const data = JSON.parse(event.data);
            this.addDebugMessage(data, 'incoming');
            console.log('Received message:', data);

            switch(data.type) {
                case 'session.updated':
                    this.updateSessionInfo(data.session);
                    if (data.session.tools) {
                        this.updateToolsList(data.session.tools);
                    }
                    break;
                    
                case 'start_assistant_chat':
                    // Reset any existing message buffers
                    this.currentMessage = '';
                    this.currentResponseId = null;
                    this.responseBuffers.clear();
                    break;

                case 'joined':
                    this.currentRoom = data.room;
                    if (data.members) {
                        data.members.forEach(member => {
                            this.roomMembers.set(member.fd, member);
                        });
                    }
                    this.updateRoomStatus();
                    // Add system message about joining room
                    this.addMessage(`Joined room: ${data.room}`, 'system');
                    // Add messages for existing members
                    data.members?.forEach(member => {
                        this.addMessage(`${member.name || member.fd} joined`, 'system');
                    });
                    break;

                case 'response.created':
                    this.currentResponseId = data.response_id;
                    this.isWaitingForResponse = true;
                    this.transcriptBuffers.set(data.response_id, {
                        text: '',
                        transcript: '',
                        complete: false
                    });
                    this.updateConnectionStatus('processing');
                    this.updateSendButtonState();
                    break;

                case 'response.audio.delta':
                    if (data.delta && data.response_id === this.currentResponseId && !this.isSpeakerMuted) {
                        console.log('Processing audio delta:', {
                            responseId: data.response_id,
                            dataLength: data.delta.length,
                            isMuted: this.isSpeakerMuted
                        });

                        // Convert base64 to PCM16 audio data
                        const binaryString = atob(data.delta);
                        const bytes = new Uint8Array(binaryString.length);
                        for (let i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }

                        // Convert to Int16Array for PCM16 format
                        const pcm16Data = new Int16Array(bytes.buffer);
                        const float32Data = new Float32Array(pcm16Data.length);
                        
                        // Convert PCM16 to Float32 (-1 to 1 range)
                        for (let i = 0; i < pcm16Data.length; i++) {
                            float32Data[i] = pcm16Data[i] / 32768.0;
                        }

                        if (!this.audioContext) {
                            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                            this.audioGainNode = this.audioContext.createGain();
                            this.audioGainNode.gain.value = this.config.initialVolume;
                            this.audioGainNode.connect(this.audioContext.destination);
                            console.log('Created new AudioContext:', {
                                sampleRate: this.audioContext.sampleRate,
                                state: this.audioContext.state
                            });
                        }

                        // Create audio buffer with correct sample rate (24000Hz for PCM16)
                        const audioBuffer = this.audioContext.createBuffer(1, float32Data.length, 24000);
                        audioBuffer.getChannelData(0).set(float32Data);

                        // Create and configure source
                        const source = this.audioContext.createBufferSource();
                        source.buffer = audioBuffer;

                        // Connect directly to gain node for minimal latency
                        source.connect(this.audioGainNode);

                        // Calculate start time to ensure sequential playback
                        const startTime = Math.max(this.audioContext.currentTime, this.lastPlayTime);
                        source.start(startTime);
                        this.lastPlayTime = startTime + audioBuffer.duration;

                        console.log('Playing audio chunk:', {
                            duration: audioBuffer.duration,
                            startTime: startTime,
                            samplesCount: float32Data.length,
                            volume: this.audioGainNode.gain.value
                        });

                        // Cleanup when done
                        source.onended = () => {
                            source.disconnect();
                            console.log('Audio chunk playback completed');
                        };
                    }
                    break;

                case 'response.text.delta':
                    if (data.response_id && data.delta) {
                        const buffer = this.transcriptBuffers.get(data.response_id);
                        if (buffer) {
                            buffer.text += data.delta;
                            
                            // Update or create text message
                            const messages = document.getElementById(this.getId('messages'));
                            const textId = `text-${data.response_id}`;
                            let textElement = document.getElementById(textId);
                            
                            if (!textElement) {
                                textElement = document.createElement('div');
                                textElement.id = textId;
                                textElement.className = 'message assistant text';
                                messages.appendChild(textElement);
                            }
                            
                            textElement.textContent = buffer.text;
                            messages.scrollTop = messages.scrollHeight;
                        }
                    }
                    break;

                case 'response.audio_transcript.delta':
                    if (data.response_id && data.delta) {
                        const buffer = this.transcriptBuffers.get(data.response_id);
                        if (buffer) {
                            buffer.transcript += data.delta;
                            
                            // Update or create transcript message
                            const messages = document.getElementById(this.getId('messages'));
                            const transcriptId = `transcript-${data.response_id}`;
                            let transcriptElement = document.getElementById(transcriptId);
                            
                            if (!transcriptElement) {
                                transcriptElement = document.createElement('div');
                                transcriptElement.id = transcriptId;
                                transcriptElement.className = 'message assistant transcript';
                                messages.appendChild(transcriptElement);
                            }
                            
                            transcriptElement.textContent = buffer.transcript;
                            messages.scrollTop = messages.scrollHeight;
                            
                            console.log('Updated transcript:', {
                                responseId: data.response_id,
                                currentLength: buffer.transcript.length,
                                deltaLength: data.delta.length
                            });
                        }
                    }
                    break;

                case 'response.content_part.done':
                    if (data.response_id && data.part) {
                        const buffer = this.transcriptBuffers.get(data.response_id);
                        if (buffer && data.part.transcript) {
                            buffer.transcript = data.part.transcript;
                            
                            // Update final transcript
                            const transcriptId = `transcript-${data.response_id}`;
                            const transcriptElement = document.getElementById(transcriptId);
                            if (transcriptElement) {
                                transcriptElement.textContent = buffer.transcript;
                                transcriptElement.classList.add('complete');
                            }
                            
                            console.log('Content part completed:', {
                                responseId: data.response_id,
                                finalTranscript: buffer.transcript
                            });
                        }
                    }
                    break;

                case 'response.output_item.done':
                    if (data.response_id && data.item) {
                        const buffer = this.transcriptBuffers.get(data.response_id);
                        if (buffer && data.item.content) {
                            // Find the transcript content
                            const transcriptContent = data.item.content.find(c => c.type === 'audio' && c.transcript);
                            if (transcriptContent) {
                                buffer.transcript = transcriptContent.transcript;
                                
                                // Update final transcript
                                const transcriptId = `transcript-${data.response_id}`;
                                const transcriptElement = document.getElementById(transcriptId);
                                if (transcriptElement) {
                                    transcriptElement.textContent = buffer.transcript;
                                    transcriptElement.classList.add('complete');
                                }
                                
                                console.log('Output item completed:', {
                                    responseId: data.response_id,
                                    finalTranscript: buffer.transcript
                                });
                            }
                        }
                    }
                    break;

                case 'response.done':
                    if (data.response.id) {
                        // Clean up buffers
                        const buffer = this.transcriptBuffers.get(data.response.id);
                        if (buffer) {
                            // If we have a complete transcript in the response, use it
                            if (data.response.output && data.response.output[0]?.content) {
                                const transcriptContent = data.response.output[0].content.find(c => c.type === 'audio' && c.transcript);
                                if (transcriptContent) {
                                    buffer.transcript = transcriptContent.transcript;
                                    
                                    // Update final transcript
                                    const transcriptId = `transcript-${data.response.id}`;
                                    const transcriptElement = document.getElementById(transcriptId);
                                    console.log('Response done handling transcript:', {
                                        responseId: data.response.id,
                                        hasTranscriptElement: !!transcriptElement,
                                        handler: 'response.done'
                                    });
                                    
                                    if (transcriptElement) {
                                        transcriptElement.textContent = buffer.transcript;
                                        transcriptElement.classList.add('complete');
                                    } else {
                                        // If no transcript element exists, create a new message
                                        this.addMessage(buffer.transcript, 'assistant');
                                    }
                                }
                            }
                            
                            console.log('Response completed:', {
                                responseId: data.response.id,
                                hadTranscript: buffer.transcript.length > 0,
                                hadText: buffer.text.length > 0
                            });
                            this.transcriptBuffers.delete(data.response.id);
                        }
                        
                        if (this.currentResponseId === data.response.id) {
                            this.currentResponseId = null;
                            this.isWaitingForResponse = false;
                        }
                    }
                    
                    this.updateConnectionStatus('ready');
                    this.updateSendButtonState();
                    break;

                case 'assistant_chat_started':
                    // Assistant chat started
                    this.currentRoom = data.room;
                    document.getElementById(this.getId('room-status')).style.display = 'block';
                    document.getElementById(this.getId('chat-section')).style.display = 'block';
                    this.addMessage('Assistant chat started', 'system');
                    break;

                case 'error':
                    if (data.error && data.error.message === 'Cancellation failed: no active response found') {
                        console.log('Response already completed or not found');
                        this.currentResponseId = null;
                        this.isWaitingForResponse = false;
                        this.updateConnectionStatus('ready');
                        this.updateSendButtonState();
                    } else {
                        console.error('Server error:', data.error);
                        this.addMessage(`Error: ${data.error.message}`, 'system');
                    }
                    break;

                case 'rate_limits.updated':
                    // Rate limits update
                    console.log('Rate limits updated:', data.rate_limits);
                    break;

                case 'response.output_item.added':
                    // New output item
                    if (this.currentResponseId && this.currentResponseId !== data.response_id) {
                        this.clearCurrentResponse();
                    }
                    this.currentResponseId = data.response_id;
                    break;

                case 'response.function_call_arguments.delta':
                    // Function call argument updates
                    if (data.response_id === this.currentResponseId) {
                        if (!this.currentToolCall) {
                            this.currentToolCall = {
                                name: data.name || 'Unknown Tool',
                                arguments: data.delta || ''
                            };
                        } else {
                            this.currentToolCall.arguments += data.delta || '';
                        }
                        this.showToolStatus(this.currentToolCall.name);
                    }
                    break;

                case 'response.function_call_arguments.done':
                    // Function call complete
                    if (data.response_id === this.currentResponseId) {
                        const toolName = data.name || (this.currentToolCall ? this.currentToolCall.name : 'Unknown Tool');
                        this.addMessage(`Using tool: ${toolName}`, 'system');
                    }
                    break;

                case 'user_joined':
                    // New user joined
                    if (!this.roomMembers.has(data.user)) {
                        const member = {
                            fd: data.user,
                            type: data.member_type,
                            name: data.member_name,
                            assistant: data.member_assistant
                        };
                        this.roomMembers.set(data.user, member);
                        this.updateParticipantsGrid();
                        // Add system message about new user joining
                        this.addMessage(`${member.name || 'User'} joined`, 'system');
                    }
                    break;

                case 'user_left':
                    // User left
                    const member = this.roomMembers.get(data.user);
                    if (member) {
                        this.roomMembers.delete(data.user);
                        this.updateParticipantsGrid();
                        // Add system message about user leaving
                        this.addMessage(`${member.name || 'User'} left`, 'system');
                    }
                    break;

                case 'left':
                    // Left room
                    this.currentRoom = null;
                    this.roomMembers.clear();
                    this.updateRoomStatus();
                    // Add system message about leaving room
                    this.addMessage('Left room', 'system');
                    break;

                case 'response.audio_transcript.done':
                    if (data.response_id && data.transcript) {
                        console.log('Audio transcript completed:', {
                            responseId: data.response_id,
                            transcript: data.transcript
                        });
                        
                        // Add the transcript to the chat log
                        this.addMessage(data.transcript, 'assistant');
                        
                        // Update the buffer if it exists
                        const buffer = this.transcriptBuffers.get(data.response_id);
                        if (buffer) {
                            buffer.transcript = data.transcript;
                            buffer.complete = true;
                        }
                    }
                    break;

                case 'response.audio.done':
                    // Just log this for now, we don't need to do anything with it
                    console.log('Audio playback completed:', {
                        responseId: data.response_id,
                        itemId: data.item_id
                    });
                    break;

                default:
                    console.log('Unhandled message type:', data.type);
            }
        } catch (error) {
            console.error('Error handling message:', error);
            this.emit('error', error);
        }
    }

    clearCurrentResponse() {
        this.currentResponseId = null;
        this.currentToolCall = null;
        this.audioQueue = [];
        if (this.audioSourceNode) {
            this.audioSourceNode.stop();
            this.audioSourceNode.disconnect();
            this.audioSourceNode = null;
        }
        this.isPlaying = false;
        this.lastPlayTime = 0;
        document.getElementById(this.getId('tool-status')).style.display = 'none';
    }

    showToolStatus(toolName) {
        const toolStatus = document.getElementById(this.getId('tool-status'));
        if (toolStatus) {
            toolStatus.style.display = 'block';
            toolStatus.classList.add('active');
            toolStatus.querySelector('.tool-name').textContent = `Using tool: ${toolName}`;
        }
    }

    async playNextAudioChunk() {
        if (this.audioQueue.length === 0) {
            this.isPlaying = false;
            return;
        }

        this.isPlaying = true;
        const audioData = this.audioQueue.shift();

        try {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                this.audioGainNode = this.audioContext.createGain();
                this.audioGainNode.connect(this.audioContext.destination);
            }

            const binaryString = atob(audioData);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }

            const audioBuffer = await this.audioContext.decodeAudioData(bytes.buffer);
            const source = this.audioContext.createBufferSource();
            source.buffer = audioBuffer;

            const compressor = this.audioContext.createDynamicsCompressor();
            compressor.threshold.value = -24;
            compressor.knee.value = 30;
            compressor.ratio.value = 12;
            compressor.attack.value = 0.003;
            compressor.release.value = 0.25;

            source.connect(compressor);
            compressor.connect(this.audioGainNode);

            const startTime = Math.max(this.audioContext.currentTime, this.lastPlayTime);
            source.start(startTime);
            this.lastPlayTime = startTime + audioBuffer.duration;

            source.onended = () => {
                this.isPlaying = false;
                this.playNextAudioChunk();
            };

        } catch (error) {
            console.error('Error playing audio:', error);
            this.isPlaying = false;
            this.playNextAudioChunk();
        }
    }

    base64ToBuffer(base64) {
        const binaryString = window.atob(base64);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
    }

    updateSessionInfo(session) {
        // Get the info toggle button and add attention animation
        const infoToggle = document.getElementById(this.getId('info-toggle'));
        if (infoToggle) {
            infoToggle.classList.remove('has-update');
            void infoToggle.offsetWidth;
            infoToggle.classList.add('has-update');
            
            setTimeout(() => {
                infoToggle.classList.remove('has-update');
            }, 1000);
        }

        // Update modalities badge
        const modalitiesBadge = document.getElementById(this.getId('modalities-badge'));
        const modalitiesText = document.getElementById(this.getId('modalities-text'));
        if (modalitiesBadge && modalitiesText && session.modalities) {
            modalitiesBadge.style.display = 'inline';
            modalitiesText.textContent = session.modalities.join(', ');
        }

        // Show the session info section
        const sessionInfo = document.getElementById(this.getId('session-info'));
        
        // Update basic info
        document.getElementById(this.getId('model')).textContent = session.model || '-';
        document.getElementById(this.getId('voice')).textContent = session.voice || '-';
        document.getElementById(this.getId('modalities')).textContent = (session.modalities || []).join(', ');
        document.getElementById(this.getId('temperature')).textContent = session.temperature || '-';
        
        // Update audio format info
        document.getElementById(this.getId('input-format')).textContent = session.input_audio_format || '-';
        document.getElementById(this.getId('output-format')).textContent = session.output_audio_format || '-';
        
        // Update turn detection info
        const turnDetectionEl = document.getElementById(this.getId('turn-detection'));
        if (session.turn_detection) {
            turnDetectionEl.innerHTML = Object.entries(session.turn_detection)
                .map(([key, value]) => `<li>${key}: ${value}</li>`)
                .join('');
        }
        
        // Update tools list
        const toolsList = document.getElementById(this.getId('tools-list'));
        if (session.tools && session.tools.length > 0) {
            toolsList.innerHTML = session.tools.map(tool => `
                <div class="tool-item">
                    <div class="tool-name">${tool.name}</div>
                    <div class="tool-description">${tool.description}</div>
                    <div class="tool-params">
                        Required params: ${tool.parameters.required.join(', ')}
                    </div>
                </div>
            `).join('');
        } else {
            toolsList.innerHTML = '<p class="text-muted">No tools available</p>';
        }
    }

    updateSendButtonState() {
        const sendBtn = document.getElementById(this.getId('send-btn'));
        if (sendBtn) {
            sendBtn.disabled = this.isWaitingForResponse;
        }
    }

    addDebugMessage(message, direction = 'incoming') {
        const timestamp = new Date().toISOString();
        const debugMessage = {
            timestamp,
            direction,
            message: typeof message === 'string' ? message : JSON.stringify(message, null, 2),
            type: typeof message === 'string' ? 'text' : (message.type || 'unknown')
        };

        this.debugMessages.push(debugMessage);
        this.debugMessageCount++;
        this.updateDebugDisplay();
    }

    updateDebugDisplay() {
        const debugMessagesDiv = document.getElementById('richbot-debug-messages');
        const debugMessageCount = document.getElementById('richbot-debug-message-count');
        if (!debugMessagesDiv || !debugMessageCount) {
            console.warn('Debug elements not found:', { debugMessagesDiv, debugMessageCount });
            return;
        }

        // Update message count
        debugMessageCount.textContent = `${this.debugMessageCount} messages`;

        // Filter messages if filter is set
        const filteredMessages = this.debugFilter
            ? this.debugMessages.filter(msg => 
                msg.message.toLowerCase().includes(this.debugFilter.toLowerCase()) ||
                msg.type.toLowerCase().includes(this.debugFilter.toLowerCase()))
            : this.debugMessages;

        // Create message elements
        debugMessagesDiv.innerHTML = filteredMessages.map((msg, index) => `
            <div class="debug-message mb-1 border rounded ${msg.direction === 'incoming' ? 'bg-light' : 'bg-info bg-opacity-10'}">
                <div class="d-flex justify-content-between align-items-center p-2" 
                     style="cursor: pointer; min-height: 32px;" 
                     onclick="document.getElementById('debug-details-${index}').classList.toggle('d-none')">
                    <div class="d-flex align-items-center flex-grow-1 pe-3">
                        <span class="badge ${msg.direction === 'incoming' ? 'bg-primary' : 'bg-success'} me-2">${msg.direction}</span>
                        <span class="badge bg-secondary me-2">${msg.type}</span>
                        <small class="text-muted">${new Date(msg.timestamp).toLocaleTimeString()}</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div id="debug-details-${index}" class="d-none p-2 border-top">
                    <div class="small text-muted mb-1">${msg.timestamp}</div>
                    <pre class="mt-1 mb-0" style="max-height: 200px; overflow-y: auto;">${msg.message}</pre>
                </div>
            </div>
        `).join('');

        // Scroll to bottom
        debugMessagesDiv.scrollTop = debugMessagesDiv.scrollHeight;
    }

    updateToolsList(tools) {
        this.availableTools = tools;
        const toolsContainer = document.getElementById('richbot-available-tools');
        if (!toolsContainer) return;

        toolsContainer.innerHTML = tools.map(tool => `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${tool.name}</h6>
                        <p class="mb-1 small">${tool.description}</p>
                    </div>
                    <span class="badge bg-primary">${tool.type}</span>
                </div>
                ${tool.parameters ? `
                    <div class="mt-2">
                        <small class="text-muted">Parameters:</small>
                        <ul class="list-unstyled mb-0">
                            ${Object.entries(tool.parameters.properties).map(([name, param]) => `
                                <li class="small">
                                    <strong>${name}</strong>: ${param.description}
                                    ${tool.parameters.required?.includes(name) ? 
                                        '<span class="badge bg-danger ms-1">required</span>' : ''}
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    sendSystemUpdate(key, value) {
        if (!this.isConnected) {
            this.addMessage('Not connected to server', 'system');
            return;
        }

        // Format the message based on the key
        let message;
        switch(key) {
            case 'vad_mode':
                // Match the turn detection presets from OpenAISessionMaker
                const turnDetectionConfig = {
                    'server': {
                        type: 'server_vad',
                        prefix_padding_ms: 300,
                        silence_duration_ms: 500,
                        threshold: 0.5,
                        create_response: true,
                        interrupt_response: true
                    },
                    'semantic': {
                        type: 'semantic_vad',
                        create_response: true,
                        interrupt_response: true,
                        eagerness: 'auto'
                    },
                    'none': null
                };

                message = {
                    type: 'session.update',
                    event_id: 'vad_' + Date.now(),
                    session: {
                        turn_detection: turnDetectionConfig[value] || turnDetectionConfig['server']
                    }
                };
                break;

            case 'temperature':
                message = {
                    type: 'session.update',
                    event_id: 'temp_' + Date.now(),
                    session: {
                        temperature: parseFloat(value)
                    }
                };
                break;

            case 'voice':
                message = {
                    type: 'session.update',
                    event_id: 'voice_' + Date.now(),
                    session: {
                        voice: value
                    }
                };
                break;

            case 'modalities':
                message = {
                    type: 'session.update',
                    event_id: 'mod_' + Date.now(),
                    session: {
                        modalities: Array.isArray(value) ? value : [value]
                    }
                };
                break;

            default:
                message = {
                    type: 'session.update',
                    event_id: 'update_' + Date.now(),
                    session: {
                        [key]: value
                    }
                };
        }

        this.socket.send(JSON.stringify(message));
        this.addDebugMessage(message, 'outgoing');
        this.addMessage(`System update: ${key} = ${value}`, 'system');
    }

    createResponse() {
        if (!this.isConnected) {
            this.addMessage('Not connected to server', 'system');
            return;
        }

        const message = {
            type: 'response.create'
        };

        this.socket.send(JSON.stringify(message));
        this.addDebugMessage(message, 'outgoing');
        this.addMessage('Creating new response...', 'system');
    }
}

// Export for both ES modules and CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RichbotClient;
} else if (typeof define === 'function' && define.amd) {
    define([], function() { return RichbotClient; });
} else {
    window.RichbotClient = RichbotClient;
} 