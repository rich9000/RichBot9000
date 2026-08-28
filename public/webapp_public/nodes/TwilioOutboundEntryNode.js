class TwilioOutboundEntryNode extends EntryNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'twilioOutbound' });
        this.type = 'entry';
        this.subtype = 'twilioOutbound';
        if (!this.options.twilioOutbound) {
            this.options.twilioOutbound = { enabled: true, phoneNumber: '', initialMessage: '' };
        }
    }

    getNodeInfo() {
        return this.options.twilioOutbound.enabled ? 
            `Twilio Outbound: ${this.options.twilioOutbound.phoneNumber || 'Enabled'}` : 
            'Twilio Outbound Disabled';
    }

    getSettingsFormTemplate(nodeIndex) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Phone Number</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            placeholder="Enter phone number..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioOutbound.phoneNumber', this.value)"
                            value="${this.options.twilioOutbound.phoneNumber || ''}">
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Initial Message</div>
                    <div class="settings-field">
                        <textarea class="form-control" rows="2"
                            placeholder="Enter initial message..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioOutbound.initialMessage', this.value)">${this.options.twilioOutbound.initialMessage || ''}</textarea>
                    </div>
                </div>
            </div>
        `;
    }

    validate() {
        return this.options.twilioOutbound.enabled;
    }
}

if (typeof window !== 'undefined') {
    window.TwilioOutboundEntryNode = TwilioOutboundEntryNode;
}

