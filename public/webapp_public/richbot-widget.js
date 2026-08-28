class RichbotWidget {
    constructor(targetElementId, config = {}) {
        this.config = {
            wsUrl: config.wsUrl || (window.appConfig?.wsUrlAlt ? `${window.appConfig.wsUrlAlt}/webclient/42` : 'wss://richbot9000.com:9502/webclient/42'),
            apiToken: config.apiToken || '',
            assistantId: config.assistantId || '42',
            autoConnect: config.autoConnect || false,
            autoStartRecording: config.autoStartRecording || true,
            initialVolume: config.initialVolume || 1.0,
            formContent: config.formContent || '',
            showFormControls: config.showFormControls || true,
            showChatLog: config.showChatLog || true,
            ...config
        };

        this.targetElement = document.getElementById(targetElementId);
        if (!this.targetElement) {
            throw new Error(`Target element with id "${targetElementId}" not found`);
        }

        this.socket = null;
        this.mediaRecorder = null;
        this.isRecording = false;
        this.messageCount = 0;
        this.audioContext = null;
        this.audioQueue = [];
        this.isPlaying = false;
        this.lastPlayTime = 0;
        this.audioGainNode = null;
        this.isWaitingForResponse = false;
        this.isConnected = false;
        this.currentMessage = '';
        this.currentTranscript = '';
        this.currentToolCall = null;
        this.currentResponseId = null;
        this.audioSourceNode = null;
        this.sendButton = null;

        this.initialize();
    }

    initialize() {
        // Create widget HTML structure
        this.createWidgetStructure();
        this.attachEventListeners();
        
        if (this.config.autoConnect) {
            this.connect();
        }
    }

    createWidgetStructure() {
        const html = `
            <div class="richbot-widget">
                ${this.config.showFormControls ? `
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Easy AI Form Maker</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="richbot-audio-controls d-flex align-items-center gap-2">
                                        <button class="btn btn-sm btn-light" id="${this.getId('mic-toggle')}" title="Toggle Microphone">
                                            <i class="fas fa-microphone"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light" id="${this.getId('speaker-toggle')}" title="Toggle Speaker">
                                            <i class="fas fa-volume-up"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light" id="${this.getId('skip-audio')}" title="Skip Audio" style="display: none;">
                                            <i class="fas fa-forward"></i>
                                        </button>
                                        <div class="form-check form-check-inline text-light mb-0">
                                            <input class="form-check-input" type="checkbox" id="${this.getId('mute-audio')}">
                                            <label class="form-check-label" for="${this.getId('mute-audio')}">Mute</label>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary me-2" id="${this.getId('connection-badge')}">Disconnected</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="${this.getId('chat-log')}" class="chat-section mb-3">
                                <div id="${this.getId('messages')}" class="chat-messages">
                                    <!-- Messages will appear here -->
                                </div>
                            </div>
                            <div class="input-section">
                                <div class="input-group">
                                    <textarea class="form-control" id="${this.getId('instructions')}" rows="2" 
                                        placeholder="Enter your message..."></textarea>
                                    <button class="btn btn-primary" id="${this.getId('send-button')}" title="Send Message">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="${this.getId('tool-status')}" class="tool-status" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tools me-2"></i>
                                        <span class="tool-name"></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary" id="${this.getId('message-count')}">0 messages</span>
                                    <button class="btn btn-sm btn-outline-primary" id="${this.getId('chat-toggle')}">
                                        <i class="fas fa-comments" id="${this.getId('chat-toggle-icon')}"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        this.targetElement.innerHTML = html;
        this.addStyles();
        this.sendButton = document.getElementById(this.getId('send-button'));
        this.updateSendButtonState();
    }

    addStyles() {
        const styleId = 'richbot-widget-styles';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                ${this.getExistingStyles()}
                
                .chat-section {
                    max-height: 300px;
                    overflow-y: auto;
                }
                
                .chat-messages {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                
                .chat-message {
                    padding: 8px 12px;
                    border-radius: 8px;
                    max-width: 80%;
                    word-break: break-word;
                }
                
                .chat-message.user {
                    background-color: #e3f2fd;
                    margin-left: auto;
                }
                
                .chat-message.assistant {
                    background-color: #f5f5f5;
                    margin-right: auto;
                }
                
                .chat-message.system {
                    background-color: #fff3cd;
                    margin: 0 auto;
                    text-align: center;
                    font-style: italic;
                }
                
                .tool-status {
                    padding: 4px 8px;
                    border-radius: 4px;
                    background-color: #e8f5e9;
                    border: 1px solid #c8e6c9;
                    transition: all 0.3s ease;
                    font-size: 0.875rem;
                }
                
                .tool-status.active {
                    background-color: #4caf50;
                    color: white;
                    animation: toolPulse 2s infinite;
                }
                
                @keyframes toolPulse {
                    0% { background-color: #4caf50; }
                    50% { background-color: #2e7d32; }
                    100% { background-color: #4caf50; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    getExistingStyles() {
        return `
            .richbot-widget {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            .richbot-controls {
                padding: 10px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .richbot-audio-controls {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            #${this.getId('mic-toggle')}.recording {
                color: #dc3545;
                animation: richbotPulse 1s infinite;
            }
            #${this.getId('speaker-toggle')}.muted {
                color: #dc3545;
            }
            .chat-messages {
                max-height: 300px;
                overflow-y: auto;
            }
            .chat-message {
                margin-bottom: 10px;
                padding: 8px 12px;
                border-radius: 8px;
                max-width: 80%;
            }
            .chat-message.user {
                background-color: #e3f2fd;
                margin-left: auto;
            }
            .chat-message.assistant {
                background-color: #f5f5f5;
                margin-right: auto;
            }
            .chat-message.system {
                background-color: #fff3cd;
                margin: 0 auto;
                text-align: center;
                font-style: italic;
            }
            @keyframes richbotPulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        `;
    }

    getId(elementName) {
        return `richbot-${this.targetElement.id}-${elementName}`;
    }

    attachEventListeners() {
        // Audio controls
        document.getElementById(this.getId('mic-toggle'))?.addEventListener('click', () => {
            if (!this.isRecording) {
                this.startRecording();
            } else {
                this.stopRecording();
            }
        });

        document.getElementById(this.getId('mute-audio'))?.addEventListener('change', (e) => {
            const speakerToggle = document.getElementById(this.getId('speaker-toggle'));
            if (e.target.checked) {
                speakerToggle.classList.add('muted');
                speakerToggle.innerHTML = '<i class="fas fa-volume-mute"></i>';
                this.setVolume(0);
            } else {
                speakerToggle.classList.remove('muted');
                speakerToggle.innerHTML = '<i class="fas fa-volume-up"></i>';
                this.setVolume(1.0);
            }
        });

        // Form controls
        document.getElementById(this.getId('send-button'))?.addEventListener('click', () => {
            this.sendMessage();
        });

        document.getElementById(this.getId('chat-toggle'))?.addEventListener('click', () => {
            this.toggleChat();
        });

        // Handle enter key in textarea
        document.getElementById(this.getId('instructions'))?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Add skip button event listener
        document.getElementById(this.getId('skip-audio'))?.addEventListener('click', () => {
            this.skipCurrentAudio();
        });
    }

    updateSendButtonState() {
        if (this.sendButton) {
            this.sendButton.disabled = this.isWaitingForResponse;
            this.sendButton.innerHTML = this.isWaitingForResponse ? 
                '<i class="fas fa-spinner fa-spin"></i>' : 
                '<i class="fas fa-paper-plane"></i>';
        }
    }

    sendMessage() {
        if (!this.isConnected || this.isWaitingForResponse) {
            // If not connected, try to connect first
            if (!this.isConnected) {
                this.connect();
                return;
            }
            // If waiting for response, show feedback
            this.addMessage("Please wait for the current response to complete.", "system");
            return;
        }

        const instructions = document.getElementById(this.getId('instructions'))?.value;
        if (!instructions) {
            return;
        }

        const message = {
            type: 'message',
            content: instructions
        };

        this.addMessage(instructions, 'user');
        this.socket.send(JSON.stringify(message));
        this.updateMessageCount();
        document.getElementById(this.getId('instructions')).value = '';
        this.emit('messageSent', message);
    }

    toggleChat() {
        const chatSection = document.getElementById(this.getId('chat-log'));
        const toggleIcon = document.getElementById(this.getId('chat-toggle-icon'));
        
        if (chatSection && toggleIcon) {
            if (chatSection.style.display === 'none') {
                chatSection.style.display = 'block';
                toggleIcon.classList.remove('fa-comments');
                toggleIcon.classList.add('fa-comments-slash');
            } else {
                chatSection.style.display = 'none';
                toggleIcon.classList.remove('fa-comments-slash');
                toggleIcon.classList.add('fa-comments');
            }
        }
    }

    addMessage(content, type = 'assistant') {
        const messagesDiv = document.getElementById(this.getId('messages'));
        if (messagesDiv) {
            // For text deltas, try to append to the last message if it's from the assistant
            if (type === 'assistant' && content.length < 100) {
                const lastMessage = messagesDiv.lastElementChild;
                if (lastMessage && lastMessage.classList.contains('chat-message') && 
                    lastMessage.classList.contains('assistant')) {
                    lastMessage.textContent += content;
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    return;
                }
            }

            // Otherwise create a new message
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${type}`;
            
            // If the content is markdown (for transcripts), render it
            if (content.includes('**') || content.includes('#') || content.includes('\n')) {
                messageDiv.innerHTML = this.markdownToHtml(content);
            } else {
                messageDiv.textContent = content;
            }
            
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        this.emit('messageAdded', { content, type });
    }

    markdownToHtml(markdown) {
        // Basic markdown conversion
        return markdown
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold
            .replace(/\n\n/g, '<br><br>') // Double line breaks
            .replace(/\n/g, '<br>') // Single line breaks
            .replace(/\d\. /g, '<br>$&'); // Numbered lists
    }

    updateConnectionStatus(status) {
        const badge = document.getElementById(this.getId('connection-badge'));
        const micButton = document.getElementById(this.getId('mic-toggle'));
        
        if (!badge) return;
        
        badge.className = 'badge me-2';
        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        
        switch(status) {
            case 'connected':
                badge.classList.add('bg-success');
                this.isConnected = true;
                if (this.config.autoStartRecording) {
                    this.startRecording();
                }
                break;
            case 'disconnected':
                badge.classList.add('bg-danger');
                this.isConnected = false;
                this.isWaitingForResponse = false;
                if (micButton) {
                    micButton.classList.remove('recording');
                }
                this.updateSendButtonState();
                break;
            case 'connecting':
                badge.classList.add('bg-warning');
                break;
            case 'processing':
                badge.classList.add('bg-info');
                badge.textContent = 'Processing';
                if (micButton) {
                    micButton.classList.add('recording');
                }
                break;
            case 'ready':
                badge.classList.add('bg-success');
                badge.textContent = 'Ready';
                this.isWaitingForResponse = false;
                if (micButton) {
                    micButton.classList.remove('recording');
                }
                this.updateSendButtonState();
                break;
        }
    }

    updateMessageCount() {
        this.messageCount++;
        document.getElementById(this.getId('message-count')).textContent = this.messageCount + ' messages';
    }

    async connect() {
        this.updateConnectionStatus('connecting');
        const wsUrl = `${this.config.wsUrl}?token=${this.config.apiToken}`;
        
        try {
            this.socket = new WebSocket(wsUrl);
            
            this.socket.onopen = () => {
                console.log('WebSocket connection established');
                this.updateConnectionStatus('connected');
                this.emit('connected');
                
                if (this.config.autoStartRecording) {
                    setTimeout(() => {
                        this.startRecording();
                    }, 500);
                }
            };

            this.socket.onmessage = (event) => {
                this.handleSocketMessage(event);
            };
            
            this.socket.onclose = () => {
                console.log('WebSocket connection closed');
                this.updateConnectionStatus('disconnected');
                this.stopRecording();
                this.emit('disconnected');
            };

            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.updateConnectionStatus('disconnected');
                this.stopRecording();
                this.emit('error', error);
            };
        } catch (error) {
            console.error('Error connecting to WebSocket:', error);
            this.updateConnectionStatus('disconnected');
            this.emit('error', error);
        }
    }

    handleSocketMessage(event) {
        try {
            const message = JSON.parse(event.data);
            console.log('Received message:', message);
            this.updateMessageCount();
            this.emit('message', message);

            switch (message.type) {
                case 'response.output_item.added':
                    if (this.currentResponseId && this.currentResponseId !== message.response_id) {
                        this.truncateAudioAndReset();
                    }
                    this.currentResponseId = message.response_id;
                    this.isWaitingForResponse = true;
                    this.updateConnectionStatus('processing');
                    this.updateSendButtonState();
                    break;

                case 'response.created':
                    this.isWaitingForResponse = true;
                    this.updateConnectionStatus('processing');
                    this.updateSendButtonState();
                    break;

                case 'response.function_call_arguments.delta':
                    if (!this.currentResponseId || message.response_id === this.currentResponseId) {
                        if (!this.currentToolCall) {
                            this.currentToolCall = {
                                name: message.name || 'Unknown Tool',
                                arguments: message.delta || ''
                            };
                        } else {
                            this.currentToolCall.arguments += message.delta || '';
                        }
                        this.showToolStatus(this.currentToolCall.name);
                    }
                    break;

                case 'response.function_call_arguments.done':
                    if (!this.currentResponseId || message.response_id === this.currentResponseId) {
                        const toolName = message.name || (this.currentToolCall ? this.currentToolCall.name : 'Unknown Tool');
                        this.addMessage(`Using tool: ${toolName}`, 'system');
                        this.showToolStatus(toolName);
                    }
                    break;

                case 'response.done':
                    // Always update status on response.done, regardless of response_id
                    this.isWaitingForResponse = false;
                    this.currentResponseId = null;
                    this.currentToolCall = null;  // Reset tool call here
                    this.updateConnectionStatus('ready');
                    this.hideToolStatus();
                    this.updateSendButtonState();
                    break;

                case 'response.audio_transcript.done':
                    if ((!this.currentResponseId || message.response_id === this.currentResponseId) && 
                        message.transcript) {
                        this.addMessage(message.transcript, 'assistant');
                    }
                    break;

                case 'response.text.delta':
                    if (message.delta && (!this.currentResponseId || message.response_id === this.currentResponseId)) {
                        this.currentMessage += message.delta;
                        this.emit('textDelta', message.delta);
                        this.addMessage(message.delta, 'assistant');
                    }
                    break;

                case 'response.output_item.done':
                    if ((!this.currentResponseId || message.response_id === this.currentResponseId) && 
                        message.item && message.item.type === 'text') {
                        this.addMessage(message.item.text, 'assistant');
                    }
                    break;

                case 'response.audio.delta':
                    if (message.delta && (!this.currentResponseId || message.response_id === this.currentResponseId)) {
                        if (!document.getElementById(this.getId('mute-audio')).checked) {
                            this.audioQueue.push(message.delta);
                            if (!this.isPlaying) {
                                this.playNextAudioChunk();
                            }
                        }
                    }
                    break;

                case 'error':
                    console.error('Server error:', message.error);
                    if (message.error.code === 'conversation_already_has_active_response') {
                        this.addMessage("Please wait for the current response to complete before sending a new message.", "system");
                    } else {
                        this.addMessage(`Error: ${message.error.message}`, 'system');
                    }
                    this.emit('error', new Error(message.error.message));
                    break;
            }
        } catch (error) {
            console.error('Error handling message:', error);
            this.emit('error', error);
        }
    }

    truncateAudioAndReset() {
        this.audioQueue = [];
        
        if (this.audioSourceNode) {
            this.audioSourceNode.stop();
            this.audioSourceNode.disconnect();
            this.audioSourceNode = null;
        }
        
        this.isPlaying = false;
        this.lastPlayTime = 0;
        
        // Hide skip button when audio is truncated
        const skipButton = document.getElementById(this.getId('skip-audio'));
        if (skipButton) {
            skipButton.style.display = 'none';
        }
        
        this.currentMessage = '';
        this.currentToolCall = null;
        this.hideToolStatus();
    }

    async startRecording() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            console.log('Source AudioContext sample rate:', audioContext.sampleRate);

            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                } 
            });

            const source = audioContext.createMediaStreamSource(stream);
            
            const targetSampleRate = 24000;
            const resamplingLength = Math.round(2048 * targetSampleRate / audioContext.sampleRate);
            const processor = audioContext.createScriptProcessor(2048, 1, 1);
            
            source.connect(processor);
            processor.connect(audioContext.destination);
            
            processor.onaudioprocess = async (e) => {
                const inputData = e.inputBuffer.getChannelData(0);
                
                let resampledData;
                if (audioContext.sampleRate !== targetSampleRate) {
                    const offlineCtx = new OfflineAudioContext(1, resamplingLength, targetSampleRate);
                    const bufferSource = offlineCtx.createBufferSource();
                    const tempBuffer = audioContext.createBuffer(1, inputData.length, audioContext.sampleRate);
                    tempBuffer.getChannelData(0).set(inputData);
                    bufferSource.buffer = tempBuffer;
                    bufferSource.connect(offlineCtx.destination);
                    bufferSource.start();
                    const renderedBuffer = await offlineCtx.startRendering();
                    resampledData = renderedBuffer.getChannelData(0);
                } else {
                    resampledData = inputData;
                }
                
                const pcm16Data = new Int16Array(resampledData.length);
                for (let i = 0; i < resampledData.length; i++) {
                    pcm16Data[i] = Math.max(-32768, Math.min(32767, Math.round(resampledData[i] * 32767)));
                }
                
                const dataView = new DataView(new ArrayBuffer(pcm16Data.length * 2));
                pcm16Data.forEach((sample, i) => {
                    dataView.setInt16(i * 2, sample, true);
                });
                
                const base64Audio = btoa(String.fromCharCode(...new Uint8Array(dataView.buffer)));
                
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
                    this.socket.send(JSON.stringify(audioMessage));
                }
            };
            
            this.mediaRecorder = {
                stream,
                processor,
                audioContext,
                stop: () => {
                    stream.getTracks().forEach(track => track.stop());
                    processor.disconnect();
                    audioContext.close();
                }
            };
            
            this.isRecording = true;
            document.getElementById(this.getId('mic-toggle')).classList.add('recording');
            this.emit('recordingStarted');
            
        } catch (error) {
            console.error('Error starting recording:', error);
            this.emit('error', error);
        }
    }

    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            document.getElementById(this.getId('mic-toggle')).classList.remove('recording');
            this.emit('recordingStopped');
        }
    }

    async playNextAudioChunk() {
        if (this.audioQueue.length === 0) {
            this.isPlaying = false;
            // Only hide skip button when we're completely done playing
            if (!this.audioSourceNode || this.audioSourceNode.playbackState === 'finished') {
                const skipButton = document.getElementById(this.getId('skip-audio'));
                if (skipButton) {
                    skipButton.style.display = 'none';
                }
            }
            return;
        }

        this.isPlaying = true;
        const audioData = this.audioQueue.shift();
        await this.playAudio(audioData);
        
        setTimeout(() => {
            this.playNextAudioChunk();
        }, 50);
    }

    async playAudio(audioData) {
        // Show skip button when audio starts playing
        const skipButton = document.getElementById(this.getId('skip-audio'));
        if (skipButton) {
            skipButton.style.display = 'inline-block';
        }

        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.audioGainNode = this.audioContext.createGain();
            this.audioGainNode.connect(this.audioContext.destination);
        }

        if (this.audioContext.state === 'suspended') {
            await this.audioContext.resume();
        }

        try {
            const binaryString = atob(audioData);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }

            const sampleRate = 24000;
            const numChannels = 1;
            const frameCount = bytes.length / 2;
            const audioBuffer = this.audioContext.createBuffer(numChannels, frameCount, sampleRate);
            const channelData = audioBuffer.getChannelData(0);

            const dataView = new DataView(bytes.buffer);
            for (let i = 0; i < frameCount; i++) {
                const sample = dataView.getInt16(i * 2, true);
                channelData[i] = sample / 32768.0;
            }

            const compressor = this.audioContext.createDynamicsCompressor();
            compressor.threshold.value = -24;
            compressor.knee.value = 30;
            compressor.ratio.value = 12;
            compressor.attack.value = 0.003;
            compressor.release.value = 0.25;

            this.audioSourceNode = this.audioContext.createBufferSource();
            this.audioSourceNode.buffer = audioBuffer;
            this.audioSourceNode.connect(compressor);
            compressor.connect(this.audioGainNode);

            const now = this.audioContext.currentTime;
            const startTime = Math.max(now, this.lastPlayTime);
            this.audioSourceNode.start(startTime);
            this.lastPlayTime = startTime + audioBuffer.duration;

            // Add ended event listener to check if this was the last chunk
            this.audioSourceNode.addEventListener('ended', () => {
                if (this.audioQueue.length === 0) {
                    const skipButton = document.getElementById(this.getId('skip-audio'));
                    if (skipButton) {
                        skipButton.style.display = 'none';
                    }
                }
            });
            
            this.emit('audioPlaying', audioBuffer.duration);
        } catch (error) {
            console.error('Error playing audio:', error);
            this.emit('error', error);
        }
    }

    setVolume(volume) {
        if (this.audioGainNode) {
            this.audioGainNode.gain.setTargetAtTime(volume, this.audioContext?.currentTime || 0, 0.1);
        }
    }

    disconnect() {
        if (this.socket) {
            this.socket.close();
        }
        this.stopRecording();
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

    showToolStatus(toolName) {
        const toolStatus = document.getElementById(this.getId('tool-status'));
        if (toolStatus) {
            toolStatus.style.display = 'block';
            toolStatus.classList.add('active');
            toolStatus.querySelector('.tool-name').textContent = `Using tool: ${toolName}`;
        }
    }

    hideToolStatus() {
        const toolStatus = document.getElementById(this.getId('tool-status'));
        if (toolStatus) {
            toolStatus.classList.remove('active');
            toolStatus.style.display = 'none';
        }
    }

    skipCurrentAudio() {
        // Clear the audio queue
        this.audioQueue = [];
        
        // Stop current audio if playing
        if (this.audioSourceNode) {
            this.audioSourceNode.stop();
            this.audioSourceNode.disconnect();
            this.audioSourceNode = null;
        }
        
        this.isPlaying = false;
        this.lastPlayTime = 0;
        
        // Hide skip button
        const skipButton = document.getElementById(this.getId('skip-audio'));
        if (skipButton) {
            skipButton.style.display = 'none';
        }
    }
}

// Export for both ES modules and CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RichbotWidget;
} else if (typeof define === 'function' && define.amd) {
    define([], function() { return RichbotWidget; });
} else {
    window.RichbotWidget = RichbotWidget;
}
