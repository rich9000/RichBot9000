<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Integrations</h5>
        <button class="btn btn-primary btn-sm" onclick="openIntegrationModal()">
            <i class="fas fa-plus"></i> Add Integration
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="integrationsTable">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Auth Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="integrationsTableBody">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Integration Modal -->
<div class="modal fade" id="integrationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Integration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="integrationForm">
                    <input type="hidden" id="integration_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Scope</label>
                        <select class="form-select" name="scope" id="scope" required>
                            <option value="user">User</option>
                            <option value="system">System</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Service</label>
                        <input type="text" class="form-control" name="service" id="service" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Service Type</label>
                        <select class="form-select" name="service_type" id="service_type" required>
                            <option value="api">API</option>
                            <option value="email">Email</option>
                            <option value="database">Database</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Auth Type</label>
                        <select class="form-select" name="auth_type" id="auth_type" onchange="toggleAuthFields()" required>
                            <option value="basic">Basic Auth</option>
                            <option value="bearer">Bearer Token</option>
                            <option value="oauth2">OAuth2</option>
                            <option value="apikey">API Key</option>
                        </select>
                    </div>

                    <div id="authFields">
                        <!-- Dynamic auth fields will be inserted here -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveIntegration()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
const integrationModal = new bootstrap.Modal(document.getElementById('integrationModal'));
let editingId = null;

function loadIntegrations() {
    fetch('/api/integrations', {
        headers: apiHeaders()
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('integrationsTableBody');
        tbody.innerHTML = data.map(integration => `
            <tr>
                <td>${integration.service}</td>
                <td>${integration.service_type}</td>
                <td><span class="badge bg-${integration.scope === 'system' ? 'danger' : 'primary'}">${integration.scope}</span></td>
                <td>${integration.auth_type}</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="editIntegration(${integration.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteIntegration(${integration.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    });
}

function openIntegrationModal(id = null) {
    editingId = id;
    document.getElementById('modalTitle').textContent = id ? 'Edit Integration' : 'Add Integration';
    document.getElementById('integrationForm').reset();
    
    if (id) {
        fetch(`/api/integrations/${id}`, {
            headers: apiHeaders()
        })
        .then(response => response.json())
        .then(data => {
            Object.keys(data).forEach(key => {
                const input = document.getElementById(key);
                if (input) input.value = data[key];
            });
            toggleAuthFields();
        });
    }
    
    integrationModal.show();
}

function toggleAuthFields() {
    const authType = document.getElementById('auth_type').value;
    const authFields = document.getElementById('authFields');
    
    const fields = {
        basic: `
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" id="username">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password">
            </div>
        `,
        bearer: `
            <div class="mb-3">
                <label class="form-label">Access Token</label>
                <input type="text" class="form-control" name="access_token" id="access_token">
            </div>
        `,
        oauth2: `
            <div class="mb-3">
                <label class="form-label">Access Token</label>
                <input type="text" class="form-control" name="access_token" id="access_token">
            </div>
            <div class="mb-3">
                <label class="form-label">Refresh Token</label>
                <input type="text" class="form-control" name="refresh_token" id="refresh_token">
            </div>
            <div class="mb-3">
                <label class="form-label">Token Expires At</label>
                <input type="datetime-local" class="form-control" name="token_expires_at" id="token_expires_at">
            </div>
        `,
        apikey: `
            <div class="mb-3">
                <label class="form-label">API Key</label>
                <input type="text" class="form-control" name="api_key" id="api_key">
            </div>
        `
    };
    
    authFields.innerHTML = fields[authType] || '';
}

function saveIntegration() {
    const form = document.getElementById('integrationForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const url = editingId ? `/api/integrations/${editingId}` : '/api/integrations';
    const method = editingId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            ...apiHeaders(),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        showAlert('Integration saved successfully');
        integrationModal.hide();
        loadIntegrations();
    })
    .catch(error => {
        showAlert('Error saving integration', 'danger');
    });
}

function deleteIntegration(id) {
    if (!confirm('Are you sure you want to delete this integration?')) return;
    
    fetch(`/api/integrations/${id}`, {
        method: 'DELETE',
        headers: apiHeaders()
    })
    .then(() => {
        showAlert('Integration deleted successfully');
        loadIntegrations();
    })
    .catch(error => {
        showAlert('Error deleting integration', 'danger');
    });
}

function editIntegration(id) {
    fetch(`/api/integrations/${id}`, {
        headers: apiHeaders()
    })
    .then(response => response.json())
    .then(data => {
        openIntegrationModal(id);
        Object.keys(data).forEach(key => {
            const input = document.getElementById(key);
            if (input) input.value = data[key];
        });
        toggleAuthFields();
    })
    .catch(error => {
        showAlert('Error loading integration', 'danger');
    });
}

// Initial load
loadIntegrations();
toggleAuthFields();
</script> 