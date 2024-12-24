<!-- Add Assistant Button -->
<button id="addAssistantButton" class="btn btn-success mb-3">Add Assistant</button>

<!-- Assistants Table -->
<table id="assistantsTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Name</th>
        <th>Type</th> <!-- New Type Column -->
        <th>Interactive</th> <!-- New Interactive Column -->
        <th>System Message</th>
        <th>Model</th>
        <th>Tools</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Add/Edit Assistant Modal -->
<div class="modal fade" id="assistantModal" tabindex="-1" aria-labelledby="assistantModalLabel" aria-hidden="true">
    <div class="modal-dialog " style="--bs-modal-width: 900px;width:80%">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="assistantModalLabel">Add/Edit Assistant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <label for="successToolSelect" class="form-label">Success Tool</label>
                        <select class="form-control" id="successToolSelect">
                            <option value="">Select a Tool (optional)</option>
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
<div class="modal fade" id="toolsModal" tabindex="-1" aria-labelledby="toolsModalLabel" aria-hidden="true">
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

<!-- Load Assistants Button -->
<button id="loadAssistantsButton" class="btn btn-primary mb-3">Load Assistants</button>

<!-- JavaScript -->
<script>
    // Utility Function to Capitalize First Letter
    function capitalizeFirstLetter(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }


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
            success_tool_id: document.getElementById('successToolSelect').value || null,
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
                new bootstrap.Modal(document.getElementById('assistantModal')).hide();
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
        Promise.all([loadTools1(), loadModels(), loadSuccessTools()])
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
                            document.getElementById('successToolSelect').value = assistant.success_tool_id || '';

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
        document.getElementById('assistantType').value = ''; // Reset Type
        document.getElementById('assistantInteractive').value = '0'; // Reset Interactive to 'No'
        document.getElementById('successToolSelect').innerHTML = '<option value="">Select a Tool (optional)</option>'; // Reset Success Tool Select
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
                new bootstrap.Modal(document.getElementById('toolsModal')).hide();
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
                // Fetch data using fetch API
                fetch('/api/ollama_assistants', {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + appState.apiToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'omit'  // Prevent cookies from being sent
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('data', data); // Debugging: Verify the fetched data structure

                        const assistantsArray = Object.values(data.assistants);
                        console.log(assistantsArray);
                        // Transform the data into the correct format for DataTables
                        const formattedData = assistantsArray.map(assistant => ({
                            id: assistant.id,
                            name: assistant.name || 'N/A',
                            type: assistant.type ? capitalizeFirstLetter(assistant.type) : 'N/A',
                            interactive: assistant.interactive ? 'Yes' : 'No',
                            system_message: assistant.system_message.length > 30
                                ? `<span class="system-message-short" data-full-message="${assistant.system_message}">
                        ${assistant.system_message.substring(0, 30)}...
                        <a href="#" class="expand-message">[more]</a>
                      </span>`
                                : assistant.system_message,
                            model: assistant.model ? `${assistant.model.type}: ${assistant.model.name}` : 'No Model',
                            tools: assistant.tools && assistant.tools.length ? assistant.tools.map(tool => tool.name).join(', ') : 'No Tools',
                            created_at: assistant.created_at ? new Date(assistant.created_at).toLocaleString() : 'N/A',
                            actions: `
                    <button class="btn btn-warning btn-sm edit-assistant-btn" data-assistant-id="${assistant.id}">
    <i class="fas fa-edit"></i> Edit
</button>
                    <button class="btn btn-info btn-sm more-info-btn" data-assistant-id="${assistant.id}" data-assistant-name="${assistant.name}">
                        <i class="fas fa-info-circle"></i> View
                    </button>
                `
                        }));

                        // Pass the processed data to DataTables using the callback function
                        callback({
                            data: formattedData
                        });
                    })
                    .catch(err => {
                        console.error("Error fetching assistants data:", err);
                        callback({
                            data: []  // Send empty data in case of an error to prevent breaking the table
                        });
                    });
            },
            columns: [
                { data: 'name', title: 'Name' },
                { data: 'type', title: 'Type' },
                { data: 'interactive', title: 'Interactive' },
                { data: 'system_message', title: 'System Message' },
                { data: 'model', title: 'Model' },
                { data: 'tools', title: 'Tools' },
                { data: 'created_at', title: 'Created At' },
                { data: 'actions', title: 'Actions', orderable: false, searchable: false }
            ],
            destroy: true,
            searching: true,
            ordering: true,
            paging: true
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

</script>
