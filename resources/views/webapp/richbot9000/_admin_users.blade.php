<button id="loadUsersButton" class="btn btn-primary mb-3">Refresh Users</button>
<table id="usersTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Roles</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- User View Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Roles Modal -->
<div class="modal fade" id="rolesModal" tabindex="-1" aria-labelledby="rolesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rolesModalLabel">Assign Roles - <strong id="userNameSpan"></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rolesForm">
                    <input type="hidden" id="userName">
                    <input type="hidden" id="userId">
                    <div class="mb-3">
                        <label for="rolesCheckboxes" class="form-label">Roles</label>
                        <div class="form-check" id="rolesCheckboxes"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRolesButton">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize the table immediately
    loadUsersDataTables();

    // Single click handler for refresh button
    document.getElementById('loadUsersButton').addEventListener('click', loadUsersDataTables);

    function loadUsersDataTables() {
        ajaxRequest("/api/users")
            .then(data => {
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = data.map(user => `
                    <tr>
                        <td>${user.name}</td>
                        <td>${user.email}${user.email_verified_at ? '' : '<span class="text-danger"> <i class="fas fa-exclamation-circle"></i></span>'}</td>
                        <td>${user.phone_number ? `${user.phone_number}${user.phone_verified_at ? '' : '<span class="text-danger"> <i class="fas fa-exclamation-circle"></i></span>'}` : '<span class="text-muted">N/A</span>'}</td>
                        <td>${user.roles.length ? user.roles.map(role => role.name).join(', ') : '<span class="text-muted">No Roles</span>'}
                        <button class="btn btn-primary btn-sm assign-roles-btn ms-2" data-user-id="${user.id}" data-user-name="${user.name}">Assign Roles</button></td>
                        <td>${new Date(user.created_at).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-info btn-sm view-user-btn" data-user-id="${user.id}">View</button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(error => {
                console.error('Error loading users:', error);
                showAlert('Error loading users. Please try again.', 'danger');
            });
    }

    // Handle view button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-user-btn')) {
            const userId = e.target.dataset.userId;
            const viewModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
            
            ajaxRequest(`/api/users/${userId}`)
                .then(user => {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="user-details">
                            <p><strong>Name:</strong> ${user.name}</p>
                            <p><strong>Email:</strong> ${user.email}</p>
                            <p><strong>Phone:</strong> ${user.phone_number || 'N/A'}</p>
                            <p><strong>Roles:</strong> ${user.roles.map(role => role.name).join(', ') || 'No roles assigned'}</p>
                            <p><strong>Created:</strong> ${new Date(user.created_at).toLocaleString()}</p>
                        </div>
                    `;
                    viewModal.show();
                });
        }
    });

    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('assign-roles-btn')) {
            const userId = event.target.getAttribute('data-user-id');
            const userName = event.target.getAttribute('data-user-name');
            document.getElementById('userNameSpan').textContent = userName;
            document.getElementById('userId').value = userId;
            loadRoles(userId);
            new bootstrap.Modal(document.getElementById('rolesModal')).show();
        }
    });

    function loadRoles(userId) {
        const rolesCheckboxes = document.getElementById('rolesCheckboxes');
        rolesCheckboxes.innerHTML = '';

        ajaxRequest('/api/roles')
            .then(roles => {
                roles.forEach(role => {
                    const div = document.createElement('div');
                    div.classList.add('form-check');
                    div.innerHTML = `
                        <input class="form-check-input" type="checkbox" value="${role.id}" id="role-${role.id}">
                        <label class="form-check-label" for="role-${role.id}">${role.name}</label>
                    `;
                    rolesCheckboxes.appendChild(div);
                });
                loadUserRoles(userId);
            });
    }

    function loadUserRoles(userId) {
        ajaxRequest(`/api/users/${userId}`)
            .then(user => {
                user.roles.forEach(role => {
                    document.getElementById(`role-${role.id}`).checked = true;
                });
            });
    }

    document.getElementById('saveRolesButton').addEventListener('click', function() {
        const userId = document.getElementById('userId').value;
        const selectedRoles = Array.from(document.querySelectorAll('#rolesCheckboxes input:checked')).map(checkbox => checkbox.value);

        ajaxRequest(`/api/users/${userId}/roles`, 'POST', { roles: selectedRoles })
            .then(() => {
                showAlert('Roles updated successfully.', 'success');
                new bootstrap.Modal(document.getElementById('rolesModal')).hide();
                loadUsersDataTables();
            })
            .catch(err => showAlert('Error saving roles. Please try again.', 'danger'));
    });
</script>
