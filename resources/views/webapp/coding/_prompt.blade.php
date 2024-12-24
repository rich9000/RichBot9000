<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: Arial, sans-serif;
        height: 100vh;
        color: #333;
    }
    .panel-title {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: #555;
    }
    .file-browser-card, .file-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .nested {
        margin-left: 1.5rem;
        border-left: 1px dashed #ccc;
        padding-left: 0.5rem;
    }
    .directory-name, .file-name {
        cursor: pointer;
    }
    .directory:hover .directory-name, .file:hover .file-name {
        color: #007bff;
    }
</style>



<div class="container mt-4">
    <h3>Pipeline: Coding Assistant</h3>
    <div id="pipeline-stages">
        <!-- Pipeline stages and assistants will be displayed here dynamically -->
    </div>
</div>

<script>

    const pipelineId = 8; // Hardcoded ID for "Coding Assistant Pipeline"

    async function fetchPipelineStages() {
        try {
            const response = await fetch(`/api/pipelines/${pipelineId}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch pipeline stages');

            const data = await response.json();
            displayPipelineStages(data.stages);
        } catch (error) {
            console.error('Error fetching pipeline stages:', error);
        }
    }

    function displayPipelineStages(stages) {
        const stagesContainer = document.getElementById('pipeline-stages');
        stagesContainer.innerHTML = ''; // Clear previous content

        if (!stages || stages.length === 0) {
            stagesContainer.innerHTML = '<p>No stages found for this pipeline.</p>';
            return;
        }

        stages.forEach(stage => {
            const stageCard = document.createElement('div');
            stageCard.className = 'card mb-3';

            stageCard.innerHTML = `
            <div class="card-header">
                <strong>Stage ${stage.order}:</strong> ${stage.name}
<span class="badge bg-success">${stage.type}</span>



            </div>
            <div class="card-body">

                <div id="assistants-stage-${stage.id}" class="assistants-list">
                    <!-- Assistants for this stage will be loaded here -->
                </div>
            </div>
        `;

            stagesContainer.appendChild(stageCard);

            fetchStageAssistants(stage.id);
        });
    }

    async function fetchStageAssistants(stageId) {
        try {
            const response = await fetch(`/api/pipelines/${pipelineId}/stages/${stageId}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch stage assistants');

            const data = await response.json();
            displayStageAssistants(stageId, data.assistants);
        } catch (error) {
            console.error('Error fetching stage assistants:', error);
        }
    }

    function displayStageAssistants(stageId, assistants) {
        const assistantsContainer = document.getElementById(`assistants-stage-${stageId}`);
        assistantsContainer.innerHTML = ''; // Clear previous content

        if (!assistants || assistants.length === 0) {
            assistantsContainer.innerHTML = '<p>No assistants found for this stage.</p>';
            return;
        }

        assistants.forEach(assistant => {
            const assistantItem = document.createElement('div');
            assistantItem.className = 'assistant-item mb-2';

            // Find the tool information based on pivot.success_tool_id
            const successTool = appState.data.tools.find(
                tool => tool.id === assistant.pivot.success_tool_id
            );

            // Build the HTML content
            assistantItem.innerHTML = `
        <div class="d-flex align-items-center">
            <span class="me-3"><strong>${assistant.name}</strong></span>
            <span class="badge bg-primary me-3">Order: ${assistant.pivot.order}</span>
            ${
                successTool
                    ? `<span class="badge bg-success me-3">Tool: ${successTool.name}</span>`
                    : `<span class="badge bg-danger">No Tool Found</span>`
            }
        </div>
        ${
                successTool && successTool.parameters
                    ? `<div class="text-muted small ms-3">Tool Parameters: ${JSON.stringify(successTool.parameters)}</div>`
                    : ''
            }
    `;

            // Append the assistant item to the container
            assistantsContainer.appendChild(assistantItem);
        });
    }

    // Load the pipeline on page load
    fetchPipelineStages();
</script>






    <!-- Add Files Form -->
    <div class="section mt-4">
        <h2 class="panel-title">Generate or Edit Code</h2>

        <label for="action" class="form-label">Choose Action:</label>
        <select id="action" class="form-select mb-3">
            <option value="new">Generate New Code</option>
            <option value="edit">Edit Existing Code</option>
            <option value="troubleshoot">Troubleshoot Issues</option>
            <option value="validate">Validate Code for Errors</option>
            <option value="optimize">Optimize Existing Code</option>
            <option value="analyze">Analyze Code for Inconsistencies</option>
            <option value="document">Generate Documentation</option>
        </select>
        <!-- Action Description -->
        <div id="actionDescription" class="alert alert-info">
            Select an action from the dropdown to see its description here.
        </div>

        <!-- Additional Parameters for Selected Action -->
        <div id="additionalParameters" class="mt-3">
            <!-- Parameters will be dynamically displayed here based on the action -->
        </div>
        <!-- Prompt Input -->
        <label for="prompt" class="form-label">Prompt Description:</label>
        <textarea id="prompt" class="form-control mb-3" rows="4" placeholder="Describe the functionality or changes you need (e.g., 'Create a user model with name and email fields')."></textarea>

    </div>

    <!-- File Browser Modal -->
    <div class="modal fade" id="fileBrowserModal" tabindex="-1" aria-labelledby="fileBrowserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fileBrowserModalLabel">Select Files for Prompt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">File Browser</h5>
                        </div>
                        <div class="card-body file-browser-card">
                            <div id="file-browser"></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="reason" class="form-label">Reason for Inclusion:</label>
                        <select id="reason" class="form-select">
                            <option value="context">Context</option>
                            <option value="change">Making Changes</option>
                            <option value="creation">New File Creation</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="saveFileSelection" class="btn btn-primary">Save Selection</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create New File Modal -->
    <div class="modal fade" id="createNewFileModal" tabindex="-1" aria-labelledby="createNewFileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createNewFileModalLabel">Create New File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fileName" class="form-label">File Name</label>
                        <input type="text" id="fileName" class="form-control" placeholder="Enter file name">
                    </div>
                    <div class="mb-3">
                        <label for="fileFolder" class="form-label">Select Folder</label>
                        <select id="fileFolder" class="form-select">
                            <!-- Folders will be dynamically loaded -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="saveNewFile" class="btn btn-primary">Add File to Prompt</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Selected Files Overview -->
    <div class="section mt-4">
        <div class="card">
            <div class="card-header">

                <h4 class="" style="float:left;">Selected Files</h4>
                <div style="float:right;">
                    <button id="addFileToPrompt" class="btn btn-success btn-sm ">Add Files to Generate Request</button>
                    <button id="createNewFileBtn" class="btn btn-primary btn-sm">Add New File to Create</button>
                </div>

            </div>
            <div class="card-body">  <ul id="selectedFilesList" class="list-group">
                    <!-- Selected files will appear here -->
                </ul></div>

        </div>

        <div class="mt-3">
            <button id="startRequest" class="btn btn-success w-100" onclick="">Start Code Generation</button>
        </div>

    </div>

    <script>
        // Action descriptions
        const actionDescriptions = {
            new: "Generate new code for a specific component, such as a model, controller, or API endpoint.",
            edit: "Edit existing code to implement changes or updates.",
            troubleshoot: "Identify and resolve issues in your codebase.",
            validate: "Check your code for syntax errors, invalid configurations, or other potential issues.",
            optimize: "Improve the performance and efficiency of your existing code.",
            analyze: "Scan the codebase for inconsistencies, unused imports, or logical issues.",
            document: "Automatically generate documentation for your code, including comments and API docs."
        };

        // Parameters for each action
        const actionParameters = {
            troubleshoot: [
                { label: "Error Log File Path", type: "text", id: "logFilePath", placeholder: "Path to the error log file" },
                { label: "Component to Debug", type: "text", id: "debugComponent", placeholder: "Specify the component (e.g., API, model)" }
            ],
            validate: [
                { label: "Codebase Path", type: "text", id: "codebasePath", placeholder: "Path to the codebase to validate" },
                { label: "Validation Type", type: "select", id: "validationType", options: ["Syntax Check", "Configuration Check", "All"] }
            ],
            optimize: [
                { label: "Optimization Scope", type: "select", id: "optimizationScope", options: ["Database Queries", "API Calls", "Front-End Rendering", "All"] }
            ],
            analyze: [
                { label: "Analysis Level", type: "select", id: "analysisLevel", options: ["Basic", "Intermediate", "Advanced"] },
                { label: "Include Dependencies", type: "checkbox", id: "includeDependencies", text: "Include dependency analysis" }
            ],
            document: [
                { label: "Documentation Format", type: "select", id: "docFormat", options: ["Markdown", "HTML", "PDF"] }
            ]
        };

        const actionSelect = document.getElementById('action');
        const actionDescription = document.getElementById('actionDescription');
        const additionalParameters = document.getElementById('additionalParameters');

        // Update the description and additional parameters dynamically
        actionSelect.addEventListener('change', () => {
            const selectedAction = actionSelect.value;
            actionDescription.textContent = actionDescriptions[selectedAction] || "Select an action to see its description.";

            // Clear additional parameters
            additionalParameters.innerHTML = "";

            // Load additional parameters for the selected action
            if (actionParameters[selectedAction]) {
                actionParameters[selectedAction].forEach(param => {
                    const formGroup = document.createElement('div');
                    formGroup.classList.add('mb-3');

                    const label = document.createElement('label');
                    label.classList.add('form-label');
                    label.setAttribute('for', param.id);
                    label.textContent = param.label;

                    formGroup.appendChild(label);

                    let input;
                    switch (param.type) {
                        case "text":
                            input = document.createElement('input');
                            input.type = "text";
                            input.id = param.id;
                            input.classList.add('form-control');
                            input.placeholder = param.placeholder || "";
                            break;

                        case "select":
                            input = document.createElement('select');
                            input.id = param.id;
                            input.classList.add('form-select');
                            param.options.forEach(option => {
                                const opt = document.createElement('option');
                                opt.value = option;
                                opt.textContent = option;
                                input.appendChild(opt);
                            });
                            break;

                        case "checkbox":
                            input = document.createElement('input');
                            input.type = "checkbox";
                            input.id = param.id;
                            input.classList.add('form-check-input');
                            const checkboxLabel = document.createElement('label');
                            checkboxLabel.classList.add('form-check-label');
                            checkboxLabel.setAttribute('for', param.id);
                            checkboxLabel.textContent = param.text || "";
                            formGroup.appendChild(input);
                            formGroup.appendChild(checkboxLabel);
                            additionalParameters.appendChild(formGroup);
                            return; // Skip the default form group appending for checkboxes
                    }

                    if (input) {
                        formGroup.appendChild(input);
                    }
                    additionalParameters.appendChild(formGroup);
                });
            }
        });

        // Trigger initial update to load default description
        actionSelect.dispatchEvent(new Event('change'));























        const selectedFiles = [];

        // Open File Browser Modal
        document.getElementById('addFileToPrompt').addEventListener('click', () => {
            const fileBrowserModal = new bootstrap.Modal(document.getElementById('fileBrowserModal'));
            fetchDirectoryContents('/');
            fileBrowserModal.show();
        });

        // Open Create New File Modal
        document.getElementById('createNewFileBtn').addEventListener('click', () => {
            const createNewFileModal = new bootstrap.Modal(document.getElementById('createNewFileModal'));
            loadFoldersForDropdown();
            createNewFileModal.show();
        });

        async function fetchDirectoryContents(path = '/', container = document.getElementById('file-browser')) {
            const response = await fetch(`/api/files?path=${encodeURIComponent(path)}`, {
                headers: { 'Authorization': `Bearer ${appState.apiToken}` },
            });

            if (!response.ok) {
                console.error("Failed to fetch directory contents");
                return;
            }

            const data = await response.json();
            displayDirectoryContents(data, path, container);
        }
        function displayDirectoryContents(data, path, container) {
            container.innerHTML = ''; // Clear previous content

            // Create and display directories
            data.directories.forEach(directory => {
                const dirElement = document.createElement('div');
                dirElement.className = 'directory';

                // Directory template with Font Awesome icon and note input
                dirElement.innerHTML = `
            <div class="directory-header d-flex align-items-center">
                <input type="checkbox" class="folder-checkbox me-2" data-path="${directory}">
                <i class="fas fa-folder me-2"></i>
                <span class="directory-name">${directory}</span>
                <input type="text" class="note-input ms-3 form-control" placeholder="Add a note" style="flex: 1; max-width: 200px;" data-path="${directory}">
            </div>
        `;

                // Toggle nested contents on click
                const dirNameElement = dirElement.querySelector('.directory-name');
                dirNameElement.addEventListener('click', () => {
                    const existingSubDirContainer = dirElement.querySelector('.nested');
                    if (existingSubDirContainer) {
                        existingSubDirContainer.remove();
                    } else {
                        const subDirContainer = document.createElement('div');
                        subDirContainer.classList.add('nested', 'ms-4');
                        fetchDirectoryContents(directory, subDirContainer); // Recursive fetching
                        dirElement.appendChild(subDirContainer);
                    }
                });

                // Handle folder checkbox to select/deselect sub-items
                const folderCheckbox = dirElement.querySelector('.folder-checkbox');
                folderCheckbox.addEventListener('change', (event) => {
                    const isChecked = event.target.checked;
                    const subCheckboxes = dirElement.querySelectorAll('.file-checkbox, .folder-checkbox');
                    subCheckboxes.forEach(checkbox => checkbox.checked = isChecked);
                });

                container.appendChild(dirElement);
            });

            // Create and display files
            data.files.forEach(file => {
                const fileElement = document.createElement('div');
                fileElement.className = 'file';

                // File template with Font Awesome icon and note input
                fileElement.innerHTML = `
            <div class="file-item d-flex align-items-center">
                <input type="checkbox" class="file-checkbox me-2" data-path="${file}">
                <i class="fas fa-file me-2"></i>
                <span class="file-name">${file}</span>
                <input type="text" class="note-input ms-3 form-control" placeholder="Add a note" style="flex: 1; max-width: 200px;" data-path="${file}">
            </div>
        `;

                container.appendChild(fileElement);
            });
        }



        function loadFoldersForDropdown() {
            fetch('/api/list/tree', {
                headers: { 'Authorization': `Bearer ${appState.apiToken}` },
            })
                .then(response => response.json())
                .then(data => {
                    const folderDropdown = document.getElementById('fileFolder');
                    folderDropdown.innerHTML = ''; // Clear existing options

                    if (data.status === 'success' && data.data.tree) {
                        const folders = [];
                        extractFolders(data.data.tree, folders);

                        // Populate dropdown
                        folders.forEach(folder => {
                            const option = document.createElement('option');
                            option.value = folder.path;
                            option.text = folder.name;
                            folderDropdown.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading folders:', error));
        }

        // Recursive function to extract folders
        function extractFolders(tree, folders, parentPath = '') {
            tree.forEach(item => {
                if (item.type === 'folder') {
                    const fullPath = parentPath ? `${parentPath}/${item.name}` : item.name;
                    folders.push({ path: fullPath, name: fullPath });

                    // If the folder has contents, recursively extract them
                    if (item.contents && item.contents.length > 0) {
                        extractFolders(item.contents, folders, fullPath);
                    }
                }
            });
        }
        // Save New File
        document.getElementById('saveNewFile').addEventListener('click', () => {
            const fileName = document.getElementById('fileName').value;
            const folderPath = document.getElementById('fileFolder').value;
            if (!fileName || !folderPath) {
                alert("Please provide a file name and folder.");
                return;
            }

            const newFilePath = `${folderPath}/${fileName}`;
            selectedFiles.push({ path: newFilePath, reason: 'creation', type: '' });
            updateSelectedFilesList();
            bootstrap.Modal.getInstance(document.getElementById('createNewFileModal')).hide();
        });

        // Save File Selection
        document.getElementById('saveFileSelection').addEventListener('click', () => {
            const reason = document.getElementById('reason').value;
            const checkboxes = document.querySelectorAll('.file-checkbox:checked');

            checkboxes.forEach(checkbox => {
                const filePath = checkbox.getAttribute('data-path');
                if (!selectedFiles.some(file => file.path === filePath)) {
                    selectedFiles.push({ path: filePath, reason, type: '' });
                }
            });

            updateSelectedFilesList();
            bootstrap.Modal.getInstance(document.getElementById('fileBrowserModal')).hide();
        });

        function updateSelectedFilesList() {
            const selectedFilesList = document.getElementById('selectedFilesList');
            selectedFilesList.innerHTML = '';

            selectedFiles.forEach(file => {
                const listItem = document.createElement('li');
                listItem.classList.add('list-group-item');

                listItem.innerHTML = `
         <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center" style="flex: 1;">
        <strong style="flex-shrink: 0; width: 30%;">${file.path}</strong>
        <select class="form-select file-type-select btn-sm me-2" style="width: 25%;" data-path="${file.path}">
            <option value="">Select Type</option>
            <option value="model" ${file.type === 'model' ? 'selected' : ''}>Model</option>
            <option value="controller" ${file.type === 'controller' ? 'selected' : ''}>Controller</option>
            <option value="api" ${file.type === 'api' ? 'selected' : ''}>API Endpoint</option>
            <option value="migration" ${file.type === 'migration' ? 'selected' : ''}>Migration</option>
            <option value="pwa" ${file.type === 'pwa' ? 'selected' : ''}>PWA Component</option>
        </select>
        <input type="text" class="form-control file-role-input me-2" placeholder="Describe the role" value="${file.reason}" style="width: 40%;" data-path="${file.path}">
    </div>
    <button class="btn btn-danger btn-sm remove-file" data-path="${file.path}" style="flex-shrink: 0;">Remove</button>
</div>
            `;

                // Update type when dropdown is changed
                listItem.querySelector('.file-type-select').addEventListener('change', event => {
                    const updatedPath = event.target.getAttribute('data-path');
                    const fileIndex = selectedFiles.findIndex(f => f.path === updatedPath);
                    if (fileIndex !== -1) {
                        selectedFiles[fileIndex].type = event.target.value;
                    }
                });

                // Update role when input field is changed
                listItem.querySelector('.file-role-input').addEventListener('input', event => {
                    const updatedPath = event.target.getAttribute('data-path');
                    const fileIndex = selectedFiles.findIndex(f => f.path === updatedPath);
                    if (fileIndex !== -1) {
                        selectedFiles[fileIndex].reason = event.target.value;
                    }
                });

                // Remove file from the list
                listItem.querySelector('.remove-file').addEventListener('click', () => {
                    removeFile(file.path);
                });

                selectedFilesList.appendChild(listItem);
            });
        }

        // Remove File from List
        function removeFile(path) {
            const index = selectedFiles.findIndex(file => file.path === path);
            if (index !== -1) {
                selectedFiles.splice(index, 1);
                updateSelectedFilesList();
            }
        }



        function getRequestData(){

            const action = document.getElementById('action').value;
            const prompt = document.getElementById('prompt').value.trim();

            if (!prompt) {
                alert('Please enter a prompt description.');
                return;
            }

            const extraParams = {};
            if (actionParameters[action]) {
                actionParameters[action].forEach(param => {
                    if (param.type === 'checkbox') {
                        extraParams[param.id] = document.getElementById(param.id).checked;
                    } else {
                        extraParams[param.id] = document.getElementById(param.id).value;
                    }
                });
            }
            const requestData = { action, prompt, files: selectedFiles, extraParams };

            requestData.session_id = appState.current_coding_session_id;



            return requestData;



        }

        // Start Code Generation
        document.getElementById('startRequest').addEventListener('click', () => {

            const requestData = getRequestData();
startCodingSession();

        });

















        // Send the request
        fetch(`/api/coding/session/create`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`, // Include Bearer token
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: null,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Session created successfully:', data.session_id);

                    appState.coding_sessions[data.session_id] = data;
                    appState.current_coding_session_id = data.session_id;





                } else {
                    console.error('Error creating session:', data);
                }
            })
            .catch(error => console.error('Network or server error:', error));


        async function startCodingSession() {
            try {
                // Step 1: Initialize conversation with the first assistant
                const firstAssistant = await getFirstAssistant();
                if (!firstAssistant) throw new Error('No assistants available to start coding');

                const requestData = getRequestData();
                requestData.assistant_id = firstAssistant.id;
                requestData.pipeline_id = pipelineId;

                // Step 2: Start a conversation
                const conversationResponse = await fetch(`/api/coding/start`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData),

                });

                if (!conversationResponse.ok) throw new Error('Failed to start conversation');

                const conversationData = await conversationResponse.json();
                handleConversationProgress(conversationData.conversation_id);
            } catch (error) {
                console.error('Error starting coding session:', error);
            }
        }

        async function getFirstAssistant() {
            // Fetch the first stage and assistant
            const stagesResponse = await fetch(`/api/pipelines/${pipelineId}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json',
                },
            });

            if (!stagesResponse.ok) throw new Error('Failed to fetch stages');
            const stagesData = await stagesResponse.json();

            const firstStage = stagesData.stages[0];
            if (!firstStage) return null;

            const assistantsResponse = await fetch(`/api/pipelines/${pipelineId}/stages/${firstStage.id}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json',
                },
            });

            if (!assistantsResponse.ok) throw new Error('Failed to fetch assistants');
            const assistantsData = await assistantsResponse.json();

            return assistantsData.assistants[0] || null;
        }

        async function handleConversationProgress(conversationId) {
            let currentStage = 0;

            while (true) {
                const stageResponse = await fetch(`/api/conversations/${conversationId}/current-stage`, {
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`,
                        'Accept': 'application/json',
                    },
                });

                if (!stageResponse.ok) throw new Error('Failed to fetch current stage');
                const stageData = await stageResponse.json();

                if (stageData.status === 'completed') {
                    alert('Pipeline completed successfully!');
                    break;
                }

                if (stageData.status === 'error') {
                    alert('Error during pipeline execution!');
                    break;
                }

                currentStage++;
                await new Promise(resolve => setTimeout(resolve, 3000)); // Poll every 3 seconds
            }
        }


    </script>

















