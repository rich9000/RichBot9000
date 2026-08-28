<div id="tool-groups-container">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Tool Groups</h2>
        <button onclick="toolGroups.openAddModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Group
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="tool-groups-list">
        <!-- Groups will be loaded here -->
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="tool-group-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Add Tool Group</h3>
            <button class="close" onclick="toolGroups.closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="tool-group-form">
                <input type="hidden" id="group-id">
                <div class="form-group">
                    <label for="group-name">Name</label>
                    <input type="text" id="group-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="group-description">Description</label>
                    <textarea id="group-description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Tools</label>
                    <div id="tools-checkbox-container" class="tools-checkbox-group">
                        <!-- Tools will be loaded here -->
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="toolGroups.closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="toolGroups.saveGroup()">Save</button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 50%;
    max-width: 500px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-body {
    margin-bottom: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.close {
    font-size: 24px;
    cursor: pointer;
}

.tools-checkbox-group {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ccc;
    padding: 10px;
    margin-top: 5px;
}

.tools-checkbox-group .form-check {
    margin-bottom: 5px;
}
</style>

<script>
class ToolGroups {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.modal = document.getElementById('tool-group-modal');
        this.form = document.getElementById('tool-group-form');
        this.groups = [];
        this.currentGroup = null;
        this.availableTools = [];

        this.loadGroups();
        this.loadAvailableTools();
    }

    async loadGroups() {
        try {
            const response = await fetch('/api/tool-groups', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            this.groups = await response.json();
            this.renderGroups();
        } catch (error) {
            console.error('Error loading groups:', error);
        }
    }

    async loadAvailableTools() {
        try {
            const response = await fetch('/api/tools', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            this.availableTools = await response.json();
        } catch (error) {
            console.error('Error loading tools:', error);
        }
    }

    renderGroups() {
        const list = document.getElementById('tool-groups-list');
        list.innerHTML = this.groups.map(group => `
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">${group.name}</h4>
                    <p class="card-text">${group.description || 'No description'}</p>
                    <div class="card-footer">
                        <button onclick="toolGroups.openEditModal(${group.id})" class="btn btn-sm btn-secondary">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="toolGroups.deleteGroup(${group.id})" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderToolsCheckboxes(selectedToolIds = []) {
        const container = document.getElementById('tools-checkbox-container');
        container.innerHTML = this.availableTools.map(tool => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" 
                    id="tool-${tool.id}" 
                    value="${tool.id}"
                    ${selectedToolIds.includes(tool.id) ? 'checked' : ''}>
                <label class="form-check-label" for="tool-${tool.id}">
                    ${tool.name}
                    ${tool.description ? `<small class="text-muted d-block">${tool.description}</small>` : ''}
                </label>
            </div>
        `).join('');
    }

    openAddModal() {
        this.currentGroup = null;
        document.getElementById('modal-title').textContent = 'Add Tool Group';
        document.getElementById('group-id').value = '';
        document.getElementById('group-name').value = '';
        document.getElementById('group-description').value = '';
        this.renderToolsCheckboxes();
        this.modal.style.display = 'block';
    }

    openEditModal(groupId) {
        const group = this.groups.find(g => g.id === groupId);
        if (!group) return;

        this.currentGroup = group;
        document.getElementById('modal-title').textContent = 'Edit Tool Group';
        document.getElementById('group-id').value = group.id;
        document.getElementById('group-name').value = group.name;
        document.getElementById('group-description').value = group.description || '';
        this.renderToolsCheckboxes(group.tools.map(t => t.id));
        this.modal.style.display = 'block';
    }

    closeModal() {
        this.modal.style.display = 'none';
    }

    async saveGroup() {
        const id = document.getElementById('group-id').value;
        const name = document.getElementById('group-name').value;
        const description = document.getElementById('group-description').value;
        const selectedTools = Array.from(document.querySelectorAll('#tools-checkbox-container input[type="checkbox"]:checked'))
            .map(checkbox => parseInt(checkbox.value));

        if (!name) {
            alert('Name is required');
            return;
        }

        try {
            const url = id ? `/api/tool-groups/${id}` : '/api/tool-groups';
            const method = id ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken
                },
                body: JSON.stringify({ 
                    name, 
                    description,
                    tool_ids: selectedTools 
                })
            });

            if (!response.ok) throw new Error('Failed to save group');

            this.closeModal();
            await this.loadGroups();
        } catch (error) {
            console.error('Error saving group:', error);
            alert('Failed to save group');
        }
    }

    async deleteGroup(groupId) {
        if (!confirm('Are you sure you want to delete this group?')) return;

        try {
            const response = await fetch(`/api/tool-groups/${groupId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken
                }
            });

            if (!response.ok) throw new Error('Failed to delete group');

            await this.loadGroups();
        } catch (error) {
            console.error('Error deleting group:', error);
            alert('Failed to delete group');
        }
    }
}

// Initialize the tool groups manager
const toolGroups = new ToolGroups('tool-groups-container');
</script> 