class DataNode extends BaseNode {
    static nodeTypes = {
        outageCheck: {
            icon: 'fa-bolt',
            name: 'Outage Check',
            color: '#dc3545',
            description: 'Check for service outages'
        },
        customerLookup: {
            icon: 'fa-user',
            name: 'Customer Lookup',
            color: '#17a2b8',
            description: 'Look up customer information'
        },
        custom: {
            icon: 'fa-database',
            name: 'Custom Data',
            color: '#6c757d',
            description: 'Custom data operation'
        },
        contextAssistant: {
            icon: 'fa-robot',
            name: 'Context Assistant',
            color: '#6f42c1',
            description: 'Use an AI assistant with context'
        },
        assistantTool: {
            icon: 'fa-tools',
            name: 'Assistant Tool',
            color: '#6f42c1',
            description: 'Execute an assistant tool and store results'
        }
    };

    constructor(data = {}) {
        super(data);
        this.type = 'data';
        this.icon = 'fa-database';
        this.name = 'Data';
        this.color = '#ffc107';
        this.description = 'Process or store data in the conversation';
        this.contextKey = data.contextKey || '';
        this.prompt = data.prompt || '';
        this.subtype = data.subtype || '';
        const nodeType = DataNode.nodeTypes[this.subtype] || {};
        this.script = data.script || nodeType.script || '';
        if (this.subtype === 'contextAssistant') {
            this.assistantId = data.assistantId || null;
        } else if (this.subtype === 'custom') {
            this.scriptId = data.scriptId || null;
        }
        // Ensure content is always an object
        if (!this.content || typeof this.content !== 'object' || Array.isArray(this.content)) {
            this.content = {};
        }
    }

    getNodeInfo(context = {}) {
        const assistants = context.assistants || [];
        const scripts = context.scripts || [];
        switch (this.subtype) {
            case 'contextAssistant': {
                const assistant = assistants.find(a => String(a.id) === String(this.content.assistantId));
                const contextKey = this.content.contextKey ? `Key: ${this.content.contextKey}` : 'No context key';
                const prompt = this.content.prompt ?
                    `Prompt: "${this.content.prompt.substring(0, 30)}${this.content.prompt.length > 30 ? '...' : ''}"` :
                    'No prompt set';
                return `${assistant ? `Assistant: ${assistant.name}` : 'No assistant selected'}<br>${contextKey}<br>${prompt}`;
            }
            case 'custom': {
                const script = scripts.find(s => String(s.id) === String(this.content.scriptId));
                const customContextKey = this.content.contextKey ? `Key: ${this.content.contextKey}` : 'No context key';
                return `${script ? `Script: ${script.name}` : 'No script selected'}<br>${customContextKey}`;
            }
            case 'outageCheck':
            case 'customerLookup': {
                const dataContextKey = this.content.contextKey ? `Key: ${this.content.contextKey}` : 'No context key';
                const dataPrompt = this.content.prompt ?
                    `Prompt: "${this.content.prompt.substring(0, 30)}${this.content.prompt.length > 30 ? '...' : ''}"` :
                    'No prompt set';
                return `${dataContextKey}<br>${dataPrompt}`;
            }
            default:
                return '';
        }
    }

    getSettingsFormTemplate(nodeIndex, assistants = [], scripts = [], context = {}) {
        // Support both legacy and context arg
        if (typeof assistants === 'object' && !Array.isArray(assistants) && assistants !== null && !scripts) {
            context = assistants;
            assistants = context.assistants || [];
            scripts = context.scripts || [];
        }
        switch (this.subtype) {
            case 'contextAssistant':
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Assistant</div>
                            <div class="settings-field">
                                <select class="form-control" name="assistantId"
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                                    <option value="">Select Assistant</option>
                                    ${(assistants || []).map(assistant => `
                                        <option value="${assistant.id}" ${this.assistantId === assistant.id ? 'selected' : ''}>
                                            ${assistant.name}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Context Key</div>
                            <div class="settings-field">
                                <input type="text" class="form-control" name="contextKey"
                                    value="${this.contextKey || ''}"
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'contextKey', this.value)"
                                    placeholder="Enter context key...">
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Prompt</div>
                            <div class="settings-field">
                                <textarea class="form-control" name="prompt" rows="3"
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'prompt', this.value)"
                                    placeholder="Enter prompt for context management...">${this.prompt || ''}</textarea>
                            </div>
                        </div>
                    </div>
                    ${super.getSettingsFormTemplate(nodeIndex, context)}
                `;
            case 'custom':
                // Let CustomDataNode handle the form
                return super.getSettingsFormTemplate(nodeIndex, context);
            case 'outageCheck':
            case 'customerLookup':
            default:
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Script</div>
                            <div class="settings-field">
                                <select class="form-control" name="script" disabled>
                                    <option value="${this.script}" selected>${this.name}</option>
                                </select>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Prompt</div>
                            <div class="settings-field">
                                <textarea class="form-control" name="prompt" rows="3"
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'prompt', this.value)"
                                    placeholder="Enter prompt for data processing...">${this.prompt || ''}</textarea>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-label">Context Key</div>
                            <div class="settings-field">
                                <input type="text" class="form-control" name="contextKey"
                                    value="${this.contextKey || ''}"
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'contextKey', this.value)"
                                    placeholder="Enter context key...">
                            </div>
                        </div>
                    </div>
                    ${super.getSettingsFormTemplate(nodeIndex, context)}
                `;
        }
    }

    updateContent(field, value) {
        switch (this.subtype) {
            case 'contextAssistant':
                if (field === 'assistantId') this.content.assistantId = value ? parseInt(value) : null;
                else if (field === 'contextKey') this.content.contextKey = value;
                else if (field === 'prompt') this.content.prompt = value;
                break;
            case 'custom':
                if (field === 'scriptId') this.content.scriptId = value ? parseInt(value) : null;
                else if (field === 'contextKey') this.content.contextKey = value;
                break;
            default:
                if (field === 'contextKey') this.content.contextKey = value;
                else if (field === 'prompt') this.content.prompt = value;
                break;
        }
    }

    validate() {
        switch (this.subtype) {
            case 'contextAssistant':
                return !!this.content.assistantId && !!this.content.contextKey;
            
            case 'custom':
                return !!this.content.scriptId && !!this.content.contextKey;
            
            default:
                return !!this.content.contextKey;
        }
    }

    toJSON() {
        const json = {
            ...super.toJSON(),
            contextKey: this.contextKey,
            prompt: this.prompt,
            script: this.script
        };

        if (this.subtype === 'contextAssistant') {
            json.assistantId = this.content.assistantId;
        } else if (this.subtype === 'custom') {
            json.scriptId = this.content.scriptId;
        }

        return json;
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        let displayName;
        if (this.subtype === 'custom' && context.scripts) {
            console.log('[DataNode.getNodeCardHtml] scripts:', context.scripts, 'scriptId:', this.content?.scriptId);
            const script = context.scripts.find(s => String(s.id) === String(this.content.scriptId));
            displayName = script ? script.name : (DataNode.nodeTypes[this.subtype]?.name || this.subtype || 'Data');
        } else {
            displayName = (DataNode.nodeTypes[this.subtype]?.name) || this.subtype || 'Data';
        }
        return `
            <div class="conversation-node card mb-3 ${this.type} ${this.subtype}" data-node-index="${nodeIndex}">
                <div class="card-header d-flex align-items-center justify-content-between node-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas ${this.icon}"></i>
                        <span class="node-title">${displayName}</span>
                        <span class="node-brief-info ms-2">${this.getNodeInfo(context)}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                        ${this.showNodeControls(nodeIndex) ? `
                            <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${nodeIndex}" title="Move Up"><i class="fas fa-arrow-up"></i></button>
                            <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${nodeIndex}" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}" title="Delete"><i class="fas fa-trash"></i></button>
                        ` : ''}
                    </div>
                </div>
                <div class="card-body node-body" style="display:none;">
                    <div class="node-detailed-info">${this.getDetailsHtml(nodeIndex, context)}</div>
                    <form class="node-edit-form" style="display:none;">
                        ${this.subtype === 'custom' ? this.getSettingsFormTemplate(nodeIndex, context) : this.getSettingsFormTemplate(nodeIndex, context.assistants, context.scripts, context)}
                    </form>
                </div>
            </div>
        `;
    }

    getDetailsHtml(nodeIndex, context = {}) {
        let html = this.getDetailedInfo(nodeIndex, context);
        html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        return html;
    }
}

