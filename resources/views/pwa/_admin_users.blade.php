<button id="loadUsersButton" class="btn btn-primary mb-3">Load Users</button>
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
    document.getElementById('loadUsersButton').addEventListener('click', loadUsersDataTables);

    function loadUsersDataTables() {
        fetch("/api/users", {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = data.map(user => `
                <tr>
                    <td>${user.name}</td>
                    <td>${user.email}${user.email_verified_at ? '' : '<span class="text-danger"> <i class="fas fa-exclamation-circle"></i></span>'}</td>
                    <td>${user.phone_number ? `${user.phone_number}${user.phone_verified_at ? '' : '<span class="text-danger"> <i class="fas fa-exclamation-circle"></i></span>'}` : '<span class="text-muted">N/A</span>'}</td>
                    <td>${user.roles.length ? user.roles.map(role => role.name).join(', ') : '<span class="text-muted">No Roles</span>'}
                    <button class="btn btn-primary btn-sm assign-roles-btn" data-user-id="${user.id}" data-user-name="${user.name}">Assign Roles</button></td>
                    <td>${new Date(user.created_at).toLocaleString()}</td>
                    <td><button class="btn btn-info btn-sm more-info-btn" data-user-id="${user.id}" data-user-name="${user.name}">View</button></td>
                </tr>
            `).join('');
            });
    }

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

        fetch('/api/roles', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
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
        fetch(`/api/users/${userId}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(user => {
                user.roles.forEach(role => {
                    document.getElementById(`role-${role.id}`).checked = true;
                });
            });
    }

    document.getElementById('saveRolesButton').addEventListener('click', function() {
        const userId = document.getElementById('userId').value;
        const selectedRoles = Array.from(document.querySelectorAll('#rolesCheckboxes input:checked')).map(checkbox => checkbox.value);

        fetch(`/api/users/${userId}/roles`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ roles: selectedRoles })
        })
            .then(() => {
                alert('Roles updated successfully.');
                new bootstrap.Modal(document.getElementById('rolesModal')).hide();
                loadUsersDataTables();
            })
            .catch(err => alert('Error saving roles. Please try again.'));
    });
</script>
