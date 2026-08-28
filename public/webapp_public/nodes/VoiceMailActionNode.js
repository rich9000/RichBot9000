class VoiceMailActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'voiceMail' });
        this.type = 'action';
        this.subtype = 'voiceMail';
    }
    getNodeInfo() {
        return this.content.phoneNumber ?
            `Voice Mail: ${this.content.phoneNumber}` :
            'No phone number set';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Phone Number</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" name="phoneNumber"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'phoneNumber', this.value)"
                            value="${this.content.phoneNumber || ''}" placeholder="Enter phone number">
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }
    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'phoneNumber') this.content.phoneNumber = value;
    }
}

if (typeof window !== 'undefined') {
    window.VoiceMailActionNode = VoiceMailActionNode;
}

