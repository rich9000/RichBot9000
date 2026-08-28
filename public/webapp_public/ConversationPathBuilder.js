class ConversationPathBuilder {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.paths = [];
        this.selectedPath = { nodes: [] };
        this.assistants = [];
        this.tools = [];
        this.nodeTypes = {
            entry: {
                root: {
                    icon: 'fa-sign-in-alt',
                    name: 'Entry Point',
                    color: '#007bff',
                    description: 'Configure entry points for the conversation'
                },
                chat: {
                    icon: 'fa-comments',
                    name: 'Chat Entry',
                    color: '#007bff',
                    description: 'Starts a chat conversation'
                },
                twilioInbound: {
                    icon: 'fa-phone',
                    name: 'Twilio Inbound',
                    color: '#28a745',
                    description: 'Handles incoming Twilio calls'
                },
                twilioOutbound: {
                    icon: 'fa-phone',
                    name: 'Twilio Outbound',
                    color: '#dc3545',
                    description: 'Makes outbound Twilio calls'
                }
            },
            data: {
                outageCheck: {
                    icon: 'fa-exclamation-triangle',
                    name: 'Outage Check',
                    color: '#ffc107',
                    description: 'Checks for active outages',
                    script: 'outage_check'
                },
                customerLookup: {
                    icon: 'fa-user',
                    name: 'Customer Lookup',
                    color: '#17a2b8',
                    description: 'Looks up customer information',
                    script: 'customer_lookup'
                },
                custom: {
                    icon: 'fa-code',
                    name: 'Custom Script',
                    color: '#6c757d',
                    description: 'Run a custom script',
                    script: 'custom'
                },
                contextAssistant: {
                    icon: 'fa-robot',
                    name: 'Context Assistant',
                    color: '#6610f2',
                    description: 'Manage conversation context with an assistant',
                    script: 'context_assistant'
                }
            },
            decision: {
                user: {
                    icon: 'fa-user',
                    name: 'User Decision',
                    color: '#6610f2',
                    description: 'Decision based on user input'
                },
                assistant: {
                    icon: 'fa-robot',
                    name: 'Assistant Decision',
                    color: '#6610f2',
                    description: 'Decision based on assistant analysis'
                },
                conditional: {
                    icon: 'fa-code-branch',
                    name: 'Conditional Decision',
                    color: '#6610f2',
                    description: 'Decision based on a script condition'
                }
            },
            action: {
                say: {
                    icon: 'fa-comment',
                    name: 'Say',
                    color: '#20c997',
                    description: 'Speak text to the user'
                },
                play: {
                    icon: 'fa-play-circle',
                    name: 'Play',
                    color: '#20c997',
                    description: 'Play an audio file'
                },
                assistant: {
                    icon: 'fa-robot',
                    name: 'Assistant',
                    color: '#20c997',
                    description: 'Use an AI assistant to respond'
                },
                monitorCall: {
                    icon: 'fa-phone',
                    name: 'Monitor Call',
                    color: '#20c997',
                    description: 'Monitor a call with an assistant'
                },
                pipeline: {
                    icon: 'fa-project-diagram',
                    name: 'Pipeline',
                    color: '#20c997',
                    description: 'Execute a pipeline'
                },
                phoneTree: {
                    icon: 'fa-sitemap',
                    name: 'Phone Tree',
                    color: '#20c997',
                    description: 'Use a phone tree menu'
                },
                survey: {
                    icon: 'fa-poll',
                    name: 'Survey',
                    color: '#20c997',
                    description: 'Conduct a survey'
                },
                hangup: {
                    icon: 'fa-phone-slash',
                    name: 'Hang Up',
                    color: '#20c997',
                    description: 'End the call'
                },
                voiceMail: {
                    icon: 'fa-microphone',
                    name: 'Voice Mail',
                    color: '#20c997',
                    description: 'Record a voice mail message'
                },
                transfer: {
                    icon: 'fa-exchange-alt',
                    name: 'Phone Transfer',
                    color: '#20c997',
                    description: 'Transfer to another phone number'
                },
                route: {
                    icon: 'fa-random',
                    name: 'Route to Conversation Node',
                    color: '#20c997',
                    description: 'Route to another node in the conversation'
                },
                conversationPath: {
                    icon: 'fa-project-diagram',
                    name: 'Route to Conversation Path',
                    color: '#20c997',
                    description: 'Route to another conversation path'
                },
                script: {
                    icon: 'fa-code',
                    name: 'Script',
                    color: '#20c997',
                    description: 'Execute a custom script'
                },
                websocket: {
                    icon: 'fa-plug',
                    name: 'WebSocket Transfer',
                    color: '#20c997',
                    description: 'Transfer to a WebSocket connection'
                }
            }
        };
        
        // Add styles for the main drop zone
        if (!document.getElementById('conversation-path-builder-styles')) {
            const style = document.createElement('style');
            style.id = 'conversation-path-builder-styles';
            style.textContent = `
                .main-drop-zone {
                    border: 2px dashed #007bff;
                    border-radius: 4px;
                    padding: 2rem;
                    margin: 1rem auto;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    color: #007bff;
                    background: rgba(0, 123, 255, 0.05);
                    transition: all 0.2s ease;
                    max-width: 600px;
                    cursor: default;
                    min-height: 200px;
                }
                .main-drop-zone.drag-over {
                    background: rgba(0, 123, 255, 0.1);
                    border-style: solid;
                }
                .main-drop-zone i {
                    font-size: 2rem;
                }
                .main-drop-zone span {
                    font-size: 1rem;
                    font-weight: 500;
                }
                .main-drop-zone small {
                    font-size: 0.8rem;
                    opacity: 0.8;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Initialize immediately
        this.initialize();
    }

    initialize() {
        this.setupEventListeners();
        this.loadAssistants();
        this.loadTools();
        this.loadScripts();
        this.loadAudioFiles();
        this.loadPhoneTrees();
        this.loadPipelines();
        this.loadSurveys();
        this.loadPaths(); // Add this line
    }

    async loadScripts() {
        try {
            const response = await fetch('/api/scripts', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load scripts');
            }
            
            const scripts = await response.json();
            this.scripts = scripts || [];
            this.renderPathCanvas();
        } catch (error) {
            console.error('Error loading scripts:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to load scripts');
            }
        }
    }

    setupEventListeners() {
        // Add Path button
        const addPathBtn = this.container.querySelector('#add-path');
        if (addPathBtn) {
            addPathBtn.addEventListener('click', () => {
                this.showAddPathModal();
            });
        }

        // Entry point checkboxes
        const entryCheckboxes = this.container.querySelectorAll('.entry-point-option input[type="checkbox"]');
        entryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const entryType = e.target.id.replace('entry', '').toLowerCase();
                this.toggleEntryPoint(entryType, e.target.checked);
            });
        });

        // Palette drag and drop
        this.container.querySelectorAll('.palette-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.nodeType);
                e.dataTransfer.setData('nodeSubtype', item.dataset.nodeSubtype);
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
            });
        });

        // Canvas drop zone
        const canvas = this.container.querySelector('.canvas-container');
        if (canvas) {
            canvas.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                canvas.classList.add('drag-over');
            });

            canvas.addEventListener('dragleave', (e) => {
                canvas.classList.remove('drag-over');
            });

            canvas.addEventListener('drop', (e) => {
                e.preventDefault();
                canvas.classList.remove('drag-over');
                const nodeType = e.dataTransfer.getData('nodeType');
                const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
                this.addNode(nodeType, nodeSubtype);
            });
        }
    }

    toggleEntryPoint(entryType, enabled) {
        if (!this.selectedPath) return;

        const entryNode = this.selectedPath.nodes.find(node => 
            node.type === 'entry' && node.subtype === entryType
        );

        if (enabled && !entryNode) {
            // Add the entry point if it doesn't exist
            const node = {
                type: 'entry',
                subtype: entryType,
                ...this.getDefaultNodeConfig('entry', entryType)
            };
            this.selectedPath.nodes.push(node);
        } else if (!enabled && entryNode && !entryNode.isDefault) {
            // Remove the entry point if it exists and is not the default
            const index = this.selectedPath.nodes.indexOf(entryNode);
            if (index > -1) {
                this.selectedPath.nodes.splice(index, 1);
            }
        }

        this.renderPathCanvas();
    }

    showAddPathModal() {
        // Remove existing modal if any
        const existingModal = document.getElementById('addPathModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="addPathModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">New Conversation Path</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label for="pathName">Path Name</label>
                                <input type="text" class="form-control" id="pathName" placeholder="Enter path name">
                            </div>
                            <div class="form-group">
                                <label for="pathDescription">Description</label>
                                <textarea class="form-control" id="pathDescription" rows="3" placeholder="Enter path description"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="savePath">Create Path</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Initialize modal
        const modalElement = document.getElementById('addPathModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Handle save button
        const saveButton = document.getElementById('savePath');
        if (saveButton) {
            saveButton.addEventListener('click', () => {
                const pathName = document.getElementById('pathName').value.trim();
                const pathDescription = document.getElementById('pathDescription').value.trim();
                if (pathName) {
                    this.addPath(pathName, pathDescription);
                    modal.hide();
                    modalElement.remove();
                }
            });
        }

        // Handle modal hidden event
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
    }

    addPath(name, description = '') {
        const path = {
            name: name,
            description: description,
            nodes: [{
                type: 'entry',
                subtype: 'root',
                options: {
                    chat: {
                        enabled: true,
                        welcomeMessage: 'Welcome to the conversation!'
                    },
                    twilioInbound: {
                        enabled: false,
                        phoneNumber: ''
                    },
                    twilioOutbound: {
                        enabled: false,
                        phoneNumber: '',
                        initialMessage: ''
                    }
                }
            }]
        };

        this.paths.push(path);
        this.renderPathList();
        this.selectPath(path.id);
    }

    updateEntryPointCheckboxes() {
        if (!this.selectedPath) return;

        const entryTypes = ['chat', 'twilioInbound', 'twilioOutbound'];
        entryTypes.forEach(type => {
            const checkbox = this.container.querySelector(`#entry${type.charAt(0).toUpperCase() + type.slice(1)}`);
            if (checkbox) {
                const hasEntry = this.selectedPath.nodes.some(node => 
                    node.type === 'entry' && node.subtype === type
                );
                checkbox.checked = hasEntry;
            }
        });
    }

    async loadAssistants() {
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
            this.assistants = result.assistants || [];
            this.renderPathList();
            this.renderPathCanvas(); // Ensure dropdown is populated after assistants are loaded
        } catch (error) {
            console.error('Error loading assistants:', error);
        }
    }

    async loadTools() {
        try {
            const response = await fetch('/api/tools', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load tools');
            }
            
            const result = await response.json();
            this.tools = result.tools || [];
        } catch (error) {
            console.error('Error loading tools:', error);
        }
    }

    async loadAudioFiles() {
        try {
            const response = await fetch('/api/audio-files', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load audio files');
            }
            
            const result = await response.json();
            this.audioFiles = result.data || [];
        } catch (error) {
            console.error('Error loading audio files:', error);
        }
    }

    async loadPhoneTrees() {
        try {
            const response = await fetch('/api/phone-trees', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load phone trees');
            }
            
            const result = await response.json();
            this.phoneTrees = result.data || [];
        } catch (error) {
            console.error('Error loading phone trees:', error);
        }
    }

    async loadPipelines() {
        try {
            const response = await fetch('/api/pipelines', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load pipelines');
            }
            
            const result = await response.json();
            this.pipelines = result || [];
        } catch (error) {
            console.error('Error loading pipelines:', error);
        }
    }

    async loadSurveys() {
        try {
            const response = await fetch('/api/surveys', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load surveys');
            }
            
            const result = await response.json();
            this.surveys = result || [];
        } catch (error) {
            console.error('Error loading surveys:', error);
        }
    }

    async loadPaths() {
        try {
            const response = await fetch('/api/conversation-paths', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load paths');
            }
            
            const paths = await response.json();
            this.paths = paths;
            // Fix action node content
            this.paths.forEach(path => {
                if (Array.isArray(path.nodes)) {
                    path.nodes.forEach(node => {
                        if (node.type === 'action') {
                            if (!node.content || typeof node.content !== 'object' || Array.isArray(node.content)) {
                                node.content = {};
                            }
                        }
                    });
                }
            });
            this.renderPathList();
        } catch (error) {
            console.error('Error loading paths:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to load conversation paths');
            }
        }
    }

    async savePath() {
        if (!this.selectedPath) return;

        try {
            const method = this.selectedPath.id ? 'PUT' : 'POST';
            const url = this.selectedPath.id 
                ? `/api/conversation-paths/${this.selectedPath.id}`
                : '/api/conversation-paths';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.selectedPath.name,
                    description: this.selectedPath.description,
                    nodes: this.selectedPath.nodes
                })
            });

            if (!response.ok) {
                throw new Error('Failed to save path');
            }

            const savedPath = await response.json();
            
            // Update the path in our local array
            if (!this.selectedPath.id) {
                // For new paths, remove the temporary one and add the saved one
                this.paths = this.paths.filter(p => p !== this.selectedPath);
                this.paths.push(savedPath);
                this.selectedPath = savedPath;
            } else {
                const index = this.paths.findIndex(p => p.id === savedPath.id);
                if (index !== -1) {
                    this.paths[index] = savedPath;
                    this.selectedPath = savedPath;
                }
            }

            this.renderPathList();
            
            // Show success notification
            if (typeof toastr !== 'undefined') {
                toastr.success(`Path "${savedPath.name}" saved successfully`);
            } else {
                alert(`Path "${savedPath.name}" saved successfully`);
            }
        } catch (error) {
            console.error('Error saving path:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to save conversation path');
            } else {
                alert('Failed to save conversation path');
            }
        }
    }

    async deletePath(pathId) {
        if (!confirm('Are you sure you want to delete this path?')) return;

        try {
            const response = await fetch(`/api/conversation-paths/${pathId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to delete path');
            }

            // Remove from local array
            this.paths = this.paths.filter(p => p.id !== pathId);
            
            // If the deleted path was selected, clear selection
            if (this.selectedPath && this.selectedPath.id === pathId) {
                this.selectedPath = null;
            }

            this.renderPathList();
            this.renderPathCanvas();
            
            if (typeof toastr !== 'undefined') {
                toastr.success('Path deleted successfully');
            }
        } catch (error) {
            console.error('Error deleting path:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to delete conversation path');
            }
        }
    }

    createUI() {
        this.container.innerHTML = `
            <div class="conversation-path-builder">
                <div class="row">
                    <div class="col-md-3">
                        <div class="path-palette">
                            <h4>Conversation Paths</h4>
                            <div class="path-list"></div>
                            <button class="btn btn-primary btn-sm mt-3" id="add-path">
                                <i class="fas fa-plus"></i> New Path
                            </button>
                            
                            <h4 class="mt-4">Entry Points</h4>
                            <div class="node-palette entry-palette">
                                ${Object.entries(this.nodeTypes.entry).map(([key, node]) => `
                                    <div class="node-item" draggable="true" data-node-type="entry" data-node-subtype="${key}">
                                        <i class="fas ${node.icon}"></i>
                                        <span>${node.name}</span>
                                    </div>
                                `).join('')}
                            </div>

                            <h4 class="mt-4">Data Nodes</h4>
                            <div class="node-palette data-palette">
                                ${Object.entries(this.nodeTypes.data).map(([key, node]) => `
                                    <div class="node-item" draggable="true" data-node-type="data" data-node-subtype="${key}">
                                        <i class="fas ${node.icon}"></i>
                                        <span>${node.name}</span>
                                    </div>
                                `).join('')}
                            </div>

                            <h4 class="mt-4">Decision Nodes</h4>
                            <div class="node-palette decision-palette">
                                ${Object.entries(this.nodeTypes.decision).map(([key, node]) => `
                                    <div class="node-item" draggable="true" data-node-type="decision" data-node-subtype="${key}">
                                        <i class="fas ${node.icon}"></i>
                                        <span>${node.name}</span>
                                    </div>
                                `).join('')}
                            </div>

                            <h4 class="mt-4">Action Nodes</h4>
                            <div class="node-palette action-palette">
                                ${[
                                    'assistant',
                                    'pipeline',
                                    'phoneTree',
                                    'survey',
                                    'script',
                                    'say',
                                    'play',
                                    'monitorCall',
                                    'hangup',
                                    'voiceMail',
                                    'transfer',
                                    'route',
                                    'conversationPath',
                                    'websocket'
                                ].map(key => {
                                    const node = this.nodeTypes.action[key];
                                    if (!node) return '';
                                    return `
                                    <div class="node-item" draggable="true" data-node-type="action" data-node-subtype="${key}">
                                        <i class="fas ${node.icon}"></i>
                                        <span>${node.name}</span>
                                    </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="path-canvas">
                            <div class="canvas-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.container.querySelector('#add-path').addEventListener('click', () => this.addPath());
        this.setupPaletteEventListeners();
    }

    setupPaletteEventListeners() {
        this.container.querySelectorAll('.node-palette .node-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.nodeType);
                e.dataTransfer.setData('nodeSubtype', item.dataset.nodeSubtype);
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
            });
        });
    }

    renderPathList() {
        const pathList = this.container.querySelector('.path-list');
        pathList.innerHTML = this.paths.map(path => `
            <div class="path-item ${path.id === this.selectedPath?.id ? 'active' : ''}" 
                 data-path-id="${path.id}">
                <i class="fas fa-project-diagram"></i>
                <span>${path.name}</span>
                <div class="path-actions">
                    <button class="btn btn-sm btn-outline-danger delete-path" data-path-id="${path.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        pathList.querySelectorAll('.path-item').forEach(item => {
            item.addEventListener('click', (e) => {
                // Don't trigger if clicking delete button
                if (e.target.closest('.delete-path')) return;
                
                const pathId = parseInt(item.dataset.pathId);
                this.selectPath(pathId);
            });
        });

        // Add delete button event listeners
        pathList.querySelectorAll('.delete-path').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const pathId = parseInt(button.dataset.pathId);
                this.deletePath(pathId);
            });
        });
    }

    renderPathCanvas() {
        const canvas = this.container.querySelector('.canvas-container');
        if (!this.selectedPath || this.paths.length === 0) {
            canvas.innerHTML = '<div class="text-center">Select or create a path to begin</div>';
            return;
        }

        // Sort nodes to ensure entry points are first and filter out invalid nodes
        const sortedNodes = [...this.selectedPath.nodes].filter(node => node && node.type && node.subtype).sort((a, b) => {
            if (a.type === 'entry' && b.type !== 'entry') return -1;
            if (a.type !== 'entry' && b.type === 'entry') return 1;
            return 0;
        });

        canvas.innerHTML = `
            <div class="nodes-container">
                <div class="row">
                    ${sortedNodes.map((node, index) => `
                        <div class="col-md-12">
                            <div class="conversation-node ${node.type} ${node.subtype}" data-node-index="${index}">
                                <div class="node-header" ${node.type !== 'decision' ? 'onclick="window.pathBuilder.toggleNodeForm(this.parentElement)"' : ''}>
                                    <i class="fas ${this.nodeTypes[node.type][node.subtype]?.icon || 'fa-circle'}"></i>
                                    <span>${node.type === 'entry' ? this.selectedPath.name : this.nodeTypes[node.type][node.subtype]?.name || 'Node'}</span>
                                    <span class="node-info">${this.getCollapsedNodeInfo(node)}</span>
                                    ${node.type !== 'decision' ? '<i class="fas fa-chevron-down node-toggle"></i>' : ''}
                                    <div class="node-actions">
                                        ${!node.isDefault ? `
                                            <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${index}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${index}">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${index}">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </div>
                                <div class="node-body">
                                    ${this.renderNodeContent(node, index)}
                                </div>
                                ${node.type === 'decision' ? this.renderDecisionNodeContent(node, index) : ''}
                            </div>
                        </div>
                    `).join('')}
                    <div class="col-md-12">
                        <div class="main-drop-zone">
                            <i class="fas fa-plus-circle"></i>
                            <span>Drop node here to add to flow</span>
                            <small class="text-muted">Drag a node from the palette</small>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.setupCanvasEventListeners();
    }

    renderNodeContent(node, nodeIndex) {
        if (!node || !node.type || !node.subtype) return '';

        // Action Node settings area (like Decision Nodes, but no actions area)
        if (node.type === 'action') {
            return `
                <div class="settings-section">
                    <div class="settings-header">
                        <h6>Settings</h6>
                        <button class="btn btn-sm btn-outline-primary edit-settings" onclick="window.pathBuilder.toggleNodeSettings(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div class="settings-summary">
                        ${this.getCollapsedNodeInfo(node)}
                    </div>
                    <div class="settings-form" style="display: none;">
                        ${this.getActionSettingsForm(node, nodeIndex)}
                    </div>
                </div>
            `;
        }

        if (node.type === 'entry' && node.subtype === 'root') {
            return `
                <div class="entry-point-options">
                    <div class="entry-point-option">
                        <div class="entry-point-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                            <div class="entry-point-icon-group">
                                <input class="form-check-input" type="checkbox" 
                                    ${node.options.chat.enabled ? 'checked' : ''} 
                                    onchange="event.stopPropagation(); window.pathBuilder.toggleEntryOption('chat', this.checked)">
                                <i class="fas fa-comments"></i>
                            </div>
                            <span>Chat Entry</span>
                            <i class="fas fa-chevron-down entry-point-toggle"></i>
                        </div>
                        <div class="entry-point-fields">
                            <div class="form-group">
                                <label>Welcome Message</label>
                                <textarea class="form-control welcome-message" rows="2" 
                                    placeholder="Enter welcome message..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'welcomeMessage', this.value)">${node.options.chat.welcomeMessage || ''}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="entry-point-option">
                        <div class="entry-point-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                            <div class="entry-point-icon-group">
                                <input class="form-check-input" type="checkbox" 
                                    ${node.options.twilioInbound.enabled ? 'checked' : ''} 
                                    onchange="event.stopPropagation(); window.pathBuilder.toggleEntryOption('twilioInbound', this.checked)">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>Twilio Inbound</span>
                            <i class="fas fa-chevron-down entry-point-toggle"></i>
                        </div>
                        <div class="entry-point-fields">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control phone-number" 
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'phoneNumber', this.value)"
                                    value="${node.options.twilioInbound.phoneNumber || ''}">
                            </div>
                        </div>
                    </div>
                    <div class="entry-point-option">
                        <div class="entry-point-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                            <div class="entry-point-icon-group">
                                <input class="form-check-input" type="checkbox" 
                                    ${node.options.twilioOutbound.enabled ? 'checked' : ''} 
                                    onchange="event.stopPropagation(); window.pathBuilder.toggleEntryOption('twilioOutbound', this.checked)">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>Twilio Outbound</span>
                            <i class="fas fa-chevron-down entry-point-toggle"></i>
                        </div>
                        <div class="entry-point-fields">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control phone-number" 
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'phoneNumber', this.value)"
                                    value="${node.options.twilioOutbound.phoneNumber || ''}">
                            </div>
                            <div class="form-group mt-2">
                                <label>Initial Message</label>
                                <textarea class="form-control initial-message" rows="2" 
                                    placeholder="Enter initial message..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'initialMessage', this.value)">${node.options.twilioOutbound.initialMessage || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        if (!node || !node.type || !node.subtype || !this.nodeTypes[node.type] || !this.nodeTypes[node.type][node.subtype]) {
            console.error('Invalid node type or subtype:', node);
            return '';
        }

        // Get the collapsed state info first
        const collapsedInfo = this.getCollapsedNodeInfo(node);
        
        // Add the collapsed info to the node header
        const nodeElement = document.querySelector(`.conversation-node[data-node-index="${nodeIndex}"]`);
        if (nodeElement) {
            const header = nodeElement.querySelector('.node-header');
            if (header) {
                const infoSpan = header.querySelector('.node-info');
                if (infoSpan) {
                    infoSpan.textContent = collapsedInfo;
                } else {
                    const newInfoSpan = document.createElement('span');
                    newInfoSpan.className = 'node-info';
                    newInfoSpan.textContent = collapsedInfo;
                    header.insertBefore(newInfoSpan, header.querySelector('.node-toggle'));
                }
            }
        }

        switch (node.type) {
            case 'entry':
                return this.renderEntryNodeContent(node);
            case 'data':
                return this.renderDataNodeContent(node);
            case 'decision':
                return this.renderDecisionNodeContent(node);
            case 'action':
                return this.renderActionNodeContent(node);
            default:
                return '';
        }
    }

    getCollapsedNodeInfo(node) {
        if (node.type === 'action') {
            switch (node.subtype) {
                case 'say':
                    return node.content?.say_text ? 
                        `Text: "${node.content.say_text.substring(0, 30)}${node.content.say_text.length > 30 ? '...' : ''}"${node.content?.voice ? `<br>Voice: ${node.content.voice}` : ''}` : 
                        'No text set';
                case 'play':
                    if (node.content?.audioFileId) {
                        const audioFile = this.audioFiles?.find(a => a.id === node.content.audioFileId);
                        return audioFile ? 
                            `Audio: ${audioFile.name}${node.content?.loopCount ? `<br>Loop: ${node.content.loopCount}` : ''}` : 
                            'Audio file not found';
                    } else if (node.content?.audioUrl) {
                        return `URL: ${node.content.audioUrl}${node.content?.loopCount ? `<br>Loop: ${node.content.loopCount}` : ''}`;
                    }
                    return 'No audio selected';
                case 'conversationPath':
                    const targetPath = this.paths.find(p => p.id === parseInt(node.content?.targetPathId));
                    return targetPath ? `Path: ${targetPath.name}` : 'No target path selected';
                // ... other action cases stay the same ...
            }
        }
        // ... rest of the method stays the same ...
    }

    updateNodeContent(nodeIndex, field, value) {
        if (!this.selectedPath) return;

        const node = this.selectedPath.nodes[nodeIndex];
        if (!node) return;

        switch (node.type) {
            case 'action':
                if (!node.content) node.content = {};
                switch (node.subtype) {
                    case 'say':
                        if (field === 'say_text') {
                            node.content.say_text = value;
                        } else if (field === 'voice') {
                            node.content.voice = value;
                        }
                        break;
                    case 'play':
                        if (field === 'audioFileId') {
                            node.content.audioFileId = value ? parseInt(value) : null;
                            // Clear audioUrl if audioFile is selected
                            if (value) node.content.audioUrl = '';
                        } else if (field === 'audioUrl') {
                            node.content.audioUrl = value;
                            // Clear audioFileId if URL is entered
                            if (value) node.content.audioFileId = null;
                        } else if (field === 'loopCount') {
                            node.content.loopCount = parseInt(value);
                        }
                        break;
                    case 'conversationPath':
                        if (field === 'targetPathId') {
                            node.content.targetPathId = value ? parseInt(value) : null;
                        }
                        break;
                    case 'assistant':
                        if (field === 'assistantId') {
                            node.content.assistantId = value ? parseInt(value) : null;
                        } else if (field === 'prompt') {
                            node.content.prompt = value;
                        }
                        break;
                    case 'pipeline':
                        if (field === 'pipelineId') {
                            node.content.pipelineId = value ? parseInt(value) : null;
                        }
                        break;
                    case 'phoneTree':
                        if (field === 'phoneTreeId') {
                            node.content.phoneTreeId = value ? parseInt(value) : null;
                        }
                        break;
                    case 'survey':
                        if (field === 'surveyId') {
                            node.content.surveyId = value ? parseInt(value) : null;
                        } else if (field === 'surveyType') {
                            node.content.surveyType = value;
                        } else if (field === 'question') {
                            node.content.question = value;
                        } else if (field === 'timeout') {
                            node.content.timeout = parseInt(value);
                        } else if (field === 'phoneTreeId') {
                            node.content.phoneTreeId = value ? parseInt(value) : null;
                        }
                        break;
                    case 'script':
                        if (field === 'scriptId') {
                            node.content.scriptId = value ? parseInt(value) : null;
                        }
                        break;
                    case 'hangup':
                        if (field === 'reason') {
                            node.content.reason = value;
                        }
                        break;
                    case 'voiceMail':
                        if (field === 'phoneNumber') {
                            node.content.phoneNumber = value;
                        }
                        break;
                    case 'transfer':
                        if (field === 'phoneNumber') {
                            node.content.phoneNumber = value;
                        }
                        break;
                    case 'route':
                        if (field === 'targetNode') {
                            node.content.targetNode = value ? parseInt(value) : null;
                        }
                        break;
                    case 'websocket':
                        if (field === 'wsUrl') {
                            node.content.wsUrl = value;
                        }
                        break;
                }
                break;
            case 'data':
                switch (node.subtype) {
                    case 'contextAssistant':
                        if (field === 'assistantId') {
                            node.assistantId = value ? parseInt(value) : null;
                        } else if (field === 'contextKey') {
                            node.contextKey = value;
                        } else if (field === 'prompt') {
                            node.prompt = value;
                        }
                        break;
                    case 'custom':
                        if (field === 'script_id') {
                            node.script_id = value ? parseInt(value) : null;
                        } else if (field === 'contextKey') {
                            node.contextKey = value;
                        }
                        break;
                    default:
                        if (field === 'contextKey') {
                            node.contextKey = value;
                        } else if (field === 'prompt') {
                            node.prompt = value;
                        }
                        break;
                }
                break;
            case 'decision':
                if (!node.content) node.content = {};
                switch (node.subtype) {
                    case 'message':
                        if (field === 'message') {
                            node.content.message = value;
                        }
                        break;
                    case 'audio':
                        if (field === 'audioFileId') {
                            node.content.audioFileId = value ? parseInt(value) : null;
                        }
                        break;
                    case 'assistant':
                        if (field === 'assistantId') {
                            node.content.assistantId = value ? parseInt(value) : null;
                        } else if (field === 'prompt') {
                            node.content.prompt = value;
                        }
                        break;
                    case 'script':
                        if (field === 'returnType') {
                            node.content.returnType = value;
                        } else if (field === 'script') {
                            node.content.script = value;
                        } else if (field === 'description') {
                            node.content.description = value;
                        }
                        break;
                }
                break;
            case 'entry':
                if (!node.options) node.options = {};
                if (field.includes('.')) {
                    const [option, subfield] = field.split('.');
                    if (!node.options[option]) node.options[option] = {};
                    node.options[option][subfield] = value;
                } else {
                    node[field] = value;
                }
                break;
        }

        // Update both the header info and settings summary
        const nodeElement = document.querySelector(`.conversation-node[data-node-index="${nodeIndex}"]`);
        if (nodeElement) {
            const nodeInfo = nodeElement.querySelector('.node-info');
            if (nodeInfo) {
                nodeInfo.innerHTML = this.getCollapsedNodeInfo(node);
            }
            
            const settingsSummary = nodeElement.querySelector('.settings-summary');
            if (settingsSummary) {
                settingsSummary.innerHTML = this.getCollapsedNodeInfo(node);
            }
        }
    }

    getActionSettingsForm(node, nodeIndex) {
        switch (node.subtype) {
            case 'say':
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Text to Say</div>
                            <div class="settings-field">
                                <textarea class="form-control" rows="3" 
                                    placeholder="Enter text to say..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'say_text', this.value)">${node.content?.say_text || ''}</textarea>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Voice</div>
                            <div class="settings-field">
                                <select class="form-control" 
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'voice', this.value)">
                                    <option value="alloy" ${node.content?.voice === 'alloy' ? 'selected' : ''}>Alloy</option>
                                    <option value="echo" ${node.content?.voice === 'echo' ? 'selected' : ''}>Echo</option>
                                    <option value="fable" ${node.content?.voice === 'fable' ? 'selected' : ''}>Fable</option>
                                    <option value="onyx" ${node.content?.voice === 'onyx' ? 'selected' : ''}>Onyx</option>
                                    <option value="nova" ${node.content?.voice === 'nova' ? 'selected' : ''}>Nova</option>
                                    <option value="shimmer" ${node.content?.voice === 'shimmer' ? 'selected' : ''}>Shimmer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
            case 'play':
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Audio File</div>
                            <div class="settings-field">
                                <select class="form-control" 
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'audioFileId', this.value)">
                                    <option value="">Select Audio File</option>
                                    ${this.audioFiles ? this.audioFiles.map(audio => `
                                        <option value="${audio.id}" ${node.content?.audioFileId === audio.id ? 'selected' : ''}>
                                            ${audio.name}
                                        </option>
                                    `).join('') : ''}
                                </select>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Or External URL</div>
                            <div class="settings-field">
                                <input type="text" class="form-control" 
                                    placeholder="Enter audio URL..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'audioUrl', this.value)"
                                    value="${node.content?.audioUrl || ''}">
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Loop Count</div>
                            <div class="settings-field">
                                <input type="number" class="form-control" 
                                    placeholder="Number of times to play (0 for infinite)"
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'loopCount', this.value)"
                                    value="${node.content?.loopCount || 1}">
                            </div>
                        </div>
                    </div>
                `;
            case 'conversationPath':
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Target Path</div>
                            <div class="settings-field">
                                <select class="form-control" 
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'targetPathId', this.value)">
                                    <option value="">Select target path</option>
                                    ${this.paths.map(path => `
                                        <option value="${path.id}" ${node.content?.targetPathId === path.id ? 'selected' : ''}>
                                            ${path.name}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                `;
            // ... other cases stay the same ...
        }
    }

    renderEntryNodeContent(node) {
        if (!node || !node.subtype) return '';

        if (node.subtype === 'root') {
            return `
                <div class="entry-point-options">
                    <div class="entry-point-option">
                        <div class="entry-point-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                            <div class="entry-point-icon-group">
                            <input class="form-check-input" type="checkbox" 
                                ${node.options.chat.enabled ? 'checked' : ''} 
                                onchange="event.stopPropagation(); window.pathBuilder.toggleEntryOption('chat', this.checked)">
                                <i class="fas fa-comments"></i>
                            </div>
                            <span>Chat Entry</span>
                            <i class="fas fa-chevron-down entry-point-toggle"></i>
                        </div>
                        <div class="entry-point-fields">
                            <div class="form-group">
                                <label>Welcome Message</label>
                                <textarea class="form-control welcome-message" rows="2" 
                                    placeholder="Enter welcome message..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'welcomeMessage', this.value)">${node.options.chat.welcomeMessage || ''}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="entry-point-option">
                        <div class="entry-point-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                            <div class="entry-point-icon-group">
                            <input class="form-check-input" type="checkbox" 
                                ${node.options.twilioInbound.enabled ? 'checked' : ''} 
                                onchange="event.stopPropagation(); window.pathBuilder.toggleEntryOption('twilioInbound', this.checked)">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>Twilio Inbound</span>
                            <i class="fas fa-chevron-down entry-point-toggle"></i>
                        </div>
                        <div class="entry-point-fields">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control phone-number" 
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'phoneNumber', this.value)"
                                    value="${node.options.twilioInbound.phoneNumber || ''}">
                            </div>
                        </div>
                    </div>
                    <div class="entry-point-option">
                        <div class="entry-point-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                            <div class="entry-point-icon-group">
                            <input class="form-check-input" type="checkbox" 
                                ${node.options.twilioOutbound.enabled ? 'checked' : ''} 
                                onchange="event.stopPropagation(); window.pathBuilder.toggleEntryOption('twilioOutbound', this.checked)">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>Twilio Outbound</span>
                            <i class="fas fa-chevron-down entry-point-toggle"></i>
                        </div>
                        <div class="entry-point-fields">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control phone-number" 
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'phoneNumber', this.value)"
                                    value="${node.options.twilioOutbound.phoneNumber || ''}">
                            </div>
                            <div class="form-group mt-2">
                                <label>Initial Message</label>
                                <textarea class="form-control initial-message" rows="2" 
                                    placeholder="Enter initial message..."
                                    onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'initialMessage', this.value)">${node.options.twilioOutbound.initialMessage || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return '';
    }

    toggleNodeForm(element) {
        element.classList.toggle('expanded');
    }

    toggleEntryOption(optionType, enabled) {
        if (!this.selectedPath) return;

        const entryNode = this.selectedPath.nodes.find(node => 
            node.type === 'entry' && node.subtype === 'root'
        );

        if (entryNode && entryNode.options[optionType]) {
            entryNode.options[optionType].enabled = enabled;
            // Don't re-render the canvas to prevent collapsing
        }
    }

    updateEntryOption(optionType, field, value) {
        if (!this.selectedPath) return;

        const entryNode = this.selectedPath.nodes.find(node => 
            node.type === 'entry' && node.subtype === 'root'
        );

        if (entryNode && entryNode.options[optionType]) {
            entryNode.options[optionType][field] = value;
        }
    }

    renderDataNodeContent(node) {
        if (!node || !node.subtype) return '';

        const nodeInfo = this.nodeTypes.data[node.subtype];
        if (!nodeInfo) return '';

        switch (node.subtype) {
            case 'contextAssistant':
                return `
                    <div class="form-group">
                        <label>Assistant</label>
                        <select class="form-select assistant-select" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'assistantId', this.value)">
                            <option value="">Select Assistant</option>
                            ${this.assistants.map(assistant => `
                                <option value="${assistant.id}" ${node.assistantId === assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Context Key</label>
                        <input type="text" class="form-control context-key" 
                            value="${node.contextKey || ''}" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'contextKey', this.value)"
                            placeholder="Enter context key...">
                    </div>
                    <div class="form-group mt-2">
                        <label>Prompt</label>
                        <textarea class="form-control prompt" rows="3" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'prompt', this.value)"
                            placeholder="Enter prompt for context management...">${node.prompt || ''}</textarea>
                    </div>
                `;
            case 'custom':
                return `
                    <div class="form-group">
                        <label>Script</label>
                        <select class="form-select script-select" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'script_id', this.value)">
                            <option value="">Select a script...</option>
                            ${this.scripts ? this.scripts.map(script => `
                                <option value="${script.id}" 
                                    data-params='${JSON.stringify(script.parameters)}'
                                    data-return-type="${script.return_type}"
                                    ${node.script_id === script.id ? 'selected' : ''}>
                                    ${script.name}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Context Key</label>
                        <input type="text" class="form-control context-key" 
                            value="${node.contextKey || ''}" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'contextKey', this.value)"
                            placeholder="Enter context key...">
                    </div>
                `;
            default:
                return `
                    <div class="form-group">
                        <label>Script</label>
                        <select class="form-select" ${nodeInfo.script !== 'custom' ? 'disabled' : ''}>
                            <option value="${nodeInfo.script}" selected>${nodeInfo.name}</option>
                            ${nodeInfo.script === 'custom' ? `
                                <option value="custom">Custom Script</option>
                            ` : ''}
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Prompt</label>
                        <textarea class="form-control prompt" rows="3" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'prompt', this.value)"
                            placeholder="Enter prompt for data processing...">${node.prompt || ''}</textarea>
                    </div>
                    <div class="form-group mt-2">
                        <label>Context Key</label>
                        <input type="text" class="form-control context-key" 
                            value="${node.contextKey || ''}" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'contextKey', this.value)"
                            placeholder="Enter context key...">
                    </div>
                `;
        }
    }

    renderDecisionNodeContent(node, nodeIndex) {
        if (!node || !node.type || !node.subtype) return '';

        // Common decision node layout
        const getNodeSettings = (node) => {
            switch (node.subtype) {
                case 'conditional':
            return `
                        <div class="settings-grid">
                            <div class="settings-row">
                                <div class="settings-label">Return Type</div>
                                <div class="settings-field">
                                    <select class="form-control return-type" 
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'returnType', this.value)">
                                        <option value="boolean" ${(!node.returnType || node.returnType === 'boolean') ? 'selected' : ''}>True/False (1 true, 2 false)</option>
                                        <option value="index" ${node.returnType === 'index' ? 'selected' : ''}>Index (Returns Action Index 1-9,0)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="settings-row">
                                <div class="settings-label">Condition Script</div>
                                <div class="settings-field">
                                    <select class="form-control script-select" 
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'script', this.value)">
                        <option value="">Select a script</option>
                                        ${this.scripts ? this.scripts.map(script => `
                            <option value="${script.id}" ${node.script === script.id ? 'selected' : ''}>
                                ${script.name}
                            </option>
                                        `).join('') : ''}
                    </select>
                </div>
                    </div>
                            <div class="settings-row">
                                <div class="settings-label">Description</div>
                                <div class="settings-field">
                                    <textarea class="form-control" rows="2" 
                                        placeholder="Enter description for this condition..."
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'description', this.value)">${node.description || ''}</textarea>
                                </div>
                    </div>
                </div>
            `;
                case 'assistant':
                    return `
                        <div class="settings-grid">
                            <div class="settings-row">
                                <div class="settings-label">Assistant</div>
                                <div class="settings-field">
                                    <select class="form-control assistant-select" 
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                                        <option value="">Select an assistant</option>
                                        ${this.assistants ? this.assistants.map(assistant => `
                                            <option value="${assistant.id}" ${node.assistantId === assistant.id ? 'selected' : ''}>
                                                ${assistant.name}
                                            </option>
                                        `).join('') : ''}
                                    </select>
                                </div>
                            </div>
                            <div class="settings-row">
                                <div class="settings-label">Prompt</div>
                                <div class="settings-field">
                                    <textarea class="form-control" rows="3" 
                                        placeholder="Enter prompt for the assistant..."
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'prompt', this.value)">${node.prompt || ''}</textarea>
                                </div>
                            </div>
                        </div>
                    `;
                case 'user':
                    return `
                        <div class="settings-grid">
                            <div class="settings-row">
                                <div class="settings-label">Message</div>
                                <div class="settings-field">
                                    <textarea class="form-control" rows="3" 
                                        placeholder="Enter message to say before user selection..."
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'message', this.value)">${node.message || ''}</textarea>
                                </div>
                            </div>
                            <div class="settings-row">
                                <div class="settings-label">Audio File</div>
                                <div class="settings-field">
                                    <select class="form-control audio-file-select" 
                                        onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'audioFileId', this.value)">
                                        <option value="">Select Audio File</option>
                                        ${this.audioFiles ? this.audioFiles.map(audio => `
                                            <option value="${audio.id}" ${node.audioFileId === audio.id ? 'selected' : ''}>
                                                ${audio.name}
                                            </option>
                                        `).join('') : ''}
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                default:
                    return '';
            }
        };

        const getSettingsSummary = (node) => {
            switch (node.subtype) {
                case 'conditional':
                    const script = this.scripts?.find(s => s.id === node.script);
                    return script ? `Script: ${script.name}` : 'No script selected';
                case 'assistant':
                    const assistant = this.assistants?.find(a => a.id === node.assistantId);
                    return assistant ? `Assistant: ${assistant.name}` : 'No assistant selected';
                case 'user':
                    let summary = [];
                    if (node.message) {
                        summary.push(`Message: "${node.message.substring(0, 30)}${node.message.length > 30 ? '...' : ''}"`);
                    }
                    if (node.audioFileId) {
                        const audioFile = this.audioFiles?.find(a => a.id === node.audioFileId);
                        if (audioFile) {
                            summary.push(`Audio: ${audioFile.name}`);
                        }
                    }
                    return summary.length > 0 ? summary.join('<br>') : 'No message or audio set';
                default:
                    return '';
            }
        };

        return `
            <div class="decision-container">
                <div class="settings-section">
                    <div class="settings-header">
                        <h6>Settings</h6>
                        <button class="btn btn-sm btn-outline-primary edit-settings" onclick="window.pathBuilder.toggleNodeSettings(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div class="settings-summary">
                        ${getSettingsSummary(node)}
                    </div>
                    <div class="settings-form" style="display: none;">
                        ${getNodeSettings(node)}
                    </div>
                </div>
                <div class="decision-actions-container">
                <div class="decision-header">
                        <h6>Actions</h6>
                    </div>
                    <div class="decision-actions" data-node-index="${nodeIndex}">
                        ${(node.actions || []).map((action, actionIndex) => `
                            <div class="decision-action-item" data-action-index="${actionIndex}">
                                <div class="action-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                                    <div class="action-number">${actionIndex + 1}</div>
                                    <i class="fas ${this.nodeTypes.action[action?.subtype]?.icon || 'fa-circle'}"></i>
                                    <span>${this.nodeTypes.action[action?.subtype]?.name || 'Action'}</span>
                                    <span class="action-info">${action ? this.getCollapsedNodeInfo(action) : ''}</span>
                                    <i class="fas fa-chevron-down action-toggle"></i>
                                    <div class="action-actions">
                                        <button class="btn btn-sm btn-outline-danger delete-action" data-action-index="${actionIndex}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary move-up" data-action-index="${actionIndex}">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary move-down" data-action-index="${actionIndex}">
                                            <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
                                </div>
                                <div class="action-content">
                                    ${action ? this.renderActionNodeContent(action) : ''}
                                </div>
                            </div>
                        `).join('')}
                        <div class="decision-action-drop-zone">
                            <i class="fas fa-plus-circle"></i>
                            <span>Drop action here to add to decision</span>
                            <small class="text-muted">Drag an action from the palette</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    toggleNodeSettings(button) {
        const settingsSection = button.closest('.settings-section');
        const summary = settingsSection.querySelector('.settings-summary');
        const form = settingsSection.querySelector('.settings-form');
        const nodeElement = button.closest('.conversation-node');
        const nodeIndex = parseInt(nodeElement.dataset.nodeIndex);
        const node = this.selectedPath.nodes[nodeIndex];
        
        if (form.style.display === 'none') {
            summary.style.display = 'none';
            form.style.display = 'block';
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-success');
        } else {
            // When closing the settings, re-render the entire canvas to ensure consistency
            form.style.display = 'none';
            button.innerHTML = '<i class="fas fa-edit"></i>';
            button.classList.remove('btn-outline-success');
            button.classList.add('btn-outline-primary');
            this.renderPathCanvas();
        }
    }

    renderActionNodeContent(node) {
        if (!node || !node.subtype || !this.nodeTypes.action[node.subtype]) {
            console.warn('Invalid action node:', node);
            return '';
        }

        switch (node.subtype) {
            case 'say':
                return `
                    <div class="form-group">
                        <label>Text to Say</label>
                        <textarea class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'say_text', this.value)">${node.content?.say_text || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Voice</label>
                        <select class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'voice', this.value)">
                            <option value="alloy" ${node.content?.voice === 'alloy' ? 'selected' : ''}>Alloy</option>
                            <option value="echo" ${node.content?.voice === 'echo' ? 'selected' : ''}>Echo</option>
                            <option value="fable" ${node.content?.voice === 'fable' ? 'selected' : ''}>Fable</option>
                            <option value="onyx" ${node.content?.voice === 'onyx' ? 'selected' : ''}>Onyx</option>
                            <option value="nova" ${node.content?.voice === 'nova' ? 'selected' : ''}>Nova</option>
                            <option value="shimmer" ${node.content?.voice === 'shimmer' ? 'selected' : ''}>Shimmer</option>
                        </select>
                    </div>
                `;
            case 'assistant':
                const assistantId = node.content?.assistantId || node.content?.assistant_id;
                return `
                    <div class="form-group">
                        <label>Assistant</label>
                        <select class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'assistantId', this.value)">
                            <option value="">Select an assistant</option>
                            ${this.assistants.map(assistant => `
                                <option value="${assistant.id}" ${assistantId === assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Prompt</label>
                        <textarea class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'prompt', this.value)">${node.content?.prompt || ''}</textarea>
                    </div>
                `;
            case 'play':
                return `
                    <div class="form-group mb-3">
                        <label>Audio File</label>
                        <select class="form-control audio-file-select" 
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'audioFileId', this.value)">
                            <option value="">Select Audio File</option>
                            ${this.audioFiles ? this.audioFiles.map(audio => `
                                <option value="${audio.id}" ${node.audioFileId === audio.id ? 'selected' : ''}>
                                    ${audio.name}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Or External URL</label>
                        <input type="text" class="form-control audio-url" 
                            placeholder="Enter audio URL..."
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'audioUrl', this.value)"
                            value="${node.audioUrl || ''}">
                    </div>
                    <div class="form-group mt-2">
                        <label>Loop Count</label>
                        <input type="number" class="form-control loop-count" 
                            placeholder="Number of times to play (0 for infinite)"
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'loopCount', this.value)"
                            value="${node.loopCount || 1}">
                    </div>
                `;
            case 'phoneTree':
                return `
                    <div class="form-group">
                        <label>Phone Tree</label>
                        <select class="form-select phone-tree-select" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'phoneTreeId', this.value)">
                            <option value="">Select Phone Tree</option>
                            ${this.phoneTrees ? this.phoneTrees.map(tree => `
                                <option value="${tree.id}" ${node.phoneTreeId === tree.id ? 'selected' : ''}>
                                    ${tree.name}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                `;
            case 'pipeline':
                return `
                    <div class="form-group">
                        <label>Pipeline</label>
                        <select class="form-select pipeline-select" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'pipelineId', this.value)">
                            <option value="">Select Pipeline</option>
                            ${this.pipelines ? this.pipelines.map(pipeline => `
                                <option value="${pipeline.id}" ${node.pipelineId === pipeline.id ? 'selected' : ''}>
                                    ${pipeline.name}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                `;
            case 'route':
                return `
                    <div class="form-group">
                        <label>Route To</label>
                        <select class="form-control route-target" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'targetNode', this.value)">
                            <option value="">Select Target Node</option>
                            ${this.selectedPath.nodes.map((targetNode, index) => `
                                <option value="${index}" ${node.targetNode === index ? 'selected' : ''}>
                                    ${this.nodeTypes[targetNode.type][targetNode.subtype]?.name || 'Node'} ${index + 1}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'conversationPath':
                return `
                    <div class="form-group">
                        <label>Target Path</label>
                        <select class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'target_path_id', this.value)">
                            <option value="">Select target path</option>
                            ${this.paths.map(path => `
                                <option value="${path.id}" ${node.content?.target_path_id === path.id ? 'selected' : ''}>
                                    ${path.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'script':
                const script = this.scripts.find(s => s.id === node.content?.script_id);
                return script ? `Script: ${script.name}` : 'Select a script';
            case 'websocket':
                return `WebSocket: ${node.content?.wsUrl}`;
            case 'hangup':
                return `
                    <div class="form-group">
                        <label>Reason</label>
                        <input type="text" class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'reason', this.value)" value="${node.content?.reason || ''}" placeholder="Enter reason for hanging up">
                    </div>
                `;
            case 'voiceMail':
                return `
                    <div class="form-group">
                        <label>Greeting Message</label>
                        <textarea class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'greeting', this.value)" placeholder="Enter greeting message...">${node.content?.greeting || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Max Duration (seconds)</label>
                        <input type="number" class="form-control" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'max_duration', this.value)" value="${node.content?.max_duration || 60}" min="1" max="300">
                    </div>
                    <div class="form-group">
                        <label>Beep Sound</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'beep', this.checked)" ${node.content?.beep ? 'checked' : ''}>
                            <label class="form-check-label">Play beep sound before recording</label>
                        </div>
                    </div>
                `;
            case 'transfer':
                return node.transferTo ? `Transfer to: ${node.transferTo}` : 'Enter phone number';
            case 'survey':
                return `
                    <div class="form-group mb-3">
                        <label>Survey Type</label>
                        <select class="form-control survey-type" 
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'surveyType', this.value)">
                            <option value="">Select Survey Type</option>
                            <option value="phone_tree_survey" ${node.surveyType === 'phone_tree_survey' ? 'selected' : ''}>Phone Tree Survey</option>
                            <option value="ask_and_wait" ${node.surveyType === 'ask_and_wait' ? 'selected' : ''}>Ask and Wait</option>
                            <option value="survey_assistant" ${node.surveyType === 'survey_assistant' ? 'selected' : ''}>Survey Assistant</option>
                        </select>
                    </div>
                    ${node.surveyType === 'phone_tree_survey' ? `
                        <div class="form-group mb-3">
                            <label>Phone Tree</label>
                            <select class="form-control phone-tree-select" 
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'phoneTreeId', this.value)">
                                <option value="">Select Phone Tree</option>
                                ${this.phoneTrees ? this.phoneTrees.map(tree => `
                                    <option value="${tree.id}" ${node.phoneTreeId === tree.id ? 'selected' : ''}>
                                        ${tree.name}
                                    </option>
                                `).join('') : ''}
                            </select>
                        </div>
                    ` : ''}
                    ${node.surveyType === 'ask_and_wait' ? `
                        <div class="form-group mb-3">
                            <label>Question</label>
                            <textarea class="form-control survey-question" rows="3" 
                                placeholder="Enter survey question..."
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'question', this.value)">${node.question || ''}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>Timeout (seconds)</label>
                            <input type="number" class="form-control timeout" 
                                placeholder="Enter timeout in seconds..."
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'timeout', this.value)"
                                value="${node.timeout || 30}">
                        </div>
                    ` : ''}
                    ${node.surveyType === 'survey_assistant' ? `
                        <div class="form-group mb-3">
                            <label>Assistant</label>
                            <select class="form-control assistant-select" 
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'assistantId', this.value)">
                                <option value="">Select Assistant</option>
                                ${this.assistants ? this.assistants.map(assistant => `
                                    <option value="${assistant.id}" ${node.assistantId === assistant.id ? 'selected' : ''}>
                                        ${assistant.name}
                                    </option>
                                `).join('') : ''}
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Survey Instructions</label>
                            <textarea class="form-control survey-instructions" rows="3" 
                                placeholder="Enter instructions for the assistant..."
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'instructions', this.value)">${node.instructions || ''}</textarea>
                        </div>
                    ` : ''}
                    <div class="form-group mb-3">
                        <label>Survey</label>
                        <select class="form-control survey-select" 
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'surveyId', this.value)">
                            <option value="">Select Survey</option>
                            ${this.surveys ? this.surveys.map(survey => `
                                <option value="${survey.id}" ${node.surveyId === survey.id ? 'selected' : ''}>
                                    ${survey.title}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                `;
            case 'monitorCall':
                return `
                    <div class="form-group mb-3">
                        <label>Assistant</label>
                        <select class="form-control assistant-select" 
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'assistantId', this.value)">
                            <option value="">Select Assistant</option>
                            ${this.assistants ? this.assistants.map(assistant => `
                                <option value="${assistant.id}" ${node.assistantId === assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Monitoring Options</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" 
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'transcribe', this.checked)"
                                ${node.transcribe ? 'checked' : ''}>
                            <label class="form-check-label">Transcribe Call</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" 
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'record', this.checked)"
                                ${node.record ? 'checked' : ''}>
                            <label class="form-check-label">Record Call</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" 
                                onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'intervene', this.checked)"
                                ${node.intervene ? 'checked' : ''}>
                            <label class="form-check-label">Allow Assistant Intervention</label>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>Monitoring Instructions</label>
                        <textarea class="form-control monitoring-instructions" rows="3" 
                            placeholder="Enter instructions for the assistant..."
                            onchange="window.pathBuilder.updateNodeContent(${this.selectedPath.nodes.indexOf(node)}, 'instructions', this.value)">${node.instructions || ''}</textarea>
                    </div>
                `;
            case 'sms':
                return `
                    <div class="form-group">
                        <label>SMS Type</label>
                        <select class="form-select sms-type" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'smsType', this.value)">
                            <option value="user" ${node.smsType === 'user' ? 'selected' : ''}>Send to User</option>
                            <option value="phone" ${node.smsType === 'phone' ? 'selected' : ''}>Send to Number</option>
                            <option value="customer" ${node.smsType === 'customer' ? 'selected' : ''}>Send to Customer</option>
                        </select>
                    </div>
                    ${node.smsType === 'phone' ? `
                        <div class="form-group mt-2">
                            <label>Phone Number</label>
                            <input type="text" class="form-control phone-number" 
                                placeholder="Enter phone number..."
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'phoneNumber', this.value)"
                                value="${node.phoneNumber || ''}">
                        </div>
                    ` : ''}
                    ${node.smsType === 'customer' ? `
                        <div class="form-group mt-2">
                            <label>Customer Field</label>
                            <input type="text" class="form-control customer-field" 
                                placeholder="Enter customer field name (e.g., phone_number)..."
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'customerField', this.value)"
                                value="${node.customerField || ''}">
                        </div>
                    ` : ''}
                    <div class="form-group mt-2">
                        <label>Message</label>
                        <textarea class="form-control sms-message" rows="3" 
                            placeholder="Enter SMS message..."
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'message', this.value)">${node.message || ''}</textarea>
                    </div>
                `;
            case 'wait':
                return `
                    <div class="form-group">
                        <label>Wait Type</label>
                        <select class="form-select wait-type" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'waitType', this.value)">
                            <option value="fixed" ${node.waitType === 'fixed' ? 'selected' : ''}>Fixed Time</option>
                            <option value="condition" ${node.waitType === 'condition' ? 'selected' : ''}>Until Condition</option>
                        </select>
                    </div>
                    ${node.waitType === 'fixed' ? `
                        <div class="form-group mt-2">
                            <label>Duration (seconds)</label>
                            <input type="number" class="form-control duration" 
                                placeholder="Enter duration in seconds..."
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'duration', this.value)"
                                value="${node.duration || 0}">
                        </div>
                    ` : ''}
                    ${node.waitType === 'condition' ? `
                        <div class="form-group mt-2">
                            <label>Condition Script</label>
                            <select class="form-select condition-script" 
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'scriptId', this.value)">
                                <option value="">Select Script</option>
                                ${this.scripts ? this.scripts.map(script => `
                                    <option value="${script.id}" ${node.scriptId === script.id ? 'selected' : ''}>
                                        ${script.name}
                                    </option>
                                `).join('') : ''}
                            </select>
                        </div>
                        <div class="form-group mt-2">
                            <label>Timeout (seconds)</label>
                            <input type="number" class="form-control timeout" 
                                placeholder="Enter timeout in seconds..."
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'timeout', this.value)"
                                value="${node.timeout || 30}">
                        </div>
                    ` : ''}
                `;
            case 'conditionalDecision':
                return `
                    <div class="form-group">
                        <label>Condition Type</label>
                        <select class="form-select condition-type" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'conditionType', this.value)">
                            <option value="boolean" ${node.conditionType === 'boolean' ? 'selected' : ''}>Boolean (True/False)</option>
                            <option value="numeric" ${node.conditionType === 'numeric' ? 'selected' : ''}>Numeric (Multiple Outcomes)</option>
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Condition Script</label>
                        <select class="form-select condition-script" 
                            onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'scriptId', this.value)">
                            <option value="">Select Script</option>
                            ${this.scripts ? this.scripts.map(script => `
                                <option value="${script.id}" ${node.scriptId === script.id ? 'selected' : ''}>
                                    ${script.name}
                                </option>
                            `).join('') : ''}
                        </select>
                    </div>
                    ${node.conditionType === 'boolean' ? `
                        <div class="form-group mt-2">
                            <label>True Action</label>
                            <select class="form-select true-action" 
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'trueAction', this.value)">
                                <option value="">Select Action</option>
                                ${this.selectedPath.nodes.map((targetNode, index) => `
                                    <option value="${index}" ${node.trueAction === index ? 'selected' : ''}>
                                        ${this.nodeTypes[targetNode.type][targetNode.subtype]?.name || 'Node'} ${index + 1}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="form-group mt-2">
                            <label>False Action</label>
                            <select class="form-select false-action" 
                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'falseAction', this.value)">
                                <option value="">Select Action</option>
                                ${this.selectedPath.nodes.map((targetNode, index) => `
                                    <option value="${index}" ${node.falseAction === index ? 'selected' : ''}>
                                        ${this.nodeTypes[targetNode.type][targetNode.subtype]?.name || 'Node'} ${index + 1}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    ` : ''}
                    ${node.conditionType === 'numeric' ? `
                        <div class="form-group mt-2">
                            <label>Outcome Actions</label>
                            <div class="outcome-actions">
                                ${(node.outcomeActions || []).map((action, index) => `
                                    <div class="outcome-action-item">
                                        <div class="form-group">
                                            <label>Value ${index}</label>
                                            <select class="form-select outcome-action" 
                                                onchange="window.pathBuilder.updateNodeContent(this.closest('.conversation-node').dataset.nodeIndex, 'outcomeActions', this.value, ${index})">
                                                <option value="">Select Action</option>
                                                ${this.selectedPath.nodes.map((targetNode, idx) => `
                                                    <option value="${idx}" ${action === idx ? 'selected' : ''}>
                                                        ${this.nodeTypes[targetNode.type][targetNode.subtype]?.name || 'Node'} ${idx + 1}
                                                    </option>
                                                `).join('')}
                                            </select>
                                        </div>
                                    </div>
                                `).join('')}
                                <button class="btn btn-sm btn-outline-primary mt-2" 
                                    onclick="window.pathBuilder.addOutcomeAction(this.closest('.conversation-node').dataset.nodeIndex)">
                                    Add Outcome
                                </button>
                            </div>
                        </div>
                    ` : ''}
                `;
            default:
                return '';
        }
    }

    setupCanvasEventListeners() {
        const canvas = this.container.querySelector('.canvas-container');

        // Node containers for drag and drop
        canvas.querySelectorAll('.nodes-container').forEach(container => {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                container.classList.add('drag-over');
            });

            container.addEventListener('dragleave', (e) => {
                container.classList.remove('drag-over');
            });
        });

        // Main drop zone handling
        const mainDropZone = canvas.querySelector('.main-drop-zone');
        if (mainDropZone) {
            mainDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                mainDropZone.classList.add('drag-over');
            });

            mainDropZone.addEventListener('dragleave', () => {
                mainDropZone.classList.remove('drag-over');
            });

            mainDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                mainDropZone.classList.remove('drag-over');
                
                const nodeType = e.dataTransfer.getData('nodeType');
                const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
                
                if (nodeType && nodeSubtype) {
                    const newNode = {
                        type: nodeType,
                        subtype: nodeSubtype,
                        content: '',
                        id: this.generateUniqueId(),
                        actions: []
                    };
                    
                    if (!this.selectedPath.nodes) {
                        this.selectedPath.nodes = [];
                    }
                    
                    this.selectedPath.nodes.push(newNode);
                    this.renderPathCanvas();
                }
            });
        }

        // Decision actions as drop targets
        canvas.querySelectorAll('.decision-actions').forEach(container => {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                container.classList.add('drag-over');
            });

            container.addEventListener('dragleave', (e) => {
                container.classList.remove('drag-over');
            });

            container.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevents bubbling to main container
                container.classList.remove('drag-over');
                const nodeType = e.dataTransfer.getData('nodeType');
                const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
                if (nodeType === 'action') {
                    const nodeIndex = parseInt(container.dataset.nodeIndex);
                    const targetNode = this.selectedPath.nodes[nodeIndex];
                    if (!targetNode.actions) {
                        targetNode.actions = [];
                    }
                    const newAction = {
                        type: nodeType,
                        subtype: nodeSubtype,
                        ...this.getDefaultNodeConfig(nodeType, nodeSubtype)
                    };
                    targetNode.actions.push(newAction);
                    this.renderPathCanvas();
                }
            });
        });

        // Delete node buttons
        canvas.querySelectorAll('.delete-node').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const nodeIndex = parseInt(button.dataset.nodeIndex);
                if (confirm('Are you sure you want to delete this node?')) {
                    this.selectedPath.nodes.splice(nodeIndex, 1);
                    this.renderPathCanvas();
                }
            });
        });

        // Move up/down buttons for nodes
        canvas.querySelectorAll('.move-up').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const nodeIndex = parseInt(button.dataset.nodeIndex);
                if (nodeIndex > 0) {
                    const temp = this.selectedPath.nodes[nodeIndex];
                    this.selectedPath.nodes[nodeIndex] = this.selectedPath.nodes[nodeIndex - 1];
                    this.selectedPath.nodes[nodeIndex - 1] = temp;
                    this.renderPathCanvas();
                }
            });
        });

        canvas.querySelectorAll('.move-down').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const nodeIndex = parseInt(button.dataset.nodeIndex);
                if (nodeIndex < this.selectedPath.nodes.length - 1) {
                    const temp = this.selectedPath.nodes[nodeIndex];
                    this.selectedPath.nodes[nodeIndex] = this.selectedPath.nodes[nodeIndex + 1];
                    this.selectedPath.nodes[nodeIndex + 1] = temp;
                    this.renderPathCanvas();
                }
            });
        });

        // Delete action buttons
        canvas.querySelectorAll('.delete-action').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const actionIndex = parseInt(button.dataset.actionIndex);
                const nodeIndex = parseInt(button.closest('.decision-actions').dataset.nodeIndex);
                const targetNode = this.selectedPath.nodes[nodeIndex];
                if (confirm('Are you sure you want to delete this action?')) {
                    targetNode.actions.splice(actionIndex, 1);
                    this.renderPathCanvas();
                }
            });
        });

        // Move action up/down buttons
        canvas.querySelectorAll('.decision-action-item .move-up').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const actionIndex = parseInt(button.closest('.decision-action-item').dataset.actionIndex);
                const nodeIndex = parseInt(button.closest('.decision-actions').dataset.nodeIndex);
                const targetNode = this.selectedPath.nodes[nodeIndex];
                if (actionIndex > 0) {
                    const temp = targetNode.actions[actionIndex];
                    targetNode.actions[actionIndex] = targetNode.actions[actionIndex - 1];
                    targetNode.actions[actionIndex - 1] = temp;
                    this.renderPathCanvas();
                }
            });
        });

        canvas.querySelectorAll('.decision-action-item .move-down').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const actionIndex = parseInt(button.closest('.decision-action-item').dataset.actionIndex);
                const nodeIndex = parseInt(button.closest('.decision-actions').dataset.nodeIndex);
                const targetNode = this.selectedPath.nodes[nodeIndex];
                if (actionIndex < targetNode.actions.length - 1) {
                    const temp = targetNode.actions[actionIndex];
                    targetNode.actions[actionIndex] = targetNode.actions[actionIndex + 1];
                    targetNode.actions[actionIndex + 1] = temp;
                    this.renderPathCanvas();
                }
            });
        });
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.decision-action-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    moveNode(nodeIndex, direction) {
        if (!this.selectedPath) return;
        
        const newIndex = nodeIndex + direction;
        if (newIndex < 0 || newIndex >= this.selectedPath.nodes.length) return;

        const node = this.selectedPath.nodes[nodeIndex];
        this.selectedPath.nodes.splice(nodeIndex, 1);
        this.selectedPath.nodes.splice(newIndex, 0, node);
        
        this.renderPathCanvas();
    }

    selectPath(pathId) {
        this.selectedPath = this.paths.find(p => p.id === pathId);
        this.renderPathList();
        this.renderPathCanvas();
        this.updateEntryPointCheckboxes();
    }

    addNode(nodeType, nodeSubtype) {
        if (!this.selectedPath) return;

        let node = {
            type: nodeType,
            subtype: nodeSubtype,
            ...this.getDefaultNodeConfig(nodeType, nodeSubtype)
        };
        // Ensure content is always an object for action nodes
        if (nodeType === 'action') {
            node.content = {};
        }
        this.selectedPath.nodes.push(node);
        this.renderPathCanvas();
    }

    getDefaultNodeConfig(nodeType, nodeSubtype) {
        if (!this.nodeTypes[nodeType] || !this.nodeTypes[nodeType][nodeSubtype]) {
            console.error('Invalid node type or subtype:', { nodeType, nodeSubtype });
            return {};
        }

        switch (nodeType) {
            case 'entry':
                return {
                    welcomeMessage: '',
                    phoneNumber: '',
                    initialMessage: ''
                };
            case 'data':
                return {
                    prompt: '',
                    contextKey: '',
                    script: this.nodeTypes.data[nodeSubtype].script
                };
            case 'decision':
                return {
                    assistantId: null,
                    prompt: '',
                    actions: []
                };
            case 'action':
                switch (nodeSubtype) {
                    case 'say':
                        return {
                            text: '',
                            voice: 'default'
                        };
                    case 'play':
                        return {
                            audioUrl: '',
                            loopCount: 1
                        };
                    case 'route':
                        return {
                            targetNode: null
                        };
                    default:
                        return {
                            transferTo: '',
                            wsUrl: '',
                            phoneTreeId: null,
                            pipelineId: null
                        };
                }
            default:
                return {};
        }
    }

    /**
     * Generate a unique ID for nodes
     * @returns {string} A unique ID
     */
    generateUniqueId() {
        return 'node_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    addOutcomeAction(nodeIndex) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || node.subtype !== 'conditionalDecision') return;

        if (!node.outcomeActions) {
            node.outcomeActions = [];
        }
        node.outcomeActions.push(null);
        this.renderPathCanvas();
    }
}

// Add styles
const style = document.createElement('style');
style.textContent = `
    .conversation-path-builder {
        height: 100%;
        padding: 1rem;
    }
    .path-palette {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        height: 100%;
    }
    .path-palette h4 {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    .path-list, .node-palette {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .path-item, .node-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
    }
    .path-item:hover, .node-item:hover {
        background: #e9ecef;
    }
    .path-item.active {
        background: #007bff;
        color: white;
        border-color: #0056b3;
    }
    .node-item {
        cursor: move;
        height: 100%;
        margin-bottom: 1rem;
        min-height: 200px;
        display: flex;
        flex-direction: column;
    }
    .path-canvas {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        height: 100%;
        min-height: 600px;
        position: relative;
    }
    .canvas-container {
        position: relative;
        width: 100%;
        height: 100%;
        padding: 1rem;
    }
    .nodes-container {
        width: 100%;
    }
    .nodes-container .row {
        margin: 0 -0.5rem;
        display: flex;
        flex-wrap: wrap;
    }
    .nodes-container .col-md-6 {
        padding: 0 0.5rem;
        width: 50%;
        min-width: 300px;
    }
    .nodes-container.drag-over {
        background: #e9ecef;
        border-color: #007bff;
    }
    .node-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.5rem;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
        min-height: 24px;
    }
    .node-header i {
        font-size: 1rem;
    }
    .node-header span {
        font-weight: 500;
        font-size: 0.9rem;
    }
    .node-info {
        margin-left: 0.5rem;
        color: #6c757d;
        font-size: 0.85rem;
        font-weight: normal;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .node-toggle {
        margin-left: auto;
        color: #6c757d;
        transition: transform 0.2s ease;
        font-size: 0.8rem;
    }
    .conversation-node.expanded .node-toggle {
        transform: rotate(180deg);
    }
    .dragging {
        opacity: 0.5;
    }
    .node-item.entry {
        border-left: 4px solid #007bff;
    }
    .node-item.data {
        border-left: 4px solid #ffc107;
    }
    .node-item.decision {
        border-left: 4px solid #6610f2;
    }
    .node-item.action {
        border-left: 4px solid #20c997;
    }
`;
document.head.appendChild(style); 