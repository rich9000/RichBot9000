// ConversationPathBuilderV2.js - Visual builder for conversation paths

class ConversationPathBuilderV2 {
    constructor(containerId) {
            console.log('Initializing ConversationPathBuilderV2 with container:', containerId);
            
            // Initialize container
        this.container = document.getElementById(containerId);
            if (!this.container) {
                console.error('Container not found:', containerId);
                return;
            }
            console.log('Container found:', this.container);

            // Initialize NodeFactory
            if (typeof window.NodeFactory === 'undefined') {
                console.error('NodeFactory not found on window object. Ensure NodeFactory.js is loaded before ConversationPathBuilderV2.js');
                return;
            }
            this.nodeFactory = window.NodeFactory;
            console.log('NodeFactory initialized');

            // Initialize state
        this.paths = [];
            this.selectedPath = { nodes: [] };
        this.assistants = [];
        this.tools = [];
        this.scripts = [];
        this.audioFiles = [];
        this.phoneTrees = [];
        this.pipelines = [];
        this.surveys = [];
        this.isEditMode = true;
        this.nodeUiState = {}; // Track expanded/collapsed state for each node
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
                        description: 'Checks for active outages'
                    },
                    assistantTool: {
                        icon: 'fa-database',
                        name: 'Assistant Tool Data',
                        color: '#17a2b8',
                        description: 'Execute an assistant tool and store results'
                    },
                    customerLookup: {
                        icon: 'fa-user',
                        name: 'Customer Lookup',
                        color: '#17a2b8',
                        description: 'Looks up customer information'
                    },
                    custom: {
                        icon: 'fa-code',
                        name: 'Custom Script',
                        color: '#6c757d',
                        description: 'Run a custom script'
                    },
                    contextAssistant: {
                        icon: 'fa-robot',
                        name: 'Context Assistant',
                        color: '#6610f2',
                        description: 'Manage conversation context with an assistant'
                    },
                    file: {
                        icon: 'fa-file',
                        name: 'File',
                        color: '#17a2b8',
                        description: 'Select or browse a file or folder'
                    }
                },
                decision: {
                    user: {
                        icon: 'fa-user',
                        name: 'User Decision',
                        color: '#6610f2',
                        description: 'Decision based on user input'
                    },
                    assistantTool: {
                        icon: 'fa-code-branch',
                        name: 'Assistant Tool Decision',
                        color: '#6610f2',
                        description: 'Decision based on assistant tool results'
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
                    assistantTool: {
                        icon: 'fa-bolt',
                        name: 'Assistant Tool',
                        color: '#17a2b8',
                        description: 'Execute an assistant tool'
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
                    monitorCall: {
                        icon: 'fa-headphones',
                        name: 'Monitor Call',
                        color: '#20c997',
                        description: 'Monitor an active call'
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
                        name: 'Route to Node',
                        color: '#20c997',
                        description: 'Route to another node'
                    },
                    conversationPath: {
                        icon: 'fa-project-diagram',
                        name: 'Route to Path',
                        color: '#20c997',
                        description: 'Route to another path'
                    },
                    script: {
                        icon: 'fa-code',
                        name: 'Script',
                        color: '#20c997',
                        description: 'Execute a script'
                    },
                    websocket: {
                        icon: 'fa-plug',
                        name: 'WebSocket',
                        color: '#20c997',
                        description: 'WebSocket connection'
                    },
                    sms: {
                        icon: 'fa-sms',
                        name: 'SMS',
                        color: '#20c997',
                        description: 'Send SMS message'
                    },
                    email: {
                        icon: 'fa-envelope',
                        name: 'Email',
                        color: '#20c997',
                        description: 'Send email'
                    },
                    wait: {
                        icon: 'fa-clock',
                        name: 'Wait',
                        color: '#20c997',
                        description: 'Wait for condition or time'
                    }
                }
            };

            // Initialize UI
            this.initializeUI();
            
            // Load required data
            this.initialize().catch(error => {
                console.error('Failed to initialize ConversationPathBuilderV2:', error);
                toastr.error('Failed to initialize builder');
            });

            this.handleDrop = this.handleDrop.bind(this); // Bind drop handler
        }

        async initialize() {
            console.log('Starting initialization...');
            try {
                // Load all required data in parallel
                await Promise.all([
                    this.loadAssistants(),
                    this.loadTools(),
                    this.loadScripts(),
                    this.loadAudioFiles(),
                    this.loadPhoneTrees(),
                    this.loadPipelines(),
                    this.loadSurveys(),
                    this.loadPaths()
                ]);
                
                console.log('All data loaded successfully');
                this.initializeNodeMenu();
                this.setupDragAndDrop();
                this.renderNodes(); // <-- Added to ensure nodes are rendered with loaded tools
            } catch (error) {
                console.error('Error during initialization:', error);
                throw error;
            }
        }

        initializeUI() {
            // Initialize debug area toggle
            const debugToggle = this.container.querySelector('.debug-area + button');
            if (debugToggle) {
                debugToggle.addEventListener('click', () => {
                    this.toggleDebugArea(debugToggle);
                });
            }

            // Initialize path edit form
            const pathEdit = this.container.querySelector('.path-edit');
            const editToggle = this.container.querySelector('.path-edit-toggle');
            if (pathEdit && editToggle) {
                editToggle.addEventListener('click', () => {
                    this.togglePathEdit(editToggle);
                });
            }
    }

    setupEventListeners() {
            // Path edit toggle
            const pathEditToggle = this.container.querySelector('.path-edit-toggle');
            if (pathEditToggle) {
                pathEditToggle.addEventListener('click', () => {
                    const pathEdit = this.container.querySelector('.path-edit');
                    const icon = pathEditToggle.querySelector('i');
                    
                    if (pathEdit.style.display === 'none') {
                        pathEdit.style.display = 'block';
                        icon.classList.remove('fa-edit');
                        icon.classList.add('fa-check');
                        pathEditToggle.classList.remove('btn-outline-primary');
                        pathEditToggle.classList.add('btn-outline-success');
                    } else {
                        pathEdit.style.display = 'none';
                        icon.classList.remove('fa-check');
                        icon.classList.add('fa-edit');
                        pathEditToggle.classList.remove('btn-outline-success');
                        pathEditToggle.classList.add('btn-outline-primary');
                    }
                });
            }

        // Drop zone for new nodes
            const dropZone = this.container.querySelector('.drop-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                dropZone.classList.add('drag-over');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('drag-over');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('drag-over');
                
                const nodeType = e.dataTransfer.getData('nodeType');
                const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
                
                if (nodeType && nodeSubtype) {
                    this.addNode(nodeType, nodeSubtype);
                }
            });
        }

            // Path name and description inputs
            const pathName = this.container.querySelector('#pathName');
            if (pathName) {
                pathName.addEventListener('change', (e) => {
                    this.updatePathName(e.target.value);
                });
            }

            const pathDescription = this.container.querySelector('#pathDescription');
            if (pathDescription) {
                pathDescription.addEventListener('change', (e) => {
                    this.updatePathDescription(e.target.value);
                });
            }

            // Save path button
            const savePathBtn = this.container.querySelector('.save-path');
            if (savePathBtn) {
                savePathBtn.addEventListener('click', () => {
                    this.savePath();
                });
            }

            // Back to list button
            const backToListBtn = this.container.querySelector('.btn-outline-secondary');
            if (backToListBtn) {
                backToListBtn.addEventListener('click', () => {
                    this.showPathList();
            });
        }

        // Node palette items
            this.container.querySelectorAll('.palette-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.nodeType);
                e.dataTransfer.setData('nodeSubtype', item.dataset.nodeSubtype);
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });
        });

            // Debug area toggle
            const debugToggle = this.container.querySelector('.debug-area + button');
            if (debugToggle) {
                debugToggle.addEventListener('click', () => {
                    this.toggleDebugArea(debugToggle);
                });
            }
    }

    async loadAssistants() {
        try {
            console.log('[Builder] Loading assistants...');
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
            console.log('[Builder] Loaded assistants:', this.assistants);
            
            // Update any existing nodes that need assistants
            if (this.selectedPath && this.selectedPath.nodes) {
                this.selectedPath.nodes.forEach((node, index) => {
                    if (node.type === 'action' && (node.subtype === 'monitorCall' || node.subtype === 'assistant')) {
                        this.updateNodeHeaderInfo(index);
                    }
                });
            }
        } catch (error) {
            console.error('Error loading assistants:', error);
            toastr.error('Failed to load assistants');
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
            this.tools = Array.isArray(result) ? result : (result.tools || []);
            console.log('[loadTools] tools:', this.tools); // Log the loaded tools
        } catch (error) {
            console.error('Error loading tools:', error);
            toastr.error('Failed to load tools');
        }
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
            if (window.appState && appState.data) {
                appState.data.scripts = this.scripts;
            }
        } catch (error) {
            console.error('Error loading scripts:', error);
            toastr.error('Failed to load scripts');
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
            toastr.error('Failed to load audio files');
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
            toastr.error('Failed to load phone trees');
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
            if (window.appState && appState.data) {
                appState.data.pipelines = this.pipelines;
            }
        } catch (error) {
            console.error('Error loading pipelines:', error);
            toastr.error('Failed to load pipelines');
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
            toastr.error('Failed to load surveys');
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
            if (window.appState && appState.data) {
                appState.data.conversation_paths = this.paths;
            }
            this.renderPathsList();
        } catch (error) {
            console.error('Error loading paths:', error);
                toastr.error('Failed to load conversation paths');
        }
    }

    renderPathsList() {
        const tbody = document.getElementById('pathsList');
        if (!tbody) return;

        tbody.innerHTML = this.paths.map(path => `
            <tr>
                <td>${path.id}</td>
                <td>${path.name}</td>
                <td>${path.description || ''}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="window.pathBuilder.showPathTextBreakdown(${path.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success me-1" onclick="window.pathBuilder.showPath(${path.id}, true)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="window.pathBuilder.deletePath(${path.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    showPath(pathId, isEdit = true) {
            const pathListView = document.getElementById('pathListView');
            const builderView = document.getElementById('pathBuilderView');
            
            if (pathListView) pathListView.style.display = 'none';
            if (builderView) builderView.style.display = 'block';
        
        this.isEditMode = isEdit;
        
        if (pathId) {
            this.loadPath(pathId);
        } else {
            this.createNewPath();
        }
    }

        showPathList() {
            const pathListView = document.getElementById('pathListView');
            const builderView = document.getElementById('pathBuilderView');
            
            if (pathListView) pathListView.style.display = 'block';
            if (builderView) builderView.style.display = 'none';
            
            this.selectedPath = { nodes: [] };
            this.renderPathsList();
    }

    async loadPath(pathId) {
        try {
            const response = await fetch(`/api/conversation-paths/${pathId}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load path');
            }

            const path = await response.json();
            this.selectedPath = path;
            // Always instantiate nodes using createNode (NodeFactory)
            if (Array.isArray(path.nodes)) {
                this.selectedPath.nodes = path.nodes.map(nodeData => this.createNode(nodeData)).filter(node => node !== null);
            } else {
                this.selectedPath.nodes = [];
            }
            // Update form fields
            document.getElementById('pathName').value = path.name;
            document.getElementById('pathDescription').value = path.description || '';
            document.querySelector('.path-title').textContent = 'Conversation Path: ' + path.name;
            this.renderNodes();
            this.updateDebugInfo();
        } catch (error) {
            console.error('Error loading path:', error);
                toastr.error('Failed to load path');
        }
    }

    async savePath() {
        try {
            const pathData = {
                name: document.getElementById('pathName').value,
                description: document.getElementById('pathDescription').value,
                    nodes: this.selectedPath.nodes.map(node => node.toJSON())
            };

            // Log the pathData before sending
            console.log('[Builder] savePath: pathData', JSON.stringify(pathData));

            const url = this.selectedPath?.id ? 
                `/api/conversation-paths/${this.selectedPath.id}` : 
            '/api/conversation-paths';

            const method = this.selectedPath?.id ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(pathData)
            });

            if (!response.ok) {
                throw new Error('Failed to save path');
            }

            const result = await response.json();
                this.selectedPath = result;
            
            // Show toast or alert on success
            if (typeof toastr !== 'undefined') {
                toastr.success('Path saved successfully');
            } else {
                alert('Path saved successfully');
            }

            this.showPathList();
            await this.loadPaths(); // Refresh the list
        } catch (error) {
            console.error('Error saving path:', error);
            toastr.error('Failed to save path');
        }
    }

    async deletePath(pathId) {
        if (!confirm('Are you sure you want to delete this path?')) {
            return;
        }

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

            toastr.success('Path deleted successfully');
            await this.loadPaths(); // Refresh the list
        } catch (error) {
            console.error('Error deleting path:', error);
            toastr.error('Failed to delete path');
        }
    }

    updatePathSummary() {
        const name = document.getElementById('pathName').value || 'New Path';
        const description = document.getElementById('pathDescription').value;

        // Update title
        document.querySelector('.path-title').textContent = 'Conversation Path: ' + name;

        // Update node info
        const nodeInfo = document.querySelector('.conversation-node.root .node-info');
        if (nodeInfo) {
            nodeInfo.innerHTML = description || 'No description set';
        }

        // Update settings summary
        const summary = document.querySelector('.settings-summary');
        if (summary) {
            let summaryHtml = `<div class="mb-2"><strong>Name:</strong> ${name}</div>`;
            if (description) {
                summaryHtml += `<div><strong>Description:</strong> ${description}</div>`;
            }
            summary.innerHTML = summaryHtml;
        }
    }

    updatePathName(value) {
        document.querySelector('.path-title').textContent = 'Conversation Path: ' + (value || 'New Path');
        this.updatePathSummary();
    }

    updatePathDescription(value) {
        // Just store it, will be saved with the path
    }

    reset() {
            this.selectedPath = { nodes: [] };
        document.getElementById('pathName').value = '';
        document.getElementById('pathDescription').value = '';
        document.querySelector('.path-title').textContent = 'Conversation Path: New Path';
        
        // Reset path edit
        const pathEdit = document.querySelector('.path-edit');
        const editToggle = document.querySelector('.path-edit-toggle');
        if (pathEdit) pathEdit.style.display = 'none';
        if (editToggle) {
            editToggle.innerHTML = '<i class="fas fa-edit"></i>';
            editToggle.classList.remove('btn-outline-success');
            editToggle.classList.add('btn-outline-primary');
        }
        
        // Reset nodes
        this.clearNodes();
        
        // Enable node grids
        document.querySelectorAll('.node-grid').forEach(grid => {
            grid.style.pointerEvents = 'auto';
            grid.style.opacity = '1';
        });
    }

    loadNodes(nodes) {
        this.selectedPath.nodes = (nodes || []).map(normalizeNode);
        this.renderNodes();
    }

    clearNodes() {
            this.selectedPath.nodes = [];
        const container = document.querySelector('.flow-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    addNode(nodeType, nodeSubtype, position = null) {
        console.log('Adding node:', { type: nodeType, subtype: nodeSubtype, position });
        if (!this.selectedPath) {
            console.error('No path selected');
            return;
        }
        try {
            const nodeData = {
                type: nodeType,
                subtype: nodeSubtype,
                content: {},
                options: this.getDefaultNodeOptions(nodeType, nodeSubtype)
            };
            const node = this.createNode(nodeData);
            if (!node) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to create node');
                } else {
                    alert('Failed to create node');
                }
                return;
            }
            let newIndex;
            if (position !== null && position >= 0 && position <= this.selectedPath.nodes.length) {
                this.selectedPath.nodes.splice(position, 0, node);
                newIndex = position;
            } else {
                this.selectedPath.nodes.push(node);
                newIndex = this.selectedPath.nodes.length - 1;
            }
            // Track the last added node index for auto-edit
            this.lastAddedNodeIndex = newIndex;
        this.renderNodes();
            this.updateDebugInfo();
            console.log('Node added successfully');
        } catch (error) {
            console.error('Error adding node:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to add node');
            } else {
                alert('Failed to add node');
            }
        }
    }

    getDefaultNodeOptions(nodeType, nodeSubtype) {
        const defaults = {
            entry: {
                root: {
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
            },
            action: {
                say: { message: '' },
                assistantTool: {
                    assistantId: null,
                    prompt: ''
                },
                play: { audioFileId: null },
                assistant: { assistantId: null, prompt: '' },
                pipeline: { pipelineId: null },
                phoneTree: { phoneTreeId: null },
                survey: { surveyId: null },
                hangup: { message: '' },
                monitorCall: { message: '' },
                voiceMail: { prompt: '', maxDuration: 60 },
                transfer: { phoneNumber: '', message: '' },
                route: { targetNodeId: null },
                conversationPath: { targetPathId: null },
                script: { scriptId: null },
                websocket: { url: '', event: '' },
                sms: { message: '', phoneNumber: '' },
                email: { subject: '', body: '', to: '' }
            },
            decision: {
                user: { message: '', options: [] },
                assistantTool: {
                    assistantId: null,
                    prompt: ''
                },
                assistant: { assistantId: null, prompt: '' },
                conditional: { scriptId: null, conditions: [] }
            },
            data: {
                outageCheck: { serviceId: null },
                assistantTool: {
                    searchField: 'phone'
                },
                custom: { scriptId: null },
                contextAssistant: { assistantId: null, contextKey: '' },
                file: {
                    icon: 'fa-file',
                    name: 'File',
                    color: '#17a2b8',
                    description: 'Select or browse a file or folder'
                }
            }
        };

        return defaults[nodeType]?.[nodeSubtype] || {};
    }

    renderNodes() {
        // --- Collect expanded/collapsed state before rendering ---
        this.nodeUiState = this.nodeUiState || {};
        const container = this.container.querySelector('.flow-container');
        if (container) {
            // For each node, store info and actions area open state
            container.querySelectorAll('.conversation-node').forEach(card => {
                const nodeId = card.getAttribute('id') || card.dataset.nodeIndex;
                if (!nodeId) return;
                const body = card.querySelector('.node-body');
                const isBodyOpen = body && body.style.display === 'block';
                // For decision nodes, also check actions area
                let isActionsOpen = false;
                if (card.classList.contains('decision')) {
                    const actionsArea = card.querySelector('.decision-actions-area');
                    isActionsOpen = actionsArea && actionsArea.style.display === 'block';
                }
                this.nodeUiState[nodeId] = {
                    infoOpen: isBodyOpen,
                    actionsOpen: isActionsOpen
                };
            });
        }
        console.log('Rendering nodes...');
        if (!container) {
            console.error('Flow container not found');
            return;
        }

        try {
            const context = {
                pipelines: this.pipelines,
                phoneTrees: this.phoneTrees,
                surveys: this.surveys,
                paths: this.paths,
                audioFiles: this.audioFiles,
                assistants: this.assistants,
                scripts: this.scripts,
                tools: this.tools,
                nodes: this.selectedPath.nodes,
                nodeTypes: this.nodeTypes
            };
            console.log('[renderNodes] context.nodes', context.nodes);
            let html = '';
            this.selectedPath.nodes.forEach((node, index) => {
                html += node.getNodeCardHtml(index, context);
                if (index < this.selectedPath.nodes.length - 1) {
                    html += '<div class="node-connector"><div class="arrow"></div></div>';
                }
            });
            // Add a visually distinct drop zone at the bottom
        html += `
                <div class="custom-drop-zone" style="margin: 2rem auto 0 auto; padding: 2rem; border: 2px dashed #007bff; border-radius: 12px; background: #f8fbff; text-align: center; min-height: 80px; max-width: 600px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer;">
                    <i class="fas fa-plus-circle" style="font-size: 2rem; color: #007bff;"></i>
                    <span style="font-size: 1.1rem; color: #007bff; font-weight: 500;">Drop node here to add to flow</span>
                    <small class="text-muted">Drag a node from the palette or click to focus</small>
            </div>
            `;
        container.innerHTML = html;
            this.setupNodeEventListeners();
            // If a node was just added, open its edit form and hide info area
            if (typeof this.lastAddedNodeIndex === 'number') {
                const card = container.querySelector(`.conversation-node[data-node-index="${this.lastAddedNodeIndex}"]`);
                if (card) {
                    const info = card.querySelector('.node-detailed-info');
                    const form = card.querySelector('.node-edit-form');
                    if (info && form) {
                        info.style.display = 'none';
                        form.style.display = 'block';
                        // For RootEntryNode, hide the edit button and only show Save
                        if (card.classList.contains('entry') && card.classList.contains('root')) {
                            const editBtn = info.querySelector('.edit-toggle');
                            if (editBtn) editBtn.style.display = 'none';
                        }
                    }
                }
                this.lastAddedNodeIndex = undefined;
            }
            // --- Restore expanded/collapsed state after rendering ---
            const newContainer = this.container.querySelector('.flow-container');
            if (newContainer) {
                newContainer.querySelectorAll('.conversation-node').forEach(card => {
                    const nodeId = card.getAttribute('id') || card.dataset.nodeIndex;
                    if (!nodeId) return;
                    const state = this.nodeUiState[nodeId];
                    if (state) {
                        // Restore info area
                        const body = card.querySelector('.node-body');
                        if (body) body.style.display = state.infoOpen ? 'block' : 'none';
                        // Restore actions area for decision nodes
                        if (card.classList.contains('decision')) {
                            const actionsArea = card.querySelector('.decision-actions-area');
                            if (actionsArea) actionsArea.style.display = state.actionsOpen ? 'block' : 'none';
                        }
                    }
                });
            }
            console.log('Nodes rendered successfully');
        } catch (error) {
            console.error('Error rendering nodes:', error);
            toastr.error('Failed to render nodes');
        }

        // After rendering nodes and restoring state
        // Attach event delegation for ActionNodeList row controls
        container.querySelectorAll('.decision-actions-list').forEach(list => {
            list.addEventListener('click', (e) => {
                const row = e.target.closest('.action-node-list-row');
                if (!row) return;
                const actionIdx = parseInt(row.dataset.nodeIndex);
                // Find parent node index
                const parent = row.closest('.conversation-node[data-node-index]');
                if (!parent) return;
                const parentIdx = parseInt(parent.dataset.nodeIndex);
                const parentNode = this.selectedPath.nodes[parentIdx];
                const actionNode = parentNode.actions.get ? parentNode.actions.get(actionIdx) : parentNode.actions[actionIdx];
                if (!actionNode) return;
                // Info toggle
                if (e.target.closest('.info-action-list-node')) {
                    // Toggle info/details (accordion style)
                    const nextRow = row.nextElementSibling;
                    if (nextRow && nextRow.classList.contains('action-node-list-info-row')) {
                        nextRow.remove();
                    } else {
                        list.querySelectorAll('.action-node-list-info-row, .action-node-list-edit-form-row').forEach(f => f.remove());
                        const infoRow = document.createElement('div');
                        infoRow.innerHTML = actionNode.actionNodeListInfoTemplate(actionIdx, {
                            pipelines: this.pipelines,
                            phoneTrees: this.phoneTrees,
                            surveys: this.surveys,
                            paths: this.paths,
                            audioFiles: this.audioFiles,
                            assistants: this.assistants,
                            scripts: this.scripts,
                            tools: this.tools,
                            nodeTypes: this.nodeTypes
                        });
                        row.after(infoRow);
                        // Attach direct handler to the edit button in the info row
                        const editBtn = infoRow.querySelector('.edit-action-list-node');
                        if (editBtn) {
                            editBtn.onclick = (ev) => {
                                console.log('Edit Action List Node button pressed', { actionIdx, actionNode });
                                ev.preventDefault();
                                infoRow.remove();
                                // Insert the edit form after the main row
                                const formRow = document.createElement('div');
                                formRow.innerHTML = actionNode.actionNodeListEditTemplate(actionIdx, {
                                    pipelines: this.pipelines,
                                    phoneTrees: this.phoneTrees,
                                    surveys: this.surveys,
                                    paths: this.paths,
                                    audioFiles: this.audioFiles,
                                    assistants: this.assistants,
                                    scripts: this.scripts,
                                    tools: this.tools,
                                    nodeTypes: this.nodeTypes
                                });
                                row.after(formRow);
                                // Save handler
                                formRow.querySelector('.save-action-node').onclick = (ev) => {
                                    ev.preventDefault();
                                    formRow.querySelectorAll('input, textarea, select').forEach(field => {
                                        const name = field.name || field.getAttribute('data-field') || field.getAttribute('name');
                                        if (name) {
                                            actionNode.updateContent(name, field.value);
                                        }
                                    });
                                    if (typeof toastr !== 'undefined') toastr.success('Action node updated');
                                    this.renderNodes();
                                };
                                // Cancel handler
                                formRow.querySelector('.cancel-action-node').onclick = (ev) => {
                                    ev.preventDefault();
                                    formRow.remove();
                                };
                            };
                        }
                    }
                } else if (e.target.closest('.edit-action-list-node')) {
                    // Do nothing here; handled by direct handler above
                } else if (e.target.closest('.delete-action-list-node')) {
                    showConfirmDeleteModal(() => {
                        console.log('[Builder] Before delete, actions:', parentNode.actions.nodes ? parentNode.actions.nodes.map(n => n.name || n.subtype || n.type) : parentNode.actions);
                        if (typeof parentNode.actions.remove === 'function') {
                            parentNode.actions.remove(actionIdx);
                        } else if (Array.isArray(parentNode.actions)) {
                            parentNode.actions.splice(actionIdx, 1);
                        }
                        console.log('[Builder] After delete, actions:', parentNode.actions.nodes ? parentNode.actions.nodes.map(n => n.name || n.subtype || n.type) : parentNode.actions);
                        if (typeof toastr !== 'undefined') toastr.success('Action node deleted');
                        this.renderNodes();
                    });
                } else if (e.target.closest('.move-up-node')) {
                    if (typeof parentNode.actions.move === 'function') {
                        parentNode.actions.move(actionIdx, actionIdx - 1);
                    } else if (Array.isArray(parentNode.actions)) {
                        const newIndex = actionIdx - 1;
                        if (newIndex >= 0) {
                            const temp = parentNode.actions[actionIdx];
                            parentNode.actions[actionIdx] = parentNode.actions[newIndex];
                            parentNode.actions[newIndex] = temp;
                        }
                    }
                    this.renderNodes();
                } else if (e.target.closest('.move-down-node')) {
                    if (typeof parentNode.actions.move === 'function') {
                        parentNode.actions.move(actionIdx, actionIdx + 1);
                    } else if (Array.isArray(parentNode.actions)) {
                        const newIndex = actionIdx + 1;
                        if (newIndex < parentNode.actions.length) {
                            const temp = parentNode.actions[actionIdx];
                            parentNode.actions[actionIdx] = parentNode.actions[newIndex];
                            parentNode.actions[newIndex] = temp;
                        }
                    }
                    this.renderNodes();
                }
            });
        });
    }

    setupNodeEventListeners() {
        // Up, down, delete buttons
        this.container.querySelectorAll('.move-up').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = parseInt(btn.dataset.nodeIndex);
                this.moveNode(idx, -1);
            });
        });
        this.container.querySelectorAll('.move-down').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = parseInt(btn.dataset.nodeIndex);
                this.moveNode(idx, 1);
            });
        });
        this.container.querySelectorAll('.delete-node').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = parseInt(btn.dataset.nodeIndex);
                showConfirmDeleteModal(() => this.deleteNode(idx, true));
            });
        });
        // Info button only expands/collapses the node body
        this.container.querySelectorAll('.info-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleNodeInfo(btn);
            });
        });
        // Edit button toggles to edit form
        this.container.querySelectorAll('.conversation-node .edit-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleNodeEdit(btn);
            });
        });
        // Save button in edit form toggles back to info area
        this.container.querySelectorAll('.node-edit-form .save-node').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.saveNodeEdit(btn);
            });
        });
        // On change for edit form fields
        this.container.querySelectorAll('.node-edit-form input, .node-edit-form textarea, .node-edit-form select').forEach(field => {
            field.addEventListener('change', (e) => {
                const card = field.closest('.conversation-node');
                const idx = parseInt(card.dataset.nodeIndex);
                // Try to get field name and value
                const name = field.name || field.getAttribute('data-field') || field.getAttribute('name');
                if (name) {
                    this.updateNodeContent(idx, name, field.value);
                    // Update header info live
                    this.updateNodeHeaderInfo(idx);
                }
            });
        });
        // Add dragend event for .decision-action-item
        this.container.querySelectorAll('.decision-action-item').forEach(item => {
            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
            });
        });
    }

    // Info button only expands/collapses the node body
    toggleNodeInfo(button) {
        const card = button.closest('.conversation-node');
        const body = card.querySelector('.node-body');
        if (body) {
            const isOpen = body.style.display === 'block';
            body.style.display = isOpen ? 'none' : 'block';
            // Always show info area and hide edit form when expanding
            if (!isOpen) {
                const info = card.querySelector('.node-detailed-info');
                const form = card.querySelector('.node-edit-form');
                if (info && form) {
                    info.style.display = 'block';
                    form.style.display = 'none';
                }
            }
        }
    }

    // Edit button toggles to edit form
    toggleNodeEdit(button) {
        const card = button.closest('.conversation-node');
        if (!card) return;
        const info = card.querySelector('.node-detailed-info');
        const form = card.querySelector('.node-edit-form');
        if (info && form) {
            info.style.display = 'none';
            form.style.display = 'block';
        }
    }

    // Save button in edit form toggles back to info area
    saveNodeEdit(button) {
        const card = button.closest('.conversation-node');
        const idx = parseInt(card.dataset.nodeIndex);
        const node = this.selectedPath.nodes[idx];
        // Validate node
        if (!node.validate()) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Please fill out all required fields.');
            } else {
                alert('Please fill out all required fields.');
            }
            return;
        }
        // Log node content before saving
        console.log('[Builder] saveNodeEdit: node content before save', JSON.stringify(node.content));
        
        // Ensure boolean values are properly set
        if (node.type === 'action' && node.subtype === 'monitorCall') {
            const form = card.querySelector('.node-edit-form');
            if (form) {
                const startInteractive = form.querySelector('input[name="startInteractive"]');
                const recordAudio = form.querySelector('input[name="recordAudio"]');
                const transcribeAudio = form.querySelector('input[name="transcribeAudio"]');
                
                if (startInteractive) node.content.startInteractive = startInteractive.checked;
                if (recordAudio) node.content.recordAudio = recordAudio.checked;
                if (transcribeAudio) node.content.transcribeAudio = transcribeAudio.checked;
            }
        }
        
        // Hide edit form, show details
        const info = card.querySelector('.node-detailed-info');
        const form = card.querySelector('.node-edit-form');
        // Build context for info rendering
        const context = {
            pipelines: this.pipelines,
            phoneTrees: this.phoneTrees,
            surveys: this.surveys,
            paths: this.paths,
            audioFiles: this.audioFiles,
            assistants: this.assistants,
            scripts: this.scripts,
            tools: this.tools,
            nodes: this.selectedPath.nodes,
            nodeTypes: this.nodeTypes
        };
        console.log('[updateNodeHeaderInfo] context.nodes', context.nodes);
        if (info && form) {
            form.style.display = 'none';
            info.style.display = 'block';
            // Update details area with latest info
            info.innerHTML = node.getDetailsHtml(idx, context);
            // Re-attach edit button event if not root entry
            if (!(card.classList.contains('entry') && card.classList.contains('root'))) {
                const editBtn = info.querySelector('.edit-toggle');
                if (editBtn) {
                    editBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.toggleNodeEdit(editBtn);
                    });
                }
            }
        }
        // Update header info
        this.updateNodeHeaderInfo(idx, context);
    }

    // Update the header info (brief info) for a node
    updateNodeHeaderInfo(idx, context = null) {
        const card = this.container.querySelector(`.conversation-node[data-node-index="${idx}"]`);
        if (card) {
            const node = this.selectedPath.nodes[idx];
            const brief = card.querySelector('.node-brief-info');
            if (brief) {
                if (!context) {
                    context = {
                        pipelines: this.pipelines,
                        phoneTrees: this.phoneTrees,
                        surveys: this.surveys,
                        paths: this.paths,
                        audioFiles: this.audioFiles,
                        assistants: this.assistants,
                        scripts: this.scripts,
                        tools: this.tools,
                        nodes: this.selectedPath.nodes,
                        nodeTypes: this.nodeTypes
                    };
                }
                console.log('[updateNodeHeaderInfo] context.nodes', context.nodes);
                brief.innerHTML = node.getNodeInfo(context);
            }
        }
    }

    updateNodeContent(nodeIndex, field, value, assistantName = null) {
        console.log('[Builder] updateNodeContent:', nodeIndex, field, value, assistantName);
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node) return;

        node.updateContent(field, value, assistantName);
        // Build context for info rendering
        const context = {
            pipelines: this.pipelines,
            phoneTrees: this.phoneTrees,
            surveys: this.surveys,
            paths: this.paths,
            audioFiles: this.audioFiles,
            assistants: this.assistants || [],
            scripts: this.scripts,
            tools: this.tools,
            nodes: this.selectedPath.nodes,
            nodeTypes: this.nodeTypes
        };
        console.log('[updateNodeContent] context.assistants:', context.assistants);
        // Update the node info in the UI
        const nodeElement = document.querySelector(`.conversation-node[data-node-index="${nodeIndex}"]`);
        if (nodeElement) {
            const nodeInfo = nodeElement.querySelector('.node-info');
            const settingsSummary = nodeElement.querySelector('.settings-summary');
            let infoArg1 = null, infoArg2 = null, infoArg3 = null;
            if (node.type === 'action') {
                switch (node.subtype) {
                    case 'route':
                        infoArg1 = context.nodes;
                        infoArg2 = context.nodeTypes;
                        break;
                    case 'conversationPath':
                        infoArg1 = context.paths;
                        break;
                    case 'pipeline':
                        infoArg1 = context.pipelines;
                        break;
                    case 'assistant':
                        infoArg1 = context.assistants;
                        break;
                    case 'play':
                        infoArg1 = context.audioFiles;
                        break;
                    case 'phoneTree':
                        infoArg1 = context.phoneTrees;
                        break;
                    case 'survey':
                        infoArg1 = context.surveys;
                        break;
                    case 'script':
                        infoArg1 = context.scripts;
                        break;
                    case 'websocket':
                        if (field === 'wsUrl') {
                            node.content.wsUrl = value;
                        }
                        break;
                    case 'monitorCall':
                        if (field === 'assistantId') {
                            node.content.assistantId = value ? parseInt(value) : null;
                            if (assistantName && assistantName !== 'Select Assistant') {
                                node.content.assistantName = assistantName;
                            }
                        } else if (field === 'startInteractive') {
                            node.content.startInteractive = value === true || value === 'true';
                        } else if (field === 'recordAudio') {
                            node.content.recordAudio = value === true || value === 'true';
                        } else if (field === 'transcribeAudio') {
                            node.content.transcribeAudio = value === true || value === 'true';
                        }
                        infoArg1 = context.assistants;
                        break;
                    // Add more as needed
                    default:
                        infoArg1 = undefined;
                }
                if (nodeInfo) nodeInfo.innerHTML = node.getNodeInfo(infoArg1, infoArg2 || context.scripts);
                if (settingsSummary) settingsSummary.innerHTML = node.getNodeInfo(infoArg1, infoArg2 || context.scripts);
            } else if (node.type === 'decision') {
                switch (node.subtype) {
                    case 'assistant':
                    case 'user':
                    case 'conditional':
                        infoArg1 = context;
                        infoArg2 = null;
                        infoArg3 = null;
                        break;
                    default:
                        infoArg1 = undefined;
                }
                if (nodeInfo) nodeInfo.innerHTML = node.getNodeInfo(infoArg1, infoArg2, infoArg3);
                if (settingsSummary) settingsSummary.innerHTML = node.getNodeInfo(infoArg1, infoArg2, infoArg3);
            } else {
                if (nodeInfo) nodeInfo.innerHTML = node.getNodeInfo();
                if (settingsSummary) settingsSummary.innerHTML = node.getNodeInfo();
            }
        }
    }

    moveNode(index, direction) {
        if (index === 0) return; // Don't move root node
        
        const newIndex = index + direction;
        if (newIndex < 1 || newIndex >= this.selectedPath.nodes.length) return;

        // Swap nodes
        [this.selectedPath.nodes[index], this.selectedPath.nodes[newIndex]] = [this.selectedPath.nodes[newIndex], this.selectedPath.nodes[index]];
        
        this.renderNodes();
        this.updateDebugInfo();
    }

    deleteNode(index, skipConfirm = false) {
        console.log('deleteNode', index);
        if (index === 0) return; // Don't delete root node
        console.log('this.selectedPath.nodes', this.selectedPath.nodes);
        if (!skipConfirm) {
            showConfirmDeleteModal(() => this.deleteNode(index, true));
            return;
        }
        console.log('[Builder] Before delete, nodes:', this.selectedPath.nodes.map(n => n.name || n.subtype || n.type));
        this.selectedPath.nodes.splice(index, 1);
        console.log('[Builder] After delete, nodes:', this.selectedPath.nodes.map(n => n.name || n.subtype || n.type));
        this.renderNodes();
        this.updateDebugInfo();
    }

    getNodesData() {
            return this.selectedPath.nodes.map(node => node.toJSON());
    }

    togglePathEdit(button) {
        // Find the nearest .card to the button, then the .path-edit inside it
        const card = button.closest('.card');
        const pathEdit = card ? card.querySelector('.path-edit') : document.querySelector('.path-edit');
        if (!pathEdit) return;
        
        // Find the <i> icon inside the button
        const icon = button.querySelector('i');

        if (pathEdit.style.display === 'none' || pathEdit.style.display === '') {
            pathEdit.style.display = 'block';
            if (icon) icon.className = 'fas fa-check';
            button.classList.add('expanded');
        } else {
            pathEdit.style.display = 'none';
            if (icon) icon.className = 'fas fa-edit';
            button.classList.remove('expanded');
        }
    }

    createNewPath() {
        console.log('Creating new path...');
        // Create a new path with default RootEntryNode
        const rootNode = this.createNode({
            type: 'entry',
            subtype: 'root',
            content: {
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
            }
        });

        console.log('Created root node:', rootNode);

        if (rootNode) {
            this.selectedPath = {
                name: 'New Path',
                description: '',
                nodes: [rootNode]
            };
            console.log('Selected path initialized:', this.selectedPath);
        this.renderNodes();
            this.updateDebugInfo();
        } else {
            console.error('Failed to create root node');
        }
    }

    createNode(nodeData) {
        console.log('[Builder] createNode: nodeData', nodeData);
        if (!nodeData || !nodeData.type || !nodeData.subtype) {
            console.error('Invalid node data:', nodeData);
            return null;
        }
        try {
            const node = this.nodeFactory.createNode(nodeData);
            console.log('[Builder] createNode: created', node, 'class:', node?.constructor?.name);
            if (!node) {
                console.error('Failed to create node:', nodeData);
                return null;
            }
            return node;
        } catch (error) {
            console.error('Error creating node:', error);
            return null;
        }
    }

    initializeNodeMenu() {
        console.log('Initializing node menu...');
        // Get the node menu containers
        const actionGrid = this.container.querySelector('.action-nodes');
        const dataGrid = this.container.querySelector('.data-nodes');
        const decisionGrid = this.container.querySelector('.decision-nodes');

        if (!actionGrid || !dataGrid || !decisionGrid) {
            console.error('Node grid containers not found:', {
                actionGrid,
                dataGrid,
                decisionGrid
            });
            return;
        }

        console.log('Found grid containers');

        // Clear existing content
        actionGrid.innerHTML = '';
        dataGrid.innerHTML = '';
        decisionGrid.innerHTML = '';

        // Add nodes to grids...
        console.log('Adding nodes to grids...');

        // Add action nodes
        Object.entries(this.nodeTypes.action).forEach(([subtype, node]) => {
            const nodeItem = document.createElement('div');
            nodeItem.className = 'palette-item';
            nodeItem.draggable = true;
            nodeItem.dataset.nodeType = 'action';
            nodeItem.dataset.nodeSubtype = subtype;
            nodeItem.innerHTML = `
                <i class="fas ${node.icon}"></i>
                <span>${node.name}</span>
            `;
            actionGrid.appendChild(nodeItem);
        });

        // Add data nodes
        Object.entries(this.nodeTypes.data).forEach(([subtype, node]) => {
            const nodeItem = document.createElement('div');
            nodeItem.className = 'palette-item';
            nodeItem.draggable = true;
            nodeItem.dataset.nodeType = 'data';
            nodeItem.dataset.nodeSubtype = subtype;
            nodeItem.innerHTML = `
                <i class="fas ${node.icon}"></i>
                <span>${node.name}</span>
            `;
            dataGrid.appendChild(nodeItem);
        });

        // Add decision nodes
        Object.entries(this.nodeTypes.decision).forEach(([subtype, node]) => {
            const nodeItem = document.createElement('div');
            nodeItem.className = 'palette-item';
            nodeItem.draggable = true;
            nodeItem.dataset.nodeType = 'decision';
            nodeItem.dataset.nodeSubtype = subtype;
            nodeItem.innerHTML = `
                <i class="fas ${node.icon}"></i>
                <span>${node.name}</span>
            `;
            decisionGrid.appendChild(nodeItem);
        });

        // Setup drag and drop for the new items
        this.setupDragAndDrop();
        console.log('Node menu initialized');
    }

    setupDragAndDrop() {
        // Setup drag events for palette items
        this.container.querySelectorAll('.palette-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.nodeType);
                e.dataTransfer.setData('nodeSubtype', item.dataset.nodeSubtype);
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });
        });

        // Setup drop zone for flow-container
        const dropZone = this.container.querySelector('.flow-container');
        if (dropZone) {
            // Remove any previous drop event listener
            dropZone.removeEventListener('drop', this.handleDrop);
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                dropZone.classList.add('drag-over');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('drag-over');
            });

            dropZone.addEventListener('drop', this.handleDrop);
        }
        // Setup drop zone for custom bottom drop area
        const customDropZone = this.container.querySelector('.custom-drop-zone');
        if (customDropZone) {
            customDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                customDropZone.classList.add('drag-over');
            });
            customDropZone.addEventListener('dragleave', () => {
                customDropZone.classList.remove('drag-over');
            });
            customDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                customDropZone.classList.remove('drag-over');
                const nodeType = e.dataTransfer.getData('nodeType');
                const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
                if (nodeType && nodeSubtype) {
                    this.addNode(nodeType, nodeSubtype);
                }
            });
        }
    }

    handleDrop(e) {
        e.preventDefault();
        const dropZone = e.currentTarget;
        dropZone.classList.remove('drag-over');
        const nodeType = e.dataTransfer.getData('nodeType');
        const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
        if (nodeType && nodeSubtype) {
            this.addNode(nodeType, nodeSubtype);
        }
    }

    toggleDebugArea(button) {
        const debugArea = this.container.querySelector('.debug-area');
        const icon = button.querySelector('i');
        
        if (debugArea.style.display === 'none') {
            debugArea.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            this.updateDebugInfo();
        } else {
            debugArea.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }

    updateDebugInfo(section = 'all') {
        try {
            if (section === 'all' || section === 'path') {
                // Update path data
                const pathDebug = this.container.querySelector('#pathDebug');
                if (pathDebug) {
                    pathDebug.textContent = JSON.stringify(this.selectedPath, null, 2);
                }
            }

            if (section === 'all' || section === 'nodes') {
                // Update nodes data
                const nodesDebug = this.container.querySelector('#nodesDebug');
                if (nodesDebug) {
                    nodesDebug.textContent = JSON.stringify(this.selectedPath.nodes, null, 2);
                }
            }

            if (section === 'all' || section === 'nodeTypes') {
                // Update node types info
                const nodeTypesDebug = this.container.querySelector('#nodeTypesDebug');
                if (nodeTypesDebug) {
                    const nodeTypesInfo = {
                        entry: this.nodeTypes.entry,
                        data: this.nodeTypes.data,
                        decision: this.nodeTypes.decision,
                        action: this.nodeTypes.action
                    };
                    nodeTypesDebug.textContent = JSON.stringify(nodeTypesInfo, null, 2);
                }
            }

            if (section === 'all' || section === 'state') {
                // Update state data
                const stateDebug = this.container.querySelector('#stateDebug');
                if (stateDebug) {
                    const state = {
                        assistants: this.assistants,
                        tools: this.tools,
                        scripts: this.scripts,
                        audioFiles: this.audioFiles,
                        phoneTrees: this.phoneTrees,
                        pipelines: this.pipelines,
                        surveys: this.surveys,
                        paths: this.paths,
                        isEditMode: this.isEditMode
                    };
                    stateDebug.textContent = JSON.stringify(state, null, 2);
                }
            }
        } catch (error) {
            console.error('Error updating debug info:', error);
        }
    }

    toggleNodeForm(element) {
        if (!element) return;
        
        element.classList.toggle('expanded');
        const body = element.querySelector('.node-body');
        const toggle = element.querySelector('.node-toggle');
        
        if (body && toggle) {
            if (element.classList.contains('expanded')) {
                body.style.display = 'block';
                toggle.style.transform = 'rotate(180deg)';
            } else {
                body.style.display = 'none';
                toggle.style.transform = 'rotate(0deg)';
            }
        }
    }

    // Add to your existing styles
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .builder-layout {
                display: grid;
                grid-template-columns: 300px 1fr;
                gap: 1rem;
                min-height: 600px;
            }

            .node-menu-sidebar {
                background: #f8f9fa;
                border-right: 1px solid #dee2e6;
                padding: 1rem;
                height: 100%;
                overflow-y: auto;
            }

            .node-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 0.75rem;
            }

            .palette-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                cursor: move;
                transition: all 0.2s;
            }

            .palette-item:hover {
                background: #f8f9fa;
                border-color: #adb5bd;
                transform: translateY(-1px);
            }

            .flow-container-wrapper {
                padding: 1rem;
                overflow-y: auto;
            }

            .flow-container {
                min-height: 600px;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                padding: 1rem;
            }

            .node-wrapper {
                width: 100%;
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .node-controls {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .node-controls button {
                padding: 0.25rem;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .conversation-node {
                flex: 1;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }

            .conversation-node .node-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
                background: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
                cursor: pointer;
            }

            .conversation-node .node-body {
                padding: 1rem;
                display: none;
            }

            .conversation-node.expanded .node-body {
                display: block;
            }

            .conversation-node.entry { border-left: 4px solid #007bff; }
            .conversation-node.data { border-left: 4px solid #ffc107; }
            .conversation-node.decision { border-left: 4px solid #6610f2; }
            .conversation-node.action { border-left: 4px solid #20c997; }

            .node-connector {
                width: 2px;
                height: 2rem;
                background: #dee2e6;
                position: relative;
                margin: 0 auto;
            }

            .node-connector::after {
                content: '';
                position: absolute;
                bottom: -4px;
                left: 50%;
                transform: translateX(-50%);
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #dee2e6;
            }

            .drop-zone {
                border: 2px dashed #dee2e6;
                border-radius: 8px;
                padding: 2rem;
                margin: 1rem auto;
                text-align: center;
                background: #f8f9fa;
                transition: all 0.2s ease;
                max-width: 600px;
            }

            .drop-zone.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }

            .accordion-button:not(.collapsed) {
                background-color: #f8f9fa;
                color: #212529;
            }

            .debug-content {
                font-family: monospace;
                font-size: 0.875rem;
                background: #f8f9fa;
                padding: 1rem;
                border-radius: 0.25rem;
                max-height: 300px;
                overflow-y: auto;
                margin: 0;
            }

            @media (max-width: 992px) {
                .builder-layout {
                    grid-template-columns: 1fr;
                }

                .node-menu-sidebar {
                    border-right: none;
                    border-bottom: 1px solid #dee2e6;
                }
            }
        `;
        document.head.appendChild(style);
    }

    toggleDecisionActions(actionsAreaId) {
        const area = document.getElementById(actionsAreaId);
        if (area) {
            area.style.display = (area.style.display === 'none' || area.style.display === '') ? 'block' : 'none';
        }
    }

    handleDecisionActionDrop(event, nodeIndex) {
        event.preventDefault();
        event.stopPropagation(); // Prevent main flow drop handler from firing
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || node.type !== 'decision') return;
        const nodeType = event.dataTransfer.getData('nodeType');
        const nodeSubtype = event.dataTransfer.getData('nodeSubtype');
        if (nodeType === 'action' && nodeSubtype) {
            // Create a new action node object (minimal, can be expanded)
            const newAction = {
                type: 'action',
                subtype: nodeSubtype,
                name: this.nodeTypes.action[nodeSubtype]?.name || nodeSubtype
            };
            if (!node.actions) node.actions = new window.ActionNodeList();
            if (typeof node.actions.add === 'function') {
                node.actions.add(newAction);
            } else if (Array.isArray(node.actions)) {
                node.actions.push(newAction);
            }
            this.renderNodes();
        }
    }

    handleDecisionActionDragStart(event, nodeIndex, actionIndex) {
        event.dataTransfer.setData('decisionNodeIndex', nodeIndex);
        event.dataTransfer.setData('actionIndex', actionIndex);
        // Optionally add a dragging class for styling
        event.target.classList.add('dragging');
    }

    removeDecisionAction(nodeIndex, actionIndex) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || node.type !== 'decision' || !node.actions) return;
        if (typeof node.actions.remove === 'function') {
            node.actions.remove(actionIndex);
        } else if (Array.isArray(node.actions)) {
            node.actions.splice(actionIndex, 1);
        }
        this.renderNodes();
    }

    moveDecisionAction(nodeIndex, actionIndex, direction) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || node.type !== 'decision' || !node.actions) return;
        if (typeof node.actions.move === 'function') {
            node.actions.move(actionIndex, actionIndex + direction);
        } else if (Array.isArray(node.actions)) {
            const newIndex = actionIndex + direction;
            if (newIndex < 0 || newIndex >= node.actions.length) return;
            const temp = node.actions[actionIndex];
            node.actions[actionIndex] = node.actions[newIndex];
            node.actions[newIndex] = temp;
        }
        this.renderNodes();
    }

    // Utility: Get a text summary of a node, resolving IDs to names
    getNodeTextSummary(node, context = {}) {
        let lines = [];
        lines.push(`Type: ${node.type}`);
        lines.push(`Subtype: ${node.subtype}`);
        if (node.name) lines.push(`Name: ${node.name}`);
        if (node.content) {
            Object.entries(node.content).forEach(([key, value]) => {
                if (key.endsWith('Id') || key.endsWith('ID') || key.endsWith('id')) {
                    let resolved = null;
                    if (key === 'audioFileId' && context.audioFiles) {
                        const found = context.audioFiles.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    } else if (key === 'assistantId' && context.assistants) {
                        const found = context.assistants.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    } else if (key === 'pipelineId' && context.pipelines) {
                        const found = context.pipelines.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    } else if (key === 'phoneTreeId' && context.phoneTrees) {
                        const found = context.phoneTrees.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    } else if (key === 'surveyId' && context.surveys) {
                        const found = context.surveys.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    } else if (key === 'targetPathId' && context.paths) {
                        const found = context.paths.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    } else if (key === 'scriptId' && context.scripts) {
                        const found = context.scripts.find(a => a.id == value);
                        resolved = found ? found.name : null;
                    }
                    if (resolved) {
                        lines.push(`${key}: ${resolved} (ID: ${value})`);
                    } else {
                        lines.push(`${key}: ${value}`);
                    }
                } else {
                    lines.push(`${key}: ${value}`);
                }
            });
        }
        return lines.join('\n');
    }

    // Show a modal with a text breakdown of the selected path
    showPathTextBreakdown(pathId) {
        // Find the path object
        const path = this.paths.find(p => p.id == pathId);
        if (!path) {
            toastr.error('Path not found');
            return;
        }
        // If nodes are not loaded, fetch them
        if (!path.nodes || !Array.isArray(path.nodes) || path.nodes.length === 0) {
            toastr.error('No nodes found for this path');
            return;
        }
        // Use NodeFactory to instantiate nodes if needed
        const nodes = path.nodes.map(nodeData => this.createNode(nodeData));
        const context = {
            audioFiles: this.audioFiles,
            assistants: this.assistants,
            pipelines: this.pipelines,
            phoneTrees: this.phoneTrees,
            surveys: this.surveys,
            paths: this.paths,
            scripts: this.scripts,
            nodeTypes: this.nodeTypes
        };
        const summaries = nodes.map((node, idx) =>
            `Node ${idx + 1}:\n${this.getNodeTextSummary(node, context)}`
        ).join('\n\n---\n\n');
        // Create modal if not exists
        let modal = document.getElementById('pathTextBreakdownModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'pathTextBreakdownModal';
            modal.tabIndex = -1;
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Conversation Path Breakdown</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <pre id="pathTextBreakdownContent"></pre>
                            <hr>
                            <div id="pathFlowchart" style="min-height:200px;"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            // If modal exists but doesn't have the flowchart div, add it
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody && !modalBody.querySelector('#pathFlowchart')) {
                const hr = document.createElement('hr');
                const flowDiv = document.createElement('div');
                flowDiv.id = 'pathFlowchart';
                flowDiv.style.minHeight = '200px';
                modalBody.appendChild(hr);
                modalBody.appendChild(flowDiv);
            }
        }
        document.getElementById('pathTextBreakdownContent').textContent = summaries;
        // Render flowchart into #pathFlowchart
        const flowchartDiv = document.getElementById('pathFlowchart');
        if (flowchartDiv) {
            // Generate flowchart.js definition
            let def = '';
            nodes.forEach((node, idx) => {
                let label = node.name || node.subtype || node.type;
                let color = 'lightblue';
                if (node.type === 'action') color = 'orange';
                if (node.type === 'decision') color = 'purple';
                if (node.type === 'data') color = 'green';
                def += `n${idx}=>operation: ${label}|${color}\n`;
            });
            for (let i = 0; i < nodes.length - 1; i++) {
                def += `n${i}->n${i+1}\n`;
            }
            flowchartDiv.innerHTML = '';
            try {
                flowchart.parse(def).drawSVG('pathFlowchart', {
                    'line-width': 2,
                    'maxWidth': 600,
                    'line-length': 40,
                    'text-margin': 10,
                    'font-size': 14,
                    'font-color': 'black',
                    'element-color': '#222',
                    'fill': 'white',
                    'yes-text': 'yes',
                    'no-text': 'no',
                    'arrow-end': 'block',
                    'scale': 1,
                    'symbols': {
                        'operation': {
                            'font-color': 'white',
                            'element-color': '#222',
                            'fill': 'orange'
                        }
                    }
                });
            } catch (e) {
                flowchartDiv.innerHTML = '<div class="text-danger">Could not render flowchart.</div>';
            }
        }
        // Show modal (Bootstrap 5)
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            modal.style.display = 'block';
        }
    }

    // Add tool-related functions
    addToolToNode(nodeIndex) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node) return;

        // Create the modal element if it doesn't exist
        if (!document.getElementById('tool-selection-modal')) {
            const modalHtml = `
                <div class="modal fade" id="tool-selection-modal" tabindex="-1" aria-labelledby="tool-selection-modal-label" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="tool-selection-modal-label">Select Tool</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="toolSearchInput" placeholder="Search tools by name, description, or parameters...">
                                </div>
                                <div class="tool-list" style="max-height: 500px; overflow-y: auto;">
                                    <!-- Tools will be populated here dynamically -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        const modal = document.getElementById('tool-selection-modal');
        if (!modal) {
            console.error('Tool selection modal not found');
            return;
        }

        modal.dataset.nodeIndex = nodeIndex;

        const toolList = modal.querySelector('.tool-list');
        const searchInput = modal.querySelector('#toolSearchInput');
        if (!toolList || !searchInput) return;

        // Function to render tools
        const renderTools = (tools) => {
            toolList.innerHTML = tools.map(tool => `
                <div class="card mb-3 tool-card" data-tool-name="${tool.name.toLowerCase()}" data-tool-description="${(tool.description || '').toLowerCase()}" data-tool-params="${(tool.parameters || []).map(p => p.name).join(' ').toLowerCase()}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title mb-1">${tool.name}</h5>
                                <p class="card-text text-muted mb-2">${tool.description || ''}</p>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="window.pathBuilder.selectTool(${tool.id})">
                                Select
                            </button>
                        </div>
                        ${tool.parameters && tool.parameters.length > 0 ? `
                            <div class="tool-parameters mt-2">
                                <small class="text-muted d-block mb-1">Parameters:</small>
                                <div class="d-flex flex-wrap gap-2">
                                    ${tool.parameters.map(param => `
                                        <span class="badge bg-light text-dark border">
                                            ${param.name}${param.required ? ' *' : ''}
                                            <small class="text-muted ms-1">${param.type || 'string'}</small>
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        ${tool.returns ? `
                            <div class="tool-returns mt-2">
                                <small class="text-muted d-block mb-1">Returns:</small>
                                <span class="badge bg-info text-white">${tool.returns}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        };

        // Initial render
        renderTools(this.tools);

        // Add search functionality
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const toolCards = toolList.querySelectorAll('.tool-card');
            
            toolCards.forEach(card => {
                const toolName = card.dataset.toolName;
                const toolDescription = card.dataset.toolDescription;
                const toolParams = card.dataset.toolParams;
                const isVisible = toolName.includes(searchTerm) || 
                                toolDescription.includes(searchTerm) || 
                                toolParams.includes(searchTerm);
                card.style.display = isVisible ? 'block' : 'none';
            });
        });

        // Add some styles
        const style = document.createElement('style');
        style.textContent = `
            .tool-list::-webkit-scrollbar {
                width: 8px;
            }
            .tool-list::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            .tool-list::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .tool-list::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
            .tool-card {
                transition: all 0.2s ease;
                border: 1px solid #dee2e6;
            }
            .tool-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border-color: #007bff;
            }
            .tool-card .card-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #2c3e50;
            }
            .tool-card .card-text {
                font-size: 0.9rem;
                line-height: 1.4;
            }
            .tool-card .btn-primary {
                padding: 0.375rem 1rem;
                font-size: 0.9rem;
            }
            .tool-card .badge {
                font-size: 0.8rem;
                padding: 0.35em 0.65em;
            }
            .tool-parameters, .tool-returns {
                font-size: 0.85rem;
            }
            .tool-card .text-muted {
                color: #6c757d !important;
            }
        `;
        document.head.appendChild(style);

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    selectTool(toolId) {
        const modal = document.getElementById('tool-selection-modal');
        if (!modal) return;

        const nodeIndex = modal.dataset.nodeIndex;
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node) return;

        if (!node.content.tools) {
            node.content.tools = [];
        }
        node.content.tools.push({
            toolId: toolId,
            parameters: {},
            pathStateKey: ''
        });

        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }

        this.renderNodes();
    }

    removeToolFromNode(nodeIndex, toolIndex) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || !node.content.tools) return;

        node.content.tools.splice(toolIndex, 1);
        this.renderNodes();
    }

    updateToolParameter(nodeIndex, toolIndex, paramName, value) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || !node.content.tools || !node.content.tools[toolIndex]) return;

        if (!node.content.tools[toolIndex].parameters) {
            node.content.tools[toolIndex].parameters = {};
        }
        node.content.tools[toolIndex].parameters[paramName] = value;
        this.renderNodes();
    }

    updateToolPathStateKey(nodeIndex, toolIndex, value) {
        const node = this.selectedPath.nodes[nodeIndex];
        if (!node || !node.content.tools || !node.content.tools[toolIndex]) return;

        node.content.tools[toolIndex].pathStateKey = value;
        this.renderNodes();
    }

    showPathStateSelector(nodeIndex, toolIndex, paramName) {
        // TODO: Implement path state selector modal
        console.log('Path state selector not implemented yet');
    }
}

function normalizeNode(node) {
    const nodeFields = [
        'message', 'audioFileId', 'userDecisionType', 'smsTo', 'smsBody', 'emailTo', 'emailSubject', 'emailBody',
        'assistantId', 'prompt', 'script', 'returnType', 'description'
    ];
    node.content = node.content || {};
    nodeFields.forEach(field => {
        if (node.hasOwnProperty(field) && typeof node.content[field] === 'undefined') {
            node.content[field] = node[field];
            delete node[field];
        }
    });
    if (Array.isArray(node.actions)) {
        // Wrap actions in ActionNodeList if not already
        node.actions = new window.ActionNodeList(node.actions.map(normalizeNode));
    } else if (node.actions && typeof node.actions === 'object' && typeof node.actions.nodes === 'object') {
        // Already an ActionNodeList
        node.actions = new window.ActionNodeList(node.actions.nodes.map(normalizeNode));
    }
    return node;
}

// Add this helper at the top-level (outside the class)
function showConfirmDeleteModal(callback) {
    const modalEl = document.getElementById('confirmDeleteModal');
    if (!modalEl) {
        alert('Delete modal not found!');
        return;
    }
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    // Remove any previous click handlers
    confirmBtn.onclick = null;
    confirmBtn.onclick = function() {
        modal.hide();
        callback();
    };
    modal.show();
}
