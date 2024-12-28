<style>
    /* Message Styling */
    .message {
        margin-bottom: 1rem;
        padding: 0.75rem;
        border-radius: 0.5rem;
        max-width: 85%;
        position: relative;
        opacity: 0;
        transform: translateY(20px);
        animation: messageAppear 0.3s ease forwards;
    }

    @keyframes messageAppear {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .assistant-message {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        align-self: flex-start;
    }

    .user-message {
        background-color: #e7f5ff;
        border-right: 4px solid #0d6efd;
        align-self: flex-end;
    }

    .system-message {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        align-self: center;
        width: 100%;
    }

    .tool-message {
        background-color: #d1e7dd;
        border-left: 4px solid #198754;
        font-family: monospace;
        white-space: pre-wrap;
    }

    .message-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #495057;
    }

    .message-timestamp {
        position: absolute;
        top: 0.25rem;
        right: 0.5rem;
        font-size: 0.75rem;
        color: #6c757d;
    }

    /* Assistant Details Panel */
    .assistant-details {
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .assistant-details-header {
        padding: 0.75rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .assistant-details-content {
        padding: 0 0.75rem 0.75rem;
    }

    .assistant-detail-item {
        margin-bottom: 0.5rem;
        display: flex;
        gap: 0.5rem;
    }

    .assistant-detail-label {
        font-weight: 600;
        min-width: 120px;
    }

    /* Start Chat Button Emphasis */
    .start-chat-container {
        position: relative;
    }

    .start-chat-hint {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        text-align: center;
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    /* Add loading state styles */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .loading-spinner {
        width: 3rem;
        height: 3rem;
    }

    /* Error state styling */
    .error-message {
        background-color: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }

    /* Typing indicator */
    .typing-indicator {
        display: flex;
        gap: 0.5rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 1rem;
        width: fit-content;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        background: #0d6efd;
        border-radius: 50%;
        animation: typingBounce 1s infinite;
    }

    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typingBounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-4px); }
    }

    .assistant-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        height: 450px;
        display: flex;
        flex-direction: column;
    }

    .assistant-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .assistant-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        flex-shrink: 0;
    }

    .assistant-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .model-badge {
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #495057;
    }

    .assistant-description {
        color: #6c757d;
        font-size: 0.9rem;
        line-height: 1.4;
        cursor: pointer;
        user-select: none;
        margin-bottom: 12px;
        flex: 1;
        overflow: hidden;
    }

    .description-preview {
        max-height: 180px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #6c757d #f8f9fa;
        white-space: pre-wrap;
        padding-right: 10px;
    }

    .description-preview::-webkit-scrollbar {
        width: 6px;
    }

    .description-preview::-webkit-scrollbar-track {
        background: #f8f9fa;
    }

    .description-preview::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border-radius: 3px;
    }

    .description-full {
        white-space: pre-wrap;
        padding-right: 10px;
    }

    .expand-indicator {
        color: #0d6efd;
        font-size: 0.8rem;
        margin-left: 0.5rem;
    }

    .assistant-description:hover {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 2px;
    }

    .capabilities {
        margin-bottom: 10px;
        flex-shrink: 0;
        height: 40px;
        overflow: hidden;
    }

    .capability-badge {
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #495057;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .status-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .tools-section {
        margin-top: auto;
        margin-bottom: 10px;
        flex-shrink: 0;
        height: 80px;
    }

    .tools-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #495057;
        margin-bottom: 0.25rem;
    }

    .tools-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        max-height: 60px;
        overflow-y: auto;
        padding-right: 10px;
        scrollbar-width: thin;
        scrollbar-color: #6c757d #f8f9fa;
    }

    .tools-list::-webkit-scrollbar {
        width: 6px;
    }

    .tools-list::-webkit-scrollbar-track {
        background: #f8f9fa;
    }

    .tools-list::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border-radius: 3px;
    }

    .tool-badge {
        background: #e9ecef;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #495057;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        height: fit-content;
        margin-bottom: 2px;
    }

    .tool-badge i {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .btn {
        flex-shrink: 0;
    }

    /* Chat Interface Styles */
    .chat-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 100px);
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .chat-header {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        background: #f8f9fa;
        border-radius: 12px 12px 0 0;
    }

    .assistant-details {
        margin-bottom: 10px;
    }

    .current-assistant {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    #active-assistant-select {
        flex-grow: 1;
        max-width: 300px;
        color: #212529;
        background-color: #f8f9fa;
        border-color: #dee2e6;
        font-weight: 500;
    }

    .assistant-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
        color: #212529;
    }

    .scrollable-tools {
        max-height: 100px;
        overflow-y: auto;
        padding: 8px;
        background: white;
        border-radius: 4px;
        margin-top: 5px;
        border: 1px solid #dee2e6;
    }

    /* Scrollbar styles for tools list */
    .scrollable-tools::-webkit-scrollbar {
        width: 6px;
    }

    .scrollable-tools::-webkit-scrollbar-track {
        background: #f8f9fa;
    }

    .scrollable-tools::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border-radius: 3px;
    }

    .tool-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 4px;
        margin: 2px;
        font-size: 0.8rem;
        color: #212529;
    }

    .tool-badge i {
        color: #495057;
    }

    .chat-title {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .assistant-info {
        padding: 10px;
        background: white;
        border-radius: 8px;
        margin-top: 10px;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
    }

    .input-area {
        padding: 15px;
        border-top: 1px solid #e0e0e0;
    }

    .audio-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }

    /* Additional styles for the assistant switcher */
    .assistant-selector {
        margin-top: 10px;
    }

    #assistant-details-panel {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin-top: 10px;
    }

    .system-message-content {
        max-height: 100px;
        overflow-y: auto;
        padding: 10px;
        background: white;
        border-radius: 4px;
        margin-top: 5px;
        white-space: pre-wrap;
    }

    .chat-controls {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin-top: 10px;
    }

    .message-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }

    .typing-indicator {
        display: flex;
        gap: 0.5rem;
        padding: 1rem;
        align-items: center;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        background: #007bff;
        border-radius: 50%;
        animation: typing 1s infinite ease-in-out;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    /* Message styles */
    .message {
        padding: 1rem;
        border-radius: 8px;
        max-width: 80%;
        margin-bottom: 1rem;
    }

    .user-message {
        background: #007bff;
        color: white;
        align-self: flex-end;
    }

    .assistant-message {
        background: #f8f9fa;
        color: #212529;
        align-self: flex-start;
        border: 1px solid #dee2e6;
    }

    .error-message {
        background: #dc3545;
        color: white;
        align-self: center;
    }

    .tool-message {
        background: #f8f9fa;
        color: #212529;
        align-self: flex-start;
        border: 1px solid #dee2e6;
    }

    .message-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .message-timestamp {
        font-size: 0.8rem;
        opacity: 0.8;
        margin-bottom: 0.5rem;
    }

    .message-content {
        white-space: pre-wrap;
    }

    .tool-response {
        background: #f8f9fa;
        padding: 0.5rem;
        border-radius: 4px;
        margin: 0;
        overflow-x: auto;
    }

    .model-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .model-badge {
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #212529;
    }

    .audio-active {
        color: #007bff;
    }

    /* Chat container background */
    .chat-container {
        background: white;
        border: 1px solid #dee2e6;
    }

    .chat-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        color: #212529;
    }

    /* Strong tags in details */
    .assistant-info strong {
        color: #495057;
    }

    .current-assistant {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .assistant-name {
        font-size: 1.1rem;
        font-weight: 500;
        color: #212529;
        flex-grow: 1;
    }

    .assistant-selector {
        display: flex;
        gap: 10px;
        flex-grow: 1;
    }

    #active-assistant-select {
        flex-grow: 1;
    }

    /* Adjust button sizes */
    .current-assistant .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Add styles for audio controls */
    .audio-controls .btn {
        padding: 0.25rem 0.5rem;
        margin-left: 0.5rem;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    /* Add animation for recording state */
    @keyframes recording-pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }

    .recording {
        animation: recording-pulse 2s infinite;
    }

    /* Add styles for info panel transitions */
    #assistant-info-panel, #assistant-selector-controls {
        transition: all 0.3s ease;
    }

    /* Update button styles */
    .btn-link {
        color: #6c757d;
        text-decoration: none;
    }

    .btn-link:hover {
        color: #0d6efd;
    }

    /* Add styles for info panel */
    .assistant-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
    }

    .system-message-content {
        max-height: 100px;
        overflow-y: auto;
        padding: 10px;
        background: white;
        border-radius: 4px;
        margin-top: 5px;
        white-space: pre-wrap;
    }

    .tools-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 5px;
    }

    .tool-badge {
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #495057;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Update connection status styles */
    .connection-status {
        font-size: 0.875rem;
    }

    .connection-status i {
        font-size: 0.75rem;
    }

    /* Status colors */
    .connection-status.connected i {
        color: #28a745;
    }

    .connection-status.disconnected i {
        color: #dc3545;
    }

    .connection-status.connecting i {
        color: #ffc107;
    }

    /* Add styles for error messages */
    .error-message {
        background-color: #fff5f5;
        border-left: 4px solid #dc3545;
    }

    .error-message .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background-color: #ffe8e8;
    }

    .error-message .message-title {
        color: #dc3545;
        font-weight: 500;
    }

    .error-message .error-summary {
        padding: 8px 12px;
        color: #dc3545;
    }

    .error-message .error-details {
        padding: 0 12px;
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .error-message .error-data {
        margin: 0;
        padding: 8px;
        font-size: 0.875rem;
        color: #666;
        white-space: pre-wrap;
    }

    .error-message .btn-link {
        color: #dc3545;
    }

    .error-message .btn-link:hover {
        color: #bd2130;
    }

    /* Add styles for function calls and transcripts */
    .function-call-container {
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }

    .function-call-container:hover {
        background-color: #e9ecef;
    }

    .function-call-header {
        padding: 8px;
        user-select: none;
    }

    .function-call-header i {
        transition: transform 0.2s ease;
    }

    .function-name {
        font-family: monospace;
        color: #0d6efd;
    }

    .function-details pre {
        margin: 0;
        max-height: 200px;
        overflow-y: auto;
    }

    .function-details code {
        font-size: 0.85em;
    }

    .message-container {
        margin: 10px 0;
        padding: 10px;
        border-radius: 8px;
        background: #f5f5f5;
    }

    .assistant-message {
        background: #e3f2fd;
    }

    .audio-player {
        margin-top: 10px;
        display: flex;
        align-items: center;
    }

    .play-button {
        background: #2196F3;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .play-button:hover {
        background: #1976D2;
    }

    .response-indicator i {
        transition: color 0.3s ease;
    }

    .response-indicator i.active {
        color: #28a745 !important;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    .transcript-area {
        font-style: italic;
        color: #666;
        margin-top: 5px;
        padding: 5px;
        border-left: 3px solid #ddd;
    }

    .audio-container {
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .audio-status {
        font-size: 0.875rem;
        color: #666;
    }

    /* Update existing message styles */
    .message {
        margin-bottom: 15px;
        max-width: 80%;
    }

    .message.received {
        margin-right: auto;
    }

    .message.sent {
        margin-left: auto;
    }

    .message-content {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 12px;
    }

    .message.sent .message-content {
        background: #007bff;
        color: white;
    }

    .message.sent .sender {
        color: #dee2e6 !important;
    }
</style>

<div id="client-dashboard">
    <!-- Status Bar -->
    <div class="status-bar">
        <span class="badge bg-secondary" id="status">Disconnected</span>
        <span id="fd-display"></span>
    </div>

    <!-- Main Content -->
    <div class="connection-panels">
        <!-- Assistants Panel -->
        <div class="panel">
            <h3>Available Assistants</h3>
            <div id="assistants-list" class="list-container"></div>
        </div>

        <!-- Active Chat -->
        <div id="active-connection" class="d-none">
            <div class="chat-container">
                <div class="chat-header">
                    <div>
                        <span id="chat-title" class="chat-title"></span>
                        <small id="connection-details" class="text-muted"></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="autoEnableMic">
                            <label class="form-check-label small" for="autoEnableMic">
                                Auto-enable mic
                            </label>
                        </div>
                        <div class="response-indicator me-3">
                            <i class="fas fa-comment text-muted" id="response-indicator"></i>
                        </div>
                        <div class="audio-status">
                            <span id="mic-status">Mic: Muted</span>
                            <span id="speaker-status">Speaker: Active</span>
                        </div>
                    </div>
                </div>

                <div id="chat-messages"></div>

                <div class="input-area">
                    <div class="input-group">
                        <input type="text" id="message-input" class="form-control" placeholder="Type your message...">
                        <button onclick="sendMessage()" class="btn btn-primary">Send</button>
                    </div>
                    
                    <div class="audio-controls">
                        <button id="toggle-mic" class="btn btn-outline-primary" onclick="toggleMic()">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button id="toggle-speaker" class="btn btn-outline-primary" onclick="toggleSpeaker()">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <input type="range" id="pitch-control" min="0.5" max="2" step="0.1" value="1.0" 
                               class="form-range" style="width: 100px;" onchange="setPitchFactor(this.value)">
                        <label for="pitch-control" class="form-label small">Pitch</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Log -->
    <div class="accordion" id="debugAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#debugLog">
                    Debug Log
                </button>
            </h2>
            <div id="debugLog" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div id="message-log"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global state
const audioBuffers = new Map();
const transcriptBuffers = new Map();
let isAssistantResponding = false;
let currentResponseId = null;

// Initialize audio state
if (!appState.audio) {
    appState.audio = {
        isMicMuted: true,
        isSpeakerMuted: false,
        mediaHandler: null,
        currentResponses: new Map(),
        socket: null,
        playbackQueue: [],
        isPlaying: false,
        context: null,
        gainNode: null,
        pitchFactor: 1.0,
        uploadQueue: [],
        isProcessingQueue: false
    };
}

function displayAssistants(assistants) {
    const container = document.getElementById('assistants-list');
    if (!container) return;

    container.innerHTML = '';
    
    assistants.forEach(assistant => {
        const card = document.createElement('div');
        card.className = 'assistant-card';
        
        // Create header with name and model
        const header = document.createElement('div');
        header.className = 'assistant-header';
        header.innerHTML = `
            <span class="assistant-name">${assistant.name}</span>
            <span class="model-badge">${assistant.model?.name || 'Unknown Model'}</span>
        `;
        
        // Create description section
        const description = document.createElement('div');
        description.className = 'assistant-description';
        description.innerHTML = `
            <div class="description-preview">${assistant.system_message || 'No description available'}</div>
        `;
        
        // Create capabilities section if available
        const capabilities = document.createElement('div');
        capabilities.className = 'capabilities';
        if (assistant.capabilities && assistant.capabilities.length > 0) {
            capabilities.innerHTML = assistant.capabilities.map(cap => 
                `<span class="capability-badge"><i class="fas fa-check"></i> ${cap}</span>`
            ).join(' ');
        }
        
        // Create tools section if assistant has tools
        const tools = document.createElement('div');
        tools.className = 'tools-section';
        if (assistant.tools && assistant.tools.length > 0) {
            tools.innerHTML = `
                <div class="tools-label">Available Tools</div>
                <div class="tools-list">
                    ${assistant.tools.map(tool => 
                        `<span class="tool-badge"><i class="fas fa-wrench"></i> ${tool.name}</span>`
                    ).join('')}
                </div>
            `;
        }
        
        // Create start chat button
        const startChat = document.createElement('div');
        startChat.className = 'start-chat-container';
        startChat.innerHTML = `
            <button class="btn btn-primary w-100" onclick="selectAssistant('${assistant.id}')">
                Start Chat
            </button>
        `;
        
        // Assemble card
        card.appendChild(header);
        card.appendChild(description);
        card.appendChild(capabilities);
        card.appendChild(tools);
        card.appendChild(startChat);
        
        container.appendChild(card);
    });
}

function selectAssistant(assistantId) {
    if (appState.audio.socket?.readyState === WebSocket.OPEN) {
        const messageData = {
            type: 'text',
            data: {
                content: '/start_chat',
                timestamp: Date.now()
            }
        };
        
        console.log('🔵 Selecting assistant:', messageData);
        logMessage(`Selecting assistant: ${assistantId}`, 'debug');
        
        try {
            appState.audio.socket.send(JSON.stringify(messageData));
        } catch (error) {
            logMessage(`Failed to select assistant: ${error.message}`, 'error');
        }
    } else {
        logMessage('WebSocket not connected. Cannot select assistant.', 'error');
    }
}

function initializeClient() {
    // Initialize UI elements
    updateStatus('Initializing...');
    
    // Load available assistants
    if (appState.data?.assistants) {
        displayAssistants(appState.data.assistants);
    }

    // Setup WebSocket connection
    setupWebSocket();
}

function setupWebSocket() {
    if (!appState.audio.socket || appState.audio.socket.readyState !== WebSocket.OPEN) {
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//${window.location.hostname}:9501/app/${appState.apiToken}`;
        
        logMessage(`Connecting to WebSocket: ${wsUrl}`, 'info');
        appState.audio.socket = new WebSocket(wsUrl);
        
        appState.audio.socket.addEventListener('open', () => {
            updateStatus('Connected');
            logMessage('WebSocket connected', 'info');
        });
        
        appState.audio.socket.addEventListener('message', (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log('📥 Received:', data);
                handleMessage(event);
            } catch (error) {
                logMessage(`Error parsing WebSocket message: ${error.message}`, 'error');
                console.error('WebSocket message error:', error);
            }
        });
        
        appState.audio.socket.addEventListener('close', (event) => {
            logMessage(`WebSocket closed with code: ${event.code}, reason: ${event.reason || 'No reason provided'}`, 'error');
            appState.audio.socket = null;
            handleSocketClose();
        });
        
        appState.audio.socket.addEventListener('error', (error) => {
            logMessage(`WebSocket error: ${error.message || 'Connection error'}`, 'error');
            console.error('WebSocket error:', error);
        });
    }
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (message && appState.audio.socket?.readyState === WebSocket.OPEN) {
        // Stop any playing audio first
        stopAllAudio();
        
        const messageData = {
            type: 'text',
            data: {
                content: message,
                timestamp: Date.now()
            }
        };
        
        console.log('🔵 Sending message:', messageData);
        logMessage(`Sending message: ${message}`, 'debug');
        
        try {
            appState.audio.socket.send(JSON.stringify(messageData));
            input.value = '';
            
            // Add message to chat immediately for better UX
            addMessageToChat('You', message, 'text', 'sent');
        } catch (error) {
            logMessage(`Failed to send message: ${error.message}`, 'error');
        }
    }
}

// Add event listener for Enter key in message input
document.getElementById('message-input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        sendMessage();
    }
});

function handleMessage(event) {
    try {
        const data = JSON.parse(event.data);
        console.log('📥 Received message:', data);

        switch (data.type) {
            case 'conversation.created':
                // Handle new conversation
                handleConversationCreated(data.data);
                break;

            case 'assistant_text_delta':
                // Handle incremental text updates
                addMessageToChat('Assistant', data.data.delta, 'text', 'received', true);
                break;

            case 'assistant_audio_delta':
                // Queue audio chunk for playback
                if (!appState.audio.isSpeakerMuted) {
                    playAudioMessage(data.data.delta);
                }
                // Create or update message container
                const container = createMessageContainer(data.data.response_id);
                const audioContainer = container.querySelector('.audio-container');
                if (audioContainer) {
                    const playButton = audioContainer.querySelector('.play-button');
                    const audioStatus = audioContainer.querySelector('.audio-status');
                    playButton.classList.remove('d-none');
                    audioStatus.textContent = 'Receiving audio...';
                }
                break;

            case 'assistant_audio_transcript_delta':
                // Handle incremental transcript updates
                const transcriptDelta = data.data.delta;
                if (!transcriptBuffers.has(data.data.response_id)) {
                    transcriptBuffers.set(data.data.response_id, '');
                }
                const currentTranscript = transcriptBuffers.get(data.data.response_id) + transcriptDelta;
                transcriptBuffers.set(data.data.response_id, currentTranscript);
                updateTranscriptDisplay(data.data.response_id, currentTranscript);
                break;

            case 'assistant_audio_transcript':
                // Handle complete transcript
                const transcript = data.data.transcript;
                transcriptBuffers.set(data.data.response_id, transcript);
                updateTranscriptDisplay(data.data.response_id, transcript);
                break;

            case 'assistant_response_complete':
                // Handle response completion
                handleResponseComplete(data.data);
                break;

            case 'response.created':
                // Handle new response creation
                handleResponseCreated(data.data);
                break;

            case 'error':
                // Handle errors
                logMessage(`Error from server: ${data.data.error}`, 'error');
                break;

            case 'session_update':
                // Handle session updates
                handleSessionUpdate(data.data);
                break;

            case 'conversation_update':
                // Handle conversation updates
                handleConversationUpdate(data.data);
                break;

            case 'function_call_output':
                handleFunctionCallOutput(data.data);
                break;

            case 'function_call':
                handleFunctionCall(data.data);
                break;

            default:
                console.log('Unhandled message type:', data.type, data);
                break;
        }
    } catch (error) {
        console.error('Error handling message:', error);
        logMessage(`Error handling message: ${error.message}`, 'error');
    }
}

function handleConversationCreated(data) {
    console.log('Conversation created:', data);
    showChat();
    
    // Update UI to show active chat
    const chatTitle = document.getElementById('chat-title');
    if (chatTitle) {
        chatTitle.textContent = `Chat with ${data.assistant?.name || 'Assistant'}`;
    }
    
    // Show the active connection panel
    const activeConnection = document.getElementById('active-connection');
    if (activeConnection) {
        activeConnection.classList.remove('d-none');
        activeConnection.style.display = 'block';
    }
    
    // Hide the assistants panel
    const panel = document.querySelector('.panel');
    if (panel) {
        panel.style.display = 'none';
    }
    
    logMessage(`Started conversation with assistant: ${data.assistant?.name}`, 'info');
}

function addMessageToChat(sender, content, type = 'text', messageType = 'received', isDelta = false) {
    const chatMessages = document.getElementById('chat-messages');
    
    // For deltas, try to append to the last message if it's from the same sender
    if (isDelta && chatMessages.lastElementChild) {
        const lastMessage = chatMessages.lastElementChild;
        if (lastMessage.classList.contains(messageType)) {
            const contentDiv = lastMessage.querySelector('.content');
            if (contentDiv) {
                contentDiv.textContent += content;
                chatMessages.scrollTop = chatMessages.scrollHeight;
                return;
            }
        }
    }
    
    // Create new message if not a delta or couldn't append
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${messageType}`;
    
    let messageContent = content;
    if (type === 'audio') {
        messageContent = `<i class="fas fa-microphone text-muted"></i> ${content}`;
    }

    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="sender small text-muted mb-1">${sender}</div>
            <div class="content">${messageContent}</div>
        </div>
    `;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function createMessageContainer(responseId) {
    let container = document.querySelector(`[data-response-id="${responseId}"]`);
    if (!container) {
        container = document.createElement('div');
        container.className = 'message-container assistant-message';
        container.setAttribute('data-response-id', responseId);
        container.innerHTML = `
            <div class="message-content">
                <div class="message-text"></div>
                <div class="transcript-area"></div>
                <div class="function-calls-area"></div>
                <div class="audio-container">
                    <button class="play-button d-none">
                        <i class="fas fa-play"></i>
                    </button>
                    <span class="audio-status"></span>
                </div>
            </div>
        `;
        document.getElementById('chat-messages').appendChild(container);
        container.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }
    return container;
}

function updateTranscriptDisplay(responseId, text) {
    const container = createMessageContainer(responseId);
    if (container) {
        const transcriptArea = container.querySelector('.transcript-area');
        if (transcriptArea) {
            transcriptArea.textContent = text;
            container.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }
    }
}

function handleResponseComplete(data) {
    console.log('Response complete:', data);
    isAssistantResponding = false;
    
    // Hide response indicator
    const indicator = document.getElementById('response-indicator');
    if (indicator) {
        indicator.classList.remove('active');
    }
    
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`);
    if (container) {
        const audioStatus = container.querySelector('.audio-status');
        if (audioStatus) {
            audioStatus.textContent = 'Audio ready';
        }
        
        // Enable play button for complete audio
        if (audioBuffers.has(data.response_id)) {
            const playButton = container.querySelector('.play-button');
            if (playButton) {
                playButton.classList.remove('d-none');
                playButton.onclick = () => {
                    // Stop any other playing audio first
                    if (appState.audio?.mediaHandler) {
                        appState.audio.mediaHandler.stopCurrentAudio();
                    }
                    
                    const audioChunks = audioBuffers.get(data.response_id);
                    audioChunks.forEach(chunk => playAudioMessage(chunk));
                };
            }
        }
    }
}

function handleFunctionCall(data) {
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`) || createMessageContainer(data.response_id);
    const functionCallsArea = container.querySelector('.function-calls-area');
    
    const functionCallElement = document.createElement('div');
    functionCallElement.className = 'function-call-container mt-2 p-2 border rounded';
    
    // Format the function call details
    const functionName = data.name;
    const args = JSON.stringify(data.arguments, null, 2);
    const result = JSON.stringify(data.result, null, 2);
    
    functionCallElement.innerHTML = `
        <div class="function-call-header d-flex align-items-center" style="cursor: pointer;" onclick="toggleFunctionDetails(this)">
            <i class="fas fa-code me-2"></i>
            <span class="function-name">${functionName}</span>
            <i class="fas fa-chevron-down ms-auto"></i>
        </div>
        <div class="function-details mt-2" style="display: none;">
            <div class="arguments mb-2">
                <div class="text-muted small">Arguments:</div>
                <pre class="bg-light p-2 rounded"><code>${args}</code></pre>
            </div>
            <div class="result">
                <div class="text-muted small">Result:</div>
                <pre class="bg-light p-2 rounded"><code>${result}</code></pre>
            </div>
        </div>
    `;
    
    functionCallsArea.appendChild(functionCallElement);
    container.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

function handleFunctionCallOutput(data) {
    const container = document.querySelector(`[data-response-id="${data.response_id}"]`) || createMessageContainer(data.response_id);
    const functionCallsArea = container.querySelector('.function-calls-area');
    
    const functionCallElement = document.createElement('div');
    functionCallElement.className = 'function-call-container mt-2 p-2 border rounded';
    
    // Format the function call details
    const functionName = data.name;
    const args = JSON.stringify(data.arguments, null, 2);
    const result = JSON.stringify(data.result, null, 2);
    
    functionCallElement.innerHTML = `
        <div class="function-call-header d-flex align-items-center" style="cursor: pointer;" onclick="toggleFunctionDetails(this)">
            <i class="fas fa-code me-2"></i>
            <span class="function-name">${functionName}</span>
            <i class="fas fa-chevron-down ms-auto"></i>
        </div>
        <div class="function-details mt-2" style="display: none;">
            <div class="arguments mb-2">
                <div class="text-muted small">Arguments:</div>
                <pre class="bg-light p-2 rounded"><code>${args}</code></pre>
            </div>
            <div class="result">
                <div class="text-muted small">Result:</div>
                <pre class="bg-light p-2 rounded"><code>${result}</code></pre>
            </div>
        </div>
    `;
    
    functionCallsArea.appendChild(functionCallElement);
    container.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

function logMessage(message, type = 'info') {
    const logContainer = document.getElementById('message-log');
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.innerHTML = `
        <span class="timestamp">${new Date().toLocaleTimeString()}</span>
        <span class="type">[${type.toUpperCase()}]</span>
        <span class="message">${message}</span>
    `;
    logContainer.appendChild(entry);
    logContainer.scrollTop = logContainer.scrollHeight;
}

function updateStatus(status) {
    const statusElement = document.getElementById('status');
    if (statusElement) {
        statusElement.textContent = status;
        statusElement.className = `badge ${status === 'Connected' ? 'bg-success' : 'bg-secondary'}`;
    }
}

function handleSocketClose() {
    updateStatus('Disconnected');
    logMessage('WebSocket connection closed', 'error');
    
    // Clean up existing connection
    if (appState.currentConnection) {
        handleChatEnded();
    }
    
    // Attempt to reconnect after a delay
    setTimeout(() => {
        if (!appState.audio.socket || appState.audio.socket.readyState === WebSocket.CLOSED) {
            logMessage('Attempting to reconnect...', 'info');
            initializeClient();
        }
    }, 5000);
}

function handleChatEnded() {
    // Clean up audio
    if (appState.audio.mediaHandler) {
        appState.audio.mediaHandler.stopRecording();
    }
    
    // Reset connection state
    appState.currentConnection = null;
    
    // Reset UI
    document.getElementById('chat-messages').innerHTML = '';
    document.getElementById('active-connection').classList.add('d-none');
    document.querySelector('.panel').style.display = 'block';
    
    updateStatus('Disconnected');
}

function handleSessionUpdate(data) {
    console.log('Session update:', data);
    updateConnectionStatus(data);
}

function updateConnectionStatus(data) {
    const statusEl = document.getElementById('status');
    if (statusEl) {
        const isConnected = data.session_id && data.model;
        statusEl.textContent = isConnected ? 'Connected' : 'Disconnected';
        statusEl.className = `badge ${isConnected ? 'bg-success' : 'bg-secondary'}`;
        
        if (isConnected) {
            showChat();
        }
    }
}

function showChat() {
    const activeConnection = document.getElementById('active-connection');
    if (activeConnection) {
        activeConnection.classList.remove('d-none');
        activeConnection.style.display = 'block';
        
        // Hide assistants list
        const panel = document.querySelector('.panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }
}

function toggleFunctionDetails(header) {
    const details = header.nextElementSibling;
    const icon = header.querySelector('.fa-chevron-down');
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        details.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Initialize immediately
initializeClient();
</script>