class UserDecisionNode extends DecisionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'user' });
        this.type = 'decision';
        this.subtype = 'user';
        this.message = data.message || '';
        this.audioFileId = data.audioFileId || null;
        if (data.actions && typeof data.actions === 'object' && data.actions.constructor && data.actions.constructor.name === 'ActionNodeList') {
            this.actions = data.actions;
        } else if (Array.isArray(data.actions)) {
            this.actions = new window.ActionNodeList(data.actions);
        } else {
            this.actions = new window.ActionNodeList();
        }
    }

    getNodeInfo(assistants = [], scripts = [], audioFiles = []) {
        let summary = [];
        const message = this.content?.message || '';
        const audioFileId = this.content?.audioFileId || null;
        const userDecisionType = this.content?.userDecisionType || 'dtmf';
        if (message) {
            summary.push(`Message: "${message.substring(0, 30)}${message.length > 30 ? '...' : ''}"`);
        }
        if (audioFileId) {
            const audioFile = audioFiles.find(a => a.id == audioFileId);
            if (audioFile) {
                summary.push(`Audio: ${audioFile.name}`);
            }
        }
        summary.push(`Type: ${userDecisionType}`);
        return summary.length > 0 ? summary.join('<br>') : 'No message or audio set';
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const audioFiles = context.audioFiles || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">User Decision Type</div>
                    <div class="settings-field">
                        <select class="form-control" name="userDecisionType"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'userDecisionType', this.value)">
                            <option value="dtmf" ${!this.content?.userDecisionType || this.content?.userDecisionType === 'dtmf' ? 'selected' : ''}>DTMF (Keypad, default)</option>
                            <option value="realtime" ${this.content?.userDecisionType === 'realtime' ? 'selected' : ''}>Realtime</option>
                            <option value="askandwait" ${this.content?.userDecisionType === 'askandwait' ? 'selected' : ''}>AskAndWait</option>
                            <option value="sms" ${this.content?.userDecisionType === 'sms' ? 'selected' : ''}>SMS</option>
                            <option value="email" ${this.content?.userDecisionType === 'email' ? 'selected' : ''}>Email</option>
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Message</div>
                    <div class="settings-field">
                        <textarea class="form-control" name="message" rows="3"
                            placeholder="Enter message to say before user selection..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'message', this.value)">${this.content?.message || ''}</textarea>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Audio File</div>
                    <div class="settings-field">
                        <select class="form-control" name="audioFileId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'audioFileId', this.value)">
                            <option value="">Select Audio File</option>
                            ${audioFiles.map(audio => `
                                <option value="${audio.id}" ${this.content?.audioFileId == audio.id ? 'selected' : ''}>
                                    ${audio.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
            </div>
            <button class="btn btn-sm btn-success save-node mt-2" type="button">
                <i class="fas fa-save"></i> Save
            </button>
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'userDecisionType') this.content.userDecisionType = value ? value.toLowerCase() : 'dtmf';
        else this.content[field] = value;
        // Keep legacy fields in sync for getNodeInfo, etc.
        if (field === 'message') this.message = value;
        if (field === 'audioFileId') this.audioFileId = value ? parseInt(value) : null;
    }

    validate() {
        return !!(this.content.message || this.content.audioFileId);
    }

    toJSON() {
        return {
            ...super.toJSON(),
            actions: this.actions ? (typeof this.actions.toJSON === 'function' ? this.actions.toJSON() : this.actions) : [],
            userDecisionType: this.content.userDecisionType || 'dtmf'
        };
    }

    getDetailsHtml(nodeIndex, context = {}) {
        // Show both message and audio file info, just like the header
        let html = `<div>${this.getNodeInfo(context.assistants, context.scripts, context.audioFiles)}</div>`;
        html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        return html;
    }
}

if (typeof window !== 'undefined') {
    window.UserDecisionNode = UserDecisionNode;
}

