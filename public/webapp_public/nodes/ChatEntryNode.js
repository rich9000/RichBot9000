class ChatEntryNode extends EntryNode {
    constructor(data = {}) {
        super(data);
        this.type = 'entry';
        this.subtype = 'chat';
        this.icon = 'fa-comments';
        this.name = 'Chat Entry';
        this.description = 'Starts a chat conversation';
        this.welcomeMessage = data.welcomeMessage || '';
    }

    getNodeInfo() {
        return this.welcomeMessage ? 
            `Welcome: "${this.welcomeMessage.substring(0, 30)}${this.welcomeMessage.length > 30 ? '...' : ''}"` : 
            'No welcome message set';
    }

    getSettingsFormTemplate(nodeIndex) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Welcome Message</div>
                    <div class="settings-field">
                        <textarea class="form-control" rows="3" 
                            placeholder="Enter welcome message..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'welcomeMessage', this.value)">${this.welcomeMessage || ''}</textarea>
                    </div>
                </div>
            </div>
        `;
    }

    updateContent(field, value) {
        if (field === 'welcomeMessage') {
            this.welcomeMessage = value;
        }
    }
}

if (typeof window !== 'undefined') {
    window.ChatEntryNode = ChatEntryNode;
}

