class AssistantToolActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'assistantTool' });
        this.type = 'action';
        this.subtype = 'assistantTool';
        this.icon = 'fa-bolt';
        this.name = 'Assistant Tool';
        this.description = 'Execute an assistant tool';
        this.toolHandler = new AssistantToolHandler(this);
    }

    getNodeInfo(context = {}) {
        console.log('[AssistantToolActionNode] getNodeInfo context:', context);
        if (!this.content.tools || !this.content.tools.length) {
            return 'No tools configured';
        }

        // Get tools from context or builder
        const tools = context.tools || (window.cronbotBuilder || window.pathBuilder)?.tools || [];
        console.log('[AssistantToolActionNode] Available tools:', tools);

        return this.content.tools.map(tool => {
            const toolInfo = tools.find(t => t.id === tool.toolId);
            console.log('[AssistantToolActionNode] Tool info for', tool.toolId, ':', toolInfo);
            return toolInfo ? toolInfo.name : 'Unknown Tool';
        }).join(', ');
    }

    getDetailsHtml(nodeIndex, context = {}) {
        let html = '';
        if (!this.content.tools || !this.content.tools.length) {
            html = '<div>No tools configured</div>';
        } else {
            html = this.content.tools.map(tool => {
                const toolInfo = context.tools?.find(t => t.id === tool.toolId);
                if (!toolInfo) return '<div>Unknown Tool</div>';

                let toolHtml = `<div><strong>${toolInfo.name}</strong>`;
                if (toolInfo.description) {
                    toolHtml += `<br><small class="text-muted">${toolInfo.description}</small>`;
                }
                
                // Add parameters info
                if (tool.parameters && Object.keys(tool.parameters).length > 0) {
                    toolHtml += '<br>Parameters:';
                    Object.entries(tool.parameters).forEach(([key, value]) => {
                        toolHtml += `<br>- ${key}: ${value}`;
                    });
                }
                
                toolHtml += '</div>';
                return toolHtml;
            }).join('<hr>');
        }

        // Add edit button
        html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        
        return html;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const tools = context.tools || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Tools</div>
                    <div class="settings-field">
                        ${this.toolHandler.renderToolList(tools, nodeIndex)}
                    </div>
                </div>
            </div>
            <button class="btn btn-sm btn-success save-node mt-2" type="button">
                <i class="fas fa-save"></i> Save
            </button>
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'tools') {
            this.content.tools = value;
        }
    }

    addTool(toolId) {
        this.toolHandler.addTool(toolId);
    }

    removeTool(index) {
        this.toolHandler.removeTool(index);
    }

    updateToolParameter(toolIndex, paramName, value) {
        this.toolHandler.updateToolParameter(toolIndex, paramName, value);
    }

    validate() {
        return this.toolHandler.validate();
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        return `
            <div class="conversation-node card mb-3 action assistantTool" data-node-index="${nodeIndex}" style="max-width:540px;margin-left:auto;margin-right:auto;">
                <div class="card-header node-header node-header-action-grid" style="display:grid;grid-template-columns:1fr 2fr 1fr;grid-template-rows:38px;align-items:center;gap:0.25rem;width:100%;background:#f8f9fa;border-bottom:1px solid #dee2e6;border-radius:8px 8px 0 0;">
                    <div class="node-header-action-cell node-header-action-icon" style="grid-row:1;grid-column:1;display:flex;align-items:center;gap:0.5rem;">
                        <i class="fas ${this.icon}"></i>
                        <span class="node-title">${this.name}</span>
                    </div>
                    <div class="node-header-action-cell node-header-action-info" style="grid-row:1;grid-column:2;align-self:center;text-align:center;display:flex;align-items:center;justify-content:center;">
                        <span class="node-brief-info">${this.getNodeInfo(context)}</span>
                    </div>
                    <div class="node-header-action-cell node-header-action-controls" style="grid-row:1;grid-column:3;display:flex;flex-direction:row;gap:0.5rem;justify-content:flex-end;align-items:center;">
                        <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                        <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${nodeIndex}" title="Move Up" ${nodeIndex === 0 ? 'disabled' : ''}><i class="fas fa-arrow-up"></i></button>
                        <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${nodeIndex}" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="card-body node-body" style="display: none;">
                    <div class="node-detailed-info">
                        ${this.getDetailsHtml(nodeIndex, context)}
                    </div>
                    <form class="node-edit-form" style="display: none;">
                        ${this.getSettingsFormTemplate(nodeIndex, context)}
                    </form>
                </div>
            </div>
        `;
    }
}

if (typeof window !== 'undefined') {
    window.AssistantToolActionNode = AssistantToolActionNode;
} 