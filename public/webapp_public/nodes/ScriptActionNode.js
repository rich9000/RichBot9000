class ScriptActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'script' });
        this.type = 'action';
        this.subtype = 'script';
    }
    getNodeInfo(scripts = []) {
        const script = scripts.find(s => s.id === this.content.scriptId);
        return script ? `Script: ${script.name}` : 'No script selected';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        const scripts = context.scripts || [];
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
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }
    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'scriptId') this.content.scriptId = value ? parseInt(value) : null;
    }
}

if (typeof window !== 'undefined') {
    window.ScriptActionNode = ScriptActionNode;
}

