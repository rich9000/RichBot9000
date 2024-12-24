<!-- Add Tool Button -->
<button id="addToolButton" class="btn btn-success mb-3">Add Tool</button>

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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveToolButton">Save Tool</button>
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
        <th>Strict</th>
        <th>Parameters</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<script>
    // Load Tools into DataTable
    function loadToolsTable() {
        fetch('/api/tools', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#toolsTable tbody');
                tbody.innerHTML = data.map(tool => `
            <tr>
                <td>${tool.name}</td>
                <td>${tool.description}</td>
                <td>${tool.strict ? 'Yes' : 'No'}</td>
                <td>${tool.parameters.map(p => p.name).join(', ')}</td>
                <td>
                    <button class="btn btn-warning btn-sm edit-tool-btn" data-tool-id="${tool.id}">Edit</button>
                    <button class="btn btn-danger btn-sm delete-tool-btn" data-tool-id="${tool.id}">Delete</button>
                </td>
            </tr>
        `).join('');
            });
    }

    // Handle Add Tool Button
    document.getElementById('addToolButton').addEventListener('click', () => {
        openToolModal();  // Open modal for adding
    });

    // Handle Edit Tool Button (delegate event)
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('edit-tool-btn')) {
            const toolId = event.target.getAttribute('data-tool-id');
            openToolModal(toolId);  // Open modal for editing
        }
    });

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
                    // Load existing parameters
                    loadParameters(tool.parameters);
                });
        } else {
            document.getElementById('toolModalLabel').textContent = 'Add Tool';
        }

        new bootstrap.Modal(document.getElementById('toolModal')).show();
    }

    // Clear the Tool Form
    function clearToolForm() {
        document.getElementById('toolForm').reset();
        document.getElementById('parameterInputs').innerHTML = '';
        document.getElementById('toolId').value = '';
    }

    // Add parameter input fields dynamically
    document.getElementById('addParameterButton').addEventListener('click', () => {
        const parameterSection = document.getElementById('parameterInputs');
        const paramId = `param-${Date.now()}`; // Unique ID
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
    document.getElementById('saveToolButton').addEventListener('click', () => {
        const toolId = document.getElementById('toolId').value;
        const toolName = document.getElementById('toolName').value;
        const toolDescription = document.getElementById('toolDescription').value;
        const strict = document.getElementById('strictCheck').checked;
        const parameters = Array.from(document.querySelectorAll('#parameterInputs .mb-3')).map(paramDiv => ({
            name: paramDiv.querySelector('input[id^="paramName"]').value,
            type: paramDiv.querySelector('input[id^="paramType"]').value,
            required: paramDiv.querySelector('input[id^="paramRequired"]').checked
        }));

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
                parameters: parameters
            })
        })
            .then(response => response.json())
            .then(() => {
                alert('Tool saved successfully.');
                new bootstrap.Modal(document.getElementById('toolModal')).hide();
                loadToolsTable();
            })
            .catch(err => alert('Error saving tool. Please try again.'));
    });

    // Load Tools on page load

        loadToolsTable();

</script>
