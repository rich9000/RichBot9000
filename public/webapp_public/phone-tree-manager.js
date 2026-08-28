class PhoneTreeManager {
    constructor() {
        this.currentPhoneTree = null;
        this.currentMenu = null;
        this.currentOption = null;
        this.currentWebsocket = null;
        this.currentCall = null;
        this.currentRecording = null;
        this.currentTranscription = null;
        
        // Don't initialize here, wait for explicit initialization
    }

    // Helper function to escape HTML special characters
    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Helper function to format dates
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    initialize() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this._initialize());
        } else {
            this._initialize();
        }
    }

    _initialize() {
        this.initializeEventListeners();
        this.loadPhoneTrees();
    }

    initializeEventListeners() {
        // Helper function to safely add event listeners
        const addEventListener = (id, event, handler) => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener(event, handler);
            }
        };

        // Phone Tree buttons
        addEventListener('create-phone-tree-btn', 'click', () => this.showPhoneTreeModal());
        addEventListener('edit-phone-tree-btn', 'click', () => this.showPhoneTreeModal('edit'));
        addEventListener('delete-phone-tree-btn', 'click', () => this.showDeleteConfirmation('phoneTree'));
        addEventListener('back-to-phone-trees-btn', 'click', () => this.showPhoneTreesList());
        addEventListener('save-phone-tree-btn', 'click', () => this.savePhoneTree());

        // Number buttons
        addEventListener('add-number-btn', 'click', () => this.showNumberModal());
        addEventListener('save-number-btn', 'click', () => this.saveNumber());

        // Menu buttons
        addEventListener('add-menu-btn', 'click', () => this.showMenuModal());
        addEventListener('save-menu-btn', 'click', () => this.saveMenu());

        // Option buttons
        addEventListener('add-option-btn', 'click', () => this.showOptionModal());
        addEventListener('save-option-btn', 'click', () => this.saveOption());

        // WebSocket buttons
        addEventListener('add-websocket-btn', 'click', () => this.showWebSocketModal());
        addEventListener('save-websocket-btn', 'click', () => this.saveWebSocket());

        // Delete confirmation
        addEventListener('confirm-delete-btn', 'click', () => this.confirmDelete());

        // Form field changes
        addEventListener('option-action-type', 'change', (e) => this.handleActionTypeChange(e));
        addEventListener('websocket-auth-type', 'change', (e) => this.handleWebsocketAuthTypeChange(e));
    }

    async loadPhoneTrees() {
        try {
            const response = await fetch('/api/phone-trees', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            
            if (!result.success) {
                throw new Error('Failed to load phone trees');
            }

            // The API returns { success: true, data: [] }
            // We need to ensure data is an array before using it
            const phoneTrees = result.data || [];
            if (!Array.isArray(phoneTrees)) {
                console.error('Invalid phone trees data:', phoneTrees);
                throw new Error('Invalid phone trees data received');
            }

            this.renderPhoneTreesList(phoneTrees);
        } catch (error) {
            console.error('Error loading phone trees:', error);
            this.showError('Failed to load phone trees');
        }
    }

    renderPhoneTreesList(phoneTrees) {
        const tbody = document.getElementById('phone-trees-list');
        if (!tbody) {
            console.error('Phone trees list table body not found');
            return;
        }

        tbody.innerHTML = '';

        // Handle empty array case
        if (!phoneTrees || !Array.isArray(phoneTrees) || phoneTrees.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="alert alert-info">
                            No phone trees found. Click "Create Phone Tree" to get started.
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        try {
            // Render phone trees
            phoneTrees.forEach(phoneTree => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${this.escapeHtml(phoneTree.name)}</td>
                    <td>${this.escapeHtml(phoneTree.description || '')}</td>
                    <td>
                        <span class="badge ${phoneTree.is_active ? 'bg-success' : 'bg-secondary'}">
                            ${phoneTree.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>${phoneTree.numbers?.length || 0}</td>
                    <td>${phoneTree.menus?.length || 0}</td>
                    <td>${this.formatDate(phoneTree.created_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.viewPhoneTree(${phoneTree.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="phoneTreeManager.confirmDeletePhoneTree(${phoneTree.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (error) {
            console.error('Error rendering phone trees:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="alert alert-danger">
                            Error rendering phone trees. Please try again.
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    async viewPhoneTree(id) {
        try {
            console.log('Viewing phone tree:', id);
            
            const response = await fetch(`/api/phone-trees/${id}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load phone tree');
            }

            console.log('Phone tree data loaded:', result.data);
            this.currentPhoneTree = result.data;
            
            // Show the detail view
            this.showPhoneTreeDetail();
            
            // Log the current state of UI elements
            console.log('UI Elements State:', {
                managementSection: document.getElementById('phone-tree-management')?.classList.contains('d-none'),
                detailSection: document.getElementById('phone-tree-detail-section')?.classList.contains('d-none'),
                titleElement: document.getElementById('phone-tree-detail-title')?.textContent,
                numbersTab: document.getElementById('numbers-tab'),
                menusTab: document.getElementById('menus-tab'),
                websocketsTab: document.getElementById('websockets-tab'),
                callsTab: document.getElementById('calls-tab')
            });
        } catch (error) {
            console.error('Error loading phone tree:', error);
            this.showError('Failed to load phone tree details');
        }
    }

    showPhoneTreeDetail() {
        if (!this.currentPhoneTree) {
            console.error('No phone tree selected');
            return;
        }

        console.log('Showing phone tree detail:', this.currentPhoneTree);

        // Hide the management section
        const managementSection = document.getElementById('phone-tree-management');
        if (managementSection) {
           // managementSection.classList.add('d-none');
            console.log('Management section hidden -- not hidden');
        } else {
            console.error('Phone tree management section not found');
        }

        // Show the detail section
        const detailSection = document.getElementById('phone-tree-detail-section');
        if (detailSection) {
            detailSection.classList.remove('d-none');
            console.log('Detail section shown');
        } else {
            console.error('Phone tree detail section not found');
        }

        // Update the title and description
        const titleElement = document.getElementById('phone-tree-detail-title');
        const descriptionElement = document.getElementById('phone-tree-detail-description');
        if (titleElement) {
            titleElement.textContent = this.escapeHtml(this.currentPhoneTree.name);
            console.log('Title updated:', this.currentPhoneTree.name);
        }
        if (descriptionElement) {
            descriptionElement.textContent = this.escapeHtml(this.currentPhoneTree.description || 'No description provided');
        }

        // Update configuration section
        const statusElement = document.getElementById('phone-tree-status');
        if (statusElement) {
            statusElement.className = `badge ${this.currentPhoneTree.is_active ? 'bg-success' : 'bg-secondary'}`;
            statusElement.textContent = this.currentPhoneTree.is_active ? 'Active' : 'Inactive';
        }

        document.getElementById('details-max-retries').textContent = this.currentPhoneTree.max_retries || '3';
        document.getElementById('details-timeout-seconds').textContent = `${this.currentPhoneTree.timeout_seconds || '5'} seconds`;
        document.getElementById('details-welcome-message').textContent = this.escapeHtml(this.currentPhoneTree.welcome_message || 'Not set');
        document.getElementById('details-timeout-message').textContent = this.escapeHtml(this.currentPhoneTree.timeout_message || 'Not set');
        document.getElementById('details-invalid-input-message').textContent = this.escapeHtml(this.currentPhoneTree.invalid_input_message || 'Not set');

        // Initialize Bootstrap tabs
        const tabElement = document.querySelector('#phoneTreeTabs button[data-bs-toggle="tab"]');
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
            console.log('Initial tab shown');
        } else {
            console.error('Phone tree tabs not found');
        }

        // Load the details
        this.loadPhoneTreeDetails();
    }

    showPhoneTreesList() {
        //document.getElementById('phone-tree-detail-section').classList.add('d-none');
        //document.getElementById('phone-tree-management').classList.remove('d-none');
        this.currentPhoneTree = null;
    }

    async loadPhoneTreeDetails() {
        try {
            // Load phone tree details
            const phoneTreeResponse = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!phoneTreeResponse.ok) {
                throw new Error('Failed to load phone tree details');
            }

            const phoneTreeResult = await phoneTreeResponse.json();
            if (!phoneTreeResult.success) {
                throw new Error('Failed to load phone tree details');
            }

            // Load assistants
            const assistantsResponse = await fetch('/api/user_assistants', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!assistantsResponse.ok) {
                throw new Error('Failed to load assistants');
            }

            const assistantsResult = await assistantsResponse.json();
            const assistants = assistantsResult.assistants || [];

            // Load pipelines
            const pipelinesResponse = await fetch('/api/pipelines', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!pipelinesResponse.ok) {
                throw new Error('Failed to load pipelines');
            }

            const pipelines = await pipelinesResponse.json();
            
            // Add assistants and pipelines to the phone tree data
            this.currentPhoneTree = {
                ...phoneTreeResult.data,
                pipelines,
                assistants
            };

            // Update assistant references in menus
            if (this.currentPhoneTree.menus) {
                this.currentPhoneTree.menus = this.currentPhoneTree.menus.map(menu => {
                    if (menu.assistant_id) {
                        menu.assistant = assistants.find(a => a.id === menu.assistant_id);
                    }
                    return menu;
                });
            }

            console.log('Current phone tree with assistants and pipelines:', this.currentPhoneTree);

            this.updatePhoneTreeDetail(this.currentPhoneTree);
            this.renderNumbersList(this.currentPhoneTree.numbers);
            this.renderMenusList(this.currentPhoneTree.menus);
            this.renderWebSocketsList(this.currentPhoneTree.websockets);
            this.renderCallsList(this.currentPhoneTree.calls);
            this.renderScriptsList(this.currentPhoneTree.scripts);

            // Update tab counts
            document.getElementById('numbers-count').textContent = this.currentPhoneTree.numbers?.length || 0;
            document.getElementById('menus-count').textContent = this.currentPhoneTree.menus?.length || 0;
            document.getElementById('websockets-count').textContent = this.currentPhoneTree.websockets?.length || 0;
            document.getElementById('calls-count').textContent = this.currentPhoneTree.calls?.length || 0;
            document.getElementById('scripts-count').textContent = this.currentPhoneTree.scripts?.length || 0;
        } catch (error) {
            console.error('Error loading phone tree details:', error);
            this.showError('Failed to load phone tree details');
        }
    }

    renderNumbersList(numbers) {
        const tbody = document.getElementById('numbers-list');
        console.log('Numbers list element:', tbody);
        if (!tbody) {
            console.error('Numbers list table body not found');
            return;
        }

        tbody.innerHTML = '';

        // Handle empty array case
        if (!numbers || !Array.isArray(numbers) || numbers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="alert alert-info">
                            No phone numbers found. Click "Add Number" to get started.
                        </div>
                    </td>
                </tr>
            `;
            console.log('Rendered empty numbers message');
            return;
        }

        try {
        numbers.forEach(number => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td>${this.escapeHtml(number.phone_number)}</td>
                    <td>${this.escapeHtml(number.description || '')}</td>
                    <td>
                        <span class="badge ${number.is_active ? 'bg-success' : 'bg-secondary'}">
                            ${number.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.editNumber(${number.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="phoneTreeManager.confirmDeleteNumber(${number.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        } catch (error) {
            console.error('Error rendering numbers:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="alert alert-danger">
                            Error rendering phone numbers. Please try again.
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    renderMenusList(menus) {
        const container = document.getElementById('menus-container');
        console.log('Menus container element:', container);
        if (!container) {
            console.error('Menus container not found');
            return;
        }

        container.innerHTML = '';

        // Handle empty array case
        if (!menus || !Array.isArray(menus) || menus.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    No menus found. Click "Add Menu" to get started.
                </div>
            `;
            console.log('Rendered empty menus message');
            return;
        }

        try {
        const rootMenus = menus.filter(menu => !menu.parent_id);
            if (rootMenus.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        No root menus found. Click "Add Menu" to get started.
                    </div>
                `;
                return;
            }

        rootMenus.forEach(menu => this.renderMenu(menu, menus, container));
        } catch (error) {
            console.error('Error rendering menus:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Error rendering menus. Please try again.
                </div>
            `;
        }
    }

    renderMenu(menu, allMenus, container, level = 0) {
        const div = document.createElement('div');
        div.className = 'card mb-3';
        div.style.marginLeft = `${level * 20}px`;

        const options = menu.options || [];
        
        div.innerHTML = `
            <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">${menu.name}</h6>
                    <small class="text-muted">${menu.description || ''}</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-success" onclick="phoneTreeManager.showOptionModal(null, ${menu.id})">
                        <i class="fas fa-plus"></i> Add Option
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.editMenu(${menu.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="phoneTreeManager.confirmDeleteMenu(${menu.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <!-- Status -->
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">
                        <span class="badge ${menu.is_active ? 'bg-success' : 'bg-secondary'}">
                            ${menu.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </dd>

                    <!-- Assistant Section -->
                    ${menu.assistant_id ? `
                        <dt class="col-sm-3">Assistant</dt>
                        <dd class="col-sm-9">
                            <i class="fas fa-robot me-1"></i>
                            ${menu.assistant?.name || 'Unknown Assistant'}
                            ${menu.pipeline_id ? ` <small class="text-muted">(Pipeline: ${this.currentPhoneTree.pipelines.find(p => String(p.id) === String(menu.pipeline_id))?.name || menu.pipeline_id})</small>` : ''}
                        </dd>
                    ` : ''}

                    <!-- Pipeline Section -->
                    ${menu.pipeline_id ? `
                        <dt class="col-sm-3">Pipeline</dt>
                        <dd class="col-sm-9">${this.currentPhoneTree.pipelines.find(p => String(p.id) === String(menu.pipeline_id))?.name || menu.pipeline_id}</dd>
                    ` : ''}

                    <!-- Welcome Section -->
                    ${menu.welcome_audio_id ? `
                        <dt class="col-sm-3">Welcome Audio</dt>
                        <dd class="col-sm-9">${menu.welcomeAudio?.name || 'Not set'}</dd>
                    ` : ''}
                    ${menu.welcome_message ? `
                        <dt class="col-sm-3">Welcome Message</dt>
                        <dd class="col-sm-9">${menu.welcome_message}</dd>
                    ` : ''}

                    <!-- Prompt Section -->
                    ${menu.prompt_audio_id ? `
                        <dt class="col-sm-3">Prompt Audio</dt>
                        <dd class="col-sm-9">${menu.promptAudio?.name || 'Not set'}</dd>
                    ` : ''}
                    ${menu.prompt_message ? `
                        <dt class="col-sm-3">Prompt Message</dt>
                        <dd class="col-sm-9">${menu.prompt_message}</dd>
                    ` : ''}

                    <!-- Options Section -->
                    <dt class="col-sm-3">Options</dt>
                    <dd class="col-sm-9">
                        ${options.length ? `
                            <ul class="list-unstyled mb-0">
                                ${options.map(option => {
                                    let targetInfo = '';
                                    let targetIcon = '';
                                    
                                    switch(option.action_type) {
                                        case 'menu':
                                            targetIcon = 'fa-list';
                                            targetInfo = `→ ${option.target?.name || 'Unknown Menu'}`;
                                            break;
                                        case 'websocket':
                                            targetIcon = 'fa-plug';
                                            targetInfo = `→ ${option.target?.endpoint_url || 'Unknown WebSocket'}`;
                                            break;
                                        case 'script':
                                            targetIcon = 'fa-code';
                                            targetInfo = option.target ? 
                                                `→ ${option.target.name}${option.target.description ? ` (${option.target.description})` : ''}` : 
                                                '→ Unknown Script';
                                            break;
                                        case 'number':
                                            targetIcon = 'fa-phone';
                                            targetInfo = `→ ${option.target?.phone_number || 'Unknown Number'}`;
                                            break;
                                        case 'audio_file':
                                            targetIcon = 'fa-music';
                                            targetInfo = `→ ${option.target?.name || 'Unknown Audio'}`;
                                            break;
                                        case 'assistant':
                                            targetIcon = 'fa-robot';
                                            targetInfo = option.target ? 
                                                `→ ${option.target.name}${option.pipeline_id ? ` (Pipeline: ${option.pipeline_id})` : ''}` : 
                                                '→ Unknown Assistant';
                                            break;
                                    }

                                    return `
                                        <li class="mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary me-2">${option.digit}</span>
                                                <div class="flex-grow-1">
                                                    <div>${option.description || 'No description'}</div>
                                                    <small class="text-muted">
                                                        <i class="fas ${targetIcon} me-1"></i>
                                                        ${option.action_type} ${targetInfo}
                                                        ${option.welcome_message ? `<br><i class="fas fa-comment me-1"></i>Welcome: ${option.welcome_message}` : ''}
                                                        ${option.finish_menu_id ? `<br><i class="fas fa-forward me-1"></i>Finish Menu: ${allMenus.find(m => m.id === option.finish_menu_id)?.name || 'Unknown'}` : ''}
                                                        ${option.assistant_id ? `<br><i class="fas fa-robot me-1"></i>Assistant: ${option.assistant?.name || 'Unknown'}` : ''}
                                                    </small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.editOption(${option.id})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="phoneTreeManager.confirmDeleteOption(${option.id})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    `;
                                }).join('')}
                            </ul>
                            
                            <!-- Option Settings - Only show when options exist -->
                            <dt class="col-sm-3">Speak Options</dt>
                            <dd class="col-sm-9">
                                <i class="fas ${menu.speak_options ? 'fa-check text-success' : 'fa-times text-danger'}"></i>
                            </dd>

                            <dt class="col-sm-3">Max Retries</dt>
                            <dd class="col-sm-9">${menu.max_retries}</dd>

                            <dt class="col-sm-3">Timeout</dt>
                            <dd class="col-sm-9">${menu.timeout_seconds}s</dd>
                        ` : `
                            <div class="text-muted">Click "Add Option" to create one</div>
                        `}
                    </dd>

                    <!-- WebSocket Section -->
                    ${menu.websocket_id ? `
                        <dt class="col-sm-3">WebSocket</dt>
                        <dd class="col-sm-9">${menu.websocket?.endpoint_url || 'Not set'}</dd>
                    ` : ''}
                    ${menu.websocket_fail_menu_id ? `
                        <dt class="col-sm-3">WebSocket Fail Menu</dt>
                        <dd class="col-sm-9">${allMenus.find(m => m.id === menu.websocket_fail_menu_id)?.name || 'Unknown'}</dd>
                    ` : ''}

                    <!-- Transfer Section -->
                    ${menu.transfer_number ? `
                        <dt class="col-sm-3">Transfer Number</dt>
                        <dd class="col-sm-9">${menu.transfer_number}</dd>
                    ` : ''}

                    <!-- Script Section -->
                    ${menu.script_id ? `
                        <dt class="col-sm-3">Script</dt>
                        <dd class="col-sm-9">${menu.script?.name || 'Not set'}</dd>
                        <dt class="col-sm-3">Script Description</dt>
                        <dd class="col-sm-9">${menu.script?.description || 'Not set'}</dd>
                    ` : ''}

                    <!-- Finish Section -->
                    ${menu.finish_audio_id ? `
                        <dt class="col-sm-3">Finish Audio</dt>
                        <dd class="col-sm-9">${menu.finishAudio?.name || 'Not set'}</dd>
                    ` : ''}
                    ${menu.finish_message ? `
                        <dt class="col-sm-3">Finish Message</dt>
                        <dd class="col-sm-9">${menu.finish_message}</dd>
                    ` : ''}

                    <dt class="col-sm-3">Disconnect on Finish</dt>
                    <dd class="col-sm-9">
                        <i class="fas ${menu.disconnect_on_finish ? 'fa-check text-success' : 'fa-times text-danger'}"></i>
                    </dd>

                    ${menu.finish_menu_id ? `
                        <dt class="col-sm-3">Finish Menu</dt>
                        <dd class="col-sm-9">${allMenus.find(m => m.id === menu.finish_menu_id)?.name || 'Unknown'}</dd>
                    ` : ''}
                </dl>
            </div>
        `;

        container.appendChild(div);

        // Render child menus
        const childMenus = allMenus.filter(m => m.parent_id === menu.id);
        childMenus.forEach(childMenu => this.renderMenu(childMenu, allMenus, container, level + 1));
    }

    renderWebSocketsList(websockets) {
        const tbody = document.getElementById('websockets-list');
        console.log('WebSockets list element:', tbody);
        if (!tbody) {
            console.error('WebSockets list table body not found');
            return;
        }

        tbody.innerHTML = '';

        // Handle empty array case
        if (!websockets || !Array.isArray(websockets) || websockets.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="alert alert-info">
                            No WebSocket connections found. Click "Add Connection" to get started.
                        </div>
                    </td>
                </tr>
            `;
            console.log('Rendered empty websockets message');
            return;
        }

        try {
        websockets.forEach(websocket => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td>${this.escapeHtml(websocket.endpoint_url)}</td>
                    <td>${this.escapeHtml(websocket.connection_type)}</td>
                    <td>
                        <span class="badge ${websocket.is_active ? 'bg-success' : 'bg-secondary'}">
                            ${websocket.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.editWebSocket(${websocket.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="phoneTreeManager.confirmDeleteWebSocket(${websocket.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        } catch (error) {
            console.error('Error rendering WebSockets:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="alert alert-danger">
                            Error rendering WebSocket connections. Please try again.
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    renderCallsList(calls) {
        const tbody = document.getElementById('calls-list');
        console.log('Calls list element:', tbody);
        if (!tbody) {
            console.error('Calls list table body not found');
            return;
        }

        tbody.innerHTML = '';

        // Handle empty array case
        if (!calls || !Array.isArray(calls) || calls.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="alert alert-info">
                            No calls found. Calls will appear here when they are made to your phone tree.
                        </div>
                    </td>
                </tr>
            `;
            console.log('Rendered empty calls message');
            return;
        }

        try {
        calls.forEach(call => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td>${this.escapeHtml(call.from_number)}</td>
                    <td>${this.escapeHtml(call.to_number)}</td>
                    <td>${this.formatDate(call.start_time)}</td>
                    <td>
                        <span class="badge ${call.status === 'completed' ? 'bg-success' : 'bg-warning'}">
                            ${this.escapeHtml(call.status)}
                        </span>
                    </td>
                    <td>${this.escapeHtml(call.current_menu?.name || 'N/A')}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.viewCallDetails(${call.id})">
                        <i class="fas fa-eye"></i> Details
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        } catch (error) {
            console.error('Error rendering calls:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="alert alert-danger">
                            Error rendering calls. Please try again.
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    renderScriptsList(scripts) {
        const container = document.getElementById('scripts-list');
        container.innerHTML = '';

        if (!scripts || scripts.length === 0) {
            container.innerHTML = '<tr><td colspan="5" class="text-center">No scripts found</td></tr>';
            return;
        }

        scripts.forEach(script => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${this.escapeHtml(script.name)}</td>
                <td>${this.escapeHtml(script.description || '')}</td>
                <td>${this.escapeHtml(script.path)}</td>
                <td>
                    <span class="badge ${script.is_active ? 'bg-success' : 'bg-danger'}">
                        ${script.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="phoneTreeManager.editScript(${script.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="phoneTreeManager.confirmDeleteScript(${script.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            container.appendChild(row);
        });
    }

    async showPhoneTreeModal(phoneTree = null) {
        const modal = document.getElementById('phone-tree-modal');
        const form = document.getElementById('phone-tree-form');
        const idInput = document.getElementById('phone-tree-id');
        const nameInput = document.getElementById('phone-tree-name');
        const descriptionInput = document.getElementById('phone-tree-description');
        const welcomeMessageInput = document.getElementById('phone-tree-welcome-message');
        const timeoutMessageInput = document.getElementById('phone-tree-timeout-message');
        const invalidInputMessageInput = document.getElementById('phone-tree-invalid-input-message');
        const maxRetriesInput = document.getElementById('phone-tree-max-retries');
        const timeoutSecondsInput = document.getElementById('phone-tree-timeout-seconds');
        const isActiveInput = document.getElementById('phone-tree-is-active');
        const isDefaultInput = document.getElementById('phone-tree-is-default');
        const welcomeAudioInput = document.getElementById('phone-tree-welcome-audio');
        const rootMenuInput = document.getElementById('phone-tree-root-menu');
        const modalLabel = document.getElementById('phone-tree-modal-label');

        form.reset();
        idInput.value = '';
        modalLabel.textContent = 'Create Phone Tree';

        // Load audio files for welcome audio select
        try {
            const response = await fetch('/api/audio-files', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio files');
            }
            
            const audioFiles = result.data;
            welcomeAudioInput.innerHTML = '<option value="">None</option>';
            audioFiles.forEach(audio => {
                const option = document.createElement('option');
                option.value = audio.id;
                option.textContent = audio.name;
                welcomeAudioInput.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading audio files:', error);
            this.showError('Failed to load audio files');
        }

        console.log('phoneTree', phoneTree);

        if(phoneTree == 'edit') {
            modalLabel.textContent = 'Edit Phone Tree';
            phoneTree = this.currentPhoneTree;
        } else {
            modalLabel.textContent = 'Create Phone Tree';
        }

        if (phoneTree) {
            modalLabel.textContent = 'Edit Phone Tree';
            idInput.value = phoneTree.id;
            nameInput.value = phoneTree.name;
            descriptionInput.value = phoneTree.description || '';
            welcomeMessageInput.value = phoneTree.welcome_message;
            timeoutMessageInput.value = phoneTree.timeout_message;
            invalidInputMessageInput.value = phoneTree.invalid_input_message;
            maxRetriesInput.value = phoneTree.max_retries;
            timeoutSecondsInput.value = phoneTree.timeout_seconds;
            isActiveInput.checked = phoneTree.is_active;
            isDefaultInput.checked = phoneTree.is_default;
            welcomeAudioInput.value = phoneTree.welcome_audio_id || '';
            
            // Populate root menu dropdown with existing menus
            rootMenuInput.innerHTML = '<option value="">Select Root Menu</option>';
            if (phoneTree.menus && phoneTree.menus.length > 0) {
                phoneTree.menus.forEach(menu => {
                    const option = document.createElement('option');
                    option.value = menu.id;
                    option.textContent = menu.name;
                    rootMenuInput.appendChild(option);
                });
                rootMenuInput.value = phoneTree.root_menu_id || '';
            }
        }

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    async savePhoneTree() {
        const form = document.getElementById('phone-tree-form');
        const idInput = document.getElementById('phone-tree-id');
        const data = {
            name: document.getElementById('phone-tree-name').value,
            description: document.getElementById('phone-tree-description').value,
            welcome_message: document.getElementById('phone-tree-welcome-message').value,
            timeout_message: document.getElementById('phone-tree-timeout-message').value,
            invalid_input_message: document.getElementById('phone-tree-invalid-input-message').value,
            max_retries: parseInt(document.getElementById('phone-tree-max-retries').value),
            timeout_seconds: parseInt(document.getElementById('phone-tree-timeout-seconds').value),
            is_active: document.getElementById('phone-tree-is-active').checked,
            is_default: document.getElementById('phone-tree-is-default').checked,
            welcome_audio_id: document.getElementById('phone-tree-welcome-audio').value || null,
            root_menu_id: document.getElementById('phone-tree-root-menu').value || null
        };

        try {
            let response;
            if (idInput.value) {
                response = await fetch(`/api/phone-trees/${idInput.value}`, {
                    method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            } else {
                response = await fetch('/api/phone-trees', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + appState.apiToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
            }

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to save phone tree');
            }

            const result = await response.json();
            console.log('Save response:', result);
            
            this.showSuccess('Phone tree saved successfully');
            this.loadPhoneTrees();
            bootstrap.Modal.getInstance(document.getElementById('phone-tree-modal')).hide();
        } catch (error) {
            console.error('Error saving phone tree:', error);
            this.showError('Failed to save phone tree');
        }
    }

    async editPhoneTree(id) {
        try {
            const response = await fetch(`/api/phone-trees/${id}`);
            if (!response.ok) {
                throw new Error('Failed to fetch phone tree');
            }
            const phoneTree = await response.json();
            await this.showPhoneTreeModal(phoneTree);
        } catch (error) {
            console.error('Error editing phone tree:', error);
            this.showError('Failed to load phone tree for editing');
        }
    }

    async confirmDeletePhoneTree(id) {
        try {
            const response = await fetch(`/api/phone-trees/${id}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load phone tree');
            }

            this.currentPhoneTree = result.data;
            
            document.getElementById('confirm-delete-title').textContent = 'Delete Phone Tree';
            document.getElementById('confirm-delete-message').textContent = 
                `Are you sure you want to delete the phone tree "${this.escapeHtml(this.currentPhoneTree.name)}" (ID: ${id})? This will also delete all associated menus, options, and configurations. This action cannot be undone.`;
            
            const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
            modal.show();
        } catch (error) {
            console.error('Error loading phone tree for deletion:', error);
            this.showError('Failed to load phone tree details');
        }
    }

    async deleteConfirmed() {
        if (!this.currentPhoneTree) {
            console.error('No phone tree selected for deletion');
            return;
        }

        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to delete phone tree');

            bootstrap.Modal.getInstance(document.getElementById('confirm-delete-modal')).hide();
            this.showPhoneTreesList();
            this.loadPhoneTrees();
            this.showSuccess('Phone tree deleted successfully');
        } catch (error) {
            console.error('Error deleting phone tree:', error);
            this.showError('Failed to delete phone tree');
        }
    }

    showSuccess(message) {
        const container = document.getElementById('success-container');
        if (!container) return;

        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `
            ${this.escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        container.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }

    showError(message) {
        const container = document.getElementById('error-container');
        if (!container) return;

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${this.escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        container.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }

    showNumberModal(number = null) {
        const modal = document.getElementById('number-modal');
        const form = document.getElementById('number-form');

        if (number) {
            // Handle the case where number is nested in a data property
            const numberData = number.data || number;
            
            document.getElementById('number-modal-label').textContent = 'Edit Phone Number';
            document.getElementById('number-id').value = numberData.id;
            document.getElementById('phone-number').value = numberData.phone_number;
            document.getElementById('number-description').value = numberData.description || '';
            document.getElementById('number-is-active').checked = numberData.is_active;
        } else {
            document.getElementById('number-modal-label').textContent = 'Add Phone Number';
            form.reset();
        }

        new bootstrap.Modal(modal).show();
    }

    async saveNumber() {
        const form = document.getElementById('number-form');
        const id = document.getElementById('number-id').value;
        const data = {
            phone_number: document.getElementById('phone-number').value,
            description: document.getElementById('number-description').value,
            is_active: document.getElementById('number-is-active').checked
        };

        try {
            const url = id ? 
                `/api/phone-trees/${this.currentPhoneTree.id}/numbers/${id}` : 
                `/api/phone-trees/${this.currentPhoneTree.id}/numbers`;
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) throw new Error('Failed to save phone number');

            bootstrap.Modal.getInstance(document.getElementById('number-modal')).hide();
            this.loadPhoneTreeDetails();
        } catch (error) {
            console.error('Error saving phone number:', error);
            this.showError('Failed to save phone number');
        }
    }

    async editNumber(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/numbers/${id}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            const number = await response.json();
            this.showNumberModal(number);
        } catch (error) {
            console.error('Error loading number:', error);
            this.showError('Failed to load number details');
        }
    }

    confirmDeleteNumber(id) {
        const number = this.currentPhoneTree.numbers.find(n => n.id === id);
        document.getElementById('confirm-delete-title').textContent = 'Delete Phone Number';
        document.getElementById('confirm-delete-message').textContent = 
            `Are you sure you want to delete phone number "${number?.phone_number || 'Unknown'}" (ID: ${id})? This action cannot be undone.`;
        
        const deleteButton = document.getElementById('confirm-delete-button');
        deleteButton.onclick = () => this.deleteNumber(id);
        const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        modal.show();
    }

    async deleteNumber(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/numbers/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to delete number');

            bootstrap.Modal.getInstance(document.getElementById('confirm-delete-modal')).hide();
            this.loadPhoneTreeDetails();
            this.showSuccess('Phone number deleted successfully');
        } catch (error) {
            console.error('Error deleting number:', error);
            this.showError('Failed to delete number');
        }
    }

    async showMenuModal(menu = null) {
        const modal = document.getElementById('menu-modal');
        const form = document.getElementById('menu-form');

        // Reset form and set initial values
        form.reset();

        // Load audio files for all audio selects
        try {
            const response = await fetch('/api/audio-files', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load audio files');
            }
            
            const audioFiles = result.data;
            
            // Helper function to populate audio dropdown
            const populateAudioDropdown = (selectId) => {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">None</option>';
                    audioFiles.forEach(audio => {
                        const option = document.createElement('option');
                        option.value = audio.id;
                        option.textContent = audio.name;
                        select.appendChild(option);
                    });
                }
            };

            // Populate all audio dropdowns
            populateAudioDropdown('menu-welcome-audio');
            populateAudioDropdown('menu-prompt-audio');
            populateAudioDropdown('menu-finish-audio');
        } catch (error) {
            console.error('Error loading audio files:', error);
            this.showError('Failed to load audio files');
        }

        if (menu) {
            document.getElementById('menu-id').value = menu.id;
            document.getElementById('menu-parent').value = menu.parent_id || '';
            document.getElementById('menu-name').value = menu.name || '';
            document.getElementById('menu-description').value = menu.description || '';
            document.getElementById('menu-welcome-message').value = menu.welcome_message || '';
            document.getElementById('menu-prompt').value = menu.prompt_message || '';
            document.getElementById('menu-timeout-message').value = menu.timeout_message || '';
            document.getElementById('menu-invalid-input-message').value = menu.invalid_input_message || '';
            document.getElementById('menu-max-retries').value = menu.max_retries || 3;
            document.getElementById('menu-timeout-seconds').value = menu.timeout_seconds || 5;
            document.getElementById('menu-order').value = menu.order || 0;
            document.getElementById('menu-is-active').checked = menu.is_active || false;
            document.getElementById('menu-speak-options').checked = menu.speak_options || true;
            document.getElementById('menu-disconnect-on-finish').checked = menu.disconnect_on_finish || false;
            document.getElementById('menu-welcome-audio').value = menu.welcome_audio_id || '';
            document.getElementById('menu-prompt-audio').value = menu.prompt_audio_id || '';
            document.getElementById('menu-finish-audio').value = menu.finish_audio_id || '';
            document.getElementById('menu-finish-message').value = menu.finish_message || '';
            document.getElementById('menu-finish-menu').value = menu.finish_menu_id || '';
        }

        // Show the modal first
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        try {
            // Load assistants for dropdown
            const assistantsResponse = await fetch('/api/user_assistants', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!assistantsResponse.ok) {
                throw new Error('Failed to load assistants');
            }
            
            const assistantsResult = await assistantsResponse.json();
            const assistants = assistantsResult.assistants || [];
            
            // Populate assistant dropdown
            const assistantSelect = document.getElementById('menu-assistant');
            assistantSelect.innerHTML = '<option value="">None</option>';
            
            assistants.forEach(assistant => {
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = assistant.name;
                assistantSelect.appendChild(option);
            });

            // Load pipelines for dropdown
            const pipelinesResponse = await fetch('/api/pipelines', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!pipelinesResponse.ok) {
                throw new Error('Failed to load pipelines');
            }

            const pipelines = await pipelinesResponse.json();
            
            // Populate pipeline dropdown
            const pipelineSelect = document.getElementById('menu-pipeline-id');
            pipelineSelect.innerHTML = '<option value="">None</option>';
            
            pipelines.forEach(pipeline => {
                const option = document.createElement('option');
                option.value = pipeline.id;
                option.textContent = pipeline.name;
                pipelineSelect.appendChild(option);
            });

            // Set values independently if editing
            if (menu) {
                if (menu.assistant_id) {
                    document.getElementById('menu-assistant').value = menu.assistant_id;
                }
                if (menu.pipeline_id) {
                    document.getElementById('menu-pipeline-id').value = menu.pipeline_id;
                }
            }

        } catch (error) {
            console.error('Error loading data:', error);
            this.showError('Failed to load assistants or pipelines');
        }
    }

    async saveMenu() {
        try {
        const form = document.getElementById('menu-form');
            if (!form) {
                throw new Error('Menu form not found');
            }

        const data = {
                phone_tree_id: this.currentPhoneTree.id,
                id: document.getElementById('menu-id').value,
                parent_id: document.getElementById('menu-parent').value,
            name: document.getElementById('menu-name').value,
            description: document.getElementById('menu-description').value,
                welcome_message: document.getElementById('menu-welcome-message').value,
                prompt_message: document.getElementById('menu-prompt').value,
            timeout_message: document.getElementById('menu-timeout-message').value,
            invalid_input_message: document.getElementById('menu-invalid-input-message').value,
                max_retries: parseInt(document.getElementById('menu-max-retries').value) || 3,
                timeout_seconds: parseInt(document.getElementById('menu-timeout-seconds').value) || 5,
                order: parseInt(document.getElementById('menu-order').value) || 0,
                is_active: document.getElementById('menu-is-active').checked,
                speak_options: document.getElementById('menu-speak-options').checked,
                disconnect_on_finish: document.getElementById('menu-disconnect-on-finish').checked,
                welcome_audio_id: document.getElementById('menu-welcome-audio').value || null,
                prompt_audio_id: document.getElementById('menu-prompt-audio').value || null,
                finish_audio_id: document.getElementById('menu-finish-audio').value || null,
                finish_message: document.getElementById('menu-finish-message').value,
                finish_menu_id: document.getElementById('menu-finish-menu').value || null,
                websocket_id: document.getElementById('menu-websocket').value || null,
                websocket_fail_menu_id: document.getElementById('menu-websocket-fail-menu').value || null,
                transfer_number: document.getElementById('menu-transfer-number').value,
                script_id: document.getElementById('menu-script').value || null,
                assistant_id: document.getElementById('menu-assistant').value || null,
                pipeline_id: document.getElementById('menu-pipeline-id').value || null
            };

            console.log('Saving menu with data:', data); // Debug log

            const url = data.id ? 
                `/api/phone-trees/${this.currentPhoneTree.id}/menus/${data.id}` : 
                `/api/phone-trees/${this.currentPhoneTree.id}/menus`;
            const method = data.id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${appState.apiToken}`
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to save menu');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to save menu');
            }

            this.showSuccess('Menu saved successfully');
            this.loadPhoneTreeDetails();

            // Find and close the modal
            const modal = document.getElementById('menu-modal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        } catch (error) {
            console.error('Error saving menu:', error);
            this.showError(error.message || 'Failed to save menu');
        }
    }

    async editMenu(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/menus/${id}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to load menu details');
            }
            
            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load menu details');
            }
            
            // Pass the data object to the modal
            this.showMenuModal(result.data);
        } catch (error) {
            console.error('Error loading menu:', error);
            this.showError(error.message || 'Failed to load menu details');
        }
    }

    confirmDeleteMenu(id) {
        const menu = this.currentPhoneTree.menus.find(m => m.id === id);
        document.getElementById('confirm-delete-title').textContent = 'Delete Menu';
        document.getElementById('confirm-delete-message').textContent = 
            `Are you sure you want to delete menu "${menu?.name || 'Unknown'}" (ID: ${id})? This action cannot be undone.`;
        
        const deleteButton = document.getElementById('confirm-delete-button');
        deleteButton.onclick = () => this.deleteMenu(id);
        const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        modal.show();
    }

    async deleteMenu(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/menus/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to delete menu');

            bootstrap.Modal.getInstance(document.getElementById('confirm-delete-modal')).hide();
            this.loadPhoneTreeDetails();
            this.showSuccess('Menu deleted successfully');
        } catch (error) {
            console.error('Error deleting menu:', error);
            this.showError('Failed to delete menu');
        }
    }

    async showOptionModal(option = null, menuId = null) {
        const modal = document.getElementById('option-modal');
        const form = document.getElementById('option-form');

        // Clear previous form data
        form.reset();
        document.getElementById('option-id').value = '';
        document.getElementById('display-option-id').textContent = 'New Option';

        // Store the menu ID in a hidden field
        document.getElementById('option-menu-id').value = menuId;

        if (option) {
            document.getElementById('option-modal-label').textContent = 'Edit Option';
            document.getElementById('option-id').value = option.id;
            document.getElementById('display-option-id').textContent = option.id;
            document.getElementById('option-digit').value = option.digit;
            document.getElementById('option-action-type').value = option.action_type;
            document.getElementById('option-description').value = option.description || '';
            document.getElementById('option-order').value = option.order;
            document.getElementById('option-is-active').checked = option.is_active;
            document.getElementById('option-welcome-message').value = option.welcome_message || '';
            document.getElementById('option-welcome-audio').value = option.welcome_audio_id || '';
            document.getElementById('option-finish-menu').value = option.finish_menu_id || '';
        } else {
            document.getElementById('option-modal-label').textContent = 'Add Option';
            document.getElementById('option-action-type').value = 'menu';
        }

        // Show the modal first
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Populate target menu dropdown
        const targetMenuSelect = document.getElementById('option-target-menu');
        targetMenuSelect.innerHTML = '<option value="">Select Menu</option>';
        if (this.currentPhoneTree && this.currentPhoneTree.menus) {
            this.currentPhoneTree.menus.forEach(menu => {
                const menuOption = document.createElement('option');
                menuOption.value = menu.id;
                menuOption.textContent = menu.name;
                targetMenuSelect.appendChild(menuOption);
            });
            if (option && option.target_id) {
                targetMenuSelect.value = option.target_id;
            }
        }

        // Load assistants for target dropdown
        try {
            const response = await fetch('/api/user_assistants', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load assistants');
            }
            
            const result = await response.json();
            const assistants = result.assistants || [];

            // Populate target assistant dropdown
            const targetAssistantSelect = document.getElementById('option-target-assistant');
            targetAssistantSelect.innerHTML = '<option value="">Select an assistant</option>';
            
            assistants.forEach(assistant => {
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = assistant.name;
                targetAssistantSelect.appendChild(option);
            });

            if (option && option.action_type === 'assistant') {
                document.getElementById('option-target-assistant').value = option.target_id || '';
                document.getElementById('option-pipeline-id').value = option.pipeline_id || '';
            }

            // Trigger action type change to show/hide appropriate fields
        this.handleActionTypeChange({ target: document.getElementById('option-action-type') });

        } catch (error) {
            console.error('Error loading assistants:', error);
            this.showError('Failed to load assistants');
        }
    }

    handleActionTypeChange(event) {
        const actionType = event.target.value;
        const targetMenuContainer = document.getElementById('target-menu-container');
        const targetWebsocketContainer = document.getElementById('target-websocket-container');
        const targetScriptContainer = document.getElementById('target-script-container');
        const targetNumberContainer = document.getElementById('target-number-container');
        const targetAudioContainer = document.getElementById('target-audio-container');
        const targetAssistantContainer = document.getElementById('target-assistant-container');
        const pipelineContainer = document.getElementById('pipeline-container');
        
        // Hide all containers first
        targetMenuContainer.style.display = 'none';
        targetWebsocketContainer.style.display = 'none';
        targetScriptContainer.style.display = 'none';
        targetNumberContainer.style.display = 'none';
        targetAudioContainer.style.display = 'none';
        targetAssistantContainer.style.display = 'none';
        pipelineContainer.style.display = 'none';
        
        // Show the appropriate container based on action type
        switch (actionType) {
            case 'menu':
                targetMenuContainer.style.display = 'block';
                document.getElementById('option-target-menu').required = true;
                break;
            case 'websocket':
                targetWebsocketContainer.style.display = 'block';
                document.getElementById('option-target-websocket').required = true;
                break;
            case 'script':
                targetScriptContainer.style.display = 'block';
                document.getElementById('option-target-script').required = true;
                break;
            case 'number':
                targetNumberContainer.style.display = 'block';
                document.getElementById('option-target-number').required = true;
                break;
            case 'audio_file':
                targetAudioContainer.style.display = 'block';
                document.getElementById('option-target-audio').required = true;
                break;
            case 'assistant':
                targetAssistantContainer.style.display = 'block';
                pipelineContainer.style.display = 'block';
                document.getElementById('option-target-assistant').required = true;
                break;
        }
    }

    async saveOption() {
        const form = document.getElementById('option-form');
        const id = document.getElementById('option-id').value;
        const menuId = document.getElementById('option-menu-id').value;
        const actionType = document.getElementById('option-action-type').value;
        
        console.log('Saving option:', {
            id,
            menuId,
            actionType
        });

        const data = {
            id: id,
            //phone_tree_menu_id: menuId,
            digit: document.getElementById('option-digit').value,
            action_type: actionType,
            description: document.getElementById('option-description').value,
            order: parseInt(document.getElementById('option-order').value),
            is_active: document.getElementById('option-is-active').checked,
            welcome_message: document.getElementById('option-welcome-message').value,
            welcome_audio_id: document.getElementById('option-welcome-audio').value || null,
            finish_menu_id: document.getElementById('option-finish-menu').value || null
        };

        if(menuId){
            data.phone_tree_menu_id = menuId;
        }

        // Set target_id based on action type
        switch (actionType) {
            case 'menu':
                data.target_id = document.getElementById('option-target-menu').value;
                break;
            case 'websocket':
                data.target_id = document.getElementById('option-target-websocket').value;
                break;
            case 'script':
                data.target_id = document.getElementById('option-target-script').value;
                break;
            case 'number':
                data.target_id = document.getElementById('option-target-number').value;
                break;
            case 'audio_file':
                data.target_id = document.getElementById('option-target-audio').value;
                break;
        }

        console.log('Option data to be sent:', data);

        try {
            const url = id ? 
                `/api/phone-trees/${this.currentPhoneTree.id}/options/${id}` : 
                `/api/phone-trees/${this.currentPhoneTree.id}/menus/${menuId}/options`;
            const method = id ? 'PUT' : 'POST';
            
            console.log('Making request to:', url, 'with method:', method);
            
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to save option');
            }

            const result = await response.json();
            console.log('Save option response:', result);

            bootstrap.Modal.getInstance(document.getElementById('option-modal')).hide();
            this.loadPhoneTreeDetails();
        } catch (error) {
            console.error('Error saving option:', error);
            this.showError(error.message || 'Failed to save option');
        }
    }

    async editOption(id) {
        try {
            console.log('Fetching option for editing:', id);
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/options/${id}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load option');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load option');
            }

            console.log('Loaded option data:', result.data);
            this.showOptionModal(result.data);
        } catch (error) {
            console.error('Error loading option:', error);
            this.showError('Failed to load option details');
        }
    }

    confirmDeleteOption(id) {
        const menu = this.currentPhoneTree.menus.find(m => m.options.some(o => o.id === id));
        const option = menu?.options.find(o => o.id === id);
        
        // Set up modal content
        document.getElementById('confirm-delete-title').textContent = 'Delete Option';
        document.getElementById('confirm-delete-message').textContent = 
            `Are you sure you want to delete option "${option?.description || 'Unknown'}" (ID: ${id}) from menu "${menu?.name || 'Unknown'}"? This action cannot be undone.`;
        
        // Set up delete button handler
        const deleteButton = document.getElementById('confirm-delete-btn');
        if (deleteButton) {
            deleteButton.onclick = () => this.deleteOption(id);
        }
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        modal.show();
    }

    async deleteOption(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/options/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to delete option');

            bootstrap.Modal.getInstance(document.getElementById('confirm-delete-modal')).hide();
            this.loadPhoneTreeDetails();
            this.showSuccess('Option deleted successfully');
        } catch (error) {
            console.error('Error deleting option:', error);
            this.showError('Failed to delete option');
        }
    }

    showWebSocketModal(websocket = null) {
        const modal = document.getElementById('websocket-modal');
        const form = document.getElementById('websocket-form');

        if (websocket) {
            document.getElementById('websocket-modal-label').textContent = 'Edit WebSocket Connection';
            document.getElementById('websocket-id').value = websocket.id;
            document.getElementById('websocket-endpoint').value = websocket.endpoint_url;
            document.getElementById('websocket-connection-type').value = websocket.connection_type;
            document.getElementById('websocket-auth-type').value = websocket.authentication_type;
            
            // Handle credentials based on auth type
            if (websocket.authentication_type === 'token') {
                document.getElementById('websocket-token').value = websocket.authentication_credentials || '';
            } else if (websocket.authentication_type === 'basic') {
                const credentials = JSON.parse(websocket.authentication_credentials || '{}');
                document.getElementById('websocket-username').value = credentials.username || '';
                document.getElementById('websocket-password').value = credentials.password || '';
            }
            
            document.getElementById('websocket-is-active').checked = websocket.is_active;
        } else {
            document.getElementById('websocket-modal-label').textContent = 'Add WebSocket Connection';
            form.reset();
        }

        // Trigger auth type change to show correct fields
        this.handleWebsocketAuthTypeChange({ target: document.getElementById('websocket-auth-type') });
        new bootstrap.Modal(modal).show();
    }

    handleWebsocketAuthTypeChange(event) {
        const authType = event.target.value;
        const tokenContainer = document.getElementById('websocket-token-container');
        const basicAuthContainer = document.getElementById('websocket-basic-auth-container');
        
        if (authType === 'token') {
            tokenContainer.style.display = 'block';
            basicAuthContainer.style.display = 'none';
        } else if (authType === 'basic') {
            tokenContainer.style.display = 'none';
            basicAuthContainer.style.display = 'block';
        } else {
            tokenContainer.style.display = 'none';
            basicAuthContainer.style.display = 'none';
        }
    }

    async saveWebSocket() {
        const form = document.getElementById('websocket-form');
        const id = document.getElementById('websocket-id').value;
        const authType = document.getElementById('websocket-auth-type').value;
        
        let authCredentials = null;
        if (authType === 'token') {
            authCredentials = document.getElementById('websocket-token').value;
        } else if (authType === 'basic') {
            authCredentials = JSON.stringify({
                username: document.getElementById('websocket-username').value,
                password: document.getElementById('websocket-password').value
            });
        }

        const data = {
            endpoint_url: document.getElementById('websocket-endpoint').value,
            connection_type: document.getElementById('websocket-connection-type').value,
            authentication_type: authType,
            authentication_credentials: authCredentials,
            is_active: document.getElementById('websocket-is-active').checked
        };

        try {
            const url = id ? 
                `/api/phone-trees/${this.currentPhoneTree.id}/websockets/${id}` : 
                `/api/phone-trees/${this.currentPhoneTree.id}/websockets`;
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
                const error = await response.json();
                throw new Error(error.message || 'Failed to save WebSocket connection');
            }

            const result = await response.json();
            bootstrap.Modal.getInstance(document.getElementById('websocket-modal')).hide();
            this.loadPhoneTreeDetails();
        } catch (error) {
            console.error('Error saving WebSocket connection:', error);
            this.showError(error.message || 'Failed to save WebSocket connection');
        }
    }

    async editWebSocket(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/websockets/${id}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to load WebSocket details');
            }
            
            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load WebSocket details');
            }
            
            // Pass the data object to the modal
            this.showWebSocketModal(result.data);
        } catch (error) {
            console.error('Error loading WebSocket:', error);
            this.showError(error.message || 'Failed to load WebSocket details');
        }
    }

    confirmDeleteWebSocket(id) {
        const websocket = this.currentPhoneTree.websockets.find(w => w.id === id);
        document.getElementById('confirm-delete-title').textContent = 'Delete WebSocket Connection';
        document.getElementById('confirm-delete-message').textContent = 
            `Are you sure you want to delete WebSocket connection "${websocket?.endpoint_url || 'Unknown'}" (ID: ${id})? This action cannot be undone.`;
        
        const deleteButton = document.getElementById('confirm-delete-button');
        deleteButton.onclick = () => this.deleteWebSocket(id);
        const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        modal.show();
    }

    async deleteWebSocket(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/websockets/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to delete WebSocket connection');

            bootstrap.Modal.getInstance(document.getElementById('confirm-delete-modal')).hide();
            this.loadPhoneTreeDetails();
            this.showSuccess('WebSocket connection deleted successfully');
        } catch (error) {
            console.error('Error deleting WebSocket connection:', error);
            this.showError('Failed to delete WebSocket connection');
        }
    }

    async viewCallDetails(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/calls/${id}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to load call details');
            }
            
            const call = await response.json();
            
            document.getElementById('call-from-number').textContent = call.from_number;
            document.getElementById('call-to-number').textContent = call.to_number;
            document.getElementById('call-start-time').textContent = new Date(call.start_time).toLocaleString();
            document.getElementById('call-end-time').textContent = call.end_time ? new Date(call.end_time).toLocaleString() : 'N/A';
            document.getElementById('call-status').textContent = call.status;
            document.getElementById('call-current-menu').textContent = call.current_menu?.name || 'N/A';

            // Render recordings
            const recordingsTbody = document.getElementById('recordings-list');
            recordingsTbody.innerHTML = '';
            
            call.recordings?.forEach(recording => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${recording.duration}s</td>
                    <td>${new Date(recording.start_time).toLocaleString()}</td>
                    <td>${recording.status}</td>
                    <td>
                        <a href="${recording.recording_url}" class="btn btn-sm btn-primary" target="_blank">
                            <i class="fas fa-play"></i> Play
                        </a>
                    </td>
                `;
                recordingsTbody.appendChild(tr);
            });

            // Render transcriptions
            const transcriptionsTbody = document.getElementById('transcriptions-list');
            transcriptionsTbody.innerHTML = '';
            
            call.transcriptions?.forEach(transcription => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${transcription.text}</td>
                    <td>${transcription.language}</td>
                    <td>${transcription.confidence}%</td>
                    <td>${transcription.status}</td>
                `;
                transcriptionsTbody.appendChild(tr);
            });

            new bootstrap.Modal(document.getElementById('call-details-modal')).show();
        } catch (error) {
            console.error('Error loading call details:', error);
            this.showError(error.message || 'Failed to load call details');
        }
    }

    updatePhoneTreeDetail(phoneTree) {
        document.getElementById('phone-tree-detail-title').textContent = phoneTree.name;
        document.getElementById('phone-tree-detail-description').textContent = phoneTree.description || 'No description provided';
        document.getElementById('details-status').textContent = phoneTree.is_active ? 'Active' : 'Inactive';
        document.getElementById('details-max-retries').textContent = phoneTree.max_retries;
        document.getElementById('details-timeout-seconds').textContent = phoneTree.timeout_seconds;
        document.getElementById('details-welcome-message').textContent = phoneTree.welcome_message;
        document.getElementById('details-timeout-message').textContent = phoneTree.timeout_message;
        document.getElementById('details-invalid-input-message').textContent = phoneTree.invalid_input_message;
    }

    async showScriptModal(script = null) {
        const modal = document.getElementById('script-modal');
        const form = document.getElementById('script-form');

        if (script) {
            document.getElementById('script-modal-label').textContent = 'Edit Script';
            document.getElementById('script-id').value = script.id;
            document.getElementById('script-name').value = script.name;
            document.getElementById('script-description').value = script.description || '';
            document.getElementById('script-path').value = script.path;
            document.getElementById('script-parameters').value = JSON.stringify(script.parameters || {}, null, 2);
            document.getElementById('script-is-active').checked = script.is_active;
        } else {
            document.getElementById('script-modal-label').textContent = 'Add Script';
            document.getElementById('script-id').value = '';
            form.reset();
        }

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    async saveScript() {
        const form = document.getElementById('script-form');
        const id = document.getElementById('script-id').value;
        const data = {
            name: document.getElementById('script-name').value,
            description: document.getElementById('script-description').value,
            path: document.getElementById('script-path').value,
            parameters: JSON.parse(document.getElementById('script-parameters').value || '{}'),
            is_active: document.getElementById('script-is-active').checked
        };

        try {
            const url = id ? 
                `/api/phone-trees/${this.currentPhoneTree.id}/scripts/${id}` : 
                `/api/phone-trees/${this.currentPhoneTree.id}/scripts`;
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to save script');
            }

            bootstrap.Modal.getInstance(document.getElementById('script-modal')).hide();
            this.loadPhoneTreeDetails();
        } catch (error) {
            console.error('Error saving script:', error);
            this.showError(error.message || 'Failed to save script');
        }
    }

    async editScript(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/scripts/${id}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load script');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Failed to load script');
            }

            this.showScriptModal(result.data);
        } catch (error) {
            console.error('Error loading script:', error);
            this.showError('Failed to load script');
        }
    }

    confirmDeleteScript(id) {
        const script = this.currentPhoneTree.scripts.find(s => s.id === id);
        document.getElementById('confirm-delete-title').textContent = 'Delete Script';
        document.getElementById('confirm-delete-message').textContent = 
            `Are you sure you want to delete script "${script?.name || 'Unknown'}" (ID: ${id})? This action cannot be undone.`;
        
        const deleteButton = document.getElementById('confirm-delete-button');
        deleteButton.onclick = () => this.deleteScript(id);
        const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        modal.show();
    }

    async deleteScript(id) {
        try {
            const response = await fetch(`/api/phone-trees/${this.currentPhoneTree.id}/scripts/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to delete script');

            bootstrap.Modal.getInstance(document.getElementById('confirm-delete-modal')).hide();
            this.loadPhoneTreeDetails();
            this.showSuccess('Script deleted successfully');
        } catch (error) {
            console.error('Error deleting script:', error);
            this.showError('Failed to delete script');
        }
    }
}

// Initialize the manager
const phoneTreeManager = new PhoneTreeManager(); 