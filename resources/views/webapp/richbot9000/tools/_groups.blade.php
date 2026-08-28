<table id="groupsTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Type</th>
        <th>Tools</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Group Edit Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1" aria-labelledby="editGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGroupModalLabel">Edit Tool Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editGroupForm">
                    <input type="hidden" id="groupId">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="groupName" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="groupDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="groupType" class="form-label">Type</label>
                        <select class="form-control" id="groupType" required>
                            @foreach(\App\Models\ToolGroup::getTypes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveGroupButton">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    const groupsTable = $('#groupsTable').DataTable({
        ajax: {
            url: '/api/tool-groups',
            headers: apiHeaders()
        },
        columns: [
            { data: 'name' },
            { data: 'description' },
            { 
                data: 'type',
                render: function(data) {
                    const types = @json(\App\Models\ToolGroup::getTypes());
                    return types[data] || data;
                }
            },
            { 
                data: 'tools',
                render: function(data) {
                    return data.length ? data.map(tool => tool.name).join('<br>') : '<span class="text-muted">No Tools</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-primary btn-sm edit-group-btn" data-group-id="${data.id}" title="Edit Group">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm delete-group-btn" data-group-id="${data.id}" title="Delete Group">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    function editGroup(groupId) {
        ajaxRequest(`/api/tool-groups/${groupId}`)
            .then(data => {
                document.getElementById('groupId').value = data.id;
                document.getElementById('groupName').value = data.name;
                document.getElementById('groupDescription').value = data.description;
                document.getElementById('groupType').value = data.type;
                new bootstrap.Modal(document.getElementById('editGroupModal')).show();
            });
    }

    document.getElementById('saveGroupButton').addEventListener('click', function() {
        const groupId = document.getElementById('groupId').value;
        const groupData = {
            name: document.getElementById('groupName').value,
            description: document.getElementById('groupDescription').value,
            type: document.getElementById('groupType').value
        };

        ajaxRequest(`/api/tool-groups/${groupId}`, 'PUT', groupData)
            .then(() => {
                showAlert('Tool group updated successfully.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editGroupModal')).hide();
                reloadGroupsTable();
            })
            .catch(err => showAlert('Error updating tool group. Please try again.', 'danger'));
    });
</script> 