class TwilioInboundEntryNode extends EntryNode {
    constructor(data = {}) {
        super(data);
        this.type = 'entry';
        this.subtype = 'twilioInbound';
        this.icon = 'fa-phone';
        this.name = 'Twilio Inbound';
        this.description = 'Handles incoming Twilio calls';
        this.phoneNumber = data.phoneNumber || '';
    }

    getNodeInfo() {
        return this.phoneNumber ? 
            `Phone: ${this.phoneNumber}` : 
            'No phone number set';
    }

    getSettingsFormTemplate(nodeIndex) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Phone Number</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" 
                            placeholder="Enter phone number..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'phoneNumber', this.value)"
                            value="${this.phoneNumber || ''}">
                    </div>
                </div>
            </div>
        `;
    }

    updateContent(field, value) {
        if (field === 'phoneNumber') {
            this.phoneNumber = value;
        }
    }
}

if (typeof window !== 'undefined') {
    window.TwilioInboundEntryNode = TwilioInboundEntryNode;
}

