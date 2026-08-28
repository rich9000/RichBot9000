 class RootEntryNode extends EntryNode {
    constructor(data = {}) {
        super(data);
        this.type = 'entry';
        this.subtype = 'root';
        this.icon = 'fa-sign-in-alt';
        this.name = 'Root Entry Point';
        this.description = 'Main entry point for the conversation';
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
        if (!this.content) this.content = {};
        this.content.settings = data.content?.settings || {
            maxTurns: 50,                    // Maximum number of conversation turns
            timeout: 1800,                   // Session timeout in seconds (30 minutes)
            language: 'en',                  // Default language
            fallbackLanguage: 'en',          // Fallback language if detection fails
            timeZone: 'UTC',                 // Default timezone
            recordConversation: true,        // Whether to record the conversation
            transcribeAudio: true,           // Whether to transcribe audio in real-time
            enableProfanityFilter: true,     // Whether to filter profanity
            maxMessageLength: 1000,          // Maximum message length in characters
            maxAttachmentSize: 5242880,      // Maximum attachment size in bytes (5MB)
            allowedFileTypes: ['audio/*', 'image/*', 'application/pdf'],  // Allowed file types
            enableTypingIndicator: true,     // Whether to show typing indicators
            enableReadReceipts: true,        // Whether to show read receipts
            enableUserFeedback: true,        // Whether to collect user feedback
            maxRetries: 3,                   // Maximum number of retries for failed actions
            retryDelay: 1000,               // Delay between retries in milliseconds
            queueTimeout: 300,              // Queue timeout in seconds
            maxQueueSize: 100,              // Maximum queue size
            priorityLevels: ['low', 'medium', 'high', 'urgent'],  // Priority levels
            defaultPriority: 'medium'        // Default priority level
        };
    }

    getNodeInfo() {
        const enabledEntries = [];
        if (this.options.chat.enabled) {
            enabledEntries.push('Chat');
        }
        if (this.options.twilioInbound.enabled) {
            enabledEntries.push('Twilio Inbound');
        }
        if (this.options.twilioOutbound.enabled) {
            enabledEntries.push('Twilio Outbound');
        }
        return enabledEntries.length > 0 ? 
            `Enabled: ${enabledEntries.join(', ')}` : 
            'No entry points enabled';
    }

    getDetailsHtml(nodeIndex) {
        const enabledEntries = [];
        if (this.options.chat.enabled) enabledEntries.push('Chat');
        if (this.options.twilioInbound.enabled) enabledEntries.push('Twilio Inbound');
        if (this.options.twilioOutbound.enabled) enabledEntries.push('Twilio Outbound');
        return `<div><strong>Enabled Entry Points:</strong> ${enabledEntries.length > 0 ? enabledEntries.join(', ') : 'None'}</div>
            <button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>`;
    }

    getSettingsFormTemplate(nodeIndex) {
        return `
            <div class="settings-grid">
                <!-- Chat Entry -->
                <div class="settings-row">
                    <div class="settings-label">Chat Entry</div>
                    <div class="settings-field">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                ${this.options.chat.enabled ? 'checked' : ''}
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'chat.enabled', this.checked)">
                            <label class="form-check-label">Enable Chat Entry</label>
                        </div>
                        ${this.options.chat.enabled ? `
                            <div class="mt-2">
                                <label>Welcome Message</label>
                                <textarea class="form-control" rows="2"
                                    placeholder="Enter welcome message..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'chat.welcomeMessage', this.value)">${this.options.chat.welcomeMessage || ''}</textarea>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Twilio Inbound -->
                <div class="settings-row">
                    <div class="settings-label">Twilio Inbound</div>
                    <div class="settings-field">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                ${this.options.twilioInbound.enabled ? 'checked' : ''}
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioInbound.enabled', this.checked)">
                            <label class="form-check-label">Enable Twilio Inbound</label>
                        </div>
                        ${this.options.twilioInbound.enabled ? `
                            <div class="mt-2">
                                <label>Phone Number</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioInbound.phoneNumber', this.value)"
                                    value="${this.options.twilioInbound.phoneNumber || ''}">
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Twilio Outbound -->
                <div class="settings-row">
                    <div class="settings-label">Twilio Outbound</div>
                    <div class="settings-field">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                ${this.options.twilioOutbound.enabled ? 'checked' : ''}
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioOutbound.enabled', this.checked)">
                            <label class="form-check-label">Enable Twilio Outbound</label>
                        </div>
                        ${this.options.twilioOutbound.enabled ? `
                            <div class="mt-2">
                                <label>Phone Number</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter phone number..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioOutbound.phoneNumber', this.value)"
                                    value="${this.options.twilioOutbound.phoneNumber || ''}">
                            </div>
                            <div class="mt-2">
                                <label>Initial Message</label>
                                <textarea class="form-control" rows="2"
                                    placeholder="Enter initial message..."
                                    onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'twilioOutbound.initialMessage', this.value)">${this.options.twilioOutbound.initialMessage || ''}</textarea>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            <button class="btn btn-sm btn-success save-node mt-2" type="button">
                <i class="fas fa-save"></i> Save
            </button>
        `;
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

    validate() {
        return this.options.chat.enabled || 
               this.options.twilioInbound.enabled || 
               this.options.twilioOutbound.enabled;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: this.content
        };
    }

    showNodeControls(nodeIndex) {
        return false;
    }
}

if (typeof window !== 'undefined') {
    window.RootEntryNode = RootEntryNode;
}

