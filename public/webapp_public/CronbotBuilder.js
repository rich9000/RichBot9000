// CronbotBuilder.js - Visual builder for cronbots

class CronbotBuilder {
    constructor(containerId) {
        console.log('Initializing CronbotBuilder with container:', containerId);
        
        // Initialize container
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found:', containerId);
            return;
        }
        console.log('Container found:', this.container);

        // Initialize NodeFactory
        if (typeof window.NodeFactory === 'undefined') {
            console.error('NodeFactory not found on window object. Ensure NodeFactory.js is loaded before CronbotBuilder.js');
            return;
        }
        this.nodeFactory = window.NodeFactory;
        console.log('NodeFactory initialized');

        // Initialize state
        this.nodes = [];
        this.assistants = [];
        this.availableTools = []; // Available tools from API
        this.workflowTools = []; // Tools added to the workflow
        this.isEditMode = true;
        this.nodeUiState = {}; // Track expanded/collapsed state for each node

        // Initialize UI
        this.initializeUI();
        
        // Load required data
        this.initialize().catch(error => {
            console.error('Failed to initialize CronbotBuilder:', error);
            toastr.error('Failed to initialize builder');
        });
    }

    async initialize() {
        console.log('Starting initialization...');
        try {
            // Load all required data in parallel
            await Promise.all([
                this.loadAssistants(),
                this.loadTools()
            ]);
            
            console.log('All data loaded successfully');
            this.renderNodes();
        } catch (error) {
            console.error('Error during initialization:', error);
            throw error;
        }
    }

    initializeUI() {
        // Initialize cronbot name and description inputs
        const cronbotName = this.container.querySelector('#cronbotName');
        if (cronbotName) {
            cronbotName.addEventListener('change', (e) => {
                this.updateCronbotName(e.target.value);
                this.updateDebugInfo('cronbot');
            });
        }

        const cronbotDescription = this.container.querySelector('#cronbotDescription');
        if (cronbotDescription) {
            cronbotDescription.addEventListener('change', (e) => {
                this.updateCronbotDescription(e.target.value);
                this.updateDebugInfo('cronbot');
            });
        }

        // Add event listeners for other form fields that should trigger debug updates
        const cronbotAssistantId = this.container.querySelector('#cronbotAssistantId');
        if (cronbotAssistantId) {
            cronbotAssistantId.addEventListener('change', () => {
                this.updateDebugInfo('cronbot');
            });
        }

        const cronbotPrompt = this.container.querySelector('#cronbotPrompt');
        if (cronbotPrompt) {
            cronbotPrompt.addEventListener('input', () => {
                this.updateDebugInfo('cronbot');
            });
        }

        // Save cronbot button
        const saveCronbotBtn = this.container.querySelector('.save-cronbot');
        if (saveCronbotBtn) {
            saveCronbotBtn.addEventListener('click', () => {
                this.saveCronbot();
            });
        }

        // Back to list button
        const backToListBtn = this.container.querySelector('.btn-outline-secondary');
        if (backToListBtn) {
            backToListBtn.addEventListener('click', () => {
                this.showCronbotList();
            });
        }

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
            this.availableTools = Array.isArray(result) ? result : (result.tools || []);
            console.log('[loadTools] tools:', this.availableTools);
        } catch (error) {
            console.error('Error loading tools:', error);
            toastr.error('Failed to load tools');
        }
    }

    showCronbotList() {
        // Redirect to cronbot list page
        window.location.href = '/webapp/cronbot';
    }

    async saveCronbot() {
        try {
            const frequencyType = document.querySelector('input[name="frequencyType"]:checked')?.value;
            const isSchedulingEnabled = document.getElementById('isRepeating')?.checked;
            
            const cronbotData = {
                name: document.getElementById('cronbotName').value,
                description: document.getElementById('cronbotDescription').value,
                assistant_id: document.getElementById('cronbotAssistantId').value,
                prompt: document.getElementById('cronbotPrompt').value,
                tools: this.workflowTools.map(tool => ({
                    id: tool.id,
                    name: tool.name,
                    parameters: tool.parameterValues || {}
                })),
                // Scheduling data
                is_repeating: isSchedulingEnabled && frequencyType !== 'oneTime',
                is_active: document.getElementById('isActive')?.checked || false,
                schedule: (isSchedulingEnabled && frequencyType !== 'oneTime') ? this.generateCronExpression() : null,
                next_run_at: document.getElementById('nextRunDate')?.value + 'T' + document.getElementById('nextRunTime')?.value,
                end_at: document.getElementById('endAt')?.value || null,
                scheduling_metadata: {
                    frequency_type: frequencyType,
                    is_scheduling_enabled: isSchedulingEnabled,
                    human_readable_schedule: this.getHumanReadableSchedule(frequencyType),
                    cron_expression: (isSchedulingEnabled && frequencyType !== 'oneTime') ? this.generateCronExpression() : null
                }
            };

            console.log('[Builder] saveCronbot: cronbotData', JSON.stringify(cronbotData));

            const response = await fetch('/api/scheduled-cronbots', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(cronbotData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save cronbot');
            }

            const result = await response.json();
            console.log('[Builder] saveCronbot: result', result);
            
            toastr.success('Cronbot saved successfully!');
            
            // Optionally redirect to the cronbot list
            setTimeout(() => {
            this.showCronbotList();
            }, 1500);
            
        } catch (error) {
            console.error('Error saving cronbot:', error);
            toastr.error('Failed to save cronbot: ' + error.message);
        }
    }

    updateCronbotName(value) {
        document.querySelector('.cronbot-title').textContent = 'Cronbot: ' + (value || 'New Cronbot');
    }

    updateCronbotDescription(value) {
        // Just store it, will be saved with the cronbot
    }

    reset() {
        this.workflowTools = [];
        document.getElementById('cronbotName').value = '';
        document.getElementById('cronbotDescription').value = '';
        document.getElementById('cronbotAssistantId').value = '';
        document.getElementById('cronbotPrompt').value = '';
        document.querySelector('.cronbot-title').textContent = 'Cronbot: New Cronbot';
        
        this.renderNodes();
    }

    addNode(nodeType, nodeSubtype, position = null) {
        console.log('Adding node:', { type: nodeType, subtype: nodeSubtype, position });
        
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
            if (position !== null && position >= 0 && position <= this.nodes.length) {
                this.nodes.splice(position, 0, node);
                newIndex = position;
            } else {
                this.nodes.push(node);
                newIndex = this.nodes.length - 1;
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
            data: {
                assistantTool: {
                    searchField: 'phone'
                }
            },
            action: {
                assistantTool: {
                    assistantId: null,
                    prompt: ''
                }
            },
            decision: {
                assistantTool: {
                    assistantId: null,
                    prompt: ''
                }
            }
        };

        return defaults[nodeType]?.[nodeSubtype] || {};
    }

    renderNodes() {
        console.log('Rendering tools...');
        const container = this.container.querySelector('.flow-container');
        if (!container) {
            console.error('Flow container not found');
            return;
        }

        try {
            let html = '';
            
            // Display existing tools in a simple list
            if (this.workflowTools && this.workflowTools.length > 0) {
                html += `
                    <div class="tools-section" style="margin-bottom: 2rem;">
                        <h5 class="mb-3">
                            <i class="fas fa-tools text-primary"></i>
                            Tools in Workflow
                        </h5>
                        <div class="tools-list">
                `;
                
                this.workflowTools.forEach((tool, index) => {
                    html += this.renderToolItem(tool, index);
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Add a simple "Add Tool" section
            html += `
                <div class="add-tool-section" style="margin: 2rem auto 0 auto; padding: 2rem; border: 1px solid #dee2e6; border-radius: 12px; background: #f8f9fa; text-align: center; min-height: 80px; max-width: 600px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;">
                    <button class="btn btn-primary btn-lg" id="addToolToWorkflowBtn">
                        <i class="fas fa-plus"></i> Add Tool
                    </button>
                    <small class="text-muted">Click to add a tool to the workflow</small>
                </div>
            `;
            
            container.innerHTML = html;
            this.setupToolEventListeners();
            
            // Update debug info after rendering
            this.updateDebugInfo('state');
            
            console.log('Tools rendered successfully');
        } catch (error) {
            console.error('Error rendering tools:', error);
            toastr.error('Failed to render tools');
        }
    }

    renderToolItem(tool, index) {
        const toolData = this.workflowTools[index] || {};
        const isEditing = toolData.isEditing || false;
        
        if (isEditing) {
            return `
                <div class="tool-item card mb-3" data-tool-index="${index}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Edit Tool: ${tool.name}</h6>
                        <div>
                            <button class="btn btn-sm btn-success save-tool-btn" data-tool-index="${index}">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button class="btn btn-sm btn-secondary cancel-edit-btn" data-tool-index="${index}">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Tool Name</label>
                                <p class="form-control-plaintext">${tool.name}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <p class="form-control-plaintext">${tool.description || 'No description'}</p>
                            </div>
                        </div>
                        ${this.renderToolParameters(tool, index)}
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="tool-item card mb-3" data-tool-index="${index}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-tool text-primary"></i>
                                ${tool.name}
                            </h6>
                            <small class="text-muted">${tool.description || 'No description'}</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary edit-tool-btn" data-tool-index="${index}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger remove-tool-btn" data-tool-index="${index}">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        ${this.renderToolInfo(tool, index)}
                    </div>
                </div>
            `;
        }
    }

    renderToolInfo(tool, index) {
        const toolData = this.workflowTools[index] || {};
        const parameterValues = toolData.parameterValues || {};
        
        let html = '<div class="row">';
        
        // Show current parameter values
        if (tool.parameters && tool.parameters.length > 0) {
            html += '<div class="col-12"><h6>Parameters:</h6></div>';
            tool.parameters.forEach(param => {
                const value = parameterValues[param.name] || 'Not set';
                html += `
                    <div class="col-md-6 mb-2">
                        <strong>${param.name}:</strong> 
                        <span class="text-muted">${value}</span>
                        ${param.required ? '<span class="text-danger">*</span>' : ''}
                    </div>
                `;
            });
        }
        
        if (tool.returns) {
            html += `
                <div class="col-12 mt-2">
                    <strong>Returns:</strong> <span class="text-muted">${tool.returns}</span>
                </div>
            `;
        }
        
        html += '</div>';
        return html;
    }

    renderToolParameters(tool, index) {
        const toolData = this.workflowTools[index] || {};
        const parameterValues = toolData.parameterValues || {};
        
        if (!tool.parameters || tool.parameters.length === 0) {
            return '<p class="text-muted">This tool has no configurable parameters.</p>';
        }
        
        let html = '<div class="row mt-3"><div class="col-12"><h6>Parameters:</h6></div>';
        
        tool.parameters.forEach(param => {
            const value = parameterValues[param.name] || '';
            html += `
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        ${param.name}
                        ${param.required ? '<span class="text-danger">*</span>' : ''}
                    </label>
                    <input type="text" 
                           class="form-control tool-param-input" 
                           data-tool-index="${index}"
                           data-param-name="${param.name}"
                           value="${value}"
                           placeholder="${param.description || param.name}">
                    <small class="form-text text-muted">
                        ${param.description || ''} (${param.type || 'string'})
                    </small>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    setupToolEventListeners() {
        // Edit tool button
        this.container.querySelectorAll('.edit-tool-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(btn.dataset.toolIndex);
                this.editTool(index);
            });
        });
        
        // Save tool button
        this.container.querySelectorAll('.save-tool-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(btn.dataset.toolIndex);
                this.saveTool(index);
            });
        });
        
        // Cancel edit button
        this.container.querySelectorAll('.cancel-edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(btn.dataset.toolIndex);
                this.cancelEditTool(index);
            });
        });
        
        // Remove tool button
        this.container.querySelectorAll('.remove-tool-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(btn.dataset.toolIndex);
                this.removeTool(index);
            });
        });
        
        // Tool parameter input changes
        this.container.querySelectorAll('.tool-param-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const toolIndex = parseInt(e.target.dataset.toolIndex);
                const paramName = e.target.dataset.paramName;
                const value = e.target.value;
                this.updateToolParameter(toolIndex, paramName, value);
            });
        });

        // Add Tool button event listener
        const addToolBtn = this.container.querySelector('#addToolToWorkflowBtn');
        if (addToolBtn) {
            addToolBtn.addEventListener('click', () => {
                this.showToolSelectionModal();
            });
        }
    }

    editTool(index) {
        if (!this.workflowTools[index]) return;
        
        // Set editing state
        this.workflowTools[index].isEditing = true;
        this.renderNodes();
    }

    saveTool(index) {
        if (!this.workflowTools[index]) return;
        
        // Clear editing state
        this.workflowTools[index].isEditing = false;
        this.renderNodes();
        toastr.success('Tool saved successfully');
    }

    cancelEditTool(index) {
        if (!this.workflowTools[index]) return;
        
        // Clear editing state
        this.workflowTools[index].isEditing = false;
        this.renderNodes();
    }

    removeTool(index) {
        if (!this.workflowTools[index]) return;
        
        showConfirmDeleteModal(() => {
            this.workflowTools.splice(index, 1);
            this.renderNodes();
            this.updateDebugInfo('tools');
            toastr.success('Tool removed from workflow');
        });
    }

    updateToolParameter(toolIndex, paramName, value) {
        if (!this.workflowTools[toolIndex]) return;
        
        if (!this.workflowTools[toolIndex].parameterValues) {
            this.workflowTools[toolIndex].parameterValues = {};
        }
        this.workflowTools[toolIndex].parameterValues[paramName] = value;
        
        // Update debug info when tool parameters change
        this.updateDebugInfo('tools');
    }

    showToolSelectionModal() {
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
                            <button type="button" class="btn btn-primary select-tool-btn" data-tool-id="${tool.id}">
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
        renderTools(this.availableTools);

        // Add event listeners for tool selection buttons
        toolList.querySelectorAll('.select-tool-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const toolId = e.target.dataset.toolId;
                this.selectTool(toolId);
            });
        });

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

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    selectTool(toolId) {
        const modal = document.getElementById('tool-selection-modal');
        if (!modal) return;

        // Find the tool by ID from available tools
        const tool = this.availableTools.find(t => t.id == toolId);
        if (!tool) {
            console.error('Tool not found:', toolId);
            return;
        }

        // Add the tool to the workflow tools array
        const workflowTool = {
            ...tool,
            parameterValues: {}, // Store user input values
            isEditing: false
        };
        
        this.workflowTools.push(workflowTool);

        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }

        this.renderNodes();
        this.updateDebugInfo('tools');
        toastr.success(`Tool "${tool.name}" added to workflow`);
    }

    removeToolFromNode(nodeIndex, toolIndex) {
        const node = this.nodes[nodeIndex];
        if (!node || !node.content.tools) return;

        node.content.tools.splice(toolIndex, 1);
        this.renderNodes();
    }

    updateToolPathStateKey(nodeIndex, toolIndex, value) {
        // This method is no longer needed with the new tool system
        console.warn('updateToolPathStateKey is deprecated');
    }

    addToolToWorkflow() {
        // This method is no longer needed - use showToolSelectionModal instead
        this.showToolSelectionModal();
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
            if (section === 'all' || section === 'cronbot') {
                // Update cronbot data
                const cronbotDebug = this.container.querySelector('#cronbotDebug');
                if (cronbotDebug) {
                    const frequencyType = document.querySelector('input[name="frequencyType"]:checked')?.value;
                    const isSchedulingEnabled = document.getElementById('isRepeating')?.checked;
                    
                    const cronbotData = {
                        name: document.getElementById('cronbotName')?.value,
                        description: document.getElementById('cronbotDescription')?.value,
                        assistant_id: document.getElementById('cronbotAssistantId')?.value,
                        prompt: document.getElementById('cronbotPrompt')?.value,
                        tools: this.workflowTools.map(tool => ({
                            id: tool.id,
                            name: tool.name,
                            parameters: tool.parameterValues || {}
                        })),
                        // Scheduling data
                        is_repeating: isSchedulingEnabled && frequencyType !== 'oneTime',
                        is_active: document.getElementById('isActive')?.checked || false,
                        schedule: (isSchedulingEnabled && frequencyType !== 'oneTime') ? this.generateCronExpression() : null,
                        next_run_at: document.getElementById('nextRunDate')?.value + 'T' + document.getElementById('nextRunTime')?.value,
                        end_at: document.getElementById('endAt')?.value || null,
                        scheduling_metadata: {
                            frequency_type: frequencyType,
                            is_scheduling_enabled: isSchedulingEnabled,
                            human_readable_schedule: this.getHumanReadableSchedule(frequencyType),
                            cron_expression: (isSchedulingEnabled && frequencyType !== 'oneTime') ? this.generateCronExpression() : null
                        }
                    };
                    cronbotDebug.textContent = JSON.stringify(cronbotData, null, 2);
                }
            }

            if (section === 'all' || section === 'tools') {
                // Update tools data
                const toolsDebug = this.container.querySelector('#toolsDebug');
                if (toolsDebug) {
                    const toolsData = this.workflowTools.map(tool => ({
                        id: tool.id,
                        name: tool.name,
                        description: tool.description,
                        parameters: tool.parameterValues || {},
                        returns: tool.returns
                    }));
                    toolsDebug.textContent = JSON.stringify(toolsData, null, 2);
                }
            }

            if (section === 'all' || section === 'saveData') {
                // Update what would be posted when saving
                const saveDataDebug = this.container.querySelector('#saveDataDebug');
                if (saveDataDebug) {
                    const frequencyType = document.querySelector('input[name="frequencyType"]:checked')?.value;
                    const isSchedulingEnabled = document.getElementById('isRepeating')?.checked;
                    
                    const saveData = {
                        name: document.getElementById('cronbotName')?.value,
                        description: document.getElementById('cronbotDescription')?.value,
                        assistant_id: document.getElementById('cronbotAssistantId')?.value,
                        prompt: document.getElementById('cronbotPrompt')?.value,
                        tools: this.workflowTools.map(tool => ({
                            id: tool.id,
                            name: tool.name,
                            parameters: tool.parameterValues || {}
                        })),
                        // Scheduling data
                        is_repeating: isSchedulingEnabled && frequencyType !== 'oneTime',
                        is_active: document.getElementById('isActive')?.checked || false,
                        schedule: (isSchedulingEnabled && frequencyType !== 'oneTime') ? this.generateCronExpression() : null,
                        next_run_at: document.getElementById('nextRunDate')?.value + 'T' + document.getElementById('nextRunTime')?.value,
                        end_at: document.getElementById('endAt')?.value || null,
                        scheduling_metadata: {
                            frequency_type: frequencyType,
                            is_scheduling_enabled: isSchedulingEnabled,
                            human_readable_schedule: this.getHumanReadableSchedule(frequencyType),
                            cron_expression: (isSchedulingEnabled && frequencyType !== 'oneTime') ? this.generateCronExpression() : null
                        }
                    };
                    saveDataDebug.textContent = JSON.stringify(saveData, null, 2);
                }
            }

            if (section === 'all' || section === 'state') {
                // Update state data
                const stateDebug = this.container.querySelector('#stateDebug');
                if (stateDebug) {
                    const state = {
                        availableTools: this.availableTools.length,
                        workflowTools: this.workflowTools.length,
                        assistants: this.assistants.length,
                        isEditMode: this.isEditMode
                    };
                    stateDebug.textContent = JSON.stringify(state, null, 2);
                }
            }
        } catch (error) {
            console.error('Error updating debug info:', error);
        }
    }

    // Helper functions for scheduling
    generateCronExpression() {
        const frequencyType = document.querySelector('input[name="frequencyType"]:checked')?.value;
        
        switch (frequencyType) {
            case 'oneTime':
                return null;
            case 'hourly':
                return this.generateHourlyCron();
            case 'daily':
                return this.generateDailyCron();
            case 'weekly':
                return this.generateWeeklyCron();
            case 'monthly':
                return this.generateMonthlyCron();
            case 'custom':
                return document.getElementById('cronExpression')?.value;
            default:
                return '0 * * * *';
        }
    }

    generateHourlyCron() {
        const interval = document.getElementById('hourlyInterval')?.value;
        const minuteValue = document.getElementById('hourlyMinute')?.value;
        
        if (interval === '1') {
            return `${minuteValue} * * * *`;
        } else {
            return `${minuteValue} */${interval} * * *`;
        }
    }

    generateDailyCron() {
        const timeSelect = document.getElementById('dailyTime')?.value;
        let time = timeSelect;
        
        if (timeSelect === 'custom') {
            time = document.getElementById('dailyCustomTimeInput')?.value;
        }
        
        const [hourDaily, minuteDaily] = time.split(':');
        return `${minuteDaily} ${hourDaily} * * *`;
    }

    generateWeeklyCron() {
        const day = document.getElementById('weeklyDay')?.value;
        const timeSelectWeekly = document.getElementById('weeklyTime')?.value;
        let timeWeekly = timeSelectWeekly;
        
        if (timeSelectWeekly === 'custom') {
            timeWeekly = document.getElementById('weeklyCustomTimeInput')?.value;
        }
        
        const [hourWeekly, minuteWeekly] = timeWeekly.split(':');
        return `${minuteWeekly} ${hourWeekly} * * ${day}`;
    }

    generateMonthlyCron() {
        const day = document.getElementById('monthlyDay')?.value;
        const timeSelectMonthly = document.getElementById('monthlyTime')?.value;
        let timeMonthly = timeSelectMonthly;
        
        if (timeSelectMonthly === 'custom') {
            timeMonthly = document.getElementById('monthlyCustomTimeInput')?.value;
        }
        
        const [hourMonthly, minuteMonthly] = timeMonthly.split(':');
        
        if (day === 'last') {
            return `${minuteMonthly} ${hourMonthly} 28 * *`;
        } else if (day === 'custom') {
            const customDay = document.getElementById('monthlyCustomDayInput')?.value;
            return `${minuteMonthly} ${hourMonthly} ${customDay} * *`;
        } else {
            return `${minuteMonthly} ${hourMonthly} ${day} * *`;
        }
    }

    getHumanReadableSchedule(frequencyType) {
        switch (frequencyType) {
            case 'oneTime':
                return 'One-time execution';
            case 'hourly':
                const interval = document.getElementById('hourlyInterval')?.value;
                const minuteValue = document.getElementById('hourlyMinute')?.value;
                const minuteText = minuteValue === '0' ? 'at the start of the hour' : `at minute ${minuteValue}`;
                return interval === '1' ? `Every hour ${minuteText}` : `Every ${interval} hours ${minuteText}`;
            case 'daily':
                const timeSelect = document.getElementById('dailyTime')?.value;
                let time = timeSelect;
                if (timeSelect === 'custom') {
                    time = document.getElementById('dailyCustomTimeInput')?.value;
                }
                const [hourDaily, minuteDaily] = time.split(':');
                const timeStr = this.formatTime(hourDaily, minuteDaily);
                return `Daily at ${timeStr}`;
            case 'weekly':
                const day = document.getElementById('weeklyDay')?.value;
                const timeSelectWeekly = document.getElementById('weeklyTime')?.value;
                let timeWeekly = timeSelectWeekly;
                if (timeSelectWeekly === 'custom') {
                    timeWeekly = document.getElementById('weeklyCustomTimeInput')?.value;
                }
                const [hourWeekly, minuteWeekly] = timeWeekly.split(':');
                const timeStrWeekly = this.formatTime(hourWeekly, minuteWeekly);
                const dayStr = this.getDayName(day);
                return `Weekly on ${dayStr} at ${timeStrWeekly}`;
            case 'monthly':
                const dayMonthly = document.getElementById('monthlyDay')?.value;
                const timeSelectMonthly = document.getElementById('monthlyTime')?.value;
                let timeMonthly = timeSelectMonthly;
                if (timeSelectMonthly === 'custom') {
                    timeMonthly = document.getElementById('monthlyCustomTimeInput')?.value;
                }
                const [hourMonthly, minuteMonthly] = timeMonthly.split(':');
                const timeStrMonthly = this.formatTime(hourMonthly, minuteMonthly);
                const dayStrMonthly = this.getMonthlyDayName(dayMonthly);
                return `Monthly on ${dayStrMonthly} at ${timeStrMonthly}`;
            case 'custom':
                const cronExpression = document.getElementById('cronExpression')?.value;
                return this.getHumanReadableCron(cronExpression) || 'Custom schedule';
            default:
                return 'Unknown schedule';
        }
    }

    formatTime(hour, minute) {
        const hourNum = parseInt(hour);
        const minuteNum = parseInt(minute);
        
        if (hourNum === 0) {
            return minuteNum === 0 ? 'midnight' : `12:${minute.toString().padStart(2, '0')} AM`;
        } else if (hourNum === 12) {
            return minuteNum === 0 ? 'noon' : `12:${minute.toString().padStart(2, '0')} PM`;
        } else if (hourNum > 12) {
            return `${hourNum - 12}:${minute.toString().padStart(2, '0')} PM`;
        } else {
            return `${hourNum}:${minute.toString().padStart(2, '0')} AM`;
        }
    }

    getDayName(day) {
        const days = {
            '0': 'Sunday',
            '1': 'Monday',
            '2': 'Tuesday',
            '3': 'Wednesday',
            '4': 'Thursday',
            '5': 'Friday',
            '6': 'Saturday',
            '1-5': 'weekdays (Monday-Friday)',
            '0,6': 'weekends (Saturday-Sunday)'
        };
        return days[day] || day;
    }

    getMonthlyDayName(day) {
        if (day === 'last') {
            return 'the last day';
        } else if (day === 'custom') {
            const customDay = document.getElementById('monthlyCustomDayInput')?.value;
            return `the ${customDay}${this.getOrdinalSuffix(customDay)}`;
        } else {
            return `the ${day}${this.getOrdinalSuffix(day)}`;
        }
    }

    getOrdinalSuffix(day) {
        const dayNum = parseInt(day);
        if (dayNum >= 11 && dayNum <= 13) return 'th';
        switch (dayNum % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    }

    getHumanReadableCron(cronExpression) {
        if (!cronExpression) return null;
        
        const parts = cronExpression.split(' ');
        if (parts.length !== 5) return null;
        
        const [minute, hour, day, month, dayOfWeek] = parts;
        
        let description = '';
        
        // Minute
        if (minute === '*') {
            description += 'Every minute';
        } else if (minute === '0') {
            description += 'At the start of the hour';
        } else {
            description += `At minute ${minute}`;
        }
        
        // Hour
        if (hour === '*') {
            description += ' of every hour';
        } else if (hour === '0') {
            description += ' at midnight';
        } else if (hour === '12') {
            description += ' at noon';
        } else {
            description += ` at ${hour}:00`;
        }
        
        // Day
        if (day !== '*') {
            description += ` on day ${day}`;
        }
        
        // Month
        if (month !== '*') {
            const months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
            description += ` in ${months[parseInt(month)]}`;
        }
        
        // Day of week
        if (dayOfWeek !== '*') {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            if (dayOfWeek.includes(',')) {
                const dayNums = dayOfWeek.split(',');
                const dayNames = dayNums.map(d => days[parseInt(d)]).join(', ');
                description += ` on ${dayNames}`;
            } else if (dayOfWeek === '1-5') {
                description += ' on weekdays';
            } else if (dayOfWeek === '0,6') {
                description += ' on weekends';
            } else {
                description += ` on ${days[parseInt(dayOfWeek)]}`;
            }
        }
        
        return description;
    }
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

