<!-- Add toastr CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Scripts</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" id="addScriptBtn">
                            <i class="fas fa-plus"></i> Add Script
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="scriptsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Path</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Executions</th>
                                    <th>Last Executed</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Script Modal -->
<div class="modal fade" id="scriptModal" tabindex="-1" role="dialog" aria-labelledby="scriptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scriptModalLabel">Add Script</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scriptForm">
                    <input type="hidden" id="scriptId">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <input type="text" class="form-control" id="type" required>
                    </div>
                    <div class="form-group">
                        <label for="path">Path</label>
                        <input type="text" class="form-control" id="path" required>
                    </div>
                    <div class="form-group">
                        <label for="parameters">Parameters (JSON)</label>
                        <textarea class="form-control" id="parameters" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="return_type">Return Type</label>
                        <select class="form-control" id="return_type">
                            <option value="string">String</option>
                            <option value="number">Number</option>
                            <option value="boolean">Boolean</option>
                            <option value="object">Object</option>
                            <option value="array">Array</option>
                            <option value="null">Null</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active">
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveScriptBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
class ScriptsManager {
    constructor(container) {
        this.container = container;
        this.table = null;
        this.init();
    }

    init() {
        this.initializeTable();
        this.bindEvents();
        this.loadScripts();
    }

    initializeTable() {
        this.table = $('#scriptsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/scripts',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                dataSrc: ''
            },
            columns: [
                { data: 'name' },
                { data: 'type' },
                { data: 'path' },
                { data: 'description' },
                { 
                    data: 'is_active',
                    render: (data) => data ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'
                },
                { data: 'execution_count' },
                { 
                    data: 'last_executed_at',
                    render: (data) => data ? new Date(data).toLocaleString() : 'Never'
                },
                { 
                    data: 'user',
                    render: (data) => data ? data.name : 'Unknown'
                },
                {
                    data: null,
                    render: (data) => `
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info" onclick="scriptsManager.editScript(${data.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-success" onclick="scriptsManager.executeScript(${data.id})">
                                <i class="fas fa-play"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="scriptsManager.deleteScript(${data.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `
                }
            ],
            initComplete: () => {
                console.log('Table initialization complete');
                this.bindTableEvents();
            },
            drawCallback: () => {
                console.log('Table redraw complete');
                this.bindTableEvents();
            }
        });
    }

    bindEvents() {
        $('#addScriptBtn').on('click', () => this.showModal());
        $('#saveScriptBtn').on('click', () => this.saveScript());
        
        // Close modal when clicking the close button or outside the modal
        $('.close, .modal').on('click', (e) => {
            if (e.target === e.currentTarget) {
                $('#scriptModal').modal('hide');
            }
        });
        
        // Prevent modal from closing when clicking inside
        $('.modal-content').on('click', (e) => {
            e.stopPropagation();
        });
    }

    bindTableEvents() {
        console.log('Binding table events...');
        
        // Remove any existing event handlers
        $(this.container).off('click', '.edit-script');
        $(this.container).off('click', '.execute-script');
        $(this.container).off('click', '.delete-script');

        // Add new event handlers with debugging
        $(this.container).on('click', '.edit-script', (e) => {
            console.log('Edit button clicked', e.currentTarget);
            e.preventDefault();
            e.stopPropagation();
            const id = $(e.currentTarget).data('id');
            console.log('Edit script ID:', id);
            this.editScript(id);
        });

        $(this.container).on('click', '.execute-script', (e) => {
            console.log('Execute button clicked', e.currentTarget);
            e.preventDefault();
            e.stopPropagation();
            const id = $(e.currentTarget).data('id');
            console.log('Execute script ID:', id);
            this.executeScript(id);
        });

        $(this.container).on('click', '.delete-script', (e) => {
            console.log('Delete button clicked', e.currentTarget);
            e.preventDefault();
            e.stopPropagation();
            const id = $(e.currentTarget).data('id');
            console.log('Delete script ID:', id);
            this.deleteScript(id);
        });

        // Verify event binding
        console.log('Event binding verification:');
        console.log('Edit buttons:', $(this.container).find('.edit-script').length);
        console.log('Execute buttons:', $(this.container).find('.execute-script').length);
        console.log('Delete buttons:', $(this.container).find('.delete-script').length);
    }

    loadScripts() {
        this.table.ajax.reload();
    }

    showModal(data = null) {
        $('#scriptModalLabel').text(data ? 'Edit Script' : 'Add Script');
        $('#scriptId').val(data ? data.id : '');
        $('#name').val(data ? data.name : '');
        $('#type').val(data ? data.type : '');
        $('#path').val(data ? data.path : '');
        $('#parameters').val(data ? JSON.stringify(data.parameters, null, 2) : '{}');
        $('#return_type').val(data ? data.return_type : 'string');
        $('#description').val(data ? data.description : '');
        $('#is_active').prop('checked', data ? data.is_active : true);
        $('#scriptModal').modal('show');
    }

    async saveScript() {
        try {
            // Parse parameters as JSON
            let parameters;
            try {
                parameters = JSON.parse($('#parameters').val() || '{}');
            } catch (e) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Invalid JSON in parameters field');
                } else {
                    alert('Invalid JSON in parameters field');
                }
                return;
            }

            const data = {
                name: $('#name').val(),
                type: $('#type').val(),
                path: $('#path').val(),
                parameters: parameters,
                return_type: $('#return_type').val(),
                description: $('#description').val(),
                is_active: $('#is_active').is(':checked')
            };

            const id = $('#scriptId').val();
            const url = id ? `/api/scripts/${id}` : '/api/scripts';
            const method = id ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                console.error('Failed to save script:', response.status);
                throw new Error('Failed to save script');
            }

            $('#scriptModal').modal('hide');
            this.loadScripts();
            if (typeof toastr !== 'undefined') {
                toastr.success('Script saved successfully');
            } else {
                alert('Script saved successfully');
            }
        } catch (error) {
            console.error('Error saving script:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Error saving script');
            } else {
                alert('Error saving script');
            }
        }
    }

    async editScript(id) {
        console.log('editScript called with ID:', id);
        try {
            const response = await fetch(`/api/scripts/${id}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                }
            });

            if (!response.ok) {
                console.error('Failed to fetch script:', response.status);
                throw new Error('Failed to fetch script');
            }

            const data = await response.json();
            console.log('Script data received:', data);
            this.showModal(data);
        } catch (error) {
            console.error('Error in editScript:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Error fetching script');
            } else {
                alert('Error fetching script');
            }
        }
    }

    async executeScript(id) {
        console.log('executeScript called with ID:', id);
        try {
            const response = await fetch(`/api/scripts/${id}/execute`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                }
            });

            if (!response.ok) {
                console.error('Failed to execute script:', response.status);
                throw new Error('Failed to execute script');
            }

            this.loadScripts();
            if (typeof toastr !== 'undefined') {
                toastr.success('Script executed successfully');
            } else {
                alert('Script executed successfully');
            }
        } catch (error) {
            console.error('Error in executeScript:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Error executing script');
            } else {
                alert('Error executing script');
            }
        }
    }

    async deleteScript(id) {
        console.log('deleteScript called with ID:', id);
        if (!confirm('Are you sure you want to delete this script?')) return;

        try {
            const response = await fetch(`/api/scripts/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                }
            });

            if (!response.ok) {
                console.error('Failed to delete script:', response.status);
                throw new Error('Failed to delete script');
            }

            this.loadScripts();
            if (typeof toastr !== 'undefined') {
                toastr.success('Script deleted successfully');
            } else {
                alert('Script deleted successfully');
            }
        } catch (error) {
            console.error('Error in deleteScript:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Error deleting script');
            } else {
                alert('Error deleting script');
            }
        }
    }
}

// Initialize the scripts manager when the page loads
const scriptsManager = new ScriptsManager(document.querySelector('.container-fluid'));
</script> 