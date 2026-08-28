<!-- Add Tool Button -->
<div class="d-flex gap-2 mb-3">
    <button id="addToolButton" class="btn btn-success" style="display: none;">Add Tool</button>
    <button id="refreshDataButton" class="btn btn-secondary">
        <i class="fas fa-sync-alt"></i> Refresh Data
    </button>
</div>

<!-- Tool Modal -->
<div class="modal fade" id="toolModal" tabindex="-1" aria-labelledby="toolModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toolModalLabel">Add/Edit Tool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="toolForm">
                    <input type="hidden" id="toolId">
                    <div class="mb-3">
                        <label for="toolName" class="form-label">Tool Name</label>
                        <input type="text" class="form-control" id="toolName" required>
                    </div>
                    <div class="mb-3">
                        <label for="toolDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="toolDescription" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="strictCheck" class="form-label">Strict</label>
                        <input type="checkbox" class="form-check-input" id="strictCheck">
                    </div>
                    <div class="mb-3">
                        <label for="parametersSection" class="form-label">Parameters</label>
                        <div id="parametersSection">
                            <button type="button" id="addParameterButton" class="btn btn-info btn-sm">Add Parameter</button>
                            <div id="parameterInputs"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Groups</label>
                        <div id="groupsCheckboxContainer" class="tools-checkbox-group">
                            <!-- Groups will be loaded here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveToolButton">Save Tool</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Tool Modal -->
<div class="modal fade" id="testToolModal" tabindex="-1" aria-labelledby="testToolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testToolModalLabel">Test Tool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Tool Parameters</h6>
                        <form id="testToolForm">
                            <input type="hidden" id="testToolId">
                            <div id="testParameterInputs">
                                <!-- Parameters will be loaded here -->
                            </div>
                            <button type="button" class="btn btn-primary" id="testToolButton">
                                <i class="fas fa-play"></i> Test Tool
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6>Test Results</h6>
                        <div id="testResults" class="border rounded p-3" style="min-height: 200px; background-color: #f8f9fa;">
                            <p class="text-muted">Results will appear here after testing...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Tool Table -->
<table id="toolsTable" class="table table-bordered table-striped">
    <thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Parameters</th>
        <th>Groups</th>
        <th>Assistants</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<style>
.tools-checkbox-group {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ccc;
    padding: 10px;
    margin-top: 5px;
}

.tools-checkbox-group .form-check {
    margin-bottom: 5px;
}

/* DataTables custom styling */
.dataTables_wrapper .dataTables_length select {
    width: 60px;
    display: inline-block;
}

.dataTables_wrapper .dataTables_filter input {
    width: 200px;
    margin-left: 0.5em;
}

.dataTables_wrapper .dataTables_info {
    padding-top: 0.85em;
}

.dataTables_wrapper .dataTables_paginate {
    padding-top: 0.25em;
}
</style>

<script>
(function() {
    let availableGroups = [];
    let toolsTable;
    let toolModal;
    let testToolModal;
    let isInitialized = false;

    // Check if user has required roles for tools
    function hasToolsAccess() {
        const userRoles = appState.user?.roles?.map(role => role.name.toLowerCase()) || [];
        return userRoles.some(role => ['tools_user', 'tools_admin'].includes(role));
    }

    // Check if user can edit a tool
    function canEditTool(tool) {
        const userRoles = appState.user?.roles?.map(role => role.name.toLowerCase()) || [];
        return userRoles.includes('tools_admin') || tool.user_id === appState.user?.id;
    }

    // Show/hide add button based on roles
    function updateToolsButtonVisibility() {
        const addButton = document.getElementById('addToolButton');
        if (addButton) {
            addButton.style.display = hasToolsAccess() ? 'inline-block' : 'none';
        }
    }

    // Call this when the page loads and when roles change
    document.addEventListener('DOMContentLoaded', updateToolsButtonVisibility);
    document.addEventListener('roleChanged', updateToolsButtonVisibility);

    // Cleanup function to remove event listeners and destroy DataTable
    function cleanupTools() {
        if (toolsTable) {
            toolsTable.destroy();
            toolsTable = null;
        }
        
        // Remove event listeners
        const addToolButton = document.getElementById('addToolButton');
        const addParameterButton = document.getElementById('addParameterButton');
        const saveToolButton = document.getElementById('saveToolButton');
        const refreshDataButton = document.getElementById('refreshDataButton');
        const testToolButton = document.getElementById('testToolButton');
        
        if (addToolButton) {
            addToolButton.replaceWith(addToolButton.cloneNode(true));
        }
        if (addParameterButton) {
            addParameterButton.replaceWith(addParameterButton.cloneNode(true));
        }
        if (saveToolButton) {
            saveToolButton.replaceWith(saveToolButton.cloneNode(true));
        }
        if (refreshDataButton) {
            refreshDataButton.replaceWith(refreshDataButton.cloneNode(true));
        }
        if (testToolButton) {
            testToolButton.replaceWith(testToolButton.cloneNode(true));
        }

        // Clear any existing event listeners on the table
        const toolsTableElement = document.getElementById('toolsTable');
        if (toolsTableElement) {
            const newTable = toolsTableElement.cloneNode(true);
            toolsTableElement.parentNode.replaceChild(newTable, toolsTableElement);
        }

        isInitialized = false;
    }

    // Load Groups
    async function loadGroups() {
        try {
            const response = await fetch('/api/tool-groups', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            availableGroups = await response.json();
        } catch (error) {
            console.error('Error loading groups:', error);
        }
    }

    // Refresh all data
    async function refreshData() {
        try {
            await loadGroups();
            if (toolsTable) {
                toolsTable.ajax.reload();
            }
            showAlert('Data refreshed successfully', 'success');
        } catch (error) {
            console.error('Error refreshing data:', error);
            showAlert('Error refreshing data', 'danger');
        }
    }

    // Initialize DataTable
    function initializeToolsTable() {
        if (isInitialized) {
            cleanupTools();
        }

        toolsTable = $('#toolsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/tools',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                dataSrc: ''
            },
            columns: [
                { 
                    data: 'name',
                    width: '15%'
                },
                { 
                    data: 'description',
                    width: '25%',
                    render: function(data) {
                        return `<span title="${data}">${data.length > 100 ? data.substring(0, 100) + '...' : data}</span>`;
                    }
                },
                { 
                    data: 'parameters',
                    width: '15%',
                    render: function(data) {
                        return data.map(p => p.name).join('<br>');
                    }
                },
                { 
                    data: 'groups',
                    width: '15%',
                    render: function(data) {
                        return data ? data.map(g => g.name).join('<br>') : '';
                    }
                },
                { 
                    data: 'assistants',
                    width: '15%',
                    render: function(data) {
                        return data ? data.map(a => a.name).join('<br>') : '';
                    }
                },
                {
                    data: 'id',
                    width: '15%',
                    render: function(data, type, row) {
                        const testButton = `<button class="btn btn-info btn-sm test-tool-btn" data-tool-id="${data}">Test</button>`;
                        const editDeleteButtons = canEditTool(row) ? `
                            <button class="btn btn-warning btn-sm edit-tool-btn" data-tool-id="${data}">Edit</button>
                            <button class="btn btn-danger btn-sm delete-tool-btn" data-tool-id="${data}">Delete</button>
                        ` : '';
                        return testButton + ' ' + editDeleteButtons;
                    },
                    orderable: false
                }
            ],
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "Search tools:",
                lengthMenu: "Show _MENU_ tools per page",
                info: "Showing _START_ to _END_ of _TOTAL_ tools",
                infoEmpty: "No tools available",
                infoFiltered: "(filtered from _MAX_ total tools)"
            },
            autoWidth: false,
            scrollX: true
        });

        // Add event listener for delete buttons
        $('#toolsTable').on('click', '.delete-tool-btn', function() {
            const toolId = $(this).data('tool-id');
            if (confirm('Are you sure you want to delete this tool?')) {
                deleteTool(toolId);
            }
        });

        isInitialized = true;
    }

    // Delete Tool
    function deleteTool(toolId) {
        fetch(`/api/tools/${toolId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to delete tool');
            toolsTable.ajax.reload();
            alert('Tool deleted successfully.');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting tool. Please try again.');
        });
    }

    // Render Groups Checkboxes
    function renderGroupsCheckboxes(selectedGroupIds = []) {
        const container = document.getElementById('groupsCheckboxContainer');
        container.innerHTML = availableGroups.map(group => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" 
                    id="group-${group.id}" 
                    value="${group.id}"
                    ${selectedGroupIds.includes(group.id) ? 'checked' : ''}>
                <label class="form-check-label" for="group-${group.id}">
                    ${group.name}
                    ${group.description ? `<small class="text-muted d-block">${group.description}</small>` : ''}
                </label>
            </div>
        `).join('');
    }

    // Initialize event listeners
    function initializeEventListeners() {
        // Handle Add Tool Button
        document.getElementById('addToolButton').addEventListener('click', () => {
            openToolModal();
        });

        // Handle Edit Tool Button (delegate event)
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('edit-tool-btn')) {
                const toolId = event.target.getAttribute('data-tool-id');
                openToolModal(toolId);
            }
        });

        // Handle Test Tool Button (delegate event)
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('test-tool-btn')) {
                const toolId = event.target.getAttribute('data-tool-id');
                openTestToolModal(toolId);
            }
        });

        // Add parameter input fields dynamically
        document.getElementById('addParameterButton').addEventListener('click', () => {
            const parameterSection = document.getElementById('parameterInputs');
            const paramId = `param-${Date.now()}`;
            const paramHtml = `
            <div class="mb-3" id="${paramId}">
                <label for="paramName-${paramId}" class="form-label">Parameter Name</label>
                <input type="text" class="form-control" id="paramName-${paramId}" required>
                <label for="paramType-${paramId}" class="form-label">Type</label>
                <input type="text" class="form-control" id="paramType-${paramId}" required>
                <label for="paramRequired-${paramId}" class="form-label">Required</label>
                <input type="checkbox" class="form-check-input" id="paramRequired-${paramId}">
                <button type="button" class="btn btn-danger btn-sm remove-parameter-btn" data-param-id="${paramId}">Remove</button>
            </div>
            `;
            parameterSection.insertAdjacentHTML('beforeend', paramHtml);
        });

        // Remove parameter input
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-parameter-btn')) {
                const paramId = event.target.getAttribute('data-param-id');
                document.getElementById(paramId).remove();
            }
        });

        // Save Tool
        document.getElementById('saveToolButton').addEventListener('click', saveTool);

        // Test Tool
        document.getElementById('testToolButton').addEventListener('click', executeTestTool);

        // Handle Refresh Data Button
        document.getElementById('refreshDataButton').addEventListener('click', () => {
            refreshData();
        });
    }

    // Open the Tool Modal (for add or edit)
    function openToolModal(toolId = null) {
        clearToolForm();
        if (toolId) {
            fetch(`/api/tools/${toolId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(tool => {
                    document.getElementById('toolModalLabel').textContent = 'Edit Tool';
                    document.getElementById('toolId').value = tool.id;
                    document.getElementById('toolName').value = tool.name;
                    document.getElementById('toolDescription').value = tool.description;
                    document.getElementById('strictCheck').checked = tool.strict;
                    loadParameters(tool.parameters);
                    renderGroupsCheckboxes(tool.groups ? tool.groups.map(g => g.id) : []);
                });
        } else {
            document.getElementById('toolModalLabel').textContent = 'Add Tool';
            renderGroupsCheckboxes();
        }

        if (!toolModal) {
            toolModal = new bootstrap.Modal(document.getElementById('toolModal'));
        }
        toolModal.show();
    }

    // Clear the Tool Form
    function clearToolForm() {
        document.getElementById('toolForm').reset();
        document.getElementById('parameterInputs').innerHTML = '';
        document.getElementById('toolId').value = '';
    }

    // Load existing parameters in the form (for editing)
    function loadParameters(parameters) {
        const parameterSection = document.getElementById('parameterInputs');
        parameters.forEach(param => {
            const paramId = `param-${Date.now()}`;
            const paramHtml = `
            <div class="mb-3" id="${paramId}">
                <label for="paramName-${paramId}" class="form-label">Parameter Name</label>
                <input type="text" class="form-control" id="paramName-${paramId}" value="${param.name}" required>
                <label for="paramType-${paramId}" class="form-label">Type</label>
                <input type="text" class="form-control" id="paramType-${paramId}" value="${param.type}" required>
                <label for="paramRequired-${paramId}" class="form-label">Required</label>
                <input type="checkbox" class="form-check-input" id="paramRequired-${paramId}" ${param.required ? 'checked' : ''}>
                <button type="button" class="btn btn-danger btn-sm remove-parameter-btn" data-param-id="${paramId}">Remove</button>
            </div>
            `;
            parameterSection.insertAdjacentHTML('beforeend', paramHtml);
        });
    }

    // Save Tool (Add/Edit)
    function saveTool() {
        const toolId = document.getElementById('toolId').value;
        const toolName = document.getElementById('toolName').value;
        const toolDescription = document.getElementById('toolDescription').value;
        const strict = document.getElementById('strictCheck').checked;
        const parameters = Array.from(document.querySelectorAll('#parameterInputs .mb-3')).map(paramDiv => ({
            name: paramDiv.querySelector('input[id^="paramName"]').value,
            type: paramDiv.querySelector('input[id^="paramType"]').value,
            required: paramDiv.querySelector('input[id^="paramRequired"]').checked
        }));
        const groupIds = Array.from(document.querySelectorAll('#groupsCheckboxContainer input[type="checkbox"]:checked'))
            .map(checkbox => parseInt(checkbox.value));

        const url = toolId ? `/api/tools/${toolId}` : '/api/tools';
        const method = toolId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: toolName,
                description: toolDescription,
                strict: strict,
                parameters: parameters,
                group_ids: groupIds
            })
        })
        .then(response => response.json())
        .then(() => {
            toolModal.hide();
            toolsTable.ajax.reload();
            alert('Tool saved successfully.');
        })
        .catch(err => alert('Error saving tool. Please try again.'));
    }

    // Open the Test Tool Modal
    function openTestToolModal(toolId) {
        // Clear previous results
        document.getElementById('testResults').innerHTML = '<p class="text-muted">Results will appear here after testing...</p>';
        
        // Load tool details and parameters
        fetch(`/api/tools/${toolId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(tool => {
            document.getElementById('testToolModalLabel').textContent = `Test Tool: ${tool.name}`;
            document.getElementById('testToolId').value = tool.id;
            loadTestParameters(tool.parameters);
        })
        .catch(error => {
            console.error('Error loading tool:', error);
            alert('Error loading tool details.');
        });

        if (!testToolModal) {
            testToolModal = new bootstrap.Modal(document.getElementById('testToolModal'));
        }
        testToolModal.show();
    }

    // Load test parameters in the form
    function loadTestParameters(parameters) {
        const parameterSection = document.getElementById('testParameterInputs');
        parameterSection.innerHTML = '';

        if (!parameters || parameters.length === 0) {
            parameterSection.innerHTML = '<p class="text-muted">This tool has no parameters.</p>';
            return;
        }

        parameters.forEach(param => {
            const paramHtml = `
            <div class="mb-3">
                <label for="test-param-${param.name}" class="form-label">
                    ${param.name} ${param.required ? '<span class="text-danger">*</span>' : ''}
                    <small class="text-muted d-block">${param.type}${param.description ? ' - ' + param.description : ''}</small>
                </label>
                <input type="text" class="form-control" id="test-param-${param.name}" 
                    ${param.required ? 'required' : ''} placeholder="Enter ${param.name}">
            </div>
            `;
            parameterSection.insertAdjacentHTML('beforeend', paramHtml);
        });
    }

    // Execute the test tool
    function executeTestTool() {
        const toolId = document.getElementById('testToolId').value;
        const resultsDiv = document.getElementById('testResults');
        const testButton = document.getElementById('testToolButton');
        
        // Show loading state
        testButton.disabled = true;
        testButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        resultsDiv.innerHTML = '<p class="text-info"><i class="fas fa-spinner fa-spin"></i> Executing tool...</p>';

        // Collect parameters
        const parameters = {};
        const paramInputs = document.querySelectorAll('#testParameterInputs input[type="text"]');
        paramInputs.forEach(input => {
            if (input.value.trim()) {
                parameters[input.id.replace('test-param-', '')] = input.value.trim();
            }
        });

        // Execute the test
        fetch(`/api/tools/${toolId}/test`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ parameters: parameters })
        })
        .then(response => response.json())
        .then(data => {
            // Reset button
            testButton.disabled = false;
            testButton.innerHTML = '<i class="fas fa-play"></i> Test Tool';

            if (data.success) {
                // Display successful results
                const resultHtml = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Tool executed successfully!</h6>
                        <hr>
                        <h6>Parameters used:</h6>
                        <pre class="bg-light p-2 rounded">${JSON.stringify(data.parameters_used, null, 2)}</pre>
                        <hr>
                        <h6>Result:</h6>
                        <pre class="bg-light p-2 rounded">${JSON.stringify(data.result, null, 2)}</pre>
                    </div>
                `;
                resultsDiv.innerHTML = resultHtml;
            } else {
                // Display error
                const errorHtml = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Tool execution failed!</h6>
                        <hr>
                        <p><strong>Error:</strong> ${data.error}</p>
                    </div>
                `;
                resultsDiv.innerHTML = errorHtml;
            }
        })
        .catch(error => {
            // Reset button
            testButton.disabled = false;
            testButton.innerHTML = '<i class="fas fa-play"></i> Test Tool';

            // Display error
            const errorHtml = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> Network error!</h6>
                    <hr>
                    <p><strong>Error:</strong> ${error.message}</p>
                </div>
            `;
            resultsDiv.innerHTML = errorHtml;
        });
    }

    // Initialize everything
    async function initializeTools() {
        await loadGroups();
        initializeToolsTable();
        initializeEventListeners();
        updateToolsButtonVisibility();
    }

    // Initial load
    initializeTools();

    // Cleanup when the section is hidden
    document.addEventListener('sectionHidden', function(e) {
        if (e.detail.sectionId === 'ollama-tools-section') {
            cleanupTools();
        }
    });
})();
</script>
