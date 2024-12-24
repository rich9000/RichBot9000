
    <h3>AI Assistant Chat</h3>
    <style>
        .hidden { display: none; }
        .user-message {
            background-color: #e1f5fe;
            padding: 10px;
            border-radius: 5px;
            margin: 5px;
            display: block;
            text-align: right;
            max-width: 60vw;
            unicode-bidi: embed;
            font-family: monospace;
            white-space: pre;
        }
        .assistant-message {
            max-width: 60vw;
            text-align: left;
            display: block;
            unicode-bidi: embed;
            font-family: monospace;
            white-space: pre;
            background-color: #f3e5f5;
            padding: 10px;
            border-radius: 5px;
            margin: 5px;
        }
        #chatBox {
            border: 1px solid #ddd;
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 10px;
        }
        #messageSection { margin-top: 20px; }
        /* File Tree Styles */
        #file-tree-container ul {
            list-style-type: none;
            padding-left: 20px;
        }
        #file-tree-container li::before {
            content: '📂 ';
        }
        #file-tree-container li.file::before {
            content: '📄 ';
        }
        #file-tree-container .nested {
            display: none;
        }
        #file-tree-container .active {
            display: block;
        }
        #file-tree-container .caret {
            cursor: pointer;
            user-select: none;
        }
        #file-tree-container .caret::before {
            content: "▶ ";
            color: black;
            display: inline-block;
            margin-right: 6px;
        }
        #file-tree-container .caret-down::before {
            transform: rotate(90deg);
        }
        /* Style for checkboxes */
        #file-tree-container input[type="checkbox"] {
            margin-right: 5px;
        }
    </style>

    <!-- Assistant Selection Modal -->
    <div id="assistantSelectionModal" class="card">
        <div class="card-body">
            <h2>Select an Assistant</h2>
            <select id="assistant-select" class="form-control">
                <option value="">Select an assistant</option>
                <option value="default">Default Assistant</option>
                <option value="task_manager">Task Manager</option>
                <option value="project_manager">Project Manager</option>
                <!-- Add other assistant types as needed -->
            </select>

            <h3>Initial Instructions</h3>
            <textarea id="initialInstructions" class="form-control" rows="4">You are a helpful assistant. Help the user with their request.</textarea>

            <h3>File Tree Select</h3>
            <div id="file-tree-container">
                <!-- Implement file tree selection as needed -->
                <!-- Example placeholder -->

            </div>

            <button id="confirmAssistantButton" class="btn btn-primary">Confirm</button>
        </div>
    </div>

    <!-- Message Section -->
    <div id="messageSection" class="hidden">
        <div id="displayAssistant"></div>

        <div id="chatBox"></div>
        <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." />
        <button id="sendMessageButton" class="btn btn-primary">Send Message</button>

        <button id="getMessageButton" class="btn btn-primary">Get Messages</button>
    </div>

    <!-- Additional Sections -->
    <div class="row">
        <div class="card col-6 p-0 m-2">
            <div class="card-header">
                Prompt Display
            </div>
            <div class="card-body" id="prompt_display">
                <!-- Content will be dynamically loaded -->
            </div>
        </div>
        <div class="card col-4 p-0 m-2">
            <div class="card-header">
                Thread Info - <button id="updateThreadInfoButton" class="btn btn-secondary">Update Thread Info</button>
            </div>

            <div id="threadInfo"></div>
        </div>
    </div>

    <!-- JavaScript Section -->
    <script>




        loadAssistants();
        loadAssistantFiles('file-tree-container');




        // Function to display thread info (if needed)
        function displayThreadInfo(runs) {
            const threadInfoDiv = document.getElementById('threadInfo');
            threadInfoDiv.innerHTML = ''; // Clear previous content

            runs.forEach(run => {
                const runDiv = document.createElement('div');
                runDiv.innerHTML = `
            <strong>Run ID:</strong> ${run.id}<br>
            <strong>Status:</strong> ${run.status}<br>
            <strong>Required Action:</strong> ${run.required_action ? JSON.stringify(run.required_action) : 'None'}
            <br/><strong>Last Error:</strong> ${run.last_error ? run.last_error.message : 'None'}
            <hr>
        `;
                threadInfoDiv.appendChild(runDiv);
            });
        }

        // Event listener for updating thread info
        document.getElementById('updateThreadInfoButton').addEventListener('click', () => {
            const threadId = appState.current_thread;
            if (threadId) {
                fetch('/api/openai/thread-info?thread_id=' + threadId, {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + appState.apiToken,
                        'Accept': 'application/json',
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.runs) {
                            displayThreadInfo(data.runs);
                        } else {
                            alert('Failed to get thread info.');
                        }
                    })
                    .catch(err => {
                        console.error('Failed to fetch thread info:', err);
                        alert('An error occurred while fetching thread info.');
                    });
            } else {
                alert('No current thread.');
            }
        });

        // Function to fetch and display content (if needed)
        function fetchContent() {
            const fileName = '/richbotdisplay/displays/prompt_display.html';

            if (fileName) {
                fetch(fileName)
                    .then(response => {
                        if (response.ok) {
                            return response.text();
                        }
                        throw new Error('Network response was not ok.');
                    })
                    .then(data => {
                        document.getElementById('prompt_display').innerHTML = data;
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                    });
            }
        }

        setInterval(fetchContent, 10000); // Fetch content every 10 seconds
        setInterval(() => {
            if (appState.ollama_conversation) {
                fetchMessages(appState.ollama_conversation);
            }
        }, 30000); // Fetch messages every 30 seconds

        fetchContent();

        // Function to load available assistants (if needed)
        function loadAssistants() {
            // Implement if you have dynamic assistant loading
        }

        // Function to load assistant files (if needed)
        function loadAssistantFiles() {
            // Implement if you have dynamic file loading
        }

        // Display the message section if a conversation exists
        if (appState.ollama_assistant && appState.ollama_conversation) {
            document.getElementById('messageSection').classList.remove('hidden');
            document.getElementById('assistantSelectionModal').classList.add('hidden');
            document.getElementById('displayAssistant').innerHTML = `Selected Assistant: ${appState.ollama_assistant_name} (ID: ${appState.ollama_assistant}) (Conversation ID: ${appState.ollama_conversation})` + ' <button id="resetConversationButton" class="btn btn-danger">Reset Conversation</button>';

            // Add event listener for reset button
            document.getElementById('resetConversationButton').addEventListener('click', resetConversation);
        }

        // Handle assistant confirmation
        document.getElementById('confirmAssistantButton').addEventListener('click', () => {
            const assistantSelect = document.getElementById('assistant-select');
            const assistantType = assistantSelect.value;
            const assistantName = assistantSelect.options[assistantSelect.selectedIndex].text;
            const instructions = document.getElementById('initialInstructions').value;

            if (!assistantType) {
                alert('Please select an assistant.');
                return;
            }

            // Get selected files from the file tree (if needed)
            const selectedFiles = Array.from(document.querySelectorAll('#file-tree-container input[type="checkbox"]:checked')).map(el => el.value);

            // Create a new conversation via API
            fetch('/api/conversations/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                },
                body: JSON.stringify({
                    assistant_type: assistantType,
                    title: 'New Conversation',
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        appState.ollama_conversation = data.conversation_id;
                        appState.ollama_assistant = assistantType;
                        appState.ollama_assistant_name = assistantName;
                        appState.ollama_messages = [];
                        localStorage.setItem('app_state', JSON.stringify(appState));

                        // Update the UI
                        document.getElementById('assistantSelectionModal').classList.add('hidden');
                        document.getElementById('messageSection').classList.remove('hidden');
                        document.getElementById('displayAssistant').innerHTML = `Selected Assistant: ${assistantName} (ID: ${assistantType}) (Conversation ID: ${appState.ollama_conversation})` + ' <button id="resetConversationButton" class="btn btn-danger">Reset Conversation</button>';

                        // Add event listener for reset button
                        document.getElementById('resetConversationButton').addEventListener('click', resetConversation);

                        // Optionally, send initial instructions as the first message
                        if (instructions.trim()) {
                            sendMessage(instructions);
                        }

                        // Handle tool-specific initial actions based on selected files (if needed)
                        // Example:
                        // if (selectedFiles.length > 0) {
                        //     // Perform actions based on selected files
                        // }
                    } else {
                        alert('Failed to create conversation: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the conversation.');
                });
        });

        // Function to send a message
        function sendMessage(messageContent) {
            if (!appState.ollama_conversation) {
                alert('No active conversation.');
                return;
            }

            fetch('/api/conversations/send-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                },
                body: JSON.stringify({
                    conversation_id: appState.ollama_conversation,
                    message: messageContent,
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Append user's message
                        appendMessageToChat('user', messageContent);
                        // Append assistant's response
                        appendMessageToChat('assistant', data.message);
                        // Clear input
                        document.getElementById('messageInput').value = '';
                        // Update appState
                        appState.ollama_messages.push({ role: 'user', content: messageContent });
                        appState.ollama_messages.push({ role: 'assistant', content: data.message });
                        localStorage.setItem('app_state', JSON.stringify(appState));
                    } else {
                        alert('Failed to send message: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while sending the message.');
                });
        }

        // Handle send message button click
        document.getElementById('sendMessageButton').addEventListener('click', () => {
            const messageContent = document.getElementById('messageInput').value.trim();
            if (messageContent) {
                sendMessage(messageContent);
            }
        });

        // Function to append messages to the chat box
        function appendMessageToChat(sender, messageContent) {
            const chatBox = document.getElementById('chatBox');
            const messageDiv = document.createElement('div');
            messageDiv.classList.add(sender === 'user' ? 'user-message' : 'assistant-message');
            messageDiv.innerHTML = messageContent;
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight; // Scroll to the bottom
        }

        // Function to reset the conversation
        function resetConversation() {
            if (confirm('Are you sure you want to reset the conversation? This will delete the current conversation and its messages.')) {
                // Reset appState
                appState.ollama_conversation = null;
                appState.ollama_assistant = null;
                appState.ollama_assistant_name = null;
                appState.ollama_messages = [];
                localStorage.removeItem('app_state');

                // Clear the chat box
                document.getElementById('chatBox').innerHTML = '';

                // Show the assistant selection modal
                document.getElementById('messageSection').classList.add('hidden');
                document.getElementById('assistantSelectionModal').classList.remove('hidden');

                // Clear assistant display
                document.getElementById('displayAssistant').innerHTML = '';
            }
        }

        // Load appState from localStorage on page load
        window.addEventListener('load', () => {
            const savedState = localStorage.getItem('app_state');
            if (savedState) {
                appState = JSON.parse(savedState);
                if (appState.ollama_conversation && appState.ollama_assistant_name) {
                    // Update the UI
                    document.getElementById('assistantSelectionModal').classList.add('hidden');
                    document.getElementById('messageSection').classList.remove('hidden');
                    document.getElementById('displayAssistant').innerHTML = `Selected Assistant: ${appState.ollama_assistant_name} (ID: ${appState.ollama_assistant}) (Conversation ID: ${appState.ollama_conversation})` + ' <button id="resetConversationButton" class="btn btn-danger">Reset Conversation</button>';

                    // Add event listener for reset button
                    document.getElementById('resetConversationButton').addEventListener('click', resetConversation);

                    // Load previous messages
                    appState.ollama_messages.forEach(message => {
                        appendMessageToChat(message.role, message.content);
                    });
                }
            }
        });

        // Optionally, implement fetchMessages to periodically fetch new messages
        function fetchMessages(conversationId) {
            fetch('/api/conversations/get-messages?conversation_id=' + conversationId, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear existing messages
                        document.getElementById('chatBox').innerHTML = '';
                        appState.ollama_messages = [];

                        data.messages.forEach(message => {
                            appendMessageToChat(message.role, message.content);
                            appState.ollama_messages.push({ role: message.role, content: message.content });
                        });

                        localStorage.setItem('app_state', JSON.stringify(appState));
                    } else {
                        console.error('Failed to fetch messages:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching messages:', error);
                });
        }

        // Periodically fetch new messages every 30 seconds
        setInterval(() => {
            if (appState.ollama_conversation) {
                fetchMessages(appState.ollama_conversation);
            }
        }, 30000);
    </script>

