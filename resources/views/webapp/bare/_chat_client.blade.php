<div class="container-fluid">
    <!-- Assistant Selection Form -->
    <div class="card mb-4" id="assistantSelectCard">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Assistant Chat</h5>
        </div>
        <div class="card-body">
            <form id="assistantChatForm">
                <div class="mb-3">
                    <label class="form-label">Select Assistant</label>
                    <select class="form-select" id="assistantSelect">
                        <!-- Assistants will be loaded dynamically -->
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="voiceEnabled">
                    <label class="form-check-label" for="voiceEnabled">
                        Enable Voice Chat
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-comments me-2"></i>Start Chat
                </button>
            </form>
        </div>
    </div>

    <!-- Chat Interface Container -->
    <div id="chatContainer" style="display: none;">
        <button class="btn btn-outline-secondary mb-3" id="backButton">
            <i class="fas fa-arrow-left me-2"></i>Back
        </button>
        <div id="chatInterface"></div>
        
        <!-- Debug Area -->
        <div class="card mt-3">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#debugArea" 
                 style="cursor: pointer;">
                <h5 class="mb-0">
                    <i class="fas fa-bug me-2"></i>Debug Area
                </h5>
                <span class="badge bg-secondary" id="richbot-debug-message-count">0 messages</span>
            </div>
            <div class="collapse" id="debugArea">
                <div class="card-body">
                    <!-- Tools Section -->
                    <div class="mb-3">
                        <h6 class="border-bottom pb-2">Available Tools</h6>
                        <div id="richbot-available-tools" class="list-group">
                            <!-- Tools will be listed here -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="richbot-debug-filter" placeholder="Filter messages...">
                            <button class="btn btn-outline-secondary" type="button" id="richbot-clear-debug-filter">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="debug-messages" id="richbot-debug-messages" style="max-height: 400px; overflow-y: auto;">
                        <!-- Debug messages will be added here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    // Load assistants into select
    function getAssistantSelect() {
        return document.getElementById('assistantSelect');
    }
    
    // Force refresh assistants data
    fetch('/api/user_assistants', {
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        appState.data.assistants = data.assistants;
        const select = getAssistantSelect();
        select.innerHTML = '<option value="">Select an Assistant</option>';
        data.assistants.forEach(assistant => {
            const option = document.createElement('option');
            option.value = assistant.id;
            option.textContent = `${assistant.name} (${assistant.model?.name || 'No Model'})`;
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Error loading assistants:', error);
        showAlert('Error loading assistants. Please try again.', 'danger');
    });

    // Handle form submission
    document.getElementById('assistantChatForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        const assistantId = document.getElementById('assistantSelect').value;
        if (!assistantId) {
            alert('Please select an assistant');
            return;
        }

        // Hide selection form and show chat interface
        document.getElementById('assistantSelectCard').style.display = 'none';
        document.getElementById('chatContainer').style.display = 'block';

        // Initialize RichbotClient
        window.richbotClient = new RichbotClient('chatInterface', {
            wsUrl: `${window.appConfig.wsUrlAlt}/webclient`,
            apiToken: appState.apiToken,
            assistantId: assistantId,
            autoConnect: true,
            autoStartRecording: true,
            initialVolume: 1.0,
            showFormControls: true,
            showChatLog: true
        });

        // Ensure chat section is visible
        const chatSection = document.getElementById('richbot-chatInterface-chat-section');
        if (chatSection) {
            chatSection.style.display = 'block';
        }
    });

    // Handle back button
    document.getElementById('backButton').addEventListener('click', () => {
        if (window.richbotClient) {
            window.richbotClient.disconnect();
            window.richbotClient = null;
        }
        document.getElementById('assistantSelectCard').style.display = 'block';
        document.getElementById('chatContainer').style.display = 'none';
        document.getElementById('assistantChatForm').reset();
    });

</script> 