<button id="loadUsersButton" class="btn btn-primary mb-3">Refresh Users</button>
<button id="testLoadUsersButton" class="btn btn-secondary mb-3 ms-2">Test Reload Users</button>
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
    let isLoading = false;

    // Initialize DataTables
    const usersTable = $('#usersTable').DataTable({
        ajax: {
            url: '/api/users',
            headers: apiHeaders()
        },
        columns: [
            { data: 'name' },
            { 
                data: 'email',
                render: function(data, type, row) {
                    return `${data}${row.email_verified_at ? '' : '<span class="text-danger"> <i class="fas fa-exclamation-circle"></i></span>'}`;
                }
            },
            { 
                data: 'phone_number',
                render: function(data, type, row) {
                    return data ? `${data}${row.phone_verified_at ? '' : '<span class="text-danger"> <i class="fas fa-exclamation-circle"></i></span>'}` : '<span class="text-muted">N/A</span>';
                }
            },
            { 
                data: 'roles',
                render: function(data, type, row) {
                    const roleText = data.length ? data.map(role => role.name).join(', ') : '<span class="text-muted">No Roles</span>';
                    return `${roleText}
                        <button class="btn btn-primary btn-sm assign-roles-btn ms-2" data-user-id="${row.id}" data-user-name="${row.name}">Assign Roles</button>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-info btn-sm view-user-btn" data-user-id="${row.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-primary btn-sm more-info-btn" data-user-id="${row.id}" data-user-name="${row.name}">
                                <i class="fas fa-user-edit"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Modify reload functions to use DataTables API
    function loadUsersDataTables() {
        usersTable.ajax.reload();
    }

    function reloadUsersTable() {
        return new Promise((resolve, reject) => {
            usersTable.ajax.reload(() => {
                resolve();
            }, () => {
                reject(new Error('Failed to reload users table'));
            });
        });
    }

    // Initialize the users table and event handlers
    function initializeUsersTable() {
        const usersTab = document.getElementById('users-tab');
        const loadButton = document.getElementById('loadUsersButton');
        const testLoadButton = document.getElementById('testLoadUsersButton');
        
        if (!usersTab || !loadButton || !testLoadButton) return;

        // Remove any existing event listeners
        const newUsersTab = usersTab.cloneNode(true);
        const newLoadButton = loadButton.cloneNode(true);
        const newTestLoadButton = testLoadButton.cloneNode(true);
        
        usersTab.parentNode.replaceChild(newUsersTab, usersTab);
        loadButton.parentNode.replaceChild(newLoadButton, loadButton);
        testLoadButton.parentNode.replaceChild(newTestLoadButton, testLoadButton);

        // Add event listeners
        newUsersTab.addEventListener('shown.bs.tab', loadUsersDataTables);
        newLoadButton.addEventListener('click', loadUsersDataTables);
        newTestLoadButton.addEventListener('click', function() {
            reloadUsersTable()
                .then(() => {
                    showAlert('Users reloaded successfully!', 'success');
                })
                .catch(() => {
                    showAlert('Error reloading users. Please try again.', 'danger');
                });
        });

        // Load initial data if tab is active
        if (newUsersTab.classList.contains('active')) {
            setTimeout(loadUsersDataTables, 100);
        }
    }

    // Call initialization
    initializeUsersTable();

    // Function to create and show user profile tab
    function loadUserProfile(userId, userName) {
        const adminTabs = document.getElementById('adminTabs');
        const adminTabsContent = document.getElementById('adminTabsContent');

        // Check if tab already exists
        if (!document.getElementById(`user-tab-${userId}`)) {
            // Create new tab
            const tabItem = document.createElement('li');
            tabItem.classList.add('nav-item');
            tabItem.innerHTML = `
                <button class="nav-link text-white" id="user-tab-${userId}" data-bs-toggle="tab" data-bs-target="#user-content-${userId}" type="button" role="tab" aria-controls="user-content-${userId}" aria-selected="false">
                    <i class="fas fa-user me-2"></i>${userName} <span class="ms-2 close-tab" data-user-id="${userId}">&times;</span>
                </button>
            `;
            adminTabs.appendChild(tabItem);

            // Create new tab content
            const tabContent = document.createElement('div');
            tabContent.classList.add('tab-pane', 'fade');
            tabContent.setAttribute('id', `user-content-${userId}`);
            tabContent.setAttribute('role', 'tabpanel');
            tabContent.setAttribute('aria-labelledby', `user-tab-${userId}`);
            tabContent.innerHTML = `<div id="user-profile-${userId}" class="p-3">Loading profile...</div>`;
            adminTabsContent.appendChild(tabContent);

            // Initialize the new tab using Bootstrap's Tab API
            const newTab = new bootstrap.Tab(document.getElementById(`user-tab-${userId}`));
            
            // Load user data
            ajaxRequest(`/api/users/${userId}`)
                .then(user => {
                    const profileHtml = `
                        <h4>Profile of ${user.name}</h4>
                        <p><strong>Email:</strong> ${user.email}</p>
                        <p><strong>Phone:</strong> ${user.phone_number || 'N/A'}</p>
                        <p><strong>Roles:</strong> ${user.roles.map(role => role.name).join(', ')}</p>
                        <p><strong>Address:</strong> ${user.address || 'N/A'}</p>
                        <p><strong>Date of Birth:</strong> ${user.dob || 'N/A'}</p>
                    `;
                    document.getElementById(`user-profile-${userId}`).innerHTML = profileHtml;
                    // Show the new tab
                    newTab.show();
                })
                .catch(err => {
                    document.getElementById(`user-profile-${userId}`).innerHTML = 
                        '<p class="text-danger">Error loading profile.</p>' + err.message;
                });
        } else {
            // If tab exists, just show it
            const existingTab = new bootstrap.Tab(document.getElementById(`user-tab-${userId}`));
            existingTab.show();
        }
    }

    // Handle all button clicks with a single event listener
    document.addEventListener('click', function(e) {
        const target = e.target;

        // Handle view button or its icon
        if (target.matches('.view-user-btn, .view-user-btn *')) {
            const btn = target.closest('.view-user-btn');
            const userId = btn.dataset.userId;
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

        // Handle more info button or its icon
        if (target.matches('.more-info-btn, .more-info-btn *')) {
            const btn = target.closest('.more-info-btn');
            const userId = btn.dataset.userId;
            const userName = btn.dataset.userName;
            loadUserProfile(userId, userName);
        }

        // Handle assign roles button
        if (target.matches('.assign-roles-btn, .assign-roles-btn *')) {
            const btn = target.closest('.assign-roles-btn');
            const userId = btn.dataset.userId;
            const userName = btn.dataset.userName;
            document.getElementById('userNameSpan').textContent = userName;
            document.getElementById('userId').value = userId;
            loadRoles(userId);
            new bootstrap.Modal(document.getElementById('rolesModal')).show();
        }

        // Handle close tab button
        if (target.matches('.close-tab')) {
            const userId = target.dataset.userId;
            document.getElementById(`user-tab-${userId}`).parentElement.remove();
            document.getElementById(`user-content-${userId}`).remove();
            // Find first visible tab and click it
            const firstTab = document.querySelector('#adminTabs .nav-link:not(.d-none)');
            if (firstTab) {
                firstTab.click();
            }
        }
    });

    // Load users immediately if we're on the users tab
    if (document.getElementById('users').classList.contains('active')) {
        loadUsersDataTables();
    }

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

    // Modify the save roles handler
    document.getElementById('saveRolesButton').addEventListener('click', function() {
        const userId = document.getElementById('userId').value;
        const selectedRoles = Array.from(document.querySelectorAll('#rolesCheckboxes input:checked')).map(checkbox => checkbox.value);

        ajaxRequest(`/api/users/${userId}/roles`, 'POST', { roles: selectedRoles })
            .then(() => {
                showAlert('Roles updated successfully.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('rolesModal')).hide();
                
                // Add a small delay before reloading
                setTimeout(() => {
                    reloadUsersTable()
                        .then(() => {
                            console.log('Users table reloaded after role update');
                        })
                        .catch(() => {
                            console.error('Failed to reload users table after role update');
                        });
                }, 500);
            })
            .catch(err => showAlert('Error saving roles. Please try again.', 'danger'));
    });
</script>
