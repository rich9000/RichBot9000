<style>
    /* Button styles */
    .record-btn {
        width: 100px;
        height: 40px;
        font-size: 16px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s, box-shadow 0.3s;
    }

    .btn-start {
        background-color: green;
    }

    .btn-stop {
        background-color: #b22222; /* Muted red color */
        box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.4);
        animation: pulse-red 1s infinite;
    }

    .btn-play {
        background-color: blue;
    }

    .btn-stop-playing {
        background-color: #1e90ff; /* Light blue color */
        box-shadow: 0 0 0 0 rgba(30, 144, 255, 0.4);
        animation: pulse-blue 1s infinite;
    }

    .btn-camera {
        background-color: purple;
    }

    .btn-stop-camera {
        background-color: #9932cc; /* Light purple color */
        box-shadow: 0 0 0 0 rgba(153, 50, 204, 0.4);
        animation: pulse-purple 1s infinite;
    }

    /* Pulse effect for different buttons */
    @keyframes pulse-red {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.4);
        }
        50% {
            box-shadow: 0 0 10px 5px rgba(255, 0, 0, 0.6);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.4);
        }
    }

    @keyframes pulse-blue {
        0% {
            box-shadow: 0 0 0 0 rgba(30, 144, 255, 0.4);
        }
        50% {
            box-shadow: 0 0 10px 5px rgba(30, 144, 255, 0.6);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(30, 144, 255, 0.4);
        }
    }

    @keyframes pulse-purple {
        0% {
            box-shadow: 0 0 0 0 rgba(153, 50, 204, 0.4);
        }
        50% {
            box-shadow: 0 0 10px 5px rgba(153, 50, 204, 0.6);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(153, 50, 204, 0.4);
        }
    }

    .spinner-border {
        position: absolute;
        right: 10px;
        width: 1.5rem;
        height: 1.5rem;
        visibility: hidden;
    }

    .webcam-container {
        margin-top: 20px;
        display: flex;
        justify-content: center;
    }

    .webcam-preview {
        display: none;
        width: 320px;
        height: 240px;
        border: 1px solid #ccc;
    }


    .btn-push-to-talk {
        background-color: orange;
    }

    .btn-push-to-talk-active {
        background-color: darkorange;
        box-shadow: 0 0 10px 5px rgba(255, 165, 0, 0.6);
    }
    .tools-checkbox-group {
        max-height: 300px; /* Adjust the height as needed */
        overflow-y: auto;  /* Enable vertical scrolling */
        border: 1px solid #ccc; /* Optional: border for visibility */
        padding: 10px;     /* Optional: padding for better layout */
        margin-top: 5px;   /* Optional: space from label */
    }
</style>


<div id="conversations"></div>


<div class="container mt-5">
    <h3>Create a New Conversation</h3>
    <form id="conversationForm">
        <!-- Title -->
        <div class="form-group">
            <label for="conversationTitle">Title</label>
            <input type="text" class="form-control" id="conversationTitle" name="title" required>
        </div>

        <!-- Type -->
        <div class="form-group">
            <label for="conversationType">Type</label>
            <select class="form-control" id="conversationType" name="type" required>
                <option value="prompt">Prompt</option>
                <option value="realtime">Realtime</option>
                <option value="action">Action</option>
            </select>
        </div>

        <!-- Model -->
        <div class="form-group">
            <label for="modelSelect">Model</label>
            <select class="form-control" id="modelSelect" name="model_id" required>
                <option value="">Loading models...</option>
            </select>
        </div>

        <!-- Assistant -->
        <div class="form-group">
            <label for="assistantSelect">Assistant</label>
            <select class="form-control" id="assistantSelect" name="assistant_id">
                <option value="">Loading assistants...</option>
            </select>
        </div>

        <!-- Pipeline -->
        <div class="form-group">
            <label for="pipelineSelect">Pipeline</label>
            <select class="form-control" id="pipelineSelect" name="pipeline_id">
                <option value="">Loading pipelines...</option>
            </select>
        </div>

        <!-- Active Tools (updated for checkboxes with tooltips) -->
        <div class="form-group">
            <label for="toolsContainer">Active Tools - Additional to Assistant and Pipeline tools. </label>
            <div id="toolsContainer" class="tools-checkbox-group">
                <!-- Checkboxes for tools will be populated here -->
            </div>
        </div>

        <!-- System Messages -->
        <div class="form-group">
            <label for="systemMessages">System Messages</label>
            <textarea class="form-control" id="systemMessages" name="system_message" rows="3"></textarea>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Create Conversation</button>
    </form>
</div>




<script>

    // Replace with your actual API token retrieval logic




    // Fetch assistants
    fetch('/api/ollama_assistants', {
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
        },
    })
        .then(response => response.json())
        .then(data => populateAssistants(data));

    // Fetch pipelines
    fetch('/api/pipelines', {
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
        },
    })
        .then(response => response.json())
        .then(data => populatePipelines(data));

    // Fetch tools
    fetch('/api/tools', {
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
        },
    })
        .then(response => response.json())
        .then(data => populateTools(data));



    // Fetch models
    fetch('/api/models', {
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
        },
    })
        .then(response => response.json())
        .then(data => populateModels(data));
    function populateModels(models) {
        const modelSelect = document.getElementById('modelSelect');
        modelSelect.innerHTML = ''; // Clear existing options

        models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = `${capitalizeFirstLetter(model.type)}: ${model.name}`;
            modelSelect.appendChild(option);
        });
    }



    function populateAssistants(assistants) {
        const assistantSelect = document.getElementById('assistantSelect');
        assistantSelect.innerHTML = '<option value="">None</option>'; // Optional
        assistants['assistants'].forEach(assistant => {
            const option = document.createElement('option');
            option.value = assistant.id;
            option.textContent = assistant.name;
            assistantSelect.appendChild(option);
        });
    }

    function populatePipelines(pipelines) {
        const pipelineSelect = document.getElementById('pipelineSelect');
        pipelineSelect.innerHTML = '<option value="">None</option>'; // Optional
        pipelines.forEach(pipeline => {
            const option = document.createElement('option');
            option.value = pipeline.id;
            option.textContent = pipeline.name;
            pipelineSelect.appendChild(option);
        });
    }

    function populateToolsOld(tools) {
        const toolsSelect = document.getElementById('toolsSelect');
        toolsSelect.innerHTML = ''; // Clear existing options
        tools['tools'].forEach(tool => {
            const option = document.createElement('option');
            option.value = tool.name; // Assuming tools are identified by name
            option.textContent = tool.display_name || tool.name;
            toolsSelect.appendChild(option);
        });
    }

    function populateTools(tools) {
        const toolsContainer = document.getElementById('toolsContainer');
        toolsContainer.innerHTML = ''; // Clear existing options


        console.log(tools);
        tools.forEach(tool => {
            // Create a checkbox wrapper
            const checkboxWrapper = document.createElement('div');
            checkboxWrapper.classList.add('form-check', 'm-2');

            // Create the checkbox input
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.classList.add('form-check-input');
            checkbox.id = `tool-${tool.name}`;
            checkbox.name = 'active_tools[]';
            checkbox.value = tool.id;

            // Create the label for the checkbox
            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.htmlFor = `tool-${tool.name}`;
            label.textContent = tool.display_name || tool.name;

            // Add tooltip for the description
            if (tool.description) {
                label.setAttribute('data-bs-toggle', 'tooltip');
                label.setAttribute('data-bs-placement', 'top');
                label.setAttribute('title', tool.description);
            }

            // Append checkbox and label to the wrapper
            checkboxWrapper.appendChild(checkbox);
            checkboxWrapper.appendChild(label);

            // Append wrapper to the container
            toolsContainer.appendChild(checkboxWrapper);
        });

        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }


    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

</script>
<script>
    document.getElementById('conversationForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent default form submission

        // Replace with your actual API token retrieval logic
        // Collect form data
        const formData = new FormData(this);

        // Convert FormData to JSON
        const data = {};
        formData.forEach((value, key) => {
            // Handle multiple select (active_tools)
            if (key === 'active_tools[]') {
                if (!data['active_tools']) data['active_tools'] = [];
                data['active_tools'].push(value);
            } else {
                data[key] = value;
            }
        });

        // Send POST request to create conversation
        fetch('/api/conversations', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errData => {
                        throw new Error(errData.message || 'Failed to create conversation.');
                    });
                }
                return response.json();
            })
            .then(result => {

                const conversationsContainer = document.getElementById('conversations');
                console.log('Conversation created:', result);

                var conversationId = result.id || result.conversation_id;

                appState.current_conversation_id = conversationId;
                localStorage.setItem('app_state', JSON.stringify(appState));

                const target_div = 'conversation-' + conversationId + '-conversation';

                if (conversationsContainer) {
                    // Create a new conversation div
                    const conversationDiv = document.createElement('div');

                    // Set the ID for the conversation div
                    conversationDiv.id = target_div;

                    // Append the new conversation div to the container
                    conversationsContainer.appendChild(conversationDiv);
                } else {
                    console.error('Conversations container not found');
                }

                loadContent(appState.apiToken,'/api/content/webapp.conversations._conversation',target_div);

                // window.location.href = `/conversation/${conversationId}`;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create conversation: ' + error.message);
            });
    });
</script>

