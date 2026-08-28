class AssistantDecisionNode extends DecisionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'assistant' });
        this.assistantId = data.assistantId || null;
        this.prompt = data.prompt || '';
        if (data.actions && typeof data.actions === 'object' && data.actions.constructor && data.actions.constructor.name === 'ActionNodeList') {
            this.actions = data.actions;
        } else if (Array.isArray(data.actions)) {
            this.actions = new window.ActionNodeList(data.actions);
        } else {
            this.actions = new window.ActionNodeList();
        }
    }

    getNodeInfo(assistants = []) {
        const assistantId = this.content?.assistantId || null;
        const prompt = this.content?.prompt || '';
        const assistant = assistants.find(a => a.id == assistantId);
        const promptText = prompt ? `Prompt: "${prompt.substring(0, 30)}${prompt.length > 30 ? '...' : ''}"` : 'No prompt set';
        return `${assistant ? `Assistant: ${assistant.name}` : 'No assistant selected'}<br>${promptText}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const assistants = context.assistants || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Assistant</div>
                    <div class="settings-field">
                        <select class="form-control" name="assistantId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                            <option value="">Select an assistant</option>
                            ${assistants.map(assistant => `
                                <option value="${assistant.id}" ${this.content?.assistantId == assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Prompt</div>
                    <div class="settings-field">
                        <textarea class="form-control" name="prompt" rows="3"
                            placeholder="Enter prompt for the assistant..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'prompt', this.value)">${this.content?.prompt || ''}</textarea>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        this.content[field] = value;
    }

    validate() {
        return !!this.content.assistantId && !!this.content.prompt;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            actions: this.actions ? (typeof this.actions.toJSON === 'function' ? this.actions.toJSON() : this.actions) : []
        };
    }

    getDetailsHtml(nodeIndex, context = {}) {
        let html = `<div>${this.getNodeInfo(context.assistants, context.scripts, context.audioFiles)}</div>`;
        html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        return html;
    }
}

if (typeof window !== 'undefined') {
    window.AssistantDecisionNode = AssistantDecisionNode;
}

