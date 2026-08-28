
class EntryNode extends BaseNode {
    static nodeTypes = {
        root: {
            icon: 'fa-sign-in-alt',
            name: 'Entry Point',
            color: '#007bff'
        },
        chat: {
            icon: 'fa-comments',
            name: 'Chat Entry',
            color: '#007bff'
        },
        twilioInbound: {
            icon: 'fa-phone',
            name: 'Twilio Inbound',
            color: '#28a745'
        },
        twilioOutbound: {
            icon: 'fa-phone',
            name: 'Twilio Outbound',
            color: '#dc3545'
        }
    };

    constructor(data = {}) {
        super(data);
        this.type = 'entry';
        this.icon = 'fa-sign-in-alt';
        this.name = 'Entry Point';
        this.color = '#007bff';
        this.description = 'Configure entry points for the conversation';
        this.options = data.options || {
            chat: {
                enabled: false,
                welcomeMessage: ''
            },
            twilioInbound: {
                enabled: false,
                phoneNumber: ''
            },
            twilioOutbound: {
                enabled: false,
                phoneNumber: '',
                initialMessage: ''
            }
        };
    }

    getNodeInfo() {
        switch (this.subtype) {
            case 'chat':
                return this.options.chat.enabled ? `Chat Entry: ${this.options.chat.welcomeMessage || 'Enabled'}` : 'Chat Entry Disabled';
            case 'twilioInbound':
                return this.options.twilioInbound.enabled ? `Twilio Inbound: ${this.options.twilioInbound.phoneNumber || 'Enabled'}` : 'Twilio Inbound Disabled';
            case 'twilioOutbound':
                return this.options.twilioOutbound.enabled ? `Twilio Outbound: ${this.options.twilioOutbound.phoneNumber || 'Enabled'}` : 'Twilio Outbound Disabled';
            case 'root':
            default:
                return 'Entry Point';
        }
    }

    getSettingsFormTemplate(nodeIndex) {
        switch (this.subtype) {
            case 'chat':
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Welcome Message</div>
                            <div class="settings-field">
                                <textarea class="form-control" rows="2"
                                    placeholder="Enter welcome message..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'chat.welcomeMessage', this.value)">${this.options.chat.welcomeMessage || ''}</textarea>
                            </div>
                        </div>
                    </div>
                `;
            case 'twilioInbound':
                return `
                    <div class="settings-grid">
                        <div class="settings-row">
                            <div class="settings-label">Phone Number</div>
                            <div class="settings-field">
                                <input type="text" class="form-control"
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioInbound.phoneNumber', this.value)"
                                    value="${this.options.twilioInbound.phoneNumber || ''}">
                            </div>
                        </div>
                    </div>
                `;
            case 'twilioOutbound':
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
            case 'root':
            default:
                return '';
        }
    }

    validate() {
        if (this.subtype === 'root') {
            // At least one entry point should be enabled
            return this.options.chat.enabled || 
                   this.options.twilioInbound.enabled || 
                   this.options.twilioOutbound.enabled;
        }
        return true;
    }

    updateContent(field, value) {
        if (!this.options) this.options = {};
        if (field.includes('.')) {
            const [option, subfield] = field.split('.');
            if (!this.options[option]) this.options[option] = {};
            this.options[option][subfield] = value;
        } else {
            this[field] = value;
        }
    }

    toJSON() {
        return {
            ...super.toJSON(),
            options: this.options
        };
    }

    // Override getNodeActions to return empty string for entry nodes
    getNodeActions(nodeIndex) {
        return ''; // Entry nodes don't need action buttons
    }
}

