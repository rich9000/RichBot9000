class PhoneTreePath {
    constructor(containerId) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.nodes = [];
        this.connections = [];
        this.selectedNode = null;
        this.draggingNode = null;
        this.connectingNode = null;
        
        this.nodeTypes = {
            'phone_tree': {
                name: 'Phone Tree',
                icon: 'fa-phone',
                color: '#28a745',
                isMainNode: true,
                settings: {
                    name: '',
                    phone_numbers: [],
                    greeting: '',
                    options: []
                }
            },
            'action_menu': {
                name: 'Action Menu',
                icon: 'fa-list',
                color: '#007bff',
                isMainNode: true,
                settings: {
                    name: '',
                    options: []
                }
            },
            'option_menu': {
                name: 'Option Menu',
                icon: 'fa-keyboard',
                color: '#ffc107',
                isMainNode: true,
                settings: {
                    name: '',
                    options: [],
                    numeric_options: Array(10).fill(null)
                }
            },
            'message': {
                name: 'Message',
                icon: 'fa-comment',
                color: '#6f42c1',
                isMainNode: false,
                settings: {
                    text: ''
                }
            },
            'audio_message': {
                name: 'Audio Message',
                icon: 'fa-volume-up',
                color: '#fd7e14',
                isMainNode: false,
                settings: {
                    audio_file: '',
                    available_files: [
                        'welcome.mp3',
                        'main_menu.mp3',
                        'tech_support.mp3',
                        'billing.mp3',
                        'goodbye.mp3'
                    ]
                }
            },
            'assistant': {
                name: 'AI Assistant',
                icon: 'fa-robot',
                color: '#20c997',
                isMainNode: false,
                settings: {
                    model: '',
                    system_prompt: '',
                    temperature: 0.7
                }
            },
            'pipeline': {
                name: 'Pipeline',
                icon: 'fa-project-diagram',
                color: '#17a2b8',
                isMainNode: false,
                settings: {
                    pipeline_id: '',
                    available_pipelines: [
                        'Customer Support',
                        'Technical Support',
                        'Billing Support'
                    ]
                }
            },
            'websocket': {
                name: 'WebSocket',
                icon: 'fa-plug',
                color: '#e83e8c',
                isMainNode: false,
                settings: {
                    url: '',
                    event_type: ''
                }
            },
            'script': {
                name: 'Script',
                icon: 'fa-file-code',
                color: '#6c757d',
                isMainNode: false,
                settings: {
                    script_id: '',
                    available_scripts: [
                        'Customer Verification',
                        'Service Check',
                        'Outage Check',
                        'Billing Check'
                    ]
                }
            },
            'menu_redirect': {
                name: 'Menu Redirect',
                icon: 'fa-random',
                color: '#dc3545',
                isMainNode: false,
                settings: {
                    target_menu: ''
                }
            },
            'phone_transfer': {
                name: 'Phone Transfer',
                icon: 'fa-phone-volume',
                color: '#28a745',
                isMainNode: false,
                settings: {
                    phone_number: '',
                    department: ''
                }
            },
            'hangup': {
                name: 'Hangup',
                icon: 'fa-phone-slash',
                color: '#343a40',
                isMainNode: false,
                settings: {
                    message: 'Thank you for calling. Goodbye.'
                }
            }
        };

        this.init();
    }

    init() {
        this.createUI();
        this.setupEventListeners();
    }

    createUI() {
        this.container.innerHTML = `
            <div class="phone-tree-builder">
                <div class="row">
                    <div class="col-md-3">
                        <div class="node-palette">
                            <h4>Nodes</h4>
                            <div class="node-list">
                                ${Object.entries(this.nodeTypes)
                                    .filter(([key, node]) => node.isMainNode)
                                    .map(([key, node]) => `
                                        <div class="node-item" draggable="true" data-node-type="${key}">
                                            <i class="fas ${node.icon}"></i>
                                            <span>${node.name}</span>
                                        </div>
                                    `).join('')}
                            </div>
                            <h4 class="mt-4">Options/Actions</h4>
                            <div class="node-list">
                                ${Object.entries(this.nodeTypes)
                                    .filter(([key, node]) => !node.isMainNode)
                                    .map(([key, node]) => `
                                        <div class="node-item" draggable="true" data-node-type="${key}">
                                            <i class="fas ${node.icon}"></i>
                                            <span>${node.name}</span>
                                        </div>
                                    `).join('')}
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

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .phone-tree-builder {
                height: 100%;
                padding: 1rem;
            }
            .node-palette {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1rem;
                height: 100%;
            }
            .node-palette h4 {
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 1px solid #dee2e6;
            }
            .node-list {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .node-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                cursor: move;
            }
            .node-item:hover {
                background: #e9ecef;
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
            }
            .node {
                position: absolute;
                background: white;
                border: 2px solid;
                border-radius: 8px;
                padding: 1rem;
                min-width: 200px;
                z-index: 2;
            }
            .node-drag-handle {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 20px;
                background: rgba(0,0,0,0.1);
                border-top-left-radius: 6px;
                border-top-right-radius: 6px;
                cursor: move;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #666;
            }
            .node-drag-handle:hover {
                background: rgba(0,0,0,0.2);
            }
            .node-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 20px;
                margin-bottom: 0.5rem;
            }
            .node-content {
                font-size: 0.9rem;
            }
            .node-actions {
                display: flex;
                gap: 0.5rem;
            }
            .node-options-list {
                margin-top: 1rem;
                border-top: 1px solid #dee2e6;
                padding-top: 0.5rem;
            }
            .options-header {
                font-weight: bold;
                margin-bottom: 0.5rem;
                color: #666;
            }
            .options-container {
                min-height: 50px;
                border: 1px dashed #dee2e6;
                border-radius: 4px;
                padding: 0.5rem;
                background: #f8f9fa;
            }
            .options-container.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }
            .option-item {
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 0.5rem;
                margin-bottom: 0.5rem;
                cursor: move;
                transition: background-color 0.2s;
            }
            .option-item:hover {
                background-color: #f8f9fa;
            }
            .option-item.dragging {
                opacity: 0.5;
                background-color: #e9ecef;
            }
            .option-drag-handle {
                cursor: move;
                color: #6c757d;
                margin-right: 0.5rem;
            }
            .option-drag-handle:hover {
                color: #007bff;
            }
            .option-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
            }
            .option-content {
                font-size: 0.9rem;
                color: #666;
                padding-left: 1.5rem;
            }
            .option-actions {
                margin-left: auto;
                display: flex;
                gap: 0.25rem;
            }
            .connection {
                position: absolute;
                pointer-events: none;
                z-index: 1;
            }
            .connection.temporary {
                z-index: 2;
            }
            .numeric-options {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
                margin-top: 0.5rem;
            }
            .numeric-option {
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 0.5rem;
                text-align: center;
                cursor: move;
            }
            .numeric-option:hover {
                background: #f8f9fa;
            }
            .actions-drop-zone {
                min-height: 50px;
                border: 1px dashed #dee2e6;
                border-radius: 4px;
                padding: 0.5rem;
                background: #f8f9fa;
                margin-bottom: 0.5rem;
            }
            .actions-drop-zone.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }
            .numeric-options {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }
            .numeric-option {
                display: flex;
                align-items: stretch;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                overflow: hidden;
            }
            .numeric-option:hover {
                background: #f8f9fa;
            }
            .numeric-option.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }
            .numeric-label {
                width: 40px;
                background: #f8f9fa;
                border-right: 1px solid #dee2e6;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                color: #666;
            }
            .numeric-content {
                flex: 1;
                padding: 0.5rem;
            }
            .empty-slot {
                color: #6c757d;
                font-style: italic;
                text-align: center;
                padding: 0.5rem;
            }
            .numeric-options-container {
                max-height: 300px;
                overflow-y: auto;
                margin: 0.5rem 0;
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }
            .numeric-options {
                display: flex;
                flex-direction: column;
            }
            .numeric-option {
                display: flex;
                align-items: stretch;
                background: white;
                border-bottom: 1px solid #dee2e6;
                min-height: 40px;
            }
            .numeric-option:last-child {
                border-bottom: none;
            }
            .numeric-option:hover {
                background: #f8f9fa;
            }
            .numeric-option.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }
            .numeric-label {
                width: 30px;
                background: #f8f9fa;
                border-right: 1px solid #dee2e6;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                color: #666;
                flex-shrink: 0;
            }
            .numeric-content {
                flex: 1;
                padding: 0.25rem;
                min-height: 40px;
                display: flex;
                align-items: center;
            }
            .empty-slot {
                color: #6c757d;
                font-style: italic;
                text-align: center;
                padding: 0.25rem;
                width: 100%;
            }
            .option-item {
                width: 100%;
            }
            .option-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.25rem;
            }
            .option-content {
                font-size: 0.9rem;
                color: #666;
                padding-left: 1.5rem;
            }
            .actions-drop-zone {
                min-height: 40px;
                border: 1px dashed #dee2e6;
                border-radius: 4px;
                padding: 0.25rem;
                background: #f8f9fa;
                margin-bottom: 0.5rem;
            }
            .actions-drop-zone.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }
            .actions-list {
                min-height: 40px;
                border: 1px dashed #dee2e6;
                border-radius: 4px;
                padding: 0.25rem;
                background: #f8f9fa;
            }
            .actions-list.drag-over {
                background: #e9ecef;
                border-color: #007bff;
            }
            .actions-list .option-item {
                margin-bottom: 0.25rem;
            }
            .actions-list .option-item:last-child {
                margin-bottom: 0;
            }
        `;
        document.head.appendChild(style);
    }

    setupEventListeners() {
        // Node palette drag events
        const nodeItems = this.container.querySelectorAll('.node-item');
        nodeItems.forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.nodeType);
                this.draggingNode = item.dataset.nodeType;
            });
        });

        // Canvas drop events
        const canvas = this.container.querySelector('.canvas-container');
        canvas.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            const nodeType = e.dataTransfer.getData('nodeType');
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Only create a node if we're not dropping into an options container
            const targetElement = document.elementFromPoint(e.clientX, e.clientY);
            const optionsContainer = targetElement.closest('.options-container');
            if (!optionsContainer) {
                this.createNode(nodeType, x, y);
            }
        });
    }

    createNode(type, x, y) {
        // Only allow one phone tree node
        if (type === 'phone_tree' && this.nodes.some(n => n.type === 'phone_tree')) {
            alert('Only one Phone Tree node is allowed');
            return;
        }

        const nodeType = this.nodeTypes[type];
        const node = {
            id: Date.now(),
            type: type,
            x: x,
            y: y,
            settings: { 
                ...nodeType.settings,
                numeric_options: type === 'option_menu' ? Array(10).fill(null) : undefined
            }
        };

        const nodeElement = document.createElement('div');
        nodeElement.className = 'node';
        nodeElement.dataset.nodeId = node.id;
        nodeElement.style.left = `${x}px`;
        nodeElement.style.top = `${y}px`;
        nodeElement.style.borderColor = nodeType.color;
        nodeElement.innerHTML = `
            <div class="node-drag-handle">
                <i class="fas fa-grip-lines"></i>
            </div>
            <div class="node-header">
                <div>
                    <i class="fas ${nodeType.icon}"></i>
                    <span>${nodeType.name}</span>
                </div>
                <div class="node-actions">
                    <button class="btn btn-sm btn-outline-primary edit-node">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-node">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="node-content">
                ${this.renderNodeSettings(node)}
            </div>
            ${nodeType.isMainNode ? `
                <div class="node-options-list" data-node-id="${node.id}">
                    <div class="options-header">Options</div>
                    <div class="options-container" data-node-id="${node.id}"></div>
                </div>
            ` : ''}
        `;

        this.container.querySelector('.canvas-container').appendChild(nodeElement);
        this.nodes.push(node);

        // Add event listeners for the new node
        this.setupNodeEventListeners(nodeElement, node);
    }

    renderNodeSettings(node) {
        const nodeType = this.nodeTypes[node.type];
        switch (node.type) {
            case 'phone_tree':
                return `
                    <div>Name: ${node.settings.name || 'Not set'}</div>
                    <div>Phone Numbers: ${node.settings.phone_numbers.length}</div>
                    <div>Actions: ${node.settings.options.length}</div>
                `;
            case 'action_menu':
            case 'option_menu':
                return `
                    <div>Name: ${node.settings.name || 'Not set'}</div>
                `;
            case 'message':
                return `
                    <div>Message: ${node.settings.text || 'Not set'}</div>
                `;
            case 'audio_message':
                return `
                    <div>Audio: ${node.settings.audio_file || 'Not set'}</div>
                `;
            case 'assistant':
                return `
                    <div>Model: ${node.settings.model || 'Not set'}</div>
                `;
            case 'pipeline':
                return `
                    <div>Pipeline: ${node.settings.pipeline_id || 'Not set'}</div>
                `;
            case 'websocket':
                return `
                    <div>URL: ${node.settings.url || 'Not set'}</div>
                `;
            case 'script':
                return `
                    <div>Script: ${node.settings.script_id || 'Not set'}</div>
                `;
            case 'menu_redirect':
                return `
                    <div>Target: ${node.settings.target_menu || 'Not set'}</div>
                `;
            case 'phone_transfer':
                return `
                    <div>Phone Number: ${node.settings.phone_number || 'Not set'}</div>
                    <div>Department: ${node.settings.department || 'Not set'}</div>
                `;
            case 'hangup':
                return `
                    <div>Message: ${node.settings.message || 'Not set'}</div>
                `;
            default:
                return `<div>Click edit to configure</div>`;
        }
    }

    renderNodeEditForm(node) {
        const nodeType = this.nodeTypes[node.type];
        switch (node.type) {
            case 'phone_tree':
                return `
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="${node.settings.name}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Numbers</label>
                        <div class="phone-numbers-list">
                            ${node.settings.phone_numbers.map((num, index) => `
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="phone_numbers[]" value="${num}">
                                    <button type="button" class="btn btn-outline-danger remove-phone" data-index="${index}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm add-phone">
                            <i class="fas fa-plus"></i> Add Phone Number
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Greeting</label>
                        <textarea class="form-control" name="greeting">${node.settings.greeting}</textarea>
                    </div>
                `;
            case 'action_menu':
            case 'option_menu':
                return `
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="${node.settings.name}">
                    </div>
                `;
            case 'message':
                return `
                    <div class="mb-3">
                        <label class="form-label">Message Text</label>
                        <textarea class="form-control" name="text">${node.settings.text}</textarea>
                    </div>
                `;
            case 'audio_message':
                return `
                    <div class="mb-3">
                        <label class="form-label">Audio File</label>
                        <select class="form-select" name="audio_file">
                            <option value="">Select Audio File</option>
                            ${nodeType.settings.available_files.map(file => `
                                <option value="${file}" ${node.settings.audio_file === file ? 'selected' : ''}>${file}</option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'assistant':
                return `
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="model" value="${node.settings.model}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">System Prompt</label>
                        <textarea class="form-control" name="system_prompt">${node.settings.system_prompt}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Temperature</label>
                        <input type="number" class="form-control" name="temperature" value="${node.settings.temperature}" step="0.1" min="0" max="1">
                    </div>
                `;
            case 'pipeline':
                return `
                    <div class="mb-3">
                        <label class="form-label">Pipeline</label>
                        <select class="form-select" name="pipeline_id">
                            <option value="">Select Pipeline</option>
                            ${nodeType.settings.available_pipelines.map(pipeline => `
                                <option value="${pipeline}" ${node.settings.pipeline_id === pipeline ? 'selected' : ''}>${pipeline}</option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'websocket':
                return `
                    <div class="mb-3">
                        <label class="form-label">WebSocket URL</label>
                        <input type="text" class="form-control" name="url" value="${node.settings.url}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event Type</label>
                        <input type="text" class="form-control" name="event_type" value="${node.settings.event_type}">
                    </div>
                `;
            case 'script':
                return `
                    <div class="mb-3">
                        <label class="form-label">Script</label>
                        <select class="form-select" name="script_id">
                            <option value="">Select Script</option>
                            ${nodeType.settings.available_scripts.map(script => `
                                <option value="${script}" ${node.settings.script_id === script ? 'selected' : ''}>${script}</option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'menu_redirect':
                return `
                    <div class="mb-3">
                        <label class="form-label">Target Menu</label>
                        <select class="form-select" name="target_menu">
                            <option value="">Select Target Menu</option>
                            ${this.nodes.filter(n => n.type === 'action_menu').map(menu => `
                                <option value="${menu.id}" ${node.settings.target_menu === menu.id ? 'selected' : ''}>${menu.settings.name || 'Unnamed Menu'}</option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'phone_transfer':
                return `
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone_number" value="${node.settings.phone_number}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" value="${node.settings.department}">
                    </div>
                `;
            case 'hangup':
                return `
                    <div class="mb-3">
                        <label class="form-label">Goodbye Message</label>
                        <textarea class="form-control" name="message">${node.settings.message}</textarea>
                    </div>
                `;
            default:
                return `<div>No settings available for this node type</div>`;
        }
    }

    setupNodeEventListeners(nodeElement, node) {
        // Edit button
        nodeElement.querySelector('.edit-node').addEventListener('click', () => {
            this.editNode(node);
        });

        // Delete button
        nodeElement.querySelector('.delete-node').addEventListener('click', () => {
            this.deleteNode(node);
        });

        // Make node draggable by handle only
        const dragHandle = nodeElement.querySelector('.node-drag-handle');
        dragHandle.addEventListener('mousedown', (e) => {
            const startX = e.clientX - nodeElement.offsetLeft;
            const startY = e.clientY - nodeElement.offsetTop;

            const moveHandler = (e) => {
                const x = e.clientX - startX;
                const y = e.clientY - startY;
                nodeElement.style.left = `${x}px`;
                nodeElement.style.top = `${y}px`;
                node.x = x;
                node.y = y;
                this.updateConnections();
            };

            const upHandler = () => {
                document.removeEventListener('mousemove', moveHandler);
                document.removeEventListener('mouseup', upHandler);
            };

            document.addEventListener('mousemove', moveHandler);
            document.addEventListener('mouseup', upHandler);
        });

        // Add droppable functionality for main nodes
        if (this.nodeTypes[node.type].isMainNode) {
            const optionsContainer = nodeElement.querySelector('.options-container');
            if (optionsContainer) {
                optionsContainer.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    optionsContainer.classList.add('drag-over');
                });

                optionsContainer.addEventListener('dragleave', (e) => {
                    optionsContainer.classList.remove('drag-over');
                });

                optionsContainer.addEventListener('drop', (e) => {
                    e.preventDefault();
                    optionsContainer.classList.remove('drag-over');
                    const nodeType = e.dataTransfer.getData('nodeType');
                    if (nodeType && !this.nodeTypes[nodeType].isMainNode) {
                        this.addOptionToNode(node, nodeType);
                    }
                });
            }
        }
    }

    editNode(node) {
        const nodeType = this.nodeTypes[node.type];
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit ${nodeType.name}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${this.renderNodeEditForm(node)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary save-node">Save changes</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Add event listeners for dynamic form elements
        if (node.type === 'phone_tree') {
            modal.querySelector('.add-phone').addEventListener('click', () => {
                const phoneNumbersList = modal.querySelector('.phone-numbers-list');
                const newIndex = phoneNumbersList.children.length;
                const newPhoneInput = document.createElement('div');
                newPhoneInput.className = 'input-group mb-2';
                newPhoneInput.innerHTML = `
                    <input type="text" class="form-control" name="phone_numbers[]" value="">
                    <button type="button" class="btn btn-outline-danger remove-phone" data-index="${newIndex}">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                phoneNumbersList.appendChild(newPhoneInput);
            });

            modal.querySelector('.phone-numbers-list').addEventListener('click', (e) => {
                if (e.target.closest('.remove-phone')) {
                    e.target.closest('.input-group').remove();
                }
            });
        }

        if (node.type === 'action_menu' || node.type === 'option_menu') {
            modal.querySelector('.add-item').addEventListener('click', () => {
                const menuItemsList = modal.querySelector('.menu-items-list');
                const newIndex = menuItemsList.children.length;
                const newItem = document.createElement('div');
                newItem.className = 'menu-item mb-2';
                newItem.innerHTML = `
                    <div class="input-group">
                        <select class="form-select" name="options[${newIndex}][type]">
                            <option value="message">Message</option>
                            <option value="audio_message">Audio Message</option>
                            <option value="assistant">Assistant</option>
                            <option value="pipeline">Pipeline</option>
                            <option value="websocket">WebSocket</option>
                            <option value="script">Script</option>
                            <option value="numeric_menu">Numeric Menu</option>
                            <option value="menu_redirect">Menu Redirect</option>
                            <option value="phone_transfer">Phone Transfer</option>
                            <option value="hangup">Hangup</option>
                        </select>
                        <button type="button" class="btn btn-outline-danger remove-item" data-index="${newIndex}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                menuItemsList.appendChild(newItem);
            });

            modal.querySelector('.menu-items-list').addEventListener('click', (e) => {
                if (e.target.closest('.remove-item')) {
                    e.target.closest('.menu-item').remove();
                }
            });
        }

        if (node.type === 'numeric_menu') {
            modal.querySelector('.add-option').addEventListener('click', () => {
                const optionsList = modal.querySelector('.numeric-options-list');
                const newIndex = optionsList.children.length;
                const newOption = document.createElement('div');
                newOption.className = 'input-group mb-2';
                newOption.innerHTML = `
                    <input type="text" class="form-control" name="options[${newIndex}][key]" placeholder="Key">
                    <input type="text" class="form-control" name="options[${newIndex}][description]" placeholder="Description">
                    <button type="button" class="btn btn-outline-danger remove-option" data-index="${newIndex}">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                optionsList.appendChild(newOption);
            });

            modal.querySelector('.numeric-options-list').addEventListener('click', (e) => {
                if (e.target.closest('.remove-option')) {
                    e.target.closest('.input-group').remove();
                }
            });
        }

        modal.querySelector('.save-node').addEventListener('click', () => {
            this.saveNodeSettings(node, modal);
            modalInstance.hide();
            modal.remove();
        });
    }

    saveNodeSettings(node, modal) {
        const form = modal.querySelector('.modal-body');
        
        // Handle different node types
        switch (node.type) {
            case 'phone_tree':
                node.settings.name = form.querySelector('[name="name"]').value;
                node.settings.phone_numbers = Array.from(form.querySelectorAll('[name="phone_numbers[]"]')).map(input => input.value);
                node.settings.greeting = form.querySelector('[name="greeting"]').value;
                break;
            case 'action_menu':
            case 'option_menu':
                node.settings.name = form.querySelector('[name="name"]').value;
                node.settings.options = Array.from(form.querySelectorAll('.menu-item')).map(item => ({
                    type: item.querySelector('select').value
                }));
                break;
            case 'message':
                node.settings.text = form.querySelector('[name="text"]').value;
                break;
            case 'audio_message':
                node.settings.audio_file = form.querySelector('[name="audio_file"]').value;
                break;
            case 'assistant':
                node.settings.model = form.querySelector('[name="model"]').value;
                node.settings.system_prompt = form.querySelector('[name="system_prompt"]').value;
                node.settings.temperature = parseFloat(form.querySelector('[name="temperature"]').value);
                break;
            case 'pipeline':
                node.settings.pipeline_id = form.querySelector('[name="pipeline_id"]').value;
                break;
            case 'websocket':
                node.settings.url = form.querySelector('[name="url"]').value;
                node.settings.event_type = form.querySelector('[name="event_type"]').value;
                break;
            case 'script':
                node.settings.script_id = form.querySelector('[name="script_id"]').value;
                break;
            case 'numeric_menu':
                node.settings.options = Array.from(form.querySelectorAll('.numeric-options-list .input-group')).map(group => ({
                    key: group.querySelector('[name$="[key]"]').value,
                    description: group.querySelector('[name$="[description]"]').value
                }));
                break;
            case 'menu_redirect':
                node.settings.target_menu = form.querySelector('[name="target_menu"]').value;
                break;
            case 'phone_transfer':
                node.settings.phone_number = form.querySelector('[name="phone_number"]').value;
                node.settings.department = form.querySelector('[name="department"]').value;
                break;
            case 'hangup':
                node.settings.message = form.querySelector('[name="message"]').value;
                break;
        }

        // Update node display
        const nodeElement = this.container.querySelector(`[data-node-id="${node.id}"]`);
        if (nodeElement) {
            nodeElement.querySelector('.node-content').innerHTML = this.renderNodeSettings(node);
        }
    }

    deleteNode(node) {
        if (confirm('Are you sure you want to delete this node?')) {
            const nodeElement = this.container.querySelector(`[data-node-id="${node.id}"]`);
            if (nodeElement) {
                nodeElement.remove();
            }
            this.nodes = this.nodes.filter(n => n.id !== node.id);
            this.removeConnections(node);
        }
    }

    startConnection(node, type, e) {
        this.connectingNode = { node, type };
        
        const moveHandler = (e) => {
            const canvas = this.container.querySelector('.canvas-container');
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Draw temporary connection line
            this.drawTemporaryConnection(x, y);
        };

        const upHandler = (e) => {
            document.removeEventListener('mousemove', moveHandler);
            document.removeEventListener('mouseup', upHandler);
            
            const target = document.elementFromPoint(e.clientX, e.clientY);
            const targetElement = target.closest('.node');
            
            if (targetElement && this.connectingNode) {
                const targetNodeId = targetElement.dataset.nodeId;
                const targetNodeObj = this.nodes.find(n => n.id === parseInt(targetNodeId));
                
                if (targetNodeObj && this.canConnect(this.connectingNode.node, targetNodeObj)) {
                    // Determine source and target based on connection type
                    let sourceNode, targetNode;
                    if (this.connectingNode.type === 'output') {
                        sourceNode = this.connectingNode.node;
                        targetNode = targetNodeObj;
                    } else {
                        sourceNode = targetNodeObj;
                        targetNode = this.connectingNode.node;
                    }
                    this.createConnection(sourceNode, targetNode);
                }
            }
            
            this.clearTemporaryConnection();
            this.connectingNode = null;
        };

        document.addEventListener('mousemove', moveHandler);
        document.addEventListener('mouseup', upHandler);
    }

    canConnect(sourceNode, targetNode) {
        // Prevent connecting a node to itself
        if (sourceNode.id === targetNode.id) {
            return false;
        }
        
        // Check if connection already exists
        const existingConnection = this.connections.find(conn => 
            conn.source === sourceNode.id && conn.target === targetNode.id
        );
        
        if (existingConnection) {
            return false;
        }

        // Phone tree specific connection rules
        if (sourceNode.type === 'phone_tree') {
            // Phone tree can only connect to menus
            return targetNode.type === 'action_menu' || targetNode.type === 'option_menu';
        }

        if (sourceNode.type === 'action_menu' || sourceNode.type === 'option_menu') {
            // Menus can connect to any node type except phone tree
            return targetNode.type !== 'phone_tree';
        }

        if (sourceNode.type === 'numeric_menu') {
            // Numeric menus can only connect to other menus
            return targetNode.type === 'option_menu';
        }

        if (sourceNode.type === 'menu_redirect') {
            // Redirects can only connect to menus
            return targetNode.type === 'action_menu';
        }

        // Other nodes can connect to any node type except phone tree
        return targetNode.type !== 'phone_tree';
    }

    createConnection(sourceNode, targetNode) {
        const connection = {
            id: Date.now(),
            source: sourceNode.id,
            target: targetNode.id
        };

        this.connections.push(connection);
        this.drawConnection(connection);
    }

    drawConnection(connection) {
        const sourceElement = this.container.querySelector(`[data-node-id="${connection.source}"]`);
        const targetElement = this.container.querySelector(`[data-node-id="${connection.target}"]`);
        
        if (sourceElement && targetElement) {
            const sourceRect = sourceElement.getBoundingClientRect();
            const targetRect = targetElement.getBoundingClientRect();
            const canvasRect = this.container.querySelector('.canvas-container').getBoundingClientRect();
            
            // Calculate connection points
            const startX = sourceRect.right - canvasRect.left;
            const startY = sourceRect.top + (sourceRect.height / 2) - canvasRect.top;
            const endX = targetRect.left - canvasRect.left;
            const endY = targetRect.top + (targetRect.height / 2) - canvasRect.top;
            
            // Create connection line
            const line = document.createElement('div');
            line.className = 'connection';
            line.style.position = 'absolute';
            line.style.left = `${startX}px`;
            line.style.top = `${startY}px`;
            line.style.width = `${Math.sqrt(Math.pow(endX - startX, 2) + Math.pow(endY - startY, 2))}px`;
            line.style.height = '2px';
            line.style.backgroundColor = '#6c757d';
            line.style.transform = `rotate(${Math.atan2(endY - startY, endX - startX)}rad)`;
            line.style.transformOrigin = '0 0';
            line.style.zIndex = '1';
            
            this.container.querySelector('.canvas-container').appendChild(line);
        }
    }

    drawTemporaryConnection(x, y) {
        this.clearTemporaryConnection();
        
        const sourceElement = this.container.querySelector(`[data-node-id="${this.connectingNode.node.id}"]`);
        if (!sourceElement) return;
        
        const sourceRect = sourceElement.getBoundingClientRect();
        const canvasRect = this.container.querySelector('.canvas-container').getBoundingClientRect();
        
        const startX = sourceRect.right - canvasRect.left;
        const startY = sourceRect.top + (sourceRect.height / 2) - canvasRect.top;
        
        // Create temporary line
        const line = document.createElement('div');
        line.className = 'connection temporary';
        line.style.position = 'absolute';
        line.style.left = `${startX}px`;
        line.style.top = `${startY}px`;
        line.style.width = `${Math.sqrt(Math.pow(x - startX, 2) + Math.pow(y - startY, 2))}px`;
        line.style.height = '2px';
        line.style.backgroundColor = '#007bff';
        line.style.transform = `rotate(${Math.atan2(y - startY, x - startX)}rad)`;
        line.style.transformOrigin = '0 0';
        line.style.zIndex = '2';
        
        this.container.querySelector('.canvas-container').appendChild(line);
    }

    clearTemporaryConnection() {
        const tempLine = this.container.querySelector('.connection.temporary');
        if (tempLine) {
            tempLine.remove();
        }
    }

    updateConnections() {
        // Remove all existing connections
        this.container.querySelectorAll('.connection').forEach(conn => conn.remove());
        
        // Redraw all connections
        this.connections.forEach(conn => this.drawConnection(conn));
    }

    removeConnections(node) {
        this.connections = this.connections.filter(conn => 
            conn.source !== node.id && conn.target !== node.id
        );
        this.updateConnections();
    }

    getPath() {
        return {
            nodes: this.nodes,
            connections: this.connections
        };
    }

    loadPath(path) {
        // Clear existing nodes and connections
        this.nodes = [];
        this.connections = [];
        this.container.querySelector('.canvas-container').innerHTML = '';
        
        // Load nodes
        path.nodes.forEach(node => {
            this.createNode(node.type, node.x, node.y);
        });
        
        // Load connections
        path.connections.forEach(conn => {
            this.createConnection(
                this.nodes.find(n => n.id === conn.source),
                this.nodes.find(n => n.id === conn.target)
            );
        });
    }

    addOptionToNode(node, optionType) {
        // Only allow adding options to main nodes
        if (!this.nodeTypes[node.type].isMainNode) {
            return;
        }

        const option = {
            id: Date.now(),
            type: optionType,
            settings: { ...this.nodeTypes[optionType].settings }
        };

        node.settings.options.push(option);
        this.renderNodeOptions(node);
    }

    renderNodeOptions(node) {
        const optionsContainer = this.container.querySelector(`.options-container[data-node-id="${node.id}"]`);
        if (!optionsContainer) return;

        if (node.type === 'option_menu') {
            // Reorder numeric options to put 0 at the end
            const numericOptions = [...(node.settings.numeric_options || Array(10).fill(null))];
            const zeroOption = numericOptions[0];
            numericOptions.shift();
            numericOptions.push(zeroOption);

            optionsContainer.innerHTML = `
                <div class="actions-drop-zone" data-node-id="${node.id}" data-zone="before">
                    <div class="options-header">Actions Before Options</div>
                    <div class="actions-list" data-zone="before">
                        ${(node.settings.before_actions || []).map((action, index) => `
                            <div class="option-item" draggable="true" data-action-index="${index}">
                                <div class="option-header">
                                    <i class="fas fa-grip-lines option-drag-handle"></i>
                                    <i class="fas ${this.nodeTypes[action.type].icon}"></i>
                                    <span>${this.nodeTypes[action.type].name}</span>
                                    <div class="option-actions">
                                        <button class="btn btn-sm btn-outline-primary edit-option" data-action-index="${index}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-option" data-action-index="${index}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="option-content">
                                    ${this.renderOptionSettings(action)}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="numeric-options-container">
                    <div class="numeric-options">
                        ${numericOptions.map((option, index) => `
                            <div class="numeric-option" data-index="${index === 9 ? 0 : index + 1}">
                                <div class="numeric-label">${index === 9 ? '0' : index + 1}</div>
                                <div class="numeric-content">
                                    ${option ? `
                                        <div class="option-item" draggable="true" data-numeric-index="${index === 9 ? 0 : index + 1}">
                                            <div class="option-header">
                                                <i class="fas fa-grip-lines option-drag-handle"></i>
                                                <i class="fas ${this.nodeTypes[option.type].icon}"></i>
                                                <span>${this.nodeTypes[option.type].name}</span>
                                                <div class="option-actions">
                                                    <button class="btn btn-sm btn-outline-primary edit-option" data-numeric-index="${index === 9 ? 0 : index + 1}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-option" data-numeric-index="${index === 9 ? 0 : index + 1}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="option-content">
                                                ${this.renderOptionSettings(option)}
                                            </div>
                                        </div>
                                    ` : '<div class="empty-slot">Drop option here</div>'}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="actions-drop-zone" data-node-id="${node.id}" data-zone="after">
                    <div class="options-header">Actions After Options</div>
                    <div class="actions-list" data-zone="after">
                        ${(node.settings.after_actions || []).map((action, index) => `
                            <div class="option-item" draggable="true" data-action-index="${index}">
                                <div class="option-header">
                                    <i class="fas fa-grip-lines option-drag-handle"></i>
                                    <i class="fas ${this.nodeTypes[action.type].icon}"></i>
                                    <span>${this.nodeTypes[action.type].name}</span>
                                    <div class="option-actions">
                                        <button class="btn btn-sm btn-outline-primary edit-option" data-action-index="${index}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-option" data-action-index="${index}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="option-content">
                                    ${this.renderOptionSettings(action)}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            // Add event listeners for numeric options
            optionsContainer.querySelectorAll('.numeric-option').forEach(option => {
                option.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    option.classList.add('drag-over');
                });

                option.addEventListener('dragleave', (e) => {
                    option.classList.remove('drag-over');
                });

                option.addEventListener('drop', (e) => {
                    e.preventDefault();
                    option.classList.remove('drag-over');
                    const nodeType = e.dataTransfer.getData('nodeType');
                    const fromIndex = e.dataTransfer.getData('text/plain');
                    const toIndex = parseInt(option.dataset.index);

                    if (nodeType && !this.nodeTypes[nodeType].isMainNode) {
                        // New option being dropped
                        node.settings.numeric_options[toIndex] = {
                            type: nodeType,
                            settings: { ...this.nodeTypes[nodeType].settings }
                        };
                    } else if (fromIndex !== '') {
                        // Swapping existing options
                        const fromNumericIndex = parseInt(fromIndex);
                        const temp = node.settings.numeric_options[fromNumericIndex];
                        node.settings.numeric_options[fromNumericIndex] = node.settings.numeric_options[toIndex];
                        node.settings.numeric_options[toIndex] = temp;
                    }
                    this.renderNodeOptions(node);
                });
            });

            // Add event listeners for action lists
            optionsContainer.querySelectorAll('.actions-list').forEach(list => {
                list.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    list.classList.add('drag-over');
                });

                list.addEventListener('dragleave', (e) => {
                    list.classList.remove('drag-over');
                });

                list.addEventListener('drop', (e) => {
                    e.preventDefault();
                    list.classList.remove('drag-over');
                    const nodeType = e.dataTransfer.getData('nodeType');
                    if (nodeType && !this.nodeTypes[nodeType].isMainNode) {
                        const option = {
                            type: nodeType,
                            settings: { ...this.nodeTypes[nodeType].settings }
                        };
                        if (list.dataset.zone === 'before') {
                            node.settings.before_actions = node.settings.before_actions || [];
                            node.settings.before_actions.push(option);
                        } else {
                            node.settings.after_actions = node.settings.after_actions || [];
                            node.settings.after_actions.push(option);
                        }
                        this.renderNodeOptions(node);
                    }
                });
            });

            // Add event listeners for draggable options
            optionsContainer.querySelectorAll('.option-item').forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', item.dataset.numericIndex || item.dataset.actionIndex);
                    item.classList.add('dragging');
                });

                item.addEventListener('dragend', (e) => {
                    item.classList.remove('dragging');
                });
            });

            // Add event listeners for edit and delete buttons
            optionsContainer.querySelectorAll('.edit-option').forEach(button => {
                button.addEventListener('click', (e) => {
                    const index = parseInt(button.dataset.numericIndex || button.dataset.actionIndex);
                    const isNumeric = !!button.dataset.numericIndex;
                    this.editOption(node, index, isNumeric);
                });
            });

            optionsContainer.querySelectorAll('.delete-option').forEach(button => {
                button.addEventListener('click', (e) => {
                    const index = parseInt(button.dataset.numericIndex || button.dataset.actionIndex);
                    const isNumeric = !!button.dataset.numericIndex;
                    this.deleteOption(node, index, isNumeric);
                });
            });
        } else {
            optionsContainer.innerHTML = node.settings.options.map((option, index) => `
                <div class="option-item" draggable="true" data-option-index="${index}">
                    <div class="option-header">
                        <i class="fas fa-grip-lines option-drag-handle"></i>
                        <i class="fas ${this.nodeTypes[option.type].icon}"></i>
                        <span>${this.nodeTypes[option.type].name}</span>
                        <div class="option-actions">
                            <button class="btn btn-sm btn-outline-primary edit-option" data-option-index="${index}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-option" data-option-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="option-content">
                        ${this.renderOptionSettings(option)}
                    </div>
                </div>
            `).join('');

            // Add event listeners for option items
            optionsContainer.querySelectorAll('.option-item').forEach(item => {
                // Add drag and drop reordering
                item.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', item.dataset.optionIndex);
                    item.classList.add('dragging');
                });

                item.addEventListener('dragend', (e) => {
                    item.classList.remove('dragging');
                });

                // Add edit and delete handlers
                item.querySelector('.edit-option').addEventListener('click', (e) => {
                    const index = parseInt(item.dataset.optionIndex);
                    this.editOption(node, index);
                });

                item.querySelector('.delete-option').addEventListener('click', (e) => {
                    const index = parseInt(item.dataset.optionIndex);
                    this.deleteOption(node, index);
                });
            });

            // Add drop zone handling for reordering
            optionsContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                const draggingItem = optionsContainer.querySelector('.dragging');
                if (!draggingItem) return;

                const siblings = [...optionsContainer.querySelectorAll('.option-item:not(.dragging)')];
                const nextSibling = siblings.find(sibling => {
                    const box = sibling.getBoundingClientRect();
                    const offset = e.clientY - box.top - box.height / 2;
                    return offset < 0;
                });

                optionsContainer.insertBefore(draggingItem, nextSibling);
            });

            optionsContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                const fromIndex = parseInt(e.dataTransfer.getData('text/plain'));
                const toIndex = Array.from(optionsContainer.querySelectorAll('.option-item')).indexOf(
                    optionsContainer.querySelector('.dragging')
                );
                
                if (fromIndex !== toIndex) {
                    const option = node.settings.options.splice(fromIndex, 1)[0];
                    node.settings.options.splice(toIndex, 0, option);
                    this.renderNodeOptions(node);
                }
            });
        }
    }

    renderOptionSettings(option) {
        const nodeType = this.nodeTypes[option.type];
        switch (option.type) {
            case 'message':
                return `<div>Message: ${option.settings.text || 'Not set'}</div>`;
            case 'audio_message':
                return `<div>Audio: ${option.settings.audio_file || 'Not set'}</div>`;
            case 'assistant':
                return `
                    <div>Model: ${option.settings.model || 'Not set'}</div>
                    <div>Temperature: ${option.settings.temperature}</div>
                `;
            case 'pipeline':
                return `<div>Pipeline: ${option.settings.pipeline_id || 'Not set'}</div>`;
            case 'websocket':
                return `
                    <div>URL: ${option.settings.url || 'Not set'}</div>
                    <div>Event: ${option.settings.event_type || 'Not set'}</div>
                `;
            case 'script':
                return `<div>Script: ${option.settings.script_id || 'Not set'}</div>`;
            case 'menu_redirect':
                const targetMenu = this.nodes.find(n => n.id === parseInt(option.settings.target_menu));
                return `<div>Target: ${targetMenu ? targetMenu.settings.name || 'Unnamed Menu' : 'Not set'}</div>`;
            case 'phone_transfer':
                return `
                    <div>Phone Number: ${option.settings.phone_number || 'Not set'}</div>
                    <div>Department: ${option.settings.department || 'Not set'}</div>
                `;
            case 'hangup':
                return `<div>Message: ${option.settings.message || 'Not set'}</div>`;
            default:
                return `<div>No settings available</div>`;
        }
    }

    editOption(node, optionIndex, isNumeric = false) {
        const option = isNumeric ? node.settings.numeric_options[optionIndex] : node.settings.options[optionIndex];
        const nodeType = this.nodeTypes[option.type];
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit ${nodeType.name}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${this.renderNodeEditForm(option)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary save-option">Save changes</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        modal.querySelector('.save-option').addEventListener('click', () => {
            this.saveNodeSettings(option, modal);
            modalInstance.hide();
            modal.remove();
            this.renderNodeOptions(node);
        });
    }

    deleteOption(node, optionIndex, isNumeric = false) {
        if (confirm('Are you sure you want to delete this option?')) {
            if (isNumeric) {
                node.settings.numeric_options[optionIndex] = null;
            } else {
                node.settings.options.splice(optionIndex, 1);
            }
            this.renderNodeOptions(node);
        }
    }
}