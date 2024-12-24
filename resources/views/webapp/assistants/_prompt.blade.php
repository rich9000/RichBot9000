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
</style>

<div class="container">
    <!-- Assistant Chat Card -->
    <div class="card shadow-sm">
        <div class="card-header text-center bg-primary text-white d-flex justify-content-between align-items-center">
            <span id="generalAssistantTitle">Assistant Chat</span>
            <button id="newGeneralAssistantChat" class="btn btn-sm btn-secondary d-none" onclick="resetGeneralAssistantChat()">
                Start New Chat
            </button>
        </div>
        <div class="card-body">
            <!-- Assistant Selection -->
            <div id="generalAssistantSelection" class="align-items-center mb-3">
                <div class="d-flex flex-column">
                    <div class="form-group mb-2">
                        <select id="generalAssistantSelect" class="form-control">
                            <option value="">-- Select an Assistant --</option>
                        </select>
                    </div> 
                    
                    <!-- Collapsible Assistant Details -->
                    <div id="generalAssistantDetails" class="assistant-details mb-3 d-none">
                        <div class="assistant-details-header" onclick="toggleAssistantDetails()">
                            <span>Assistant Details</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="assistant-details-content collapse">
                            <div class="assistant-detail-item">
                                <span class="assistant-detail-label">Name:</span>
                                <span class="assistant-detail-value" id="assistantDetailName"></span>
                            </div>
                            <div class="assistant-detail-item">
                                <span class="assistant-detail-label">Model:</span>
                                <span class="assistant-detail-value" id="assistantDetailModel"></span>
                            </div>
                            <div class="assistant-detail-item">
                                <span class="assistant-detail-label">Description:</span>
                                <span class="assistant-detail-value" id="assistantDetailDescription"></span>
                            </div>
                            <div class="assistant-detail-item">
                                <span class="assistant-detail-label">Tools:</span>
                                <span class="assistant-detail-value" id="assistantDetailTools"></span>
                            </div>
                            <div class="assistant-detail-item">
                                <span class="assistant-detail-label">System Message:</span>
                                <pre class="assistant-detail-value" id="assistantDetailSystemMessage"></pre>
                            </div>
                        </div>
                    </div>

                    <div class="start-chat-container">
                        <button class="btn btn-primary w-100" id="startGeneralAssistantChat" onclick="startGeneralAssistantChat()" disabled>
                            Start Chat
                        </button>
                        <div class="start-chat-hint">Select an assistant and click Start Chat to begin</div>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div id="generalAssistantMessages" class="d-none">
                <div class="message-container border rounded p-3 mb-3" style="height: 400px; overflow-y: auto;"></div>

                <!-- Chat Input -->
                <div class="input-group" id="generalAssistantInput" style="display: none;">
                    <input type="text" id="generalAssistantMessageInput" class="form-control" 
                           placeholder="Type your message..." aria-label="User message">
                    <button class="btn btn-primary" type="button" onclick="sendGeneralAssistantMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>


    // 1. First initialize the elements object
    const generalAssistantElements = {
        messages: document.getElementById('generalAssistantMessages'),
        messageContainer: document.querySelector('#generalAssistantMessages .message-container'),
        select: document.getElementById('generalAssistantSelect'),
        startButton: document.getElementById('startGeneralAssistantChat'),
        inputContainer: document.getElementById('generalAssistantInput'),
        title: document.getElementById('generalAssistantTitle'),
        selection: document.getElementById('generalAssistantSelection'),
        newChatButton: document.getElementById('newGeneralAssistantChat'),
        input: document.getElementById('generalAssistantMessageInput'),
        sendButton: document.querySelector('#generalAssistantInput .btn-primary'),
        details: {
            container: document.getElementById('generalAssistantDetails'),
            content: document.querySelector('.assistant-details-content'),
            name: document.getElementById('assistantDetailName'),
            model: document.getElementById('assistantDetailModel'),
            description: document.getElementById('assistantDetailDescription'),
            tools: document.getElementById('assistantDetailTools'),
            systemMessage: document.getElementById('assistantDetailSystemMessage'),
            toggle: document.querySelector('.assistant-details-header i')
        }
    };

    // 2. Add the overlay and typing indicator
    generalAssistantElements.loadingOverlay = createLoadingOverlay(generalAssistantElements.messageContainer);
    generalAssistantElements.typingIndicator = createTypingIndicator(generalAssistantElements.messageContainer);

    // 3. Initialize variables
    let generalAssistantConversationId = null;
    let isAssistantDetailsVisible = false;

    // 4. Initialize the assistant select
    initializeGeneralAssistantSelect();

    // 5. Load saved state if exists
    if (appState.lastGeneralAssistantConversation) {
        loadGeneralAssistantConversationState(appState.lastGeneralAssistantConversation);
    }

    // 6. Add event listeners
    generalAssistantElements.input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendGeneralAssistantMessage();
        }
    });

    generalAssistantElements.select.addEventListener('change', () => {
        const selectedOption = generalAssistantElements.select.options[generalAssistantElements.select.selectedIndex];
        generalAssistantElements.startButton.disabled = !selectedOption.value;
        updateAssistantDetails();
    });

    // Check if appState.data.assistants exists
    console.log('Available Assistants:', appState?.data?.assistants);

    // Helper functions that need generalAssistantElements
    function createLoadingOverlay(messageContainer) {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay d-none';
        overlay.innerHTML = `
            <div class="spinner-border loading-spinner text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        messageContainer.appendChild(overlay);
        return overlay;
    }

    function createTypingIndicator(messageContainer) {
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator d-none';
        indicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        messageContainer.appendChild(indicator);
        return indicator;
    }

    // Initialize assistants dropdown with enhanced details
    function initializeGeneralAssistantSelect() {
        const assistants = appState.data.assistants;
        assistants.forEach(assistant => {
            const option = document.createElement('option');
            option.value = assistant.id;
            option.textContent = assistant.name;
            option.dataset.assistant = JSON.stringify(assistant);
            generalAssistantElements.select.appendChild(option);
        });

        // Add change event listener for assistant details
        generalAssistantElements.select.addEventListener('change', updateAssistantDetails);
    }

    // Update assistant details panel
    function updateAssistantDetails() {
        const selectedOption = generalAssistantElements.select.options[generalAssistantElements.select.selectedIndex];
        
        if (selectedOption.value) {
            const assistant = JSON.parse(selectedOption.dataset.assistant);
            
            generalAssistantElements.details.name.textContent = assistant.name;
            generalAssistantElements.details.model.textContent = assistant.model || 'Not specified';
            generalAssistantElements.details.description.textContent = assistant.description || 'No description available';
            generalAssistantElements.details.systemMessage.textContent = assistant.system_message || 'No system message';
            
            // Format tools as badges
            const toolsHtml = assistant.tools?.map(tool => 
                `<span class="badge bg-secondary me-1">${tool.name}</span>`
            ).join('') || 'No tools available';
            generalAssistantElements.details.tools.innerHTML = toolsHtml;

            generalAssistantElements.details.container.classList.remove('d-none');
            generalAssistantElements.startButton.disabled = false;
        } else {
            generalAssistantElements.details.container.classList.add('d-none');
            generalAssistantElements.startButton.disabled = true;
        }
    }

    // Toggle assistant details visibility
    function toggleAssistantDetails() {
        isAssistantDetailsVisible = !isAssistantDetailsVisible;
        const content = generalAssistantElements.details.content;
        const icon = generalAssistantElements.details.toggle;
        
        if (isAssistantDetailsVisible) {
            content.classList.add('show');
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        } else {
            content.classList.remove('show');
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
        }
    }

    // Enhanced message display with timestamps and formatting
    function addGeneralAssistantMessage(role, content, timestamp = new Date()) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', `${role}-message`);
        
        // Format timestamp
        const formattedTime = timestamp.toLocaleTimeString();
        
        // Handle different message types
        switch(role) {
            case 'assistant':
                messageDiv.innerHTML = `
                    <div class="message-title">Assistant</div>
                    <div class="message-timestamp">${formattedTime}</div>
                    <div class="message-content">${formatMessageContent(content)}</div>
                `;
                break;
            case 'user':
                messageDiv.innerHTML = `
                    <div class="message-title">You</div>
                    <div class="message-timestamp">${formattedTime}</div>
                    <div class="message-content">${formatMessageContent(content)}</div>
                `;
                break;
            case 'system':
                messageDiv.innerHTML = `
                    <div class="message-title">System</div>
                    <div class="message-timestamp">${formattedTime}</div>
                    <div class="message-content text-danger">${formatMessageContent(content)}</div>
                `;
                break;
            case 'tool':
                messageDiv.innerHTML = `
                    <div class="message-title">Tool Response</div>
                    <div class="message-timestamp">${formattedTime}</div>
                    <div class="message-content">
                        <pre class="tool-response">${formatToolResponse(content)}</pre>
                        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="copyToClipboard(this.previousElementSibling.textContent)">
                            Copy Response
                        </button>
                    </div>
                `;
                break;
        }
        
        generalAssistantElements.messageContainer.appendChild(messageDiv);
        scrollToBottom();
    }

    // Helper function to format message content
    function formatMessageContent(content) {
        if (typeof content !== 'string') {
            content = JSON.stringify(content, null, 2);
        }
        
        // Basic markdown-like formatting
        return content
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^\*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^\*]+)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
    }

    // Helper function to format tool responses
    function formatToolResponse(content) {
        try {
            if (typeof content === 'string') {
                content = JSON.parse(content);
            }
            return JSON.stringify(content, null, 2);
        } catch (e) {
            return content;
        }
    }

    // Copy to clipboard functionality
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    }

    // Toast notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast position-fixed bottom-0 end-0 m-3';
        toast.innerHTML = `
            <div class="toast-body">
                ${message}
            </div>
        `;
        document.body.appendChild(toast);
        new bootstrap.Toast(toast).show();
        setTimeout(() => toast.remove(), 3000);
    }

    // Smooth scroll to bottom of messages
    function scrollToBottom() {
        generalAssistantElements.messageContainer.scrollTo({
            top: generalAssistantElements.messageContainer.scrollHeight,
            behavior: 'smooth'
        });
    }

    // Start new chat session
    async function startGeneralAssistantChat() {
        const assistantId = generalAssistantElements.select.value;
        if (!assistantId) return;

        showLoadingState();
        try {
            clearGeneralAssistantChat();
            hideGeneralAssistantSelection();

            const response = await fetch(`/api/conversations/assistant/${assistantId}/start`, {
                method: 'POST',
                headers: apiHeaders(),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            generalAssistantConversationId = data.conversation_id;
            
            updateGeneralAssistantTitle(generalAssistantElements.select.options[generalAssistantElements.select.selectedIndex].text);
            showGeneralAssistantInterface();
            addGeneralAssistantMessage('assistant', 'How can I assist you today?');
        } catch (error) {
            handleGeneralAssistantError(error, "Failed to start chat. Please try again.");
            showGeneralAssistantSelection();
        } finally {
            hideLoadingState();
        }
    }

    // Send message function
    async function sendGeneralAssistantMessage() {
        const messageText = generalAssistantElements.input.value.trim();
        if (!messageText) return;

        toggleGeneralAssistantSendButton(true);
        generalAssistantElements.input.disabled = true;
        showTypingIndicator();

        try {
            addGeneralAssistantMessage('user', messageText);
            
            const response = await fetch(`/api/conversations/${generalAssistantConversationId}/messages`, {
                method: 'POST',
                headers: {
                    ...apiHeaders(),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    message: messageText, 
                    conversation_id: generalAssistantConversationId 
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            hideTypingIndicator();

            if (data.status === 'success') {
                const assistantMessage = data.messages[data.messages.length - 1];
                addGeneralAssistantMessage(assistantMessage.role, assistantMessage.content);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        } catch (error) {
            handleGeneralAssistantError(error, "Failed to send message. Please try again.");
        } finally {
            toggleGeneralAssistantSendButton(false);
            generalAssistantElements.input.disabled = false;
            generalAssistantElements.input.value = '';
            hideTypingIndicator();
        }
    }

    // UI Helper Functions
    function updateGeneralAssistantMessages(messages) {
        generalAssistantElements.messageContainer.innerHTML = '';
        messages.forEach(message => {
            addGeneralAssistantMessage(message.role, message.content);
        });
    }

    function clearGeneralAssistantChat() {
        generalAssistantElements.messageContainer.innerHTML = '';
        generalAssistantElements.input.value = '';
        generalAssistantConversationId = null;
    }

    function resetGeneralAssistantChat() {
        clearGeneralAssistantChat();
        showGeneralAssistantSelection();
        hideGeneralAssistantInterface();
        generalAssistantElements.title.textContent = 'Assistant Chat';
    }

    // UI State Management
    function showGeneralAssistantSelection() {
        generalAssistantElements.selection.classList.remove('d-none');
        generalAssistantElements.newChatButton.classList.add('d-none');
    }

    function hideGeneralAssistantSelection() {
        generalAssistantElements.selection.classList.add('d-none');
        generalAssistantElements.newChatButton.classList.remove('d-none');
    }

    function showGeneralAssistantInterface() {
        generalAssistantElements.messages.classList.remove('d-none');
        generalAssistantElements.inputContainer.style.display = 'flex';
        generalAssistantElements.input.focus();
    }

    function hideGeneralAssistantInterface() {
        generalAssistantElements.messages.classList.add('d-none');
        generalAssistantElements.inputContainer.style.display = 'none';
    }

    function updateGeneralAssistantTitle(assistantName) {
        generalAssistantElements.title.textContent = `Chat with ${assistantName}`;
    }

    function toggleGeneralAssistantSendButton(loading) {
        generalAssistantElements.sendButton.disabled = loading;
        generalAssistantElements.sendButton.innerHTML = loading ? 
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' : 
            'Send';
    }

    

    // Error Handling
    function handleGeneralAssistantError(error, userMessage = "An error occurred. Please try again.") {
        console.error('General Assistant Error:', error);
        
        const errorDetails = error.response ? 
            `Error ${error.response.status}: ${error.response.statusText}` : 
            error.message || 'Unknown error';

        addGeneralAssistantMessage('system', `${userMessage}\n\nTechnical details: ${errorDetails}`);
        toggleGeneralAssistantSendButton(false);
        generalAssistantElements.input.disabled = false;
        hideLoadingState();
    }

    // Conversation State Management
    function saveGeneralAssistantConversationState() {
        if (!generalAssistantConversationId) return;
        
        const conversationState = {
            id: generalAssistantConversationId,
            assistantId: generalAssistantElements.select.value,
            assistantName: generalAssistantElements.select.options[generalAssistantElements.select.selectedIndex].text,
            messages: Array.from(generalAssistantElements.messageContainer.children).map(msg => ({
                role: msg.classList.contains('assistant-message') ? 'assistant' : 
                      msg.classList.contains('user-message') ? 'user' : 'system',
                content: msg.querySelector('.message-content').textContent
            }))
        };

        if (!appState.conversations) {
            appState.conversations = {};
        }
        appState.conversations[generalAssistantConversationId] = conversationState;
    }

    function loadGeneralAssistantConversationState(conversationId) {
        if (!appState.conversations?.[conversationId]) return false;
        
        const state = appState.conversations[conversationId];
        generalAssistantConversationId = conversationId;
        generalAssistantElements.select.value = state.assistantId;
        updateGeneralAssistantTitle(state.assistantName);
        updateGeneralAssistantMessages(state.messages);
        showGeneralAssistantInterface();
        hideGeneralAssistantSelection();
        return true;
    }

    // Cleanup function for when navigating away
    function cleanupGeneralAssistantChat() {
        saveGeneralAssistantConversationState();
        clearGeneralAssistantChat();
        generalAssistantElements.select.value = '';
        generalAssistantElements.startButton.disabled = true;
    }

    // Loading state management
    function showLoadingState() {
        generalAssistantElements.loadingOverlay.classList.remove('d-none');
    }

    function hideLoadingState() {
        generalAssistantElements.loadingOverlay.classList.add('d-none');
    }

    // Typing indicator management
    function showTypingIndicator() {
        generalAssistantElements.typingIndicator.classList.remove('d-none');
        scrollToBottom();
    }

    function hideTypingIndicator() {
        generalAssistantElements.typingIndicator.classList.add('d-none');
    }
</script>
