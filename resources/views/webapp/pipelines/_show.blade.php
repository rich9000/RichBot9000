
<style>
    .message-container { display: flex; flex-direction: column; gap: 1rem; }
    .message { max-width: 60%; padding: 1rem; border-radius: 8px; }
    .assistant-message { align-self: flex-start; background-color: #f1f1f1; }
    .user-message { align-self: flex-end; background-color: #e0f7fa; }
    .center-message { align-self: center; background-color: #fff3e0; }
    .message-title { font-weight: bold; }
    .modal-body { white-space: pre-line; }
    .prompt-container { display: flex; gap: 0.5rem; padding-top: 1rem; }
    .prompt-container input { flex-grow: 1; }
</style>

<div class="container my-5">
    <!-- AI Prompt Card -->
    <div class="card shadow-sm">
        <div class="card-header text-center bg-primary text-white">
            Messages
        </div>
        <div class="card-body message-container"></div>

        <!-- User Input Prompt -->
        <div class="card-footer">
            <!-- Start/Restart Conversation Button -->
            <div id="start-conversation">
                <button class="btn btn-primary w-100" onclick="startConversation(6)">Start Conversation</button>
            </div>

            <!-- Message Prompt Container (Hidden initially) -->
            <div class="prompt-container" style="display: none;">
                <input type="text" id="pipeline-prompt-input" class="form-control" placeholder="Type your message..." aria-label="User prompt">
                <button class="btn btn-primary" type="button" onclick="sendMessage()">Send</button>
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots"></i>
                </button>
                <!-- Dropdown menu for options -->
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" type="button" onclick="clearChat()">Clear Chat</button></li>
                    <li><button class="dropdown-item" type="button" onclick="startConversation(6)">Restart Conversation</button></li>
                    <li><button class="dropdown-item" type="button">Enable AI Hints</button></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Modal for System and Tool Messages -->
<div class="modal fade" id="dynamicModal" tabindex="-1" aria-labelledby="dynamicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="dynamicModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="dynamicModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    const messageContainer = document.querySelector('.message-container');
    const sendButton = document.querySelector('.prompt-container .btn-primary');
    const userInput = document.querySelector('.prompt-container input');

    let currentConversationId = null;

    // Function to start or restart a conversation
    async function startConversation(pipelineId) {
        try {
            // Clear chat when restarting
            clearChat();

            // Fetch a new conversation ID from the server
            const response = await fetch(`/api/conversations/pipeline_create/${pipelineId}`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            currentConversationId = data.conversation_id;

            if (!appState.conversations[currentConversationId]) {
                appState.conversations[currentConversationId] = {};
            }

            // Display the initial prompt message
            addMessage('assistant', `Welcome to Rainbow Communications. How can I help you? For account specific information I will need The account Name or Address or Account number.`);

            // Hide the start button and show the message prompt input
            document.getElementById('start-conversation').style.display = 'none';
            document.querySelector('.prompt-container').style.display = 'flex';
        } catch (error) {
            console.error("Error starting conversation:", error);
            addMessage('system', "Failed to start a new conversation. Please try again.");
        }
    }

    // Function to create and add a new message to the message container
    function addMessage(role, messageText) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');

        if (role === 'assistant') {
            messageDiv.classList.add('assistant-message');
            messageDiv.innerHTML = `<div class="message-title">Assistant:</div><p>${messageText}</p>`;
        } else if (role === 'user') {
            messageDiv.classList.add('user-message');
            messageDiv.innerHTML = `<div class="message-title">User:</div><p>${messageText}</p>`;
        } else if (role === 'system') {
            messageDiv.classList.add('center-message');
            messageDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span class="message-title">System Message</span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showModal('System Message', '${messageText}')">
                    Show System Message
                </button>
            </div>`;
        } else if (role === 'tool') {
            messageDiv.classList.add('center-message');
            messageDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span class="message-title">Tool Message</span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showModal('Tool Message', '${messageText}')">
                    Show Tool Message
                </button>
            </div>`;
        }

        messageContainer.appendChild(messageDiv);
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    // Function to send a message
    function sendMessage() {
        const messageText = userInput.value.trim();
        if (messageText === '') return;

      //  addMessage('user', messageText);


        toggleSendButtonLoading(true);
        userInput.disabled = true;

        // Send the message to the backend
        fetch(`/api/conversations/${currentConversationId}/messages`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: messageText, conversation_id: currentConversationId })
        })
            .then(response => response.json())
            .then(data => {

                console.log(data);

                if (data.status === 'success') {

                    updateMessages(data.messages);
                    // Clear the message input
                    //document.getElementById('newMessage').value = '';
                    // Reload messages
                    //displayConversationMessages(data.messages);
                } else {
                    console.error('Error sending message:', data.message);
                }

            })
            .catch(err => console.error('Error sending message:', err))
            .finally(() => {
                // Re-enable the button and hide spinner
                toggleSendButtonLoading(false);
                userInput.disabled = false;
                userInput.value = '';
            });



    }
    // Function to update messages and prevent duplicates
    function updateMessages(messages) {
        messages.forEach(message => {
            const messageId = message.id;
            const conversationId = message.conversation_id;

            // Initialize storage if necessary
            if (!appState.conversations[conversationId]) {
                appState.conversations[conversationId] = {};
            }

            // Check if message is already stored
            if (!appState.conversations[conversationId][messageId]) {
                appState.conversations[conversationId][messageId] = message;  // Store the message
                addMessage(message.role, message.content);  // Display the message
            }
        });
    }
    // Toggle send button loading state
    function toggleSendButtonLoading(isLoading) {
        if (isLoading) {
            sendButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...`;
            sendButton.disabled = true;
        } else {
            sendButton.innerHTML = "Send";
            sendButton.disabled = false;
        }
    }

    // Function to show a modal with a given title and message
    function showModal(title, messageText) {
        const modal = new bootstrap.Modal(document.getElementById('dynamicModal'));
        document.getElementById('dynamicModalLabel').textContent = title;
        document.getElementById('dynamicModalBody').textContent = messageText;
        modal.show();
    }

    // Function to clear the chat
    function clearChat() {
        messageContainer.innerHTML = '';
        addMessage('system', "Chat has been cleared.");
    }


/*
    // Handle sending a new message
    document.getElementById('sendMessageBtn').addEventListener('click', function () {
        const messageContent = document.getElementById('newMessage').value.trim();
        if (!messageContent) {
            alert('Please enter a message!');
            return;
        }

        // Disable the button and show spinner
        toggleSendMessageButton(true);

        // Send the message to the backend
        fetch(`/api/conversations/${conversationId}/messages`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: messageContent, conversation_id: conversationId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Clear the message input
                    document.getElementById('newMessage').value = '';
                    // Reload messages
                    displayConversationMessages(data.messages);
                } else {
                    console.error('Error sending message:', data.message);
                }
            })
            .catch(err => console.error('Error sending message:', err))
            .finally(() => {
                // Re-enable the button and hide spinner
                toggleSendMessageButton(false);
            });
    });

*/





</script>


















<div>
    <div id="show_div">

    </div>
</div>

<script>
    //loadPipelines();

    show_div = document.getElementById('show_div');

    console.log(appState.data.pipelines);
    console.log(appState.current_id);

    current_id = appState.current_id;
    let found_pipeline = null;
    //const result = appState.data.pipelines.find(item => item.id === appState.current_id);

    console.log(current_id);

    pipelines.forEach(pipeline => {

        console.log('pipeline id',pipeline.id);

        // Check if this pipeline has the target ID
        if (Number(pipeline.id) === Number(current_id)) {

            found_pipeline = pipeline;
            // Optionally, you can break out of forEach by returning
            return;

        }

    });

    console.log(found_pipeline);
    let pipeline = found_pipeline;

    const pipelineCard = document.createElement('div');
    pipelineCard.classList.add('card', 'mb-3');
    pipelineCard.innerHTML = `
    <div class="card-header">
        <h5 class="mb-1">${pipeline.name}</h5>
        <p class="mb-0 text-muted">${pipeline.description || 'No description available'}</p>
    </div>
    <div class="card-body">

        <ul class="list-group list-group-flush" id="stages-${pipeline.id}">
            ${pipeline.stages.map(stage => renderShowStage(stage)).join('')}
        </ul>
    </div>
`;
    show_div.appendChild(pipelineCard);

    function renderShowStage(stage) {
        console.log('rendering stage', stage);

        // Get assistant names and success tool
        const assistantsNames = stage.assistants.map(assistant => assistant.name).join(', ') || '<span class="text-muted">No Assistants</span>';
        const successToolName = stage.success_tool ? stage.success_tool.name : '<span class="text-muted">No Success Tool</span>';

        // Generate file list HTML
        const filesList = stage.files.length > 0
            ? stage.files.map(file => `<li><a href="/public/${file.file_path}" target="_blank">${file.file_path.split('/').pop()}</a></li>`).join('')
            : '<li class="text-muted">No Files Available</li>';

        return `
    <li class="list-group-item" style="border: 1px solid black">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4><span>Stage: ${stage.order}</span> <strong>${stage.name}</strong> <small class="text-muted">(${stage.type})</small></h4>
                <div class="text-muted"><strong>Success Tool:</strong> ${successToolName}</div>
            </div>
        </div>

        <ul class="list-group list-group-flush ms-3" id="assistants-${stage.id}">
            <li><strong>Assistants:</strong> ${assistantsNames}</li>
            ${stage.assistants.map(assistant => renderShowStageAssistant(assistant)).join('')}
        </ul>

        <div class="mt-3">
            <strong>Files:</strong>
            <ul class="list-unstyled ms-3">
                ${filesList}
            </ul>
        </div>
    </li>
    `;
    }


    function renderShowStageAssistant(assistant) {


        console.log(assistant);

        const toolNames = assistant.tools.map(tool => tool.name).join(', ') || '<span class="text-muted">No Tools</span>';

        console.log(assistant);
        return `
    <li class="list-group-item border-0 ps-0">
<div class="card mb-3">
    <div class="card-body">
        <!-- Assistant Header -->
        <div class="d-flex align-items-center mb-2">
            <h5 class="mb-0 me-2">Assistant:</h5>
            <h5 class="mb-0">${assistant.name}</h5>
            <small class="text-muted ms-2">(${assistant.type})</small>
        </div>

        <!-- Tools Section -->
        <div class="mb-3">
            <strong>Tools:</strong> <span class="text-muted">${toolNames || 'No Tools Assigned'}</span>
        </div>

        <!-- System Message Section -->
        <div class="mb-3">
            <strong>System Message:</strong>
            <pre class="bg-light p-2 rounded">${assistant.system_message}</pre>
        </div>

        <!-- Pivot Information Section -->
        <div>
            <p class="mb-0"><strong>Success Stage ID:</strong> <span class="text-muted">${assistant.pivot.success_stage_id || 'N/A'}</span></p>
            <p class="mb-0"><strong>Success Tool ID:</strong> <span class="text-muted">${assistant.pivot.success_tool_id || 'N/A'}</span></p>
        </div>
    </div>
</div>

    </li>
    `;
    }


</script>
