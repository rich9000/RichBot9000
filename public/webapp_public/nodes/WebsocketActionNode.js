class WebsocketActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'websocket' });
        this.type = 'action';
        this.subtype = 'websocket';
    }
    getNodeInfo() {
        return this.content.wsUrl ?
            `WebSocket: ${this.content.wsUrl}` :
            'No WebSocket URL set';
    }
    getSettingsFormTemplate(nodeIndex) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">WebSocket URL</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" name="wsUrl"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'wsUrl', this.value)"
                            value="${this.content.wsUrl || ''}" placeholder="Enter WebSocket URL">
                    </div>
                </div>
            </div>
        `;
    }
    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'wsUrl') this.content.wsUrl = value;
    }
}

if (typeof window !== 'undefined') {
    window.WebsocketActionNode = WebsocketActionNode;
}

