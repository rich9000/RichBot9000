function apiHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + appState.apiToken,
        'Accept': 'application/json',
    };
}

// Menu Configuration
const menuConfig = {
    mainMenu: [
        {
            id: 'dashboard',
            label: 'Dashboard',
            icon: 'fas fa-home',
            section: 'richbotSection',
            roles: ['*'],
            view: 'webapp.richbot9000._richbot_dashboard'
        },
        {
            id: 'userMenu',
            label: 'User Menu',
            icon: 'fas fa-user-cog',
            roles: ['*'],
            submenu: [
                {
                    header: 'Communication',
                    items: [
                        {
                            label: 'Assistant Chat',
                            icon: 'fas fa-comments',
                            view: 'webapp.bare._chat_client',
                            section: 'assistant-chat-section',
                            roles: ['*']
                        },
                        {
                            label: 'Assistant Phone Call',
                            icon: 'fas fa-phone',
                            view: 'webapp.bare._assistant_phone_call',
                            section: 'assistant-phone-call-section',
                            roles: ['assistant_phone_call_user', 'admin']
                        },
                        {
                            label: 'Start Bare Call',
                            icon: 'fas fa-phone',
                            view: 'webapp.bare._bare_call',
                            section: 'bare-call-section',
                            roles: ['user', 'admin']
                        },
                        {
                            label: 'Image Generator',
                            icon: 'fas fa-image',
                            view: 'webapp.openai._images',
                            section: 'image-generator-section',
                            roles: ['user']
                        }
                    ]
                },
                {
                    header: 'AI Management',
                    items: [
                        {
                            label: 'Assistants',
                            icon: 'fas fa-robot',
                            view: 'webapp.assistants._index',
                            section: 'assistants_content_section',
                            roles: ['*']
                        },
                        {
                            label: 'Tools',
                            icon: 'fas fa-tools',
                            view: 'webapp.tools._index',
                            section: 'ollama-tools-section',
                            roles: ['*']
                        }
                    ]
                },
                {
                    header: 'RichBots',
                    items: [
                        {
                            label: 'CronBots',
                            icon: 'fas fa-clock',
                            view: 'webapp.cronbot._index',
                            section: 'cronbots-section',
                            roles: ['*']
                        },
                        {
                            label: 'Create CronBot',
                            icon: 'fas fa-clock',
                            view: 'webapp.cronbot._create',
                            section: 'cronbots-create-section',
                            roles: ['*']
                        },
                        {
                            label: 'Remote Richbots',
                            icon: 'fas fa-server',
                            view: 'webapp.remote_richbot._richbots',
                            section: 'remote-richbots-section',
                            roles: ['remote_richbot_user', 'remote_richbot_admin', 'admin']
                        }
                    ]
                },
                {
                    header: 'Data Management',
                    items: [
                        {
                            label: 'RichBot Overview',
                            icon: 'fas fa-tachometer-alt',
                            view: 'webapp.richbot9000._richbot_dashboard',
                            section: 'richbotSection',
                            roles: ['richbot_user']
                        },
                        {
                            label: 'Contacts',
                            icon: 'fas fa-address-book',
                            view: 'webapp.contacts._index',
                            section: 'contacts-section',
                            roles: ['user']
                        },
                        {
                            label: 'Integrations',
                            icon: 'fas fa-plug',
                            view: 'webapp.integrations._index',
                            section: 'integrations-section',
                            roles: ['user']
                        },
                        {
                            label: 'Surveys',
                            icon: 'fas fa-file-code',
                            view: 'webapp.survey._index',
                            section: 'surveys-section',
                            roles: ['surveys_user', 'admin']
                        },
                        {
                            label: 'Appointments',
                            icon: 'fas fa-calendar',
                            view: 'webapp.appointments._index',
                            section: 'appointment-index-section',
                            roles: ['user']
                        },
                        {
                            label: 'Projects',
                            icon: 'fas fa-project-diagram',
                            view: 'webapp.project._projects',
                            section: 'projects-index-section',
                            roles: ['user']
                        }
                    ]
                }
            ]
        },
        {
            id: 'openTabs',
            label: 'Open Tabs',
            icon: 'fas fa-folder-open',
            roles: ['user'],
            submenu: [
                {
                    header: 'Recent Tabs',
                    items: [
                        {
                            label: 'No open tabs',
                            icon: 'fas fa-file',
                            roles: ['user']
                        }
                    ]
                }
            ]
        },
        {
            id: 'admin',
            label: 'Admin',
            icon: 'fas fa-shield-alt',
            roles: ['admin'],
            submenu: [
                {
                    items: [
                        {
                            label: 'Conversation Paths',
                            icon: 'fas fa-tags',
                            view: 'webapp.conversation_path._index',
                            section: 'conversation-path-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Conversations',
                            icon: 'fas fa-tags',
                            view: 'webapp.conversations._index',
                            section: 'conversations-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Screen Output',
                            icon: 'fas fa-tv',
                            view: 'webapp.screen_output._screen-output',
                            section: 'screen-output-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Tool Groups',
                            icon: 'fas fa-tags',
                            view: 'webapp.tools._groups',
                            section: 'tool-groups-section',
                            roles: ['tools_admin', 'admin']
                        },
                        {
                            label: 'Script Manager',
                            icon: 'fas fa-sitemap',
                            view: 'webapp.scripts._index',
                            section: 'conversation-script-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Phone Tree',
                            icon: 'fas fa-phone',
                            view: 'webapp.phone-tree._index',
                            section: 'phone-tree-section',
                            roles: ['phone_tree_user', 'phone_tree_admin', 'admin']
                        },
                        {
                            label: 'Audio Manager',
                            icon: 'fas fa-music',
                            view: 'webapp.audio._index',
                            section: 'audio-section',
                            roles: ['admin']
                        }
                    ]
                },
                {
                    header: 'System Management',
                    items: [
                        {
                            label: 'Admin Section',
                            icon: 'fas fa-cog',
                            view: 'webapp.richbot9000._admin_section',
                            section: 'admin-section',
                            roles: ['admin']
                        },
                        {
                            label: 'SMS Messages',
                            icon: 'fas fa-sms',
                            view: 'webapp.sms._sms_index',
                            section: 'sms-messages-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Bare Websocket Dashboard',
                            icon: 'fas fa-server',
                            view: 'webapp.bare._dashboard',
                            section: 'bare-dashboard-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Twilio Status Overview',
                            icon: 'fas fa-robot',
                            view: 'webapp.webrtc._dashboard',
                            section: 'webrtc-section',
                            roles: ['admin']
                        },
                        {
                            label: 'OpenAI Status Overview',
                            icon: 'fas fa-brain',
                            view: 'webapp.openai._realtime',
                            section: 'realtime-section',
                            roles: ['admin']
                        }
                    ]
                },
                {
                    header: 'Mock Files',
                    items: [
                        {
                            label: 'Easy AI Form Maker',
                            icon: 'fas fa-file-code',
                            view: 'webapp.ai_easy_form._easy_ai_form_maker',
                            section: 'easy-ai-form-maker-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Mock App',
                            icon: 'fas fa-file-code',
                            view: 'webapp.mock._mock',
                            section: 'mock-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Mock Conversation Path Builder',
                            icon: 'fas fa-file-code',
                            view: 'webapp.mock._conversation_path_builder',
                            section: 'conversation-path-builder-section-mock',
                            roles: ['admin']
                        }
                    ]
                }
            ]
        },
        {
            id: 'tools',
            label: 'Tools',
            icon: 'fas fa-tools',
            roles: ['tools_user', 'tools_admin', 'admin'],
            submenu: [
                {
                    header: 'Development Tools',
                    items: [
                        {
                            label: 'Sockets Test',
                            icon: 'fas fa-robot',
                            view: 'webapp.websockets._test',
                            section: 'sockets-test-section',
                            roles: ['tools_user', 'tools_admin', 'admin']
                        },
                        {
                            label: 'WebSocket Client',
                            icon: 'fas fa-robot',
                            view: 'webapp.websockets._client',
                            section: 'websocket-client-section',
                            roles: ['tools_user', 'tools_admin', 'admin']
                        },
                        {
                            label: 'WebSocket Manager',
                            icon: 'fas fa-network-wired',
                            view: 'webapp.websockets._manager',
                            section: 'websockets-manager-section',
                            roles: ['tools_admin', 'admin']
                        },
                        {
                            label: 'Assistants Prompt',
                            icon: 'fas fa-robot',
                            view: 'webapp.assistants._prompt',
                            section: 'assistants-prompt-section',
                            roles: ['tools_user', 'tools_admin', 'admin']
                        },
                        {
                            label: 'Assistants Client',
                            icon: 'fas fa-robot',
                            view: 'webapp.assistants._client',
                            section: 'assistants-client-section',
                            roles: ['tools_user', 'tools_admin', 'admin']
                        }
                    ]
                }
            ]
        },
        {
            id: 'legacy',
            label: 'Legacy',
            icon: 'fas fa-archive',
            roles: ['admin'],
            submenu: [
                {
                    header: 'Dashboard',
                    items: [
                        {
                            label: 'RichBot9000 Dashboard',
                            icon: 'fas fa-tachometer-alt',
                            view: 'webapp.richbot9000._admin_section',
                            section: 'richbotSection',
                            roles: ['admin']
                        }
                    ]
                },
                {
                    header: 'Communication',
                    items: [
                        {
                            label: 'Chat',
                            icon: 'fas fa-comment',
                            view: 'webapp.openai._prompt',
                            section: 'chat_content_section',
                            roles: ['admin']
                        },
                        {
                            label: 'SMS Messages',
                            icon: 'fas fa-sms',
                            view: 'webapp.sms._sms_index',
                            section: 'sms-messages-section',
                            roles: ['admin']
                        }
                    ]
                },
                {
                    header: 'AI & Assistants',
                    items: [
                        {
                            label: 'Functions',
                            icon: 'fas fa-code',
                            view: 'assistant_functions.content._index',
                            section: 'functions_content_section',
                            roles: ['admin']
                        },
                        {
                            label: 'Assistants',
                            icon: 'fas fa-robot',
                            view: 'assistants.content._index',
                            section: 'assistants_content_section',
                            roles: ['admin']
                        },
                        {
                            label: 'Ollama Overview',
                            icon: 'fas fa-brain',
                            view: 'webapp.ollama._dashboard',
                            section: 'ollama-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Whisper Test',
                            icon: 'fas fa-microphone',
                            view: 'webapp.whisper._prompt',
                            section: 'whisper-prompt',
                            roles: ['admin']
                        },
                        {
                            label: 'Ollama Prompt',
                            icon: 'fas fa-comments',
                            view: 'webapp.ollama_conversations._ollama_prompt',
                            section: 'ollama-prompt',
                            roles: ['admin']
                        }
                    ]
                },
                {
                    header: 'Management',
                    items: [
                        {
                            label: 'Appointments',
                            icon: 'fas fa-calendar',
                            view: 'webapp.appointments._index',
                            section: 'appointment-index-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Projects',
                            icon: 'fas fa-project-diagram',
                            view: 'webapp.project._projects',
                            section: 'projects-index-section',
                            roles: ['admin']
                        },
                        {
                            label: 'RichBots Overview',
                            icon: 'fas fa-server',
                            view: 'webapp.remote_richbot._richbots',
                            section: 'remote-richbots-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Ollama Assistants',
                            icon: 'fas fa-users',
                            view: 'webapp.assistants._index',
                            section: 'ollama-assistants-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Ollama Tools',
                            icon: 'fas fa-tools',
                            view: 'webapp.tools._index',
                            section: 'ollama-tools-section',
                            roles: ['admin']
                        },
                        {
                            label: 'Assistant Pipelines',
                            icon: 'fas fa-stream',
                            view: 'webapp.pipelines._index',
                            section: 'assistant-pipelines-section',
                            roles: ['admin']
                        }
                    ]
                }
            ]
        }
    ]
};

// Menu Generator Functions
function generateMenu(menuConfig, userRoles) {
    const navbar = document.querySelector('#navbarContent .navbar-nav');
    if (!navbar) return;

    // Clear existing menu items
    navbar.innerHTML = '';

    // Generate menu items
    menuConfig.mainMenu.forEach(menuItem => {
        if (hasAccess(menuItem.roles, userRoles)) {
            const menuElement = createMenuElement(menuItem, userRoles);
            navbar.appendChild(menuElement);
        }
    });
}

function hasAccess(requiredRoles, userRoles) {
    if (requiredRoles.includes('*')) return true;
    return userRoles.some(role => requiredRoles.includes(role.toLowerCase()));
}

function createMenuElement(menuItem, userRoles) {
    const li = document.createElement('li');
    li.className = 'nav-item dropdown';

    // Add role-based classes
    menuItem.roles.forEach(role => {
        if (userRoles.includes(role)) {
            li.classList.add(`role-${role}`);
        }
    });

    if (menuItem.submenu) {
        // Create dropdown menu
        li.innerHTML = `
            <a class="nav-link px-3 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <i class="${menuItem.icon} me-1"></i>${menuItem.label}
            </a>
            <div class="dropdown-menu border-0 shadow-sm">
                ${generateSubmenu(menuItem.submenu, userRoles)}
            </div>
        `;
    } else {
        // Create single menu item
        li.innerHTML = `
            <a class="nav-link px-3 nav-content-loader" href="#" 
               data-view="${menuItem.view}" 
               data-section="${menuItem.section}">
                <i class="${menuItem.icon} me-1"></i>${menuItem.label}
            </a>
        `;
    }

    return li;
}

function generateSubmenu(submenu, userRoles) {
    return submenu.map(section => {
        let html = '';
        
        if (section.header) {
            html += `<div class="dropdown-header">${section.header}</div>`;
        }

        if (section.items) {
            section.items.forEach(item => {
                const hasRequiredAccess = hasAccess(item.roles, userRoles);
                const roleClasses = item.roles
                    .filter(role => userRoles.includes(role))
                    .map(role => `role-${role}`)
                    .join(' ');
                
                html += `
                    <a class="dropdown-item nav-content-loader ${roleClasses} ${!hasRequiredAccess ? 'disabled' : ''}" 
                       href="#" 
                       data-view="${item.view}" 
                       data-section="${item.section}"
                       ${!hasRequiredAccess ? 'style="opacity: 0.5; pointer-events: none;"' : ''}>
                        <i class="${item.icon} me-2"></i>${item.label}
                    </a>
                `;
            });
        }

        return html;
    }).join('');
}

// Function to update menu based on user roles
function updateMenu() {
    // If not logged in, clear the menu
    if (!appState?.tokens?.richbot || !appState?.user) {
        const navbar = document.querySelector('#navbarContent .navbar-nav');
        if (navbar) {
            navbar.innerHTML = '';
        }
        return;
    }

    const userRoles = appState.user?.roles?.map(role => role.name.toLowerCase()) || [];
    generateMenu(menuConfig, userRoles);
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
    // First, set everything to its logged-out state
    showElementsByClass('hidden_richbot_logged_in');
    hideElementsByClass('hidden_richbot_logged_out');
    hideElementsByClass('hidden_email_verified');
    hideElementsByClass('hidden_email_not_verified');
    hideElementsByClass('hidden_phone_verified');
    hideElementsByClass('hidden_phone_not_verified');
    
    // Clear all user-related text
    setClassTextContent('richbot_user_name', '');
    setClassTextContent('richbot_user_email', '');
    setClassTextContent('richbot_user_phone_number', '');

    // Only update UI elements if user is logged in and appState is properly initialized
    if (appState?.tokens?.richbot && appState?.user) {
        // Show logged-in elements
        hideElementsByClass('hidden_richbot_logged_in');
        showElementsByClass('hidden_richbot_logged_out');

        // Handle email verification status
        if (appState.user.email_verified_at) {
            hideElementsByClass('hidden_email_verified');
            showElementsByClass('hidden_email_not_verified');
        } else {
            showElementsByClass('hidden_email_verified');
            hideElementsByClass('hidden_email_not_verified');
        }

        // Handle phone verification status
        if (appState.user.phone_verified_at) {
            hideElementsByClass('hidden_phone_verified');
            showElementsByClass('hidden_phone_not_verified');
        } else {
            showElementsByClass('hidden_phone_verified');
            hideElementsByClass('hidden_phone_not_verified');
        }

        // Set user information
        setClassTextContent('richbot_user_name', appState.user.name || '');
        setClassTextContent('richbot_user_email', appState.user.email || '');
        setClassTextContent('richbot_user_phone_number', appState.user.phone_number || '');

        // Update menu based on user roles
        if (appState.user.roles) {
            updateMenu();
        }
    }

    // Update services list
    populateServicesList();

    // Update roles list if user exists
    if (appState?.user?.roles) {
        populateMenuRolesList();
    }

    // Handle other service tokens
    if (appState?.tokens?.rainbow) {
        hideElementsByClass('hidden_rainbow_dash_logged_in');
        showElementsByClass('hidden_rainbow_dash_logged_out');
    } else {
        showElementsByClass('hidden_rainbow_dash_logged_in');
        hideElementsByClass('hidden_rainbow_dash_logged_out');
    }

    if (appState?.tokens?.bambooHR) {
        hideElementsByClass('hidden_bamboohr_logged_in');
        showElementsByClass('hidden_bamboohr_logged_out');
    } else {
        showElementsByClass('hidden_bamboohr_logged_in');
        hideElementsByClass('hidden_bamboohr_logged_out');
    }

    if (appState?.tokens?.libreNMS) {
        hideElementsByClass('hidden_librenms_logged_in');
        showElementsByClass('hidden_librenms_logged_out');
    } else {
        showElementsByClass('hidden_librenms_logged_in');
        hideElementsByClass('hidden_librenms_logged_out');
    }
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

function populateMenuRolesList() {
    const rolesList = document.getElementById('menuRolesList');
    if (!rolesList || !appState?.user?.roles) return;
    
    rolesList.innerHTML = '';
    appState.user.roles.forEach(role => {
        const roleWords = role.name.split(' ');
        const ucwordsRole = roleWords.map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        rolesList.innerHTML += `<li>${ucwordsRole}</li>`;
    });
}

const populateServicesList = () => {
    const servicesList = document.getElementById('servicesList');
    if (!servicesList || !appState?.tokens) return;
    
    servicesList.innerHTML = '';

    for (const [service, token] of Object.entries(appState.tokens)) {
        const statusBadge = document.createElement('span');
        statusBadge.classList.add('badge', 'rounded-pill', 'm-1');
        statusBadge.classList.add(token ? 'bg-success' : 'bg-secondary');
        statusBadge.textContent = service.charAt(0).toUpperCase() + service.slice(1);
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









function setupMenuForRoles(roles = []) {
    const roleNames = (roles || []).map(role => role.name.toLowerCase());

    // Show/hide menu items based on roles
    document.querySelectorAll('[data-visible-role]').forEach(item => {
        const requiredRole = item.getAttribute('data-visible-role').toLowerCase();
        if (!roleNames.includes(requiredRole)) {
            item.style.display = 'none';
        } else {
            item.style.display = '';
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

    console.log('saved state', saved_state);

    if (!saved_state || saved_state === "null" || saved_state === "undefined") {
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
            current_thread: null,
            current_assistant: null,
            threads: [],
            debug: false,
            current_content_section: 'publicContent',
            openSections: [],
            tokens: {
                richbot: null,
                rainbow: null,
                bambooHR: null,
                libreNMS: null,
                train: null,
            },
        };
        localStorage.setItem('app_state', JSON.stringify(appState));
    } else {
        try {
            appState = JSON.parse(saved_state);
        } catch (e) {
            console.error('Error parsing saved state:', e);
            appState = {
                apiToken: null,
                user: null,
                tokens: {
                    richbot: null,
                    rainbow: null,
                    bambooHR: null,
                    libreNMS: null,
                    train: null,
                }
            };
        }
    }

    // Initialize App
    updateUserUI();
    
    // If user is logged in, show dashboard, otherwise show login
    if (appState?.tokens?.richbot && appState?.user) {
        setupMenuForRoles(appState.user.roles || []);
        showSection('richbotSection');
    } else {
        showSection('richbotLoginSection');
    }

    if (!appState.openSections) {
        appState.openSections = [];
    }
    updateOpenTabsMenu();

    // Only load data if user is logged in
    if (appState?.tokens?.richbot && appState?.user) {
        loadAllData();
    }

    // Event listener for dynamic content loading and section showing
    document.addEventListener('click', function(e) {
        // Check if the clicked element is a nav-content-loader
        if (e.target.classList.contains('nav-content-loader')) {
            e.preventDefault();
            const view = e.target.getAttribute('data-view');
            const targetId = e.target.getAttribute('data-section') || 'dynamic_content_section';

            // Check if the target element exists, if not, create it
            let targetElement = document.getElementById(targetId);
            
            ajaxRequest(`/api/content/${view}`)
                .then(data => {
                    if(targetElement) {
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
                    console.error('Error loading content:', error);
                    showAlert('Error loading content. Please try again.', 'danger');
                });

            // Remove active class from all section showers
            document.querySelectorAll('.nav-section-toggler, .nav-section-shower').forEach(link => {
                link.classList.remove('active');
            });

            e.target.classList.add('active');
        }
    });

    // Event Listeners for section toggling
    document.querySelectorAll('.nav-section-toggler').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.dataset.section;
            showSection(sectionId);
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {





/*
    // Establish WebSocket connection
const socket = new WebSocket(window.appConfig?.wsUrl || 'wss://richbot9000.com:9501');

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

// Initialize menu when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the menu
    updateMenu();
    
    // Add event listener for role changes
    document.addEventListener('roleChanged', function() {
        updateMenu();
    });
});

// Email Verification Functions
function sendVerificationEmail() {
    fetch('/api/email/verification-notification', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert('Verification email sent successfully!', 'success');
    })
    .catch(error => {
        showAlert('Error sending verification email: ' + error.message, 'danger');
    });
}

function verifyEmail(token) {
    fetch('/api/verify-email', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ token: token })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Email verified successfully!', 'success');
            appState.user.email_verified_at = new Date().toISOString();
            localStorage.setItem('app_state', JSON.stringify(appState));
            updateUserUI();
        } else {
            showAlert('Invalid verification code', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error verifying email: ' + error.message, 'danger');
    });
}

// SMS Verification Functions
function sendSmsVerification() {
    fetch('/api/resend-sms-verification', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (response.status === 401) {
            throw new Error('Unauthorized. Please log in again.');
        }
        return response.json().then(data => {
            if (!response.ok) {
                throw new Error(data.error || 'Failed to resend verification SMS.');
            }
            return data;
        });
    })
    .then(data => {
        showAlert(data.message || 'Verification SMS sent successfully.', 'success');
    })
    .catch(error => {
        console.error('Error resending verification SMS:', error);
        showAlert(error.message || 'An error occurred. Please try again.', 'danger');
    });
}

function verifySms(token) {
    fetch('/api/verify-sms', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ token: token })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Phone number verified successfully!', 'success');
            appState.user.phone_verified_at = new Date().toISOString();
            localStorage.setItem('app_state', JSON.stringify(appState));
            updateUserUI();
        } else {
            showAlert('Invalid verification code', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error verifying phone number: ' + error.message, 'danger');
    });
}

// Notification Preferences Functions
function updateNotificationPreferences() {
    const emailNotifications = document.getElementById('emailNotifications').checked;
    const smsNotifications = document.getElementById('smsNotifications').checked;

    fetch('/api/notification-preferences', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + appState.apiToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email_notifications: emailNotifications,
            sms_notifications: smsNotifications
        })
    })
    .then(response => response.json())
    .then(data => {
        showAlert('Notification preferences updated successfully!', 'success');
    })
    .catch(error => {
        showAlert('Error updating notification preferences: ' + error.message, 'danger');
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Email verification
    document.getElementById('resendEmailVerification')?.addEventListener('click', sendVerificationEmail);
    document.querySelector('.verify-richbot-email-button')?.addEventListener('click', function() {
        const token = document.getElementById('emailCodeInput').value;
        verifyEmail(token);
    });

    // SMS verification - REMOVED: Handled by _richbot_verify_phone.blade.php
    document.getElementById('resendSmsVerification')?.addEventListener('click', sendSmsVerification);
    // Removed conflicting phone verification listener

    // Notification preferences
    document.getElementById('emailNotifications')?.addEventListener('change', updateNotificationPreferences);
    document.getElementById('smsNotifications')?.addEventListener('change', updateNotificationPreferences);

    // Initialize notification preferences
    if (appState?.user) {
        document.getElementById('emailNotifications').checked = appState.user.email_notifications === 'enabled';
        document.getElementById('smsNotifications').checked = appState.user.sms_notifications === 'enabled';
    }
});