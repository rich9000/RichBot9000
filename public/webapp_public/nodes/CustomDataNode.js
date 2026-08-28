class CustomDataNode extends DataNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'custom' });
        this.type = 'data';
        this.subtype = 'custom';
        if (!this.content) this.content = {};
        this.content.scriptId = (data.content && data.content.scriptId) || data.scriptId || null;
        this.content.contextKey = (data.content && data.content.contextKey) || data.contextKey || '';
    }

    getNodeInfo(context = {}) {
        if (Array.isArray(context)) {
            console.warn('[CustomDataNode.getNodeInfo] context is array, expected object:', context);
            context = {};
        }
        let scripts = context.scripts || [];
        if ((!scripts || scripts.length === 0) && window.appState && appState.data && Array.isArray(appState.data.scripts)) {
            scripts = appState.data.scripts;
        }
        console.log('[CustomDataNode.getNodeInfo] context:', context, 'content:', this.content, 'scripts:', scripts);
        const script = scripts.find(s => String(s.id) === String(this.content.scriptId));
        const customContextKey = this.content.contextKey ? `Key: ${this.content.contextKey}` : 'No context key';
        return `${script ? `Script: ${script.name}` : 'No script selected'}<br>${customContextKey}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        if (Array.isArray(context)) {
            console.warn('[CustomDataNode.getSettingsFormTemplate] context is array, expected object:', context);
            context = {};
        }
        const scripts = context.scripts || [];
        console.log('[CustomDataNode.getSettingsFormTemplate] context:', context, 'scripts:', scripts, 'content:', this.content);
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Script</div>
                    <div class="settings-field">
                        <select class="form-control" name="scriptId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'scriptId', this.value)">
                            <option value="">Select Script</option>
                            ${scripts.map(script => `
                                <option value="${script.id}" ${this.content.scriptId == script.id ? 'selected' : ''}>
                                    ${script.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Context Key</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" name="contextKey"
                            value="${this.content.contextKey || ''}"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'contextKey', this.value)"
                            placeholder="Enter context key...">
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        console.log('[CustomDataNode.updateContent] field:', field, 'value:', value, 'before:', this.content);
        if (field === 'scriptId') this.content.scriptId = value ? parseInt(value) : null;
        else if (field === 'contextKey') this.content.contextKey = value;
        console.log('[CustomDataNode.updateContent] after:', this.content);
    }

    validate() {
        return !!this.content.scriptId && !!this.content.contextKey;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: {
                ...this.content,
                scriptId: this.content.scriptId,
                contextKey: this.content.contextKey
            }
        };
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        if (!this.name) this.name = this.subtype || 'Custom Data';
        return super.getNodeCardHtml(nodeIndex, context);
    }
}

if (typeof window !== 'undefined') {
    window.CustomDataNode = CustomDataNode;
}

