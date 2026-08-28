class AssistantActionNode extends ActionNode {
    constructor(data = {}) {
        super(data);
        this.type = 'action';
        this.subtype = 'assistant';
        this.icon = 'fa-robot';
        this.name = 'Assistant';
        this.description = 'Use an AI assistant to respond';
    }

    getNodeInfo(assistants = []) {
        const assistantId = this.content?.assistantId || this.content?.assistant_id;
        if (!assistantId) return 'No assistant selected';
        const assistantsArr = Array.isArray(assistants) ? assistants : [];
        const assistant = assistantsArr.find(a => a.id === parseInt(assistantId));
        return assistant ? 
            `Assistant: ${assistant.name}${this.content?.prompt ? '<br>Has prompt' : ''}` : 
            'No assistant selected';
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const assistants = context.assistants || [];
        const assistantId = this.content?.assistantId || this.content?.assistant_id;
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Assistant</div>
                    <div class="settings-field">
                        <select class="form-control" name="assistantId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                            <option value="">Select an assistant</option>
                            ${assistants.map(assistant => `
                                <option value="${assistant.id}" ${assistantId == assistant.id ? 'selected' : ''}>
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
                            placeholder="Enter prompt for this interaction..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'prompt', this.value)">${this.content?.prompt || ''}</textarea>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'assistantId') {
            this.content.assistantId = value ? parseInt(value) : null;
            // For backward compatibility
            this.content.assistant_id = this.content.assistantId;
        } else if (field === 'prompt') {
            this.content.prompt = value;
        }
    }
}

if (typeof window !== 'undefined') {
    window.AssistantActionNode = AssistantActionNode;
}

