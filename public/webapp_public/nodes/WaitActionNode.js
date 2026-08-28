class WaitActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'wait' });
        this.type = 'action';
        this.subtype = 'wait';
        if (!this.content) this.content = {};
        this.content.waitType = data.content?.waitType || 'fixed';
        this.content.duration = data.content?.duration || 0;
        this.content.scriptId = data.content?.scriptId || null;
        this.content.timeout = data.content?.timeout || 30;
    }

    getNodeInfo(scripts = []) {
        if (this.content.waitType === 'fixed') {
            return `Wait for: ${this.content.duration} seconds`;
        } else {
            const script = scripts.find(s => s.id === this.content.scriptId);
            return `Wait for condition: ${script ? script.name : 'No script selected'}<br>Timeout: ${this.content.timeout} seconds`;
        }
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const scripts = context.scripts || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Wait Type</div>
                    <div class="settings-field">
                        <select class="form-control wait-type" name="waitType"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'waitType', this.value)">
                            <option value="fixed" ${this.content.waitType === 'fixed' ? 'selected' : ''}>Fixed Time</option>
                            <option value="condition" ${this.content.waitType === 'condition' ? 'selected' : ''}>Until Condition</option>
                        </select>
                    </div>
                </div>
                ${this.content.waitType === 'fixed' ? `
                    <div class="settings-row">
                        <div class="settings-label">Duration (seconds)</div>
                        <div class="settings-field">
                            <input type="number" class="form-control duration" name="duration"
                                placeholder="Enter duration in seconds..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'duration', this.value)"
                                value="${this.content.duration || 0}">
                        </div>
                    </div>
                ` : ''}
                ${this.content.waitType === 'condition' ? `
                    <div class="settings-row">
                        <div class="settings-label">Condition Script</div>
                        <div class="settings-field">
                            <select class="form-control condition-script" name="scriptId"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'scriptId', this.value)">
                                <option value="">Select Script</option>
                                ${scripts.map(script => `
                                    <option value="${script.id}" ${this.content.scriptId === script.id ? 'selected' : ''}>
                                        ${script.name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="settings-row">
                        <div class="settings-label">Timeout (seconds)</div>
                        <div class="settings-field">
                            <input type="number" class="form-control timeout" name="timeout"
                                placeholder="Enter timeout in seconds..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'timeout', this.value)"
                                value="${this.content.timeout || 30}">
                        </div>
                    </div>
                ` : ''}
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'duration' || field === 'timeout') {
            this.content[field] = parseInt(value) || 0;
        } else if (field === 'scriptId') {
            this.content[field] = value ? parseInt(value) : null;
        } else {
            this.content[field] = value;
        }
    }

    validate() {
        if (this.content.waitType === 'fixed') {
            return this.content.duration > 0;
        } else {
            return !!this.content.scriptId && this.content.timeout > 0;
        }
    }
}

if (typeof window !== 'undefined') {
    window.WaitActionNode = WaitActionNode;
}

