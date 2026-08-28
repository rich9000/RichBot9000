class CustomerLookupDataNode extends DataNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'customerLookup' });
        this.type = 'data';
        this.subtype = 'customerLookup';
        if (!this.content) this.content = {};
        this.content.searchField = (data.content && data.content.searchField) || data.searchField || '';
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
                    <div class="settings-label">Search Field</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            value="${this.content.searchField || ''}"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'searchField', this.value)"
                            placeholder="Enter search field (e.g., phone)">
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        if (!this.name) this.name = this.subtype || 'Customer Lookup';
        return super.getNodeCardHtml(nodeIndex, context);
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'searchField') this.content.searchField = value;
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
                searchField: this.content.searchField,
                contextKey: this.content.contextKey,
                prompt: this.content.prompt
            }
        };
    }
}

if (typeof window !== 'undefined') {
    window.CustomerLookupDataNode = CustomerLookupDataNode;
}

