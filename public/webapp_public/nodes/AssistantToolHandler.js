class AssistantToolHandler {
    constructor(node) {
        this.node = node;
    }

    renderToolList(tools, nodeIndex) {
        let content = '';
        
        if (!this.node.content.tools || !this.node.content.tools.length) {
            content = `<div class="text-muted mb-2">No tools configured</div>`;
        } else {
            content = `
                <div class="tool-list" style="max-height: 300px; overflow-y: auto;">
                    ${this.node.content.tools.map((tool, toolIndex) => {
                        const toolInfo = tools.find(t => t.id === tool.toolId);
                        if (!toolInfo) return '';

                        // Determine which builder to use based on context
                        const builder = window.cronbotBuilder || window.pathBuilder;

                        return `
                            <div class="card mb-3 tool-card">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="card-title mb-1">${toolInfo.name}</h5>
                                            <p class="card-text text-muted mb-2">${toolInfo.description || ''}</p>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="${builder === window.cronbotBuilder ? 'window.cronbotBuilder' : 'window.pathBuilder'}.removeToolFromNode(${nodeIndex}, ${toolIndex})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    ${toolInfo.parameters && toolInfo.parameters.length > 0 ? `
                                        <div class="tool-parameters mt-2">
                                            <small class="text-muted d-block mb-1">Parameters:</small>
                                            <div class="d-flex flex-wrap gap-2">
                                                ${toolInfo.parameters.map(param => `
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">${param.name}${param.required ? ' *' : ''}</span>
                                                        <input type="text" class="form-control" 
                                                               value="${tool.parameters[param.name] || ''}"
                                                               onchange="${builder === window.cronbotBuilder ? 'window.cronbotBuilder' : 'window.pathBuilder'}.updateToolParameter(${nodeIndex}, ${toolIndex}, '${param.name}', this.value)"
                                                               placeholder="${param.type || 'string'}">
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    ` : ''}
                                    <div class="tool-path-state mt-2">
                                        <small class="text-muted d-block mb-1">Path State Key:</small>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" 
                                                   value="${tool.pathStateKey || ''}"
                                                   onchange="${builder === window.cronbotBuilder ? 'window.cronbotBuilder' : 'window.pathBuilder'}.updateToolPathStateKey(${nodeIndex}, ${toolIndex}, this.value)"
                                                   placeholder="Enter path state key">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        // Determine which builder to use based on context
        const builder = window.cronbotBuilder || window.pathBuilder;

        return `
            ${content}
            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="${builder === window.cronbotBuilder ? 'window.cronbotBuilder' : 'window.pathBuilder'}.addToolToNode(${nodeIndex})">
                <i class="fas fa-plus"></i> Add Tool
            </button>
            <style>
                .tool-list::-webkit-scrollbar {
                    width: 6px;
                }
                .tool-list::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 3px;
                }
                .tool-list::-webkit-scrollbar-thumb {
                    background: #888;
                    border-radius: 3px;
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
                .tool-card .input-group {
                    margin-bottom: 0.5rem;
                }
                .tool-card .input-group-text {
                    font-size: 0.8rem;
                    padding: 0.25rem 0.5rem;
                }
                .tool-card .form-control {
                    font-size: 0.9rem;
                    padding: 0.25rem 0.5rem;
                }
                .tool-parameters, .tool-path-state {
                    font-size: 0.85rem;
                }
                .tool-card .text-muted {
                    color: #6c757d !important;
                }
            </style>
        `;
    }

    renderConditionField(tool, nodeIndex, toolIndex) {
        return `
            <div class="mb-3">
                <label class="form-label">Condition</label>
                <select class="form-select" onchange="window.pathBuilder.updateToolCondition(${nodeIndex}, ${toolIndex}, this.value)">
                    <option value="success" ${tool.condition === 'success' ? 'selected' : ''}>Success</option>
                    <option value="failure" ${tool.condition === 'failure' ? 'selected' : ''}>Failure</option>
                </select>
                <small class="text-muted">When this condition is met, follow this path</small>
            </div>
        `;
    }

    renderToolParameters(toolInfo, tool, nodeIndex, toolIndex) {
        if (!toolInfo.parameters || !toolInfo.parameters.length) {
            return '<div class="text-muted">No parameters required</div>';
        }

        return toolInfo.parameters.map(param => {
            const value = tool.parameters?.[param.name] || '';
            return `
                <div class="mb-2">
                    <label class="form-label">
                        ${param.name}
                        ${param.required ? '<span class="text-danger">*</span>' : ''}
                        <small class="text-muted">${param.description}</small>
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               value="${value}"
                               onchange="window.pathBuilder.updateToolParameter(${nodeIndex}, ${toolIndex}, '${param.name}', this.value)">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.pathBuilder.showPathStateSelector(${nodeIndex}, ${toolIndex}, '${param.name}')">
                            <i class="fas fa-database"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    validate() {
        console.log('[AssistantToolHandler] Starting validation');
        console.log('[AssistantToolHandler] Content:', JSON.stringify(this.node.content, null, 2));
        
        // Must have at least one tool configured
        if (!this.node.content.tools || !this.node.content.tools.length) {
            console.log('[AssistantToolHandler] Validation failed: No tools configured');
            return false;
        }

        // Determine which builder to use based on context
        const builder = window.cronbotBuilder || window.pathBuilder;
        console.log('[AssistantToolHandler] Available tools:', builder?.tools);
        
        // Check that all required parameters for each tool are filled out
        const isValid = this.node.content.tools.every(tool => {
            console.log('[AssistantToolHandler] Validating tool:', tool);
            
            // Get tool info from the tools array in the context
            const toolInfo = builder?.tools?.find(t => t.id === tool.toolId);
            console.log('[AssistantToolHandler] Found tool info:', toolInfo);
            
            if (!toolInfo) {
                console.log('[AssistantToolHandler] Tool info not found for toolId:', tool.toolId);
                return false;
            }

            // If tool has no parameters, it's valid
            if (!toolInfo.parameters || !toolInfo.parameters.length) {
                console.log('[AssistantToolHandler] Tool has no parameters, considering valid');
                return true;
            }

            console.log('[AssistantToolHandler] Tool parameters:', toolInfo.parameters);
            console.log('[AssistantToolHandler] Tool parameter values:', tool.parameters);

            // Check that all required parameters are filled out
            const paramsValid = toolInfo.parameters.every(param => {
                const isRequired = param.required;
                const hasValue = tool.parameters && tool.parameters[param.name] !== undefined && tool.parameters[param.name] !== '';
                
                if (isRequired && !hasValue) {
                    console.log(`[AssistantToolHandler] Required parameter ${param.name} is missing`);
                    return false;
                }
                
                return true;
            });
            
            console.log('[AssistantToolHandler] Tool validation result:', paramsValid);
            return paramsValid;
        });
        
        console.log('[AssistantToolHandler] Overall validation result:', isValid);
        return isValid;
    }

    addTool(toolId) {
        if (!this.node.content.tools) this.node.content.tools = [];
        const tool = {
            toolId: parseInt(toolId),
            parameters: {}
        };
        
        // Add condition for decision nodes
        if (this.node.type === 'decision') {
            tool.condition = 'success';
        }
        
        this.node.content.tools.push(tool);
    }

    removeTool(index) {
        if (this.node.content.tools) {
            this.node.content.tools.splice(index, 1);
        }
    }

    updateToolParameter(toolIndex, paramName, value) {
        if (this.node.content.tools && this.node.content.tools[toolIndex]) {
            if (!this.node.content.tools[toolIndex].parameters) {
                this.node.content.tools[toolIndex].parameters = {};
            }
            this.node.content.tools[toolIndex].parameters[paramName] = value;
        }
    }

    updateToolCondition(toolIndex, value) {
        if (this.node.type === 'decision' && this.node.content.tools && this.node.content.tools[toolIndex]) {
            this.node.content.tools[toolIndex].condition = value;
        }
    }
}

if (typeof window !== 'undefined') {
    window.AssistantToolHandler = AssistantToolHandler;
} 