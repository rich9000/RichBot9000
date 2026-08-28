class APIDataNode extends DataNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'api' });
        this.type = 'data';
        this.subtype = 'api';
        if (!this.content) this.content = {};
        this.content.url = data.content?.url || '';
        this.content.method = data.content?.method || 'GET';
        this.content.headers = data.content?.headers || {};
        this.content.parameters = data.content?.parameters || {};
        this.content.body = data.content?.body || '';
        this.content.authType = data.content?.authType || 'none';
        this.content.authToken = data.content?.authToken || '';
        this.content.username = data.content?.username || '';
        this.content.password = data.content?.password || '';
        this.content.resultPath = data.content?.resultPath || '';
        this.contextKey = data.contextKey || '';
    }

    getNodeInfo() {
        return `API Call: ${this.content.method} ${this.content.url}<br>` +
               `Auth: ${this.content.authType}<br>` +
               `Store in: ${this.contextKey}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">API URL</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            value="${this.content.url || ''}"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'url', this.value)"
                            placeholder="Enter API URL...">
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Method</div>
                    <div class="settings-field">
                        <select class="form-control"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'method', this.value)">
                            <option value="GET" ${this.content.method === 'GET' ? 'selected' : ''}>GET</option>
                            <option value="POST" ${this.content.method === 'POST' ? 'selected' : ''}>POST</option>
                            <option value="PUT" ${this.content.method === 'PUT' ? 'selected' : ''}>PUT</option>
                            <option value="DELETE" ${this.content.method === 'DELETE' ? 'selected' : ''}>DELETE</option>
                            <option value="PATCH" ${this.content.method === 'PATCH' ? 'selected' : ''}>PATCH</option>
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Authentication</div>
                    <div class="settings-field">
                        <select class="form-control"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'authType', this.value)">
                            <option value="none" ${this.content.authType === 'none' ? 'selected' : ''}>None</option>
                            <option value="bearer" ${this.content.authType === 'bearer' ? 'selected' : ''}>Bearer Token</option>
                            <option value="basic" ${this.content.authType === 'basic' ? 'selected' : ''}>Basic Auth</option>
                        </select>
                    </div>
                </div>
                ${this.content.authType === 'bearer' ? `
                    <div class="settings-row">
                        <div class="settings-label">Bearer Token</div>
                        <div class="settings-field">
                            <input type="text" class="form-control"
                                placeholder="Enter bearer token..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'authToken', this.value)"
                                value="${this.content.authToken || ''}">
                        </div>
                    </div>
                ` : ''}
                ${this.content.authType === 'basic' ? `
                    <div class="settings-row">
                        <div class="settings-label">Username</div>
                        <div class="settings-field">
                            <input type="text" class="form-control"
                                placeholder="Enter username..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'username', this.value)"
                                value="${this.content.username || ''}">
                        </div>
                    </div>
                    <div class="settings-row">
                        <div class="settings-label">Password</div>
                        <div class="settings-field">
                            <input type="password" class="form-control"
                                placeholder="Enter password..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'password', this.value)"
                                value="${this.content.password || ''}">
                        </div>
                    </div>
                ` : ''}
                <div class="settings-row">
                    <div class="settings-label">Headers</div>
                    <div class="settings-field">
                        <textarea class="form-control" rows="3"
                            placeholder="Enter headers as JSON..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'headers', this.value)">${JSON.stringify(this.content.headers || {}, null, 2)}</textarea>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Parameters</div>
                    <div class="settings-field">
                        <textarea class="form-control" rows="3"
                            placeholder="Enter query parameters as JSON..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'parameters', this.value)">${JSON.stringify(this.content.parameters || {}, null, 2)}</textarea>
                    </div>
                </div>
                ${['POST', 'PUT', 'PATCH'].includes(this.content.method) ? `
                    <div class="settings-row">
                        <div class="settings-label">Request Body</div>
                        <div class="settings-field">
                            <textarea class="form-control" rows="5"
                                placeholder="Enter request body as JSON..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'body', this.value)">${this.content.body || ''}</textarea>
                        </div>
                    </div>
                ` : ''}
                <div class="settings-row">
                    <div class="settings-label">Result Path</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            placeholder="Enter JSON path to extract (e.g., data.items)..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'resultPath', this.value)"
                            value="${this.content.resultPath || ''}">
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Context Key</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            placeholder="Enter context key to store result..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'contextKey', this.value)"
                            value="${this.contextKey || ''}">
                    </div>
                </div>
            </div>
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'contextKey') {
            this.contextKey = value;
        } else if (field === 'headers' || field === 'parameters') {
            try {
                this.content[field] = JSON.parse(value);
            } catch (e) {
                console.error(`Invalid JSON for ${field}`);
            }
        } else {
            this.content[field] = value;
        }
    }

    validate() {
        if (!this.content.url || !this.contextKey) return false;
        if (this.content.authType === 'bearer' && !this.content.authToken) return false;
        if (this.content.authType === 'basic' && (!this.content.username || !this.content.password)) return false;
        return true;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: this.content,
            contextKey: this.contextKey
        };
    }
}

if (typeof window !== 'undefined') {
    window.APIDataNode = APIDataNode;
}

