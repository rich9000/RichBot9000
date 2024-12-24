<div class="container mt-3">
    <h3 class="mt-4">Messages</h3>

    <!-- Conversation Messages Section -->
    <div id="conversation-messages" class="conversation-messages">
        <!-- Conversation messages will be loaded here -->
    </div>

    <!-- Form to Send a New Message -->
    <div class="message-form mt-4">
        <textarea id="newMessage" class="form-control" rows="3" placeholder="Type your message here..."></textarea>
        <button id="sendMessageBtn" class="btn btn-primary mt-2">
            <span id="sendMessageBtnText">Send Message</span>
            <span id="sendMessageSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
    </div>

    <!-- Conversation Details Section -->
    <div id="conversation-details" class="conversation-details mt-4">
        <!-- Conversation details will be loaded here -->
    </div>
</div> <style>
    .file-browser-card {
        max-height: 600px;
        overflow-y: auto;
    }
    .directory, .file {
        margin-left: 20px;
    }
    .directory-name, .file-name {
        cursor: pointer;
        margin-left: 8px;
        display: flex;
        align-items: center;
    }
    .nested {
        margin-left: 20px;
        display: block;
    }
    .hidden {
        display: none;
    }
    .folder-icon {
        margin-right: 5px;
    }
</style>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">File Browser</h5>
        </div>
        <div class="card-body file-browser-card">
            <div id="file-browser"></div>
        </div>
    </div>
</div>

<script>

    const token = appState.apiToken;  // Replace with the actual token

    async function fetchDirectoryContents(path = '/', container = document.getElementById('file-browser')) {
        const response = await fetch(`/api/files?path=${encodeURIComponent(path)}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            console.error("Failed to fetch directory contents");
            return;
        }

        const data = await response.json();
        displayDirectoryContents(data, path, container);
    }

    function displayDirectoryContents(data, path, container) {
        const dirContainer = document.createElement('div');
        dirContainer.classList.add('directory-container');

        data.directories.forEach(directory => {
            const dirElement = document.createElement('div');
            dirElement.className = 'directory';

            dirElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="file-checkbox me-2" data-path="${directory}">
                    <span class="directory-name d-flex align-items-center" data-path="${directory}">
                        <i class="fas fa-folder folder-icon me-2"></i> ${directory}
                    </span>
                </div>
            `;

            dirElement.querySelector('.directory-name').addEventListener('click', function () {
                // Clear any existing sub-directory content
                const existingSubDirContainer = dirElement.querySelector('.nested');
                if (existingSubDirContainer) {
                    existingSubDirContainer.remove();
                }

                // Create a new sub-directory container and fetch new contents
                const subDirContainer = document.createElement('div');
                subDirContainer.classList.add('nested', 'directory-container');
                fetchDirectoryContents(directory, subDirContainer);
                dirElement.appendChild(subDirContainer);
            });

            dirContainer.appendChild(dirElement);
        });

        data.files.forEach(file => {
            const fileElement = document.createElement('div');
            fileElement.className = 'file';
            fileElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="file-checkbox me-2" data-path="${file}">
                    <span class="file-name d-flex align-items-center" data-path="${file}">
                        <i class="fas fa-file-alt me-2"></i> ${file}
                    </span>
                </div>
            `;

            fileElement.querySelector('.file-name').addEventListener('click', async function () {
                const filePath = this.getAttribute('data-path');
                await previewFile(filePath);
            });

            dirContainer.appendChild(fileElement);
        });

        container.appendChild(dirContainer);
    }

    async function previewFile(filePath) {
        const response = await fetch(`/api/download?file=${encodeURIComponent(filePath)}`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            console.error("Failed to download file");
            return;
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const filePreviewWindow = window.open();
        filePreviewWindow.document.write(`<iframe src="${url}" style="width:100%; height:100%;" frameborder="0"></iframe>`);
    }

    fetchDirectoryContents('/');
</script>

<script>
    // Initialize variables
    const conversationId = appState.current_conversation_id;
    const apiToken = appState.apiToken;

    if (!conversationId) {
        alert('No conversation ID found!');
    } else {
        fetchConversationDetails();
        fetchConversationMessages();
    }

    // Fetch conversation details
    function fetchConversationDetails() {
        fetch(`/api/conversations/${conversationId}`, {
            headers: {
                'Authorization': 'Bearer ' + apiToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => displayConversationDetails(data))
            .catch(err => console.error('Error fetching conversation details:', err));
    }

    // Fetch conversation messages
    function fetchConversationMessages() {
        fetch(`/api/conversations/${conversationId}/messages`, {
            headers: {
                'Authorization': 'Bearer ' + apiToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => displayConversationMessages(data.messages))
            .catch(err => console.error('Error fetching conversation messages:', err));
    }

    // Function to display conversation details
    function displayConversationDetails(data) {
        const detailsContainer = document.getElementById('conversation-details');
        if (!data) {
            detailsContainer.innerHTML = '<p>No details available for this conversation.</p>';
            return;
        }

        // Display conversation details
        detailsContainer.innerHTML = `
            <div class="row">
                <!-- Conversation Details Column -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            Conversation: ${data.id || 'N/A'}
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th scope="row" class="w-25">Title</th>
                                        <td>${data.title || 'N/A'}</td>
                                    </tr>
 <tr>
                                <th scope="row">Type</th>
                                <td>${data.type || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Status</th>
                                <td>${data.status || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Model</th>
                                <td>${data.model || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Assistant</th>
                                <td>${data.assistant || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Pipeline</th>
                                <td>${data.pipeline || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th scope="row">Conversation System Message</th>
                                <td>${data.system_message || 'N/A'}</td>
                            </tr>
                                    <!-- Add other fields as necessary -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Active Tools Column -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            Active Tools
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group" id="active-tools-list">
                                <!-- Tools will be added here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add active tools to the list
        const toolsList = document.getElementById('active-tools-list');
        if (data.active_tools && data.active_tools.length > 0) {
            data.active_tools.forEach(tool => {
                const toolItem = document.createElement('li');
                toolItem.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-start');
                toolItem.innerHTML = `
                    <div>
                        <div class="fw-bold">${tool.name}</div>
                        <small class="text-muted">${tool.description}</small>
                    </div>
                    <span class="badge bg-secondary">${tool.id}</span>
                `;
                toolsList.appendChild(toolItem);
            });
        } else {
            toolsList.innerHTML = '<li class="list-group-item">No active tools available.</li>';
        }
    }

    // Function to display conversation messages
    function displayConversationMessages(messages) {
        const messagesContainer = document.getElementById('conversation-messages');
        messagesContainer.innerHTML = ''; // Clear existing messages

        if (!messages || messages.length === 0) {
            messagesContainer.innerHTML = '<p>No messages in this conversation.</p>';
            return;
        }

        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message-item', 'p-3', 'mb-2', 'rounded');

            // Apply styles based on message role
            if (message.role === 'user') {
                messageDiv.classList.add('bg-light', 'border', 'text-end');
                messageDiv.innerHTML = `
                    <p class="mb-1"><strong>User:</strong> ${message.content}</p>
                    <p class="text-muted mb-0"><small>${message.created_at}</small></p>
                `;
            } else if (message.role === 'assistant') {
                messageDiv.classList.add('bg-info', 'text-white');
                messageDiv.innerHTML = `
                    <p class="mb-1"><strong>Assistant:</strong> ${message.content}</p>
                    <p class="text-white-50 mb-0"><small>${message.created_at}</small></p>
                `;
            } else if (message.role === 'system') {
                messageDiv.classList.add('bg-warning', 'text-dark', 'border');
                messageDiv.innerHTML = `
                    <p class="mb-1"><strong>System:</strong> ${message.content}</p>
                    <p class="text-muted mb-0"><small>${message.created_at}</small></p>
                `;
            }

            messagesContainer.appendChild(messageDiv);
        });
    }

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

    // Function to toggle the send message button state
    function toggleSendMessageButton(isSending) {
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const sendMessageBtnText = document.getElementById('sendMessageBtnText');
        const sendMessageSpinner = document.getElementById('sendMessageSpinner');

        if (isSending) {
            sendMessageBtn.disabled = true;
            sendMessageBtnText.textContent = 'Sending...';
            sendMessageSpinner.classList.remove('d-none');
        } else {
            sendMessageBtn.disabled = false;
            sendMessageBtnText.textContent = 'Send Message';
            sendMessageSpinner.classList.add('d-none');
        }
    }
</script>
