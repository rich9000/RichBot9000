class ContextAssistantDataNode extends DataNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'contextAssistant' });
        this.type = 'data';
        this.subtype = 'contextAssistant';
        if (!this.content) this.content = {};
        this.content.assistantId = (data.content && data.content.assistantId) || data.assistantId || null;
        this.content.contextKey = (data.content && data.content.contextKey) || data.contextKey || '';
        this.content.prompt = (data.content && data.content.prompt) || data.prompt || '';
    }

    getNodeInfo(assistants = []) {
        const assistant = assistants.find(a => String(a.id) === String(this.content.assistantId));
        const contextKey = this.content.contextKey ? `Key: ${this.content.contextKey}` : 'No context key';
        const prompt = this.content.prompt ?
            `Prompt: "${this.content.prompt.substring(0, 30)}${this.content.prompt.length > 30 ? '...' : ''}"` :
            'No prompt set';
        return `${assistant ? `Assistant: ${assistant.name}` : 'No assistant selected'}<br>${contextKey}<br>${prompt}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const assistants = context.assistants || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Assistant</div>
                    <div class="settings-field">
                        <select class="form-control"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                            <option value="">Select Assistant</option>
                            ${assistants.map(assistant => `
                                <option value="${assistant.id}" ${this.content.assistantId === assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Context Key</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            value="${this.content.contextKey || ''}"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'contextKey', this.value)"
                            placeholder="Enter context key...">
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Prompt</div>
                    <div class="settings-field">
                        <textarea class="form-control" rows="3"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'prompt', this.value)"
                            placeholder="Enter prompt for context management...">${this.content.prompt || ''}</textarea>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        if (!this.name) this.name = this.subtype || 'Context Assistant';
        return super.getNodeCardHtml(nodeIndex, context);
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'assistantId') this.content.assistantId = value ? parseInt(value) : null;
        else if (field === 'contextKey') this.content.contextKey = value;
        else if (field === 'prompt') this.content.prompt = value;
    }

    validate() {
        return !!this.content.assistantId && !!this.content.contextKey;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: {
                ...this.content,
                assistantId: this.content.assistantId,
                contextKey: this.content.contextKey,
                prompt: this.content.prompt
            }
        };
    }
}

if (typeof window !== 'undefined') {
    window.ContextAssistantDataNode = ContextAssistantDataNode;
}

