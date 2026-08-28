<!-- Load Assistants Button -->
<button id="loadAssistantsButton" class="btn btn-primary mb-3">
    <i class="fas fa-sync-alt"></i> Reload Assistants
</button>

<!-- Add Assistant Button -->
<button id="addAssistantButton" class="btn btn-success mb-3" style="display: none;">Add Assistant</button>

<!-- Assistants Table -->
<table id="assistantsTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Pub</th>
        <th>System Message</th>
        <th>Tools</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Add/Edit Assistant Modal -->
<div class="modal fade" id="assistantModal" tabindex="-1" aria-labelledby="assistantModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog " style="--bs-modal-width: 900px;width:80%">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="assistantModalLabel">Add/Edit Assistant</h5>
                <button type="button" class="btn-close" id="assistantModalClose" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form id="assistantForm">
                    <input type="hidden" id="assistantId">

                    <!-- Existing Fields -->
                    <div class="mb-3">
                        <label for="assistantName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="assistantName" required>
                    </div>

                    <div class="mb-3">
                        <label for="systemMessage" class="form-label">System Message</label>
                        <textarea class="form-control" id="systemMessage" rows="15" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="assistantModelSelect" class="form-label">Select Model</label>
                        <select class="form-control" id="assistantModelSelect" required></select>
                    </div>

                    <!-- Updated Type Field as a Dropdown -->
                    <div class="mb-3">
                        <label for="assistantType" class="form-label">Type</label>
                        <select class="form-control" id="assistantType" required>
                            <option value="">Select Type</option>
                            <option value="assistant">Assistant</option>
                            <option value="transform">Transform</option>
                            <option value="context">Context</option>
                            <option value="gatekeeper">Gatekeeper</option>
                            <option value="cron">Cron</option>
                            <!-- Add other types as needed -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="assistantInteractive" class="form-label">Interactive</label>
                        <select class="form-control" id="assistantInteractive">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="assistantIsPublic" class="form-label">Public</label>
                        <select class="form-control" id="assistantIsPublic">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="successToolSelect" class="form-label">Success Tool</label>
                        <select class="form-control" id="successToolSelect">
                            <option value="">Select a Tool (optional)</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="successAssistantSelect" class="form-label">Success Assistant</label>
                        <select class="form-control" id="successAssistantSelect">
                            <option value="">Select an Assistant (optional)</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="failToolSelect" class="form-label">Fail Tool</label>
                        <select class="form-control" id="failToolSelect">
                            <option value="">Select a Tool (optional)</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="failAssistantSelect" class="form-label">Fail Assistant</label>
                        <select class="form-control" id="failAssistantSelect">
                            <option value="">Select an Assistant (optional)</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <!-- Existing Tools Selection -->
                    <div class="mb-3">
                        <label for="toolsCheckboxes" class="form-label">Select Tools</label>
                        <div class="form-check" id="toolsCheckboxes"></div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveAssistantButton">Save Assistant</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Tools Modal -->
<div class="modal fade" id="toolsModal" tabindex="-1" aria-labelledby="toolsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="toolsModalLabel">Assign Tools - <strong id="assistantNameSpan"></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form id="toolsForm">
                    <input type="hidden" id="assistantId">
                    <div class="mb-3">
                        <label for="modalToolsCheckboxes" class="form-label">Tools</label>
                        <div class="form-check" id="modalToolsCheckboxes"></div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveToolsButton">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Add View Assistant Modal -->
<div class="modal fade" id="viewAssistantModal" tabindex="-1" aria-labelledby="viewAssistantModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAssistantModalLabel">Assistant Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8" id="view-name"></dd>
                            
                            <dt class="col-sm-4">Type</dt>
                            <dd class="col-sm-8" id="view-type"></dd>
                            
                            <dt class="col-sm-4">Interactive</dt>
                            <dd class="col-sm-8" id="view-interactive"></dd>
                            
                            <dt class="col-sm-4">Model</dt>
                            <dd class="col-sm-8" id="view-model"></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6>Tools</h6>
                        <div id="view-tools" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>System Message</h6>
                        <pre id="view-system-message" class="border rounded p-2" style="white-space: pre-wrap;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    // Utility Function to Capitalize First Letter
    function capitalizeFirstLetter(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    // Check if user has required roles for assistants
    function hasAssistantAccess() {

        console.log('hasAssistantAccess');
        console.log(appState.user);

        const userRoles = appState.user?.roles?.map(role => role.name.toLowerCase()) || [];
        return userRoles.some(role => ['assistant_user', 'assistant_admin'].includes(role));
    }

    // Check if user can edit an assistant
    function canEditAssistant(assistant) {
        const userRoles = appState.user?.roles?.map(role => role.name.toLowerCase()) || [];
        return userRoles.includes('assistant_admin') || assistant.user_id === appState.user?.id;
    }

    // Show/hide add button based on roles
    function updateAssistantButtonVisibility() {
        const addButton = document.getElementById('addAssistantButton');
        if (addButton) {
            addButton.style.display = hasAssistantAccess() ? 'inline-block' : 'none';
        }
    }

    // Call this when the page loads and when roles change
    updateAssistantButtonVisibility();
    document.addEventListener('roleChanged', updateAssistantButtonVisibility);

    // Helper Function to Show Loading Spinner
    function toggleSpinner(button, show) {
        const spinnerIcon = '<i class="fas fa-spinner fa-spin"></i> ';
        button.innerHTML = show ? spinnerIcon + button.dataset.originalText : button.dataset.originalText;
        button.disabled = show;
    }

    // Add Original Button Text as Data Attribute for Spinner Control
    document.querySelectorAll('button').forEach(button => {
        button.dataset.originalText = button.innerHTML;
    });


    document.getElementById('saveAssistantButton').addEventListener('click', () => {
        const saveButton = document.getElementById('saveAssistantButton');
        toggleSpinner(saveButton, true);

        // Form Data
        const assistantData = {
            id: document.getElementById('assistantId').value,
            name: document.getElementById('assistantName').value,
            system_message: document.getElementById('systemMessage').value,
            model_id: document.getElementById('assistantModelSelect').value,
            type: document.getElementById('assistantType').value,
            interactive: document.getElementById('assistantInteractive').value,
            is_public: document.getElementById('assistantIsPublic').value,
            success_tool_id: document.getElementById('successToolSelect').value || null,
            success_assistant_id: document.getElementById('successAssistantSelect').value || null,
            fail_tool_id: document.getElementById('failToolSelect').value || null,
            fail_assistant_id: document.getElementById('failAssistantSelect').value || null,
            tool_ids: Array.from(document.querySelectorAll('#toolsCheckboxes input:checked')).map(checkbox => checkbox.value)
        };

        // Validation
        if (!assistantData.type) {
            alert('Please select a Type for the Assistant.');
            toggleSpinner(saveButton, false);
            return;
        }

        // API Call
        const url = assistantData.id ? `/api/assistants/${assistantData.id}` : '/api/assistants';
        const method = assistantData.id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(assistantData)
        })
            .then(response => response.json())
            .then(() => {
                alert('Assistant saved successfully.');
                const modal = bootstrap.Modal.getInstance(document.getElementById('assistantModal'));
                if (modal) {
                    modal.dispose();
                }
                loadAssistantsDataTables();
            })
            .catch(err => {
                console.error(err);
                alert('Error saving assistant. Please try again.');
            })
            .finally(() => {
                toggleSpinner(saveButton, false);
            });
    });

    // Open Assistant Modal for Adding or Editing
    function openAssistantModal(assistantId = null) {
        clearAssistantForm();
        toggleSpinner(document.getElementById('saveAssistantButton'), false);

        // Load dependencies for the modal
        Promise.all([loadTools1(), loadModels(), loadSuccessTools(), loadFailTools(), loadAssistantsForDropdowns()])
            .then(() => {
                if (assistantId) {
                    document.getElementById('assistantModalLabel').innerHTML = '<i class="fas fa-user-edit"></i> Edit Assistant';
                    fetch(`/api/assistants/${assistantId}`, {
                        headers: {
                            'Authorization': 'Bearer ' + appState.apiToken,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(assistant => {
                            document.getElementById('assistantId').value = assistant.id;
                            document.getElementById('assistantName').value = assistant.name;
                            document.getElementById('systemMessage').value = assistant.system_message;
                            document.getElementById('assistantModelSelect').value = assistant.model_id;
                            document.getElementById('assistantType').value = assistant.type || '';
                            document.getElementById('assistantInteractive').value = assistant.interactive ? '1' : '0';
                            document.getElementById('assistantIsPublic').value = assistant.is_public ? '1' : '0';
                            document.getElementById('successToolSelect').value = assistant.success_tool_id || '';
                            document.getElementById('successAssistantSelect').value = assistant.success_assistant_id || '';
                            document.getElementById('failToolSelect').value = assistant.fail_tool_id || '';
                            document.getElementById('failAssistantSelect').value = assistant.fail_assistant_id || '';

                            assistant.tools.forEach(tool => {
                                const toolCheckbox = document.getElementById(`tool-${tool.id}`);
                                if (toolCheckbox) toolCheckbox.checked = true;
                            });
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Error loading assistant data.');
                        });
                } else {
                    document.getElementById('assistantModalLabel').innerHTML = '<i class="fas fa-plus-circle"></i> Add Assistant';
                }
                new bootstrap.Modal(document.getElementById('assistantModal')).show();
            })
            .catch(err => {
                console.error(err);
                alert('Error loading form data.');
            });
    }


    // Load Models from API and Populate the Model Select Dropdown
    function loadModels() {
        return fetch('/api/models', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                populateModels(data);
            })
            .catch(err => {
                console.error(err);
                alert('Error loading models.');
            });
    }

    // Populate Models Dropdown
    function populateModels(models) {
        const modelSelect = document.getElementById('assistantModelSelect');
        modelSelect.innerHTML = ''; // Clear existing options

        models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = `${capitalizeFirstLetter(model.type)}: ${model.name}`;
            modelSelect.appendChild(option);
        });
    }

    // Load Tools for Assistant Modal (Existing Tools)
    function loadTools1() {
        return fetch('/api/tools', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(tools => {
                const toolsCheckboxes = document.getElementById('toolsCheckboxes'); // Corrected ID
                toolsCheckboxes.innerHTML = ''; // Clear previous content


                appState.data.tools.forEach(tool => {
                    const div = document.createElement('div');
                    div.classList.add('form-check');
                    div.innerHTML = `
                    <input class="form-check-input" type="checkbox" value="${tool.id}" id="tool-${tool.id}">
                    <label class="form-check-label" for="tool-${tool.id}">${tool.name}</label>
                `;
                    toolsCheckboxes.appendChild(div);
                });
            })
            .catch(err => {
                console.error(err);
                alert('Error loading tools.');
            });
    }

    // Load Success Tools into the Success Tool Select Dropdown
    function loadSuccessTools() {
        const successToolSelect = document.getElementById('successToolSelect');
        successToolSelect.innerHTML = '<option value="">Select a Tool (optional)</option>'; // Default option

        appState.data.tools.forEach(tool => {
            const option = document.createElement('option');
            option.value = tool.id;
            option.textContent = tool.name;
            successToolSelect.appendChild(option);
        });
    }

    // Load Fail Tools into the Fail Tool Select Dropdown
    function loadFailTools() {
        const failToolSelect = document.getElementById('failToolSelect');
        failToolSelect.innerHTML = '<option value="">Select a Tool (optional)</option>'; // Default option

        appState.data.tools.forEach(tool => {
            const option = document.createElement('option');
            option.value = tool.id;
            option.textContent = tool.name;
            failToolSelect.appendChild(option);
        });
    }

    // Handle Add Assistant Button
    document.getElementById('addAssistantButton').addEventListener('click', () => {
        openAssistantModal();  // Open modal for adding
    });

    // Handle Edit Assistant Button (Delegate Event)
    $('#assistantsTable').on('click', '.edit-assistant-btn', function(event) {
    const assistantId = $(this).data('assistant-id');
    openAssistantModal(assistantId);
});

    // Clear the Assistant Form
    function clearAssistantForm() {
        document.getElementById('assistantForm').reset();
        document.getElementById('toolsCheckboxes').innerHTML = '';
        document.getElementById('assistantId').value = '';
        document.getElementById('assistantType').value = '';
        document.getElementById('assistantInteractive').value = '0';
        document.getElementById('assistantIsPublic').value = '0';
        document.getElementById('successToolSelect').innerHTML = '<option value="">Select a Tool (optional)</option>';
        document.getElementById('successAssistantSelect').innerHTML = '<option value="">Select an Assistant (optional)</option>';
        document.getElementById('failToolSelect').innerHTML = '<option value="">Select a Tool (optional)</option>';
        document.getElementById('failAssistantSelect').innerHTML = '<option value="">Select an Assistant (optional)</option>';
    }

    // Load Assistants into the DataTable
    function loadAssistantsDataTablesOld() {
        fetch("/api/ollama_assistants", { // Ensure this endpoint matches your backend route
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                const assistants = data.assistants || [];
                const tbody = document.querySelector('#assistantsTable tbody');
                tbody.innerHTML = assistants.map(assistant => `
                <tr>
                    <td>${assistant.name}</td>
                    <td>${capitalizeFirstLetter(assistant.type)}</td> <!-- Display Type -->
                    <td>${assistant.interactive ? 'Yes' : 'No'}</td> <!-- Display Interactive -->
                    <td>${assistant.system_message}</td>
                    <td>${assistant.model ? assistant.model.name : '<span class="text-muted">No Model</span>'}</td>
                    <td>
                        ${assistant.tools.length ? assistant.tools.map(tool => tool.name).join(', ') : '<span class="text-muted">No Tools</span>'}
                        <button class="btn btn-primary btn-sm assign-tools-btn" data-assistant-id="${assistant.id}" data-assistant-name="${assistant.name}">Assign Tools</button>
                    </td>
                    <td>${new Date(assistant.created_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-assistant-btn" data-assistant-id="${assistant.id}">Edit</button>
                        <button class="btn btn-info btn-sm more-info-btn" data-assistant-id="${assistant.id}" data-assistant-name="${assistant.name}">View</button>
                    </td>
                </tr>
            `).join('');
            })
            .catch(err => {
                console.error(err);
                alert('Error loading Assistants.');
            });
    }

    // Handle Assign Tools Button Click
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('assign-tools-btn')) {
            const assistantId = event.target.getAttribute('data-assistant-id');
            const assistantName = event.target.getAttribute('data-assistant-name');
            document.getElementById('assistantNameSpan').textContent = assistantName;
            document.getElementById('assistantId').value = assistantId;

            loadTools(assistantId);
            new bootstrap.Modal(document.getElementById('toolsModal')).show();
        }
    });

    // Load Tools into Assign Tools Modal
    function loadTools(assistantId) {
        const toolsCheckboxes = document.getElementById('modalToolsCheckboxes');
        toolsCheckboxes.innerHTML = '';

        fetch('/api/tools', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(tools => {
                tools.tools.forEach(tool => {
                    const div = document.createElement('div');
                    div.classList.add('form-check');
                    div.innerHTML = `
                    <input class="form-check-input" name='tool_ids[]' type="checkbox" value="${tool.id}" id="tool-${tool.id}">
                    <label class="form-check-label" for="tool-${tool.id}">${tool.name}</label>
                `;
                    toolsCheckboxes.appendChild(div);
                });

                loadAssistantTools(assistantId);
            })
            .catch(err => {
                console.error(err);
                alert('Error loading tools.');
            });
    }

    // Load Assistant's Existing Tools into Assign Tools Modal
    function loadAssistantTools(assistantId) {
        if (!assistantId) {
            console.error('No assistant ID provided.');
            return false;
        }

        fetch(`/api/assistants/${assistantId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(assistant => {
                assistant.tools.forEach(tool => {
                    const toolCheckbox = document.getElementById(`tool-${tool.id}`);
                    if (toolCheckbox) {
                        toolCheckbox.checked = true;
                    }
                });
            })
            .catch(err => {
                console.error(err);
                alert('Error loading assistant tools.');
            });
    }

    // Handle Save Tools Button Click
    document.getElementById('saveToolsButton').addEventListener('click', function() {
        const assistantId = document.getElementById('assistantId').value;
        const selectedTools = Array.from(document.querySelectorAll('#modalToolsCheckboxes input:checked')).map(checkbox => checkbox.value);

        fetch(`/api/assistants/${assistantId}/tools`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ tool_ids: selectedTools })
        })
            .then(response => response.json())
            .then(() => {
                alert('Tools updated successfully.');
                const modal = bootstrap.Modal.getInstance(document.getElementById('toolsModal'));
                if (modal) {
                    modal.dispose();
                }
                loadAssistantsDataTables();
            })
            .catch(err => {
                console.error(err);
                alert('Error saving tools. Please try again.');
            });
    });

    // Handle Load Assistants Button Click
    document.getElementById('loadAssistantsButton').addEventListener('click', () => {

        alert('button pushed');
        loadAssistantsDataTables();
    });

    // Initial Load of Assistants

        loadAssistantsDataTables();

    // Load Assistants into DataTable with Sorting and Searching
    function loadAssistantsDataTables() {
        const assistantsTable = document.querySelector('#assistantsTable');

        // Initialize DataTable
        const dataTable = $(assistantsTable).DataTable({
            ajax: function(data, callback, settings) {
                fetch('/api/ollama_assistants', {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + appState.apiToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'omit'
                })
                    .then(response => response.json())
                    .then(data => {
                        const assistantsArray = Object.values(data.assistants);
                        // Transform the data into the correct format for DataTables
                        const formattedData = assistantsArray.map(assistant => {
                            // Format tools display
                            let toolsDisplay = 'No Tools';
                            if (assistant.tools && assistant.tools.length > 0) {
                                const toolNames = assistant.tools.map(tool => tool.name);
                                if (toolNames.length <= 3) {
                                    toolsDisplay = toolNames.join('<br>');
                                } else {
                                    toolsDisplay = `
                                        <div class="tools-display">
                                            <span class="tools-preview">${toolNames.slice(0, 3).join('<br>')}</span>
                                            <a href="#" class="show-more-tools" data-tools='${JSON.stringify(toolNames)}'>
                                                [${toolNames.length - 3} more]
                                            </a>
                                        </div>
                                    `;
                                }
                            }

                            // Only show edit button if user has permission
                            const actions = canEditAssistant(assistant) ? `
                                <button class="btn btn-warning btn-sm edit-assistant-btn" data-assistant-id="${assistant.id}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            ` : '';
                            
                            return {
                                id: assistant.id,
                                name: assistant.name || 'N/A',
                                type: assistant.type ? capitalizeFirstLetter(assistant.type) : 'N/A',
                                is_public: assistant.is_public ? 'Y' : 'N',
                                system_message: assistant.system_message.length > 30
                                    ? `<span class="system-message-short" data-full-message="${assistant.system_message}">
                                        ${assistant.system_message.substring(0, 30)}...
                                        <a href="#" class="expand-message">[more]</a>
                                      </span>`
                                    : assistant.system_message,
                                tools: toolsDisplay,
                                actions: actions + `
                                    <button class="btn btn-info btn-sm more-info-btn" data-assistant-id="${assistant.id}" data-assistant-name="${assistant.name}">
                                        <i class="fas fa-info-circle"></i> View
                                    </button>
                                `
                            };
                        });

                        callback({
                            data: formattedData
                        });
                    })
                    .catch(err => {
                        console.error("Error fetching assistants data:", err);
                        callback({
                            data: []
                        });
                    });
            },
            columns: [
                { data: 'name', title: 'Name' },
                { data: 'type', title: 'Type' },
                { data: 'is_public', title: 'Pub' },
                { data: 'system_message', title: 'System Message' },
                { data: 'tools', title: 'Tools' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false }
            ],
            destroy: true,
            searching: true,
            ordering: true,
            paging: true
        });

        // Handle tools expansion
        $(assistantsTable).on('click', '.show-more-tools', function(event) {
            event.preventDefault();
            const tools = JSON.parse($(this).attr('data-tools'));
            const parentDiv = $(this).closest('.tools-display');
            
            if ($(this).text().includes('more')) {
                // Show all tools
                parentDiv.html(`
                    <div class="all-tools">
                        ${tools.join('<br>')}
                        <a href="#" class="show-less-tools" data-tools='${JSON.stringify(tools)}'>[show less]</a>
                    </div>
                `);
            }
        });

        $(assistantsTable).on('click', '.show-less-tools', function(event) {
            event.preventDefault();
            const tools = JSON.parse($(this).attr('data-tools'));
            const parentDiv = $(this).closest('.tools-display');
            
            // Show preview again
            parentDiv.html(`
                <span class="tools-preview">${tools.slice(0, 3).join('<br>')}</span>
                <a href="#" class="show-more-tools" data-tools='${JSON.stringify(tools)}'>
                    [${tools.length - 3} more]
                </a>
            `);
        });

        // Event delegation to handle clicks on the expand/collapse message link
        $(assistantsTable).on('click', '.expand-message, .collapse-message', function(event) {
            event.preventDefault();
            const parentSpan = $(this).closest('.system-message-short');
            const fullMessage = parentSpan.data('full-message');

            // Toggle between expanded and collapsed
            if ($(this).hasClass('expand-message')) {
                // Expand message and add a "less" link
                parentSpan.html(`
            ${fullMessage}
            <a href="#" class="collapse-message">[less]</a>
        `);
            } else if ($(this).hasClass('collapse-message')) {
                // Collapse message and add a "more" link
                parentSpan.html(`
            ${fullMessage.substring(0, 30)}...
            <a href="#" class="expand-message">[more]</a>
        `);
            }
        });

        // Add DataTable-specific event handler
        $('#assistantsTable').on('click', '.edit-assistant-btn', function(event) {
            const assistantId = $(this).data('assistant-id');
            openAssistantModal(assistantId);
        });
    }

    // Fix modal closing issue by using proper Bootstrap modal cleanup
    document.getElementById('assistantModalClose').addEventListener('click', function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('assistantModal'));
        if (modal) {
            modal.hide();
            // Ensure backdrop is removed
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    });

    // Add proper modal hidden event handler
    document.getElementById('assistantModal').addEventListener('hidden.bs.modal', function () {
        const modal = bootstrap.Modal.getInstance(this);
        if (modal) {
            // Ensure backdrop is removed
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    });

    // Handle View Assistant Button Click
    $(document).on('click', '.more-info-btn', function() {
        const assistantId = $(this).data('assistant-id');
        
        fetch(`/api/assistants/${assistantId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(assistant => {
            // Populate the view modal with assistant details
            document.getElementById('view-name').textContent = assistant.name;
            document.getElementById('view-type').textContent = capitalizeFirstLetter(assistant.type || 'N/A');
            document.getElementById('view-interactive').textContent = assistant.interactive ? 'Yes' : 'No';
            document.getElementById('view-model').textContent = assistant.model ? `${assistant.model.type}: ${assistant.model.name}` : 'No Model';
            
            // Display tools
            const toolsContainer = document.getElementById('view-tools');
            if (assistant.tools && assistant.tools.length > 0) {
                toolsContainer.innerHTML = assistant.tools.map(tool => 
                    `<div class="tool-item mb-1">
                        <i class="fas fa-tools me-2"></i>${tool.name}
                    </div>`
                ).join('');
            } else {
                toolsContainer.innerHTML = '<em>No tools assigned</em>';
            }
            
            // Display system message
            document.getElementById('view-system-message').textContent = assistant.system_message || 'No system message';
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('viewAssistantModal')).show();
        })
        .catch(err => {
            console.error(err);
            alert('Error loading assistant details.');
        });
    });

    // Add event listener for view modal hidden event
    document.getElementById('viewAssistantModal').addEventListener('hidden.bs.modal', function () {
        const modal = bootstrap.Modal.getInstance(this);
        if (modal) {
            modal.dispose();
        }
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });

    // Add function to load assistants for dropdowns
    function loadAssistantsForDropdowns() {
        return fetch('/api/assistants', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const assistants = data.assistants || [];
            const successAssistantSelect = document.getElementById('successAssistantSelect');
            const failAssistantSelect = document.getElementById('failAssistantSelect');
            
            // Clear existing options except the first one
            successAssistantSelect.innerHTML = '<option value="">Select an Assistant (optional)</option>';
            failAssistantSelect.innerHTML = '<option value="">Select an Assistant (optional)</option>';
            
            // Add options
            assistants.forEach(assistant => {
                const successOption = document.createElement('option');
                successOption.value = assistant.id;
                successOption.textContent = assistant.name;
                successAssistantSelect.appendChild(successOption);
                
                const failOption = document.createElement('option');
                failOption.value = assistant.id;
                failOption.textContent = assistant.name;
                failAssistantSelect.appendChild(failOption);
            });
        })
        .catch(err => {
            console.error(err);
            alert('Error loading assistants for dropdowns.');
        });
    }

</script>
