

// main.js

const apiUrl = '/api'; // Base API URL

/*

ajaxRequest(url, 'POST, requestData).then(data => {
    console.log('Thread created successfully:', data);

}).catch(error => {
    console.error('Error creating thread:', error);

});

*/

function ajaxRequest(url, method = 'GET', data = {}, token = null) {
    return new Promise((resolve, reject) => {
        if (!token) {
            token = localStorage.getItem('api_token');
        }

        const headers = {
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        };

        const options = {
            method: method,
            headers: headers,
        };

        // If the method is POST, PUT, or PATCH, we add the body to the request
        if (method === 'POST' || method === 'PUT' || method === 'PATCH') {
            options.body = JSON.stringify(data);
        }

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    // Convert non-2xx HTTP responses into errors
                    return response.json().then(errorData => {
                        reject(errorData);
                    });
                }
                return response.json();
            })
            .then(data => resolve(data))
            .catch(error => reject(error));
    });
}
function ajaxRequestOld(url, type, data = {}, token = null) {

    return new Promise((resolve, reject) => {

        if (!token) {
            token = appState.apiToken;
        }

        const headers = {
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
        };

        $.ajax({
            url: url,
            type: type,
            headers: headers,
            data: data,
            success: function(response) {
                resolve(response);
            },
            error: function(err) {
                reject(err);
            }
        });
    });
}




// Function to load the file tree
function loadFileTree(root) {


    ajaxRequest(apiUrl + '/openai/listFiles', 'GET').then(data => {

        console.log('Thread created successfully:', data);
        $('#file-tree').empty();
        data.files.forEach(function(file) {
            $('#file-tree').append(`
                        <div>
                            <input type="checkbox" class="file-checkbox" value="${file.path}"> ${file.name}
                        </div>
                    `);
        });

    }).catch(error => {

        console.error('Error creating thread:', error);

    });


}
// Function to fetch users list
function fetchUsers(token) {
    ajaxRequest('/api/users', 'GET', {}, token).then(users => {
        // displayUserList(users); // Uncomment and implement this function as needed
    }).catch(() => {
        alert('Failed to fetch user list.');
    });
}

// Function to load assistants
function loadAssistants() {
    return ajaxRequest(`${apiUrl}/assistants`, 'GET').then(data => {
        $('#assistant-select').empty();
        data.assistants.forEach(function(assistant) {
            $('#assistant-select').append(`
                <option value="${assistant.id}">${assistant.name}</option>
            `);
        });
        return data;
    }).catch(err => {
        return Promise.reject(err);
    });
}

// Function to load content dynamically
function loadContent(token, url, targetId = 'contentArea') {

    return new Promise((resolve, reject) => {
        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            },
            success: function(response) {

                appState.current_content_section = targetId;

                $(`#${targetId}`).html(response.content);
                console.log('Authenticated User:', response.user);
                resolve(response);
            },
            error: function(err) {
                alert('Failed to load content. Please try again.');
                reject(err);
            }
        });
    });
}

// Function to handle login

// Function to handle login
function handleLogin(email, password) {
    return ajaxRequest(`${apiUrl}/login`, 'POST', { email, password }).then(response => {
        localStorage.setItem('api_token', response.token);
        appState.apiToken = response.token;
        localStorage.setItem('app_state', JSON.stringify(appState));
        return response;
    }).catch(xhr => {
        alert(xhr.responseJSON.message);
        return Promise.reject(xhr);
    });
}

// Function to handle logout
function handleLogout(token) {
    return ajaxRequest(`${apiUrl}/logout`, 'POST', {}, token).then(response => {
        localStorage.clear();
        appState.apiToken = null;
        appState.user = null;
        updateLocalStorageDebug();
        updateDisplay();
        return response;
    }).catch(err => {
        alert('Logout failed. Clearing storage and reloading page. Check Log');
        localStorage.clear();
        location.reload();
        return Promise.reject(err);
    });
}

function checkUser(token) {
    ajaxRequest(`${apiUrl}/user`, 'GET', {}, token).then(user => {

        console.log('check_user_success', user);


        appState.user = user;

        updateState('user', user);
       // appState.apiToken = token;
        updateDisplay();

        return user;

    }).catch(() => {
        removeStateItem('user');
        updateDisplay();
    });
}


// Function to update localStorage and global state
function updateState(key, value) {
    appState[key] = value;
    localStorage.setItem(key, JSON.stringify(value));
}

// Function to remove an item from state and localStorage
function removeStateItem(key) {
    appState[key] = null;
    localStorage.removeItem(key);
}

// Debugging utility to log current state
function logState() {
    console.log('logState:', appState);
}

// Function to update the debug section with localStorage values
function updateLocalStorageDebug() {
    let localStorageData = JSON.stringify(localStorage, null, 2);
    let appStateData = JSON.stringify(appState, null, 2);
    $('#localStorageDebug').text(localStorageData);
    $('#appStateDebug').text(appStateData);
}

// Function to update the display based on the appState
function updateDisplay() {

    console.log('global_state', appState);

    if (appState.user) {
        const user = appState.user;
        $('.user-name-span').text(user.name);
        $('.user-email-span').text(user.email);
        $('.user-email-verified-span').text(user.email_verified);

        if (user.roles && user.roles.some(role => role.name === 'Admin')) {
            $('.hidden_not_admin').removeClass('hidden');
        }

        if (user.email_verified_at) {
            $('.hidden_email_verified').addClass('hidden');
        } else {
            $('.hidden_email_verified').removeClass('hidden');
        }
        if (user.phone_verified_at) {
            $('.hidden_phone_verified').addClass('hidden');
        } else {
            $('.hidden_phone_verified').removeClass('hidden');
        }

        $('.hidden_logged_in').addClass('hidden');
        $('.hidden_not_logged_in').removeClass('hidden');
    } else {

        alert("no appState.user");


        $('.user-name-span').text('');
        $('.user-email-span').text('');
        $('.user-email-verified-span').text('');

        $('.hidden_not_admin').addClass('hidden');
        $('.hidden_logged_in').removeClass('hidden');
        $('.hidden_not_logged_in').addClass('hidden');
        $('.hidden_email_verified').addClass('hidden');
        $('.hidden_phone_verified').addClass('hidden');
    }

    updateLocalStorageDebug();
    logState();
    localStorage.setItem('app_state', JSON.stringify(appState));
}

// Document ready event for setting up event listeners
$(document).ready(function() {

    $('#updateLocalDebugBtn').on('click', function(e) {
        e.preventDefault();
        updateLocalStorageDebug();
        //alert('button pused');
    });

    $('.nav-content-loader').on('click', function(e) {
        e.preventDefault();
        const section = $(this).data('section');
        const targetId = $(this).data('target') || 'dynamic_content_section';


        console.log(section,targetId);

        if (!$(`#${targetId}`).length) {

            var div = $('<div>', {
                id: targetId,
                class: 'content-section'
            });

            // Set the HTML content of the new div
            div.html('Loading');
            console.log('Created div:', div);

            // Append the new div to the main container
            div.appendTo('#main-container');

            console.log('Appended div to #main-container');
        }

        $('.content-section').hide();
        $(`#${targetId}`).show();

        loadContent(localStorage.getItem('api_token'), `/api/content/${section}`, targetId);

        $('.nav-section-shower').removeClass('active');
    });


    // Set up menu click events
    $('.nav-section-shower').on('click', function(e) {
        e.preventDefault();
        const sectionId = $(this).data('section');

        appState.current_content_section = sectionId;

        $('.content-section').hide();
        $('#' + sectionId).show();
        $('.nav-section-shower').removeClass('active');
        $(this).addClass('active');
    });


    $('#loadUsersButton').on('click', function() {
        // Initialize DataTable with AJAX source for users
        $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            destroy: true, // Destroy previous instance if exists
            ajax: {
                url: "/api/users", // API endpoint to fetch users
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'), // Use stored API token
                    'Accept': 'application/json'
                }
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'created_at', name: 'created_at' }
            ],
            order: [[2, 'desc']], // Order by creation date descending
            language: {
                emptyTable: "No users available"
            }
        });
    });


    $('#loadEventsButton').on('click', function() {
        // Initialize DataTable with AJAX source
        $('#eventLogsTable').DataTable({
            processing: true,
            serverSide: true,
            destroy: true, // Destroy previous instance if exists
            ajax: {
                url: "/api/eventlogs", // API endpoint to fetch event logs
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'), // Use stored API token
                    'Accept': 'application/json'
                }
            },
            columns: [
                { data: 'event_type', name: 'event_type' },
                { data: 'description', name: 'description' },
                { data: 'user.name', name: 'user.name', defaultContent: 'N/A' },
                { data: 'created_at', name: 'created_at', render: function(data) {
                        return new Date(data).toLocaleString();
                    }}
            ],
            order: [[3, 'desc']], // Order by date descending
            language: {
                emptyTable: "No event logs available"
            }
        });
    });




    // Handle login form submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        const email = $('#email').val();
        const password = $('#password').val();
        handleLogin(email, password)
            .then(response => {
                checkUser(response.token);

            });
    });

    // Handle logout
    $('#logoutButton').on('click', function() {
        handleLogout(localStorage.getItem('api_token'));
    });

    // Clear localStorage and update debug section
    $('#clearLocalStorageButton').on('click', function() {
        localStorage.clear();
        updateLocalStorageDebug();
        $('#user-info').addClass('hidden');
        $('#admin-section').addClass('hidden');
        $('#loginForm').show();
        $('#user-list').empty();
    });

    // Event listener for verification email form
    $('#verificationEmailForm').on('submit', function(e) {
        e.preventDefault();
        sendVerificationEmail();
    });

    // Event listener for password update form
    $('#updatePasswordForm').on('submit', function(e) {
        e.preventDefault();
        const currentPassword = $('#update_password_current_password').val();
        const newPassword = $('#update_password_password').val();
        const confirmPassword = $('#update_password_password_confirmation').val();
        handlePasswordUpdate(currentPassword, newPassword, confirmPassword);
    });

    // Event listener for registration form


    // Initialize DataTables


    const notificationLink = document.getElementById('notificationLink');
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');

    // Function to update the notification count badge
    const updateNotificationCount = (count) => {
        notificationCount.textContent = count;
        notificationCount.classList.toggle('bg-danger', count > 0);
    };

    // Function to display notifications in the modal
    const displayNotifications = (notifications) => {
        notificationList.innerHTML = ''; // Clear the current list

        if (notifications.length === 0) {
            notificationList.innerHTML = '<p>No notifications at this time.</p>';
            return;
        }

        notifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = 'notification-item';
            notificationItem.innerHTML = `
                <div class="notification-title">${notification.event_type}</div>
                <div class="notification-message">${notification.description}</div>
                <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
            `;
            notificationList.appendChild(notificationItem);
        });
    };

    // Function to fetch notifications and update the app state
    const fetchUserNotifications = async () => {
        try {
            const response = await fetch('/api/eventlogs?user_specific=true', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch notifications');
            }

            const jsonResponse = await response.json();
            const notifications = jsonResponse.data;

            // Update appState with the new notifications
            appState.notifications = notifications;
            localStorage.setItem('app_state', JSON.stringify(appState));

            // Update the notification count and display the notifications
            updateNotificationCount(notifications.length);
            displayNotifications(notifications);
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    };

    // Check for new notifications periodically (e.g., every 60 seconds)
    setInterval(fetchUserNotifications, 60000);

    // Fetch notifications when the page loads
    fetchUserNotifications();

});



