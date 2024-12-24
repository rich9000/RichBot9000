<div class="container">







    <style>
        .assistant-message {
            background-color: #007bff; /* Bootstrap primary color */
            color: white;
            text-align: left;
        }

        .user-message {
            background-color: #6cffff; /* Bootstrap secondary color */
            text-align: right;
        }
    </style>

    <div class="card col-md-6" id="threads-div">
        Threads Div
    </div>

    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#createThread" aria-expanded="false" aria-controls="createThread">
        Create Thread
    </button>

    <div class="card col-md-6 collapse" id="createThread">
        <div class="card-header">Create New Thread</div>
        <div class="card-body" id="CreateThread">
            <div class="card">
                <div class="card-header">Instructions</div>
                <div class="card-body">
                    <textarea id="instructions" class="form-control" rows="5" placeholder="This text will be sent if/when the new thread is created."></textarea>
                </div>
            </div>
            <div class="mt-2">
                <button id="createThreadBtn" class="btn btn-primary btn-sm">Create Thread</button>
            </div>
        </div>
    </div>

    <div class="card mt-2" id="chat-div">
        <div class="card-body">
            <div id="chat-text-card" class="mb-3">
                <div id="chat-text-div" class="p-3 bg-light rounded" style="height: 300px; overflow-y: auto;">
                    <!-- Messages will be dynamically injected here -->
                </div>
            </div>

            <div class="mb-3">
                <label for="Prompt" class="form-label">Prompts:</label>
                <textarea id="Prompt" class="form-control" rows="3"></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button id="sendRequestBtn" class="btn btn-primary btn-sm">Send Request</button>
                <div class="d-flex align-items-center">
                    <label for="assistant-select" class="me-2 mb-0">Select Assistant:</label>
                    <select id="assistant-select" class="form-select form-select-sm">
                        <option>Loading...</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function sendMessage(requestData) {
        const sendRequestBtn = document.getElementById('sendRequestBtn');
        sendRequestBtn.classList.add('btn-loading');
        sendRequestBtn.innerHTML = 'Processing...';

        fetch('/api/openai/send-message', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
            .then(response => response.json())
            .then(data => {
                sendRequestBtn.classList.remove('btn-loading');
                sendRequestBtn.innerHTML = 'Send Request';
                displayMessages(data.messages);
            })
            .catch(() => {
                alert('Failed to send the message.');
            });
    }

    function displayMessages(messages) {
        const chatTextDiv = document.getElementById('chat-text-div');
        chatTextDiv.innerHTML = ''; // Clear existing messages

        messages.forEach(message => {
            const messageContent = message.content.map(content => content.text.value).join('\n');
            let messageElement;

            if (message.role === 'assistant') {
                messageElement = `
                    <div class="message text-end">
                        <div class="assistant-message p-2 rounded">
                            <strong class="d-block">${message.role}</strong>
                            <span>${messageContent}</span>
                        </div>
                    </div>
                `;
            } else if (message.role === 'user') {
                messageElement = `
                    <div class="message text-start">
                        <div class="user-message p-2 rounded">
                            <strong class="d-block text-end">${message.role}</strong>
                            <span>${messageContent}</span>
                        </div>
                    </div>
                `;
            }

            chatTextDiv.innerHTML += messageElement;
        });
    }

    function updateChatDisplay() {
        const chatDiv = document.getElementById('chat-div');
        const threadsDiv = document.getElementById('threads-div');

        if (!appState.current_thread) {
            chatDiv.classList.add('hidden');
            threadsDiv.classList.add('hidden');
        } else {
            chatDiv.classList.remove('hidden');
            threadsDiv.classList.remove('hidden');
        }

        if (appState.threads.length > 0) {
            displayThreadList();
        }
    }

    function displayThreadList() {
        const threadsDiv = document.getElementById('threads-div');
        threadsDiv.classList.remove('hidden');

        let threadListHtml = '<ul class="list-group mt-2">';
        appState.threads.forEach((thread, index) => {
            let buttonHtml;
            if (appState.current_thread === thread) {
                buttonHtml = '<span class="text-muted">Is Current</span>';
            } else {
                buttonHtml = `<button class="btn btn-sm btn-link" onclick="setCurrentThread(${index})">Select</button>`;
            }

            threadListHtml += `
                <li class="d-flex justify-content-between align-items-center">
                    Thread ${index + 1}: ${thread} ${buttonHtml}
                </li>
            `;
        });
        threadListHtml += '</ul>';
        threadsDiv.innerHTML = threadListHtml;
    }

    function createThread(requestData) {
        return fetch('/api/openai/create-thread', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
            .then(response => response.json())
            .then(data => {
                appState.current_thread = data.thread_id;
                appState.threads.push(data.thread_id);
                localStorage.setItem('app_state', JSON.stringify(appState));
                updateChatDisplay();
                displayThreadList();
                return data;
            })
            .catch(err => Promise.reject(err));
    }


        document.getElementById('createThreadBtn').addEventListener('click', function () {
            const selectedAssistant = document.getElementById('assistant-select').value;
            const instructions = document.getElementById('instructions').value;

            const requestData = {
                files: [], // You can populate this with file selections if needed
                assistant: selectedAssistant,
                instructions: instructions,
            };

            createThread(requestData)
                .then(data => {
                    console.log('Thread created successfully:', data);
                })
                .catch(error => {
                    console.error('Error creating thread:', error);
                });
        });

        document.getElementById('sendRequestBtn').addEventListener('click', function () {
            const prompt = document.getElementById('Prompt').value;
            const selectedAssistant = document.getElementById('assistant-select').value;
            const threadId = appState.current_thread;

            if (!threadId) {
                alert('Please create or select a thread first.');
                return;
            }

            const requestData = {
                thread_id: threadId,
                prompt: prompt,
                assistant: selectedAssistant,
            };

            sendMessage(requestData);
        });

        updateChatDisplay();
        loadAssistants();


</script>
