class HangupActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'hangup' });
        this.type = 'action';
        this.subtype = 'hangup';
    }
    getNodeInfo() {
        return this.content.reason ?
            `Reason: ${this.content.reason}` :
            'No reason set';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Reason</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" name="reason"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'reason', this.value)"
                            value="${this.content.reason || ''}" placeholder="Enter reason for hanging up">
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }
    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'reason') this.content.reason = value;
    }
}

if (typeof window !== 'undefined') {
    window.HangupActionNode = HangupActionNode;
}

