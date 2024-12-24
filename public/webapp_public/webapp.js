function apiHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + appState.apiToken,
        'Accept': 'application/json',
    };
}


function extractAndInjectScripts(htmlString) {
    // Create a temporary DOM element to parse the string
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = htmlString;

    // Find all the script tags in the parsed HTML
    var scripts = tempDiv.getElementsByTagName('script');

    // Loop through the script tags
    for (var i = 0; i < scripts.length; i++) {
        var newScript = document.createElement('script');

        // Copy the content of the script
        if (scripts[i].src) {
            // If the script has a `src` attribute, set the src on the new script
            newScript.src = scripts[i].src;
        } else {
            // Otherwise, use the inner content of the script
            newScript.text = scripts[i].innerHTML;
        }

        // Append the new script to the document
        document.body.appendChild(newScript);
    }
}



// public/webapp/webapp.js
// Function to hide elements with a specific class
function hideElementsByClass(className) {
    document.querySelectorAll('.' + className).forEach(element => {
        element.classList.add('hidden');
    });
}

// Function to show elements with a specific class
function showElementsByClass(className) {
    document.querySelectorAll('.' + className).forEach(element => {
        element.classList.remove('hidden');
    });
}

function setClassTextContent(className,text) {

    // Set the text content for all elements with the class 'profile-name'
    document.querySelectorAll('.' + className).forEach(element => {
        element.textContent = text;
    });

}
function showAlert(message, type = 'success') {
    // Create the alert div with Bootstrap classes
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');

    // Set the inner HTML of the alert
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Append the alert to the specific container
    const alertContainer = document.getElementById('alertContainer');
    if (alertContainer) {
        alertContainer.appendChild(alertDiv);
    } else {
        console.warn('Alert container not found! Appending to body instead.');
        document.body.appendChild(alertDiv);
    }

    // Optionally, set a timeout to automatically close the alert after a few seconds
    setTimeout(() => {
        alertDiv.classList.remove('show');
        alertDiv.classList.add('fade');
        setTimeout(() => {
            alertDiv.remove();
        }, 150); // Wait for the fade-out transition to complete before removing
    }, 10000); // 10000ms = 10 seconds
}
const updateUserUI = () => {

    if(appState.tokens.richbot){

        if (appState.user) {

            hideElementsByClass('hidden_richbot_logged_in');
            showElementsByClass('hidden_richbot_logged_out');

            console.log(appState.user.email_verified_at);
            if(appState.user.email_verified_at){

                hideElementsByClass('hidden_email_verified');
                showElementsByClass('hidden_email_not_verified');

            } else {

                hideElementsByClass('hidden_email_not_verified');
                showElementsByClass('hidden_email_verified');

            }

            console.log(appState.user.phone_verified_at);
            if(appState.user.phone_verified_at){

                hideElementsByClass('hidden_phone_verified');
                showElementsByClass('hidden_phone_not_verified');

            } else {

                hideElementsByClass('hidden_phone_not_verified');
                showElementsByClass('hidden_phone_verified');

            }









            setClassTextContent('richbot_user_name',appState.user.name);
            setClassTextContent('richbot_user_email',appState.user.email);
            setClassTextContent('richbot_user_phone_number',appState.user.phone_number);

            populateServicesList();

        }


    } else {

        showElementsByClass('hidden_email_verified');


        setClassTextContent('richbot_name','');
        setClassTextContent('richbot_email','');

        showElementsByClass('hidden_richbot_logged_in');
        hideElementsByClass('hidden_richbot_logged_out');

    }



    if(appState.tokens.rainbow){

        hideElementsByClass('hidden_rainbow_dash_logged_in');
        showElementsByClass('hidden_rainbow_dash_logged_out');

    } else {

        showElementsByClass('hidden_rainbow_dash_logged_in');
        hideElementsByClass('hidden_rainbow_dash_logged_out');

    }

    if(appState.tokens.bambooHR){

        hideElementsByClass('hidden_bamboohr_logged_in');
        showElementsByClass('hidden_bamboohr_logged_out');

    } else {


        showElementsByClass('hidden_bamboohr_logged_in');
        hideElementsByClass('hidden_bamboohr_logged_out');

    }

    if(appState.tokens.libreNMS){

        hideElementsByClass('hidden_librenms_logged_in');
        showElementsByClass('hidden_librenms_logged_out');
    } else {
        showElementsByClass('hidden_librenms_logged_in');
        hideElementsByClass('hidden_librenms_logged_out');

    }

    setupMenuForRoles(appState.user.roles);

};

// Function to load and display the file tree with checkboxes
function loadAssistantFiles(container = 'file-tree-container') {
    // Fetch the file list from the server
    fetch('/api/openai/list-files', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
        }
    })
        .then(response => response.json())
        .then(filePaths => {
            // Build and display the file tree
            const fileTree = buildFileTree(filePaths);
            const treeHTML = createTreeHTML(fileTree);
            document.getElementById(container).appendChild(treeHTML);
            // Add event listener for expanding/collapsing directories
            addTreeInteractivity();
        })
        .catch(err => {
            console.error('Failed to load files:', err);
        });
}

// Function to build the file tree data structure
function buildFileTree(paths) {
    const tree = {};

    paths.forEach(path => {
        const parts = path.split('/');
        let currentLevel = tree;

        parts.forEach((part, index) => {
            if (!currentLevel[part]) {
                currentLevel[part] = (index === parts.length - 1) ? null : {};
            }
            currentLevel = currentLevel[part];
        });
    });

    return tree;
}

// Function to create the HTML for the file tree with checkboxes
function createTreeHTML(tree,path = '') {
    const ul = document.createElement('ul');

    for (const key in tree) {
        const li = document.createElement('li');
        const fullPath = path ? `${path}/${key}` : key;

        if (tree[key] === null) {
            // It's a file
            li.classList.add('file');
            const label = document.createElement('label');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'files[]';
            checkbox.value = fullPath; // Set the file name as the value
            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(key));
            li.appendChild(label);
        } else {
            // It's a directory
            const span = document.createElement('span');
            span.textContent = key;
            span.classList.add('caret');
            li.appendChild(span);
            const nestedUL = createTreeHTML(tree[key],fullPath);
            nestedUL.classList.add('nested');
            li.appendChild(nestedUL);
        }

        ul.appendChild(li);
    }

    return ul;
}

// Function to add interactivity to the tree
function addTreeInteractivity() {
    const toggler = document.querySelectorAll('#file-tree-container .caret');
    toggler.forEach(function(element) {
        element.addEventListener('click', function() {
            this.parentElement.querySelector('.nested').classList.toggle('active');
            this.classList.toggle('caret-down');
        });
    });
}


function loadAssistants() {
    return ajaxRequest(`/api/assistants`, 'GET').then(data => {
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


const populateServicesList = () => {
    const servicesList = document.getElementById('servicesList');
    servicesList.innerHTML = '';

    console.log('populateServicesList',appState,appState.tokens);

    for (const [service, token] of Object.entries(appState.tokens)) {

        const listItem = document.createElement('li');
        listItem.textContent = service.charAt(0).toUpperCase() + service.slice(1);
        //listItem.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center','m-1');

        const statusBadge = document.createElement('span');

        statusBadge.classList.add('badge', 'rounded-pill','m-1');
        if (token) {
            statusBadge.classList.add('bg-success');
            statusBadge.textContent = listItem.textContent;
        } else {
            statusBadge.classList.add('bg-secondary');
            statusBadge.textContent = listItem.textContent;
        }
        //listItem.appendChild(statusBadge);
        //servicesList.appendChild(listItem);
        servicesList.appendChild(statusBadge);
    }
};

async function ajaxRequest(url, method = 'GET', data = {}, token = null) {
    return new Promise((resolve, reject) => {
        if (!token) {
            token = appState.apiToken;
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

// Function to load content dynamically using fetch
function loadContent(token, url, targetId = 'contentArea') {

    console.log(token);
    console.log(appState.apiToken);

    return new Promise((resolve, reject) => {
        fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json(); // Parse JSON from response
            })
            .then(data => {

                appState.current_content_section = targetId;

                // Insert the loaded content into the target element
                document.getElementById(targetId).innerHTML = data.content;
                console.log('Authenticated User:', data.user);

                extractAndInjectScripts(data.content);

                resolve(data);

            })
            .catch(err => {
                alert('Failed to load content. Please try again.');
                reject(err);
            });
    });
}

// Helper Function to Show Sections
const showSection = (sectionId) => {
    // Hide all content sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('hidden');
    });


    console.log('showing section ', sectionId);

    // Show the targeted section
    document.getElementById(sectionId).classList.remove('hidden');

    // Remove 'active' class from all nav links and dropdown items
    document.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
        link.classList.remove('active');
    });

    // Add 'active' class to the clicked link (main nav or dropdown)
    document.querySelectorAll(`[data-section="${sectionId}"]`).forEach(link => {
        link.classList.add('active');
        //section.classList.add('hidden');
    });

    // Minimize header/footer for certain sections
    if (sectionId === 'assistants-prompt-section') {
        toggleHeaderFooter(true); // Expand for default section
    } else {
        toggleHeaderFooter(false); // Minimize for other sections
    }

    // Add section to openSections if not already present
    if (!appState.openSections.find(section => section.id === sectionId)) {
        const sectionTitle = document.querySelector(`[data-section="${sectionId}"]`)?.textContent?.trim() || sectionId;
        appState.openSections.push({
            id: sectionId,
            title: sectionTitle
        });
        // Update localStorage
        localStorage.setItem('app_state', JSON.stringify(appState));
        // Update the Open Tabs menu
        updateOpenTabsMenu();
    }




};

function updateOpenTabsMenu() {
    const openTabsMenu = document.querySelector('#openTabs + .dropdown-menu');
    if (!openTabsMenu) return;

    openTabsMenu.innerHTML = appState.openSections.map(section => `
        <li>
            <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" 
               onclick="showSection('${section.id}'); return false;">
                ${section.title}
                <button class="btn btn-sm btn-link text-danger" 
                        onclick="closeSection('${section.id}', event)">
                    <i class="fas fa-times"></i>
                </button>
            </a>
        </li>
    `).join('') || '<li><span class="dropdown-item">No open tabs</span></li>';
}

// Add function to close a section
function closeSection(sectionId, event) {
    event.stopPropagation(); // Prevent triggering the parent link
    appState.openSections = appState.openSections.filter(section => section.id !== sectionId);
    localStorage.setItem('app_state', JSON.stringify(appState));
    updateOpenTabsMenu();
}

// Attach the event listener to a static parent element
document.body.addEventListener('click', function(event) {
    // Check if the clicked element matches your target dynamically created element
    console.log(event);

    if (event.target && event.target.id === 'logoutButton') {

            appState = null;
            localStorage.removeItem('app_state');

            //updateUserUI();
            showAlert('Logged out successfully!', 'info');
            //showSection('richbotSection');
            location.reload();

    }

    if (event.target && event.target.id === 'loadUsersButton') {
        console.log('Dynamic button with ID "dynamicButton" clicked!');
        fetch("/api/users", {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = data.data.map(user => `
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

    if (event.target && event.target.classList.contains('dynamic-button')) {
        console.log('Dynamically created button clicked!');
    }
});

//   console.log('loaded appstate',appState);




// Add event listeners for header icons
document.getElementById('headerIconLeft').addEventListener('click', () => {
    toggleHeaderFooter(false); // Expand the header and footer
});

document.getElementById('headerIconRight').addEventListener('click', () => {
    toggleHeaderFooter(false); // Expand the header and footer
});




function loadData(dataType, endpoint, forceRefresh = false) {
    return new Promise((resolve, reject) => {
        if (!appState.data[dataType] || forceRefresh) {
            fetch(endpoint, { headers: apiHeaders() })
                .then(response => response.json())
                .then(data => {
                    appState.data[dataType] = dataType === 'assistants' ? data.assistants : data;
                    localStorage.setItem('app_state', JSON.stringify(appState));
                    resolve(appState.data[dataType]); // Resolve with the loaded data
                })
                .catch(error => reject(error)); // Handle errors by rejecting the promise
        } else {
            resolve(appState.data[dataType]); // Resolve immediately if data already exists
        }
    });
}




function loadAllData() {
    Promise.all([
        loadData('pipelines', '/api/pipelines', true),
        loadData('assistants', '/api/user_assistants', true),
        loadData('tools', '/api/tools', true)
    ])
        .then(([pipelines, assistants, tools]) => {
            console.log('All data loaded');
            console.log('Pipelines:', pipelines);
            console.log('Assistants:', assistants);
            console.log('Tools:', tools);

            // Load pipelines on page load


            // Proceed with further actions, e.g., rendering data
            // Example: renderPipelines(pipelines);
        })
        .catch(error => console.error('Error loading data:', error));
}

function createAndLoadSection(view, targetId = 'dynamic_content_section',desc = 'dynamic_section_desc', force_reload = false){

    const method = 'GET';
    const url = `/api/content/${view}`;

    console.log(view, targetId);

    // Check if the target element exists, if not, create it
    let targetElement = document.getElementById(targetId);

    console.log(targetElement);

    const headers = {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + appState.apiToken,
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
        .then(data => {

            //alert(data);


            console.log('response data',data);



            if(targetElement){

                targetElement.remove();

            }
            targetElement = document.createElement('div');
            targetElement.id = targetId;
            targetElement.classList.add('content-section');


            targetElement.innerHTML = data.content;

            document.getElementById('main-container').appendChild(targetElement);

            showSection(targetId);

            extractAndInjectScripts(data.content);

            // Minimize header/footer for certain sections
            if (targetId === 'assistants-prompt-section') {
                toggleHeaderFooter(true); // Expand for default section
            } else {
                toggleHeaderFooter(false); // Minimize for other sections
            }


        })
        .catch(error => {

        });

    // Remove active class from all section showers
    document.querySelectorAll('.nav-section-toggler','nav-section-shower').forEach(link => {
        link.classList.remove('active');
    });


    function createAndLoadSectionOld(view, targetId = 'dynamic_content_section',force_reload = false){

        const method = 'GET';
        const url = `/api/content/${view}`;

        console.log(view, targetId);

        // Check if the target element exists, if not, create it
        let targetElement = document.getElementById(targetId);

        console.log(targetElement);

        const headers = {
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + appState.apiToken,
            'Content-Type': 'application/json'
        };

        const options = {
            method: method,
            headers: headers,
        };


        // If the target element exists and no forced reload, just show the existing section
        if (targetElement && !force_reload) {
            showSection(targetId);
            return;
        }

        console.log('about to fetch');



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
            .then(data => {

                //alert(data);

                console.log('response data',data);

                if(targetElement){
                    targetElement.remove();
                }

                targetElement = document.createElement('div');
                targetElement.id = targetId;
                targetElement.classList.add('content-section');


                targetElement.innerHTML = data.content;
                document.getElementById('main-container').appendChild(targetElement);
                showSection(targetId);

                extractAndInjectScripts(data.content);

            })
            .catch(error => {
                console.error("Error loading section:", error);
            });

        // Remove active class from all section showers
        document.querySelectorAll('.nav-section-toggler','nav-section-shower').forEach(link => {
            link.classList.remove('active');
        });




    }


}









function setupMenuForRoles(roles) {

    const roleNames = roles.map(role => role.name.toLowerCase());

    // Show/hide menu items based on roles
    document.querySelectorAll('[data-visible-role]').forEach(item => {

        const requiredRole = item.getAttribute('data-visible-role').toLowerCase();

        console.log(item,roleNames,requiredRole);

        if (!roleNames.includes(requiredRole)) {

            console.log('it does not include!',requiredRole);

            item.style.display = 'none';
        }
    });
}
function toggleHeaderFooter(minimize = true) {

    //alert('toggling header footer.');

    const header = document.getElementById('mainHeader');
    const footer = document.getElementById('mainFooter');
    const headerContent = document.getElementById('headerContent');
    const footerContent = document.getElementById('footerContent');
    const headerIconLeft = document.getElementById('headerIconLeft');
    const headerIconRight = document.getElementById('headerIconRight');

    if (minimize) {

        headerContent.classList.add('hidden');
        footerContent.classList.add('hidden');
        headerIconLeft.classList.remove('hidden');
        headerIconRight.classList.remove('hidden');
        header.style.height = '50px'; // Adjust to desired height
        footer.style.display = 'none'; // Hide footer completely

    } else {

        headerContent.classList.remove('hidden');
        footerContent.classList.remove('hidden');
        headerIconLeft.classList.add('hidden');
        headerIconRight.classList.add('hidden');
        header.style.height = ''; // Reset to default
        footer.style.display = ''; // Reset footer visibility
    }
}
 
// Utility function to capitalize first letter
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

document.addEventListener('DOMContentLoaded', () => {


    const saved_state = localStorage.getItem('app_state');

    appState = {};


  //  alert('checking saved state');

    console.log('saved state', saved_state);

    if (!saved_state || saved_state === "null" || saved_state === "undefined") {
        //if(!saved_state){


        let appState = {

        };



        console.log('making new appstate')
       // alert('making new appState');

        appState = {

            apiToken: null,
            user: null,
            audio: null,
            socket: null,
            data: {},

            ollama_model: null,
            ollama_conversation: null,
            ollama_assistant: null,
            ollama_assistant_name: null,
            ollama_messages: [],
            conversations: [],
            current_conversation: null,

            current_id: null,
            current_coding_session_id: null,
            coding_sessions: [],

            richbot: null,
            dashUser: null,
            dashApiToken: null,
            users: [],
            current_thread: null, // Holds the current active thread
            current_assistant: null, // Holds the current active assistant
            threads: [], // List of all threads
            debug: false,
            current_content_section: 'publicContent',
            openSections: [], // Array to track open sections            
            tokens: {
                richbot: null,
                rainbow: null,
                bambooHR: null,
                libreNMS: null,
                train: null,
            },
        };
        localStorage.setItem('app_state', JSON.stringify(appState));

        window.location.reload();

   //     console.log('new appstate' , appState);

    } else {

        appState = JSON.parse(saved_state);


    }





     /// Event listener for dynamic content loading and section showing
    document.querySelectorAll('.nav-content-loader').forEach(loader => {
        loader.addEventListener('click', function(e) {

            e.preventDefault();
            const view = this.getAttribute('data-view');
            const targetId = this.getAttribute('data-section') || 'dynamic_content_section';

            const method = 'GET';
            const url = `/api/content/${view}`;

            console.log(view, targetId);

            // Check if the target element exists, if not, create it
            let targetElement = document.getElementById(targetId);

            console.log(targetElement);

            const headers = {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
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
                .then(data => {

                    //alert(data);


                    console.log('response data',data);



                    if(targetElement){

                        targetElement.remove();

                    }
                    targetElement = document.createElement('div');
                    targetElement.id = targetId;
                    targetElement.classList.add('content-section');


                    targetElement.innerHTML = data.content;
                    document.getElementById('main-container').appendChild(targetElement);
                    showSection(targetId);

                    extractAndInjectScripts(data.content);

                })
                .catch(error => {

                });

            // Remove active class from all section showers
            document.querySelectorAll('.nav-section-toggler','nav-section-shower').forEach(link => {
                link.classList.remove('active');
            });

            this.classList.add('active');
        });
    });

    // Event Listeners
    document.querySelectorAll('.nav-section-toggler').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.dataset.section;
            showSection(sectionId);
        });
    });


    // Rainbow Dashboard Login Form Submission
    document.getElementById('rainbowLoginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('rainbowEmail').value;
        const password = document.getElementById('rainbowPassword').value;

        try {
            // Replace with actual API endpoint
            const response = await axios.post('https://dash.rainbowtel.net/api/login', { email, password });
            appState.tokens.rainbow = response.data.token;
            localStorage.setItem('app_state', JSON.stringify(appState));

            location.reload();

            //showAlert('Logged in to Rainbow Dashboard successfully!');
            //showSection('bambooSection');
        } catch (error) {
            console.error(error);
            showAlert('Failed to login to Rainbow Dashboard. Please check your credentials.', 'danger');
        }
    });



    // BambooHR Token Form Submission
    document.getElementById('bambooTokenForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = document.getElementById('bambooToken').value;


            const date = new Date();
            date.setDate(date.getDate() - 30); // Subtract 30 days from today
            start = date.toISOString().split('T')[0]; // Format as 'YYYY-MM-DD'


            date.setDate(date.getDate() + 14); // Add 14 days (2 weeks) to today
            end = date.toISOString().split('T')[0]; // Format as 'YYYY-MM-DD'

        //Basic MmJmOWMzYTcyMWFmNGUzN2FmNDc2ZGE4ZTFhODY2ZmZkMDc2YmY4Nzp4

        const base_token = btoa(token + ':x');

        console.log(base_token, 'MmJmOWMzYTcyMWFmNGUzN2FmNDc2ZGE4ZTFhODY2ZmZkMDc2YmY4Nzp4');

        if(base_token != 'MmJmOWMzYTcyMWFmNGUzN2FmNDc2ZGE4ZTFhODY2ZmZkMDc2YmY4Nzp4'){

            alert('base token bad ' + base_token + " != 'MmJmOWMzYTcyMWFmNGUzN2FmNDc2ZGE4ZTFhODY2ZmZkMDc2YmY4Nzp4'");

        }




// Example GET Request
        axios.get('/api/proxy/bamboohr/v1/company_information', {
            headers: {
                'Authorization': 'Bearer '+ appState.tokens.richbot,
                'Accept': 'application/json',
                // Add more headers as needed
            },
            params: {
                apikey: token,

            }
        })
            .then(response => {

                appState.tokens.bambooHR = token;
                localStorage.setItem('app_state', JSON.stringify(appState));

                console.log(response.data);
                showAlert('BambooHR token uploaded successfully!');
                showSection('profileSection');
                updateUserUI();


                console.log(appState);

            })
            .catch(error => {
                console.error('Error:', error);
            });



    });



    console.log('loaded appstate at the end: ',appState);

    // Initialize App

    loadAllData();
    showSection('richbotSection');


    updateUserUI( );
    setupMenuForRoles(appState.user.roles);

    if (!appState.openSections) {
        appState.openSections = [];
    }
    updateOpenTabsMenu();
    //window.location.reload();


});
document.addEventListener('DOMContentLoaded', function() {





/*
    // Establish WebSocket connection
const socket = new WebSocket('wss://richbot9000.com:9501');

// Handle connection open
socket.onopen = () => {
    console.log('WebSocket connected');

    // Example metadata
    const userId = 123; // Replace with actual user ID
    const conversationId = 456; // Replace with actual conversation ID

    // Simulate sending audio chunks with metadata
    const audioChunks = [
        new Uint8Array([0x01, 0x02, 0x03]), // Example binary data
        new Uint8Array([0x04, 0x05, 0x06]),
        new Uint8Array([0x07, 0x08, 0x09]),
    ];

    audioChunks.forEach((chunk, index) => {
        const message = {
            user_id: userId,
            conversation_id: conversationId,
            chunk_number: index + 1, // Optional chunk index
            audio: Array.from(chunk), // Convert binary to array (or Base64)
        };

        socket.send(JSON.stringify(message)); // Send the message as JSON
    });

    socket.send('{"type":"ping"}'); // Send the message as JSON



};


*/






    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
// Header icon click handlers
document.getElementById('headerIconLeft')?.addEventListener('click', function() {
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        const dropdownToggle = userDropdown.querySelector('.dropdown-toggle');
        if (dropdownToggle) {
            dropdownToggle.click();
        }
    }
});

document.getElementById('headerIconRight')?.addEventListener('click', function() {
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        const dropdownToggle = userDropdown.querySelector('.dropdown-toggle');
        if (dropdownToggle) {
            dropdownToggle.click();
        }
    }
});

function loadConversationsDataTable() {
    const conversationsTable = document.querySelector('#conversationsTable');
    
    const dataTable = $(conversationsTable).DataTable({
        ajax: {
            url: '/api/conversations',
            headers: apiHeaders(),
            dataSrc: 'conversations'
        },
        columns: [
            { 
                data: 'title',
                render: function(data) {
                    return data || 'Untitled';
                }
            },
            { 
                data: 'type',
                render: function(data) {
                    return capitalizeFirstLetter(data || '');
                }
            },
            { 
                data: null,
                render: function(data) {
                    return data.assistant_name || data.pipeline_name || 'N/A';
                }
            },
            { 
                data: 'last_message',
                render: function(data) {
                    if (!data) return 'No messages';
                    return `
                        <div class="message-preview">
                            <small class="text-muted">${data.role}:</small>
                            <div>${data.content.length > 50 ? data.content.substring(0, 50) + '...' : data.content}</div>
                            <small class="text-muted">${new Date(data.created_at).toLocaleString()}</small>
                        </div>
                    `;
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    return `<span class="badge bg-${getStatusBadgeClass(data)}">${capitalizeFirstLetter(data)}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            { 
                data: 'id',
                render: function(data) {
                    return `
                        <button class="btn btn-info btn-sm view-conversation-btn" data-conversation-id="${data}">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-danger btn-sm delete-conversation-btn" data-conversation-id="${data}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    `;
                },
                orderable: false
            }
        ],
        order: [[5, 'desc']], // Sort by created_at by default
        destroy: true,
        searching: true,
        ordering: true,
        paging: true
    });

    // Event handlers for view and delete buttons
    $('#conversationsTable').on('click', '.view-conversation-btn', function() {
        const conversationId = $(this).data('conversation-id');
        openConversationModal(conversationId);
    });

    $('#conversationsTable').on('click', '.delete-conversation-btn', function() {
        const conversationId = $(this).data('conversation-id');
        if (confirm('Are you sure you want to delete this conversation?')) {
            deleteConversation(conversationId);
        }
    });
}