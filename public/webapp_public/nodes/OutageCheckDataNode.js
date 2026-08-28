class OutageCheckDataNode extends DataNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'outageCheck' });
        this.type = 'data';
        this.subtype = 'outageCheck';
        if (!this.content) this.content = {};
        this.content.serviceId = (data.content && data.content.serviceId) || data.serviceId || '';
        this.content.contextKey = (data.content && data.content.contextKey) || data.contextKey || '';
        this.content.prompt = (data.content && data.content.prompt) || data.prompt || '';
    }

    getNodeInfo() {
        const contextKey = this.content.contextKey ? `Key: ${this.content.contextKey}` : 'No context key';
        const prompt = this.content.prompt ?
            `Prompt: "${this.content.prompt.substring(0, 30)}${this.content.prompt.length > 30 ? '...' : ''}"` :
            'No prompt set';
        return `${contextKey}<br>${prompt}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Service ID</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            value="${this.content.serviceId || ''}"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'serviceId', this.value)"
                            placeholder="Enter service ID">
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        if (!this.name) this.name = this.subtype || 'Outage Check';
        return super.getNodeCardHtml(nodeIndex, context);
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'serviceId') this.content.serviceId = value;
        else if (field === 'contextKey') this.content.contextKey = value;
        else if (field === 'prompt') this.content.prompt = value;
    }

    validate() {
        return !!this.content.contextKey;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: {
                ...this.content,
                serviceId: this.content.serviceId,
                contextKey: this.content.contextKey,
                prompt: this.content.prompt
            }
        };
    }
}

if (typeof window !== 'undefined') {
    window.OutageCheckDataNode = OutageCheckDataNode;
}

