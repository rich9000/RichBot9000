<div class="row mb-2 hidden_not_admin" >
    <div class="col-md-12 col-lg-12 hidden_not_admin">
        <div class="card hidden_not_admin">
            <div class="card-header bg-light">
                <ul class="nav nav-tabs card-header-tabs" id="adminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admin-overview-tab" data-bs-toggle="tab" data-bs-target="#adminOverview" type="button" role="tab" aria-controls="overview" aria-selected="true">Admin Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">Users</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="false">Events</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="adminTabsContent">
                    <div class="tab-pane fade show active" id="adminOverview" role="tabpanel" aria-labelledby="admin-overview-tab">
                       test test
                    </div>


                    <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                        @include('webapp.richbot9000._admin_users')
                    </div>

                    <script>

                     

                        $(document).ready(function() {

                            alert('this is a test');

                        });
                       
                        document.addEventListener('click', function(event) {
                            if (event.target.classList.contains('more-info-btn')) {
                                const userId = event.target.getAttribute('data-user-id');
                                const userName = event.target.getAttribute('data-user-name');
                                loadUserProfile(userId, userName);
                            }
                        });

                        function loadUserProfile(userId, userName) {
                            const adminTabs = document.getElementById('adminTabs');
                            const adminTabsContent = document.getElementById('adminTabsContent');

                            if (!document.getElementById(`user-tab-${userId}`)) {
                                const tabItem = document.createElement('li');
                                tabItem.classList.add('nav-item');
                                tabItem.innerHTML = `
                                    <button class="nav-link" id="user-tab-${userId}" data-bs-toggle="tab" data-bs-target="#user-content-${userId}" type="button" role="tab" aria-controls="user-content-${userId}" aria-selected="false">
                                        User Admin: ${userName} <span class="ms-2 close-tab" data-user-id="${userId}">&times;</span>
                                    </button>
                                `;
                                adminTabs.appendChild(tabItem);

                                const tabContent = document.createElement('div');
                                tabContent.classList.add('tab-pane', 'fade');
                                tabContent.setAttribute('id', `user-content-${userId}`);
                                tabContent.setAttribute('role', 'tabpanel');
                                tabContent.setAttribute('aria-labelledby', `user-tab-${userId}`);
                                tabContent.innerHTML = `<div id="user-profile-${userId}" class="p-3">Loading profile...</div>`;
                                adminTabsContent.appendChild(tabContent);

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
                                    })
                                    .catch(err => {
                                        document.getElementById(`user-profile-${userId}`).innerHTML = '<p class="text-danger">Error loading profile.</p>' + err.message;
                                    });
                            }

                            document.querySelector(`#user-tab-${userId}`).click();
                        }

                        document.addEventListener('click', function(event) {
                            if (event.target.classList.contains('close-tab')) {
                                const userId = event.target.getAttribute('data-user-id');
                                document.getElementById(`user-tab-${userId}`).parentElement.remove();
                                document.getElementById(`user-content-${userId}`).remove();
                                document.getElementById('admin-overview-tab').click();
                            }
                        });

                      //  document.getElementById('loadEventsButton').addEventListener('click', loadEventsDataTables);

                        function loadEventsDataTables() {
                            console.log('Loading events...');
                            // Add your logic to load events data here
                            const table = document.getElementById('eventLogsTable');
                            fetch(`${apiUrl}/eventlogs`, {
                                headers: {
                                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                                    'Accept': 'application/json'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    table.querySelector('tbody').innerHTML = data.map(event => `
                                    <tr>
                                        <td>${event.event_type}</td>
                                        <td>${event.description}</td>
                                        <td>${event.user.name || 'N/A'}</td>
                                        <td>${new Date(event.created_at).toLocaleString()}</td>
                                    </tr>
                                `).join('');
                                });
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
