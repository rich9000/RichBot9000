class DecisionNode extends BaseNode {
    constructor(data = {}) {
        super(data);
        this.type = 'decision';
        this.icon = 'fa-code-branch';
        this.name = 'Decision';
        this.color = '#6610f2';
        this.description = 'Make a decision in the conversation flow';
        // Use ActionNodeList for actions
        if (data.actions && typeof data.actions === 'object' && data.actions.constructor && data.actions.constructor.name === 'ActionNodeList') {
            this.actions = data.actions;
        } else if (Array.isArray(data.actions)) {
            this.actions = new window.ActionNodeList(data.actions);
        } else {
            this.actions = new window.ActionNodeList();
        }
        this.message = data.message || '';
        this.audioFileId = data.audioFileId || null;
        this.userDecisionType = data.userDecisionType || '';
        this.smsTo = data.smsTo || '';
        this.smsBody = data.smsBody || '';
        this.emailTo = data.emailTo || '';
        this.emailSubject = data.emailSubject || '';
        this.emailBody = data.emailBody || '';
        this.assistantId = data.assistantId || null;
        this.prompt = data.prompt || '';
        this.script = data.script || '';
        this.returnType = data.returnType || '';
        this.description = data.description || '';
    }

    static nodeTypes = {
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
        },
        assistantTool: {
            icon: 'fa-tools',
            name: 'Assistant Tool Decision',
            color: '#6610f2',
            description: 'Decision based on assistant tool results'
        }
    };

    getNodeInfo(assistants = [], scripts = [], audioFiles = []) {
        switch (this.subtype) {
            case 'user': {
                let summary = [];
                if (this.message) {
                    summary.push(`Message: "${this.message.substring(0, 30)}${this.message.length > 30 ? '...' : ''}"`);
                }
                if (this.audioFileId) {
                    const audioFile = audioFiles.find(a => a.id === this.audioFileId);
                    if (audioFile) {
                        summary.push(`Audio: ${audioFile.name}`);
                    }
                }
                return summary.length > 0 ? summary.join('<br>') : 'No message or audio set';
            }
            case 'assistant': {
                const assistant = assistants.find(a => a.id === this.assistantId);
                return assistant ? `Assistant: ${assistant.name}` : 'No assistant selected';
            }
            case 'conditional': {
                const script = scripts.find(s => s.id === this.script);
                return script ? `Script: ${script.name}` : 'No script selected';
            }
            default:
                return '';
        }
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        return super.getSettingsFormTemplate(nodeIndex, context);
    }

    validate() {
        switch (this.subtype) {
            case 'user':
                return !!(this.content.message || this.content.audioFileId);
            case 'assistant':
                return !!this.content.assistantId && !!this.content.prompt;
            case 'conditional':
                return !!this.content.script;
            default:
                return false;
        }
    }

    toJSON() {
        return {
            ...super.toJSON(),
            actions: this.actions ? (typeof this.actions.toJSON === 'function' ? this.actions.toJSON() : this.actions) : [],
            message: this.message,
            audioFileId: this.audioFileId,
            userDecisionType: this.userDecisionType,
            smsTo: this.smsTo,
            smsBody: this.smsBody,
            emailTo: this.emailTo,
            emailSubject: this.emailSubject,
            emailBody: this.emailBody,
            assistantId: this.assistantId,
            prompt: this.prompt,
            script: this.script,
            returnType: this.returnType,
            description: this.description
        };
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        let displayName = this.name;
        if (this.subtype === 'user') displayName = 'User Decision';
        else if (this.subtype === 'assistant') displayName = 'Assistant Decision';
        else if (this.subtype === 'conditional') displayName = 'Conditional Decision';
        else displayName = this.name || 'Decision';
        const actionsAreaId = `actions-area-${this.id}`;
        let actionsHtml = '';
        if (this.actions && typeof this.actions.render === 'function') {
            actionsHtml = this.actions.render(context, nodeIndex);
        } else if (this.actions && this.actions.length > 0) {
            actionsHtml = this.actions.map((action, i) => {
                let actionNode = action;
                if (!actionNode.getNodeCardHtml) {
                    if (window.NodeFactory && typeof window.NodeFactory.createNode === 'function') {
                        actionNode = window.NodeFactory.createNode({ ...action, type: 'action' });
                    }
                }
                if (actionNode && actionNode.getNodeCardHtml) {
                    return `<div class="decision-action-item-wrapper d-flex align-items-center mb-3" data-action-index="${i}">
                        <span class="badge bg-purple me-2" style="background:#a259e6; font-size:1.3rem; padding:0.6em 1em;">${i + 1}</span>
                        <div class="d-flex flex-column me-2">
                            <button class="btn btn-xs btn-outline-secondary mb-1" style="padding:1px 4px; font-size:0.85rem; min-width:24px;" title="Move Up" onclick="window.pathBuilder.moveDecisionAction(${nodeIndex}, ${i}, -1)"><i class="fas fa-arrow-up"></i></button>
                            <button class="btn btn-xs btn-outline-secondary" style="padding:1px 4px; font-size:0.85rem; min-width:24px;" title="Move Down" onclick="window.pathBuilder.moveDecisionAction(${nodeIndex}, ${i}, 1)"><i class="fas fa-arrow-down"></i></button>
                        </div>
                        <div class="flex-grow-1">${actionNode.getNodeCardHtml(i, context, { hideNodeControls: true })}</div>
                    </div>`;
                } else {
                    return `<div class="decision-action-item d-flex align-items-center mb-3" data-action-index="${i}">
                        <span class="badge bg-purple me-2" style="background:#a259e6; font-size:1.3rem; padding:0.6em 1em;">${i + 1}</span>
                        <div class="d-flex flex-column me-2">
                            <button class="btn btn-xs btn-outline-secondary mb-1" style="padding:1px 4px; font-size:0.85rem; min-width:24px;" title="Move Up" onclick="window.pathBuilder.moveDecisionAction(${nodeIndex}, ${i}, -1)"><i class="fas fa-arrow-up"></i></button>
                            <button class="btn btn-xs btn-outline-secondary" style="padding:1px 4px; font-size:0.85rem; min-width:24px;" title="Move Down" onclick="window.pathBuilder.moveDecisionAction(${nodeIndex}, ${i}, 1)"><i class="fas fa-arrow-down"></i></button>
                        </div>
                        <span>${action.name || action.subtype || 'Action'}</span>
                        <button class="btn btn-sm btn-outline-danger ms-2" type="button" onclick="window.pathBuilder.removeDecisionAction(${nodeIndex}, ${i})"><i class="fas fa-trash"></i></button>
                    </div>`;
                }
            }).join('');
        }
        // Centered, purple, visually distinct drop zone, actions above it
        const dropZoneHtml = `
            <div class="decision-actions-drop-zone actions-drop-zone custom-drop-zone" data-node-index="${nodeIndex}"
                style="margin: 1.5rem auto 0 auto; padding: 2rem; border: 2px dashed #a259e6; border-radius: 12px; background: #f6f0fa; text-align: center; min-height: 80px; max-width: 600px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer;"
                ondragover="this.classList.add('drag-over'); event.preventDefault();"
                ondragleave="this.classList.remove('drag-over');"
                ondrop="this.classList.remove('drag-over'); window.pathBuilder.handleDecisionActionDrop(event, ${nodeIndex})">
                <i class="fas fa-plus-circle" style="font-size: 2rem; color: #a259e6;"></i>
                <span style="font-size: 1.1rem; color: #a259e6; font-weight: 500;">Drop action node here to add to decision</span>
                <small class="text-muted">Drag an action node from the palette</small>
            </div>
        `;
        return `
            <div class="conversation-node card mb-3 ${this.type} ${this.subtype}" data-node-index="${nodeIndex}">
                <div class="card-header d-flex align-items-center justify-content-between node-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas ${this.icon}"></i>
                        <span class="node-title">${displayName}</span>
                        <span class="node-brief-info ms-2">${this.getNodeInfo(context.assistants, context.scripts, context.audioFiles)}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                        <button class="btn btn-sm btn-outline-primary actions-toggle" type="button" title="Show Actions" onclick="window.pathBuilder.toggleDecisionActions('${actionsAreaId}')"><i class="fas fa-bolt"></i> Actions</button>
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
                        ${this.getSettingsFormTemplate(nodeIndex, context)}
                    </form>
                </div>
                <div class="decision-actions-area" id="${actionsAreaId}" style="display:none;">
                    <div class="card mb-3">
                        <div class="card-header fw-bold bg-light">Decision Actions</div>
                        <div class="card-body p-2">
                            <div class="decision-actions-list">${actionsHtml}</div>
                        </div>
                    </div>
                    ${dropZoneHtml}
                </div>
            </div>
        `;
    }
}