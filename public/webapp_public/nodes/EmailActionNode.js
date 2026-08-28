class EmailActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'email' });
        this.type = 'action';
        this.subtype = 'email';
        if (!this.content) this.content = {};
        this.content.emailType = data.content?.emailType || 'user';
        this.content.emailAddress = data.content?.emailAddress || '';
        this.content.customerField = data.content?.customerField || '';
        this.content.subject = data.content?.subject || '';
        this.content.message = data.content?.message || '';
        this.content.template = data.content?.template || '';
        this.content.templateData = data.content?.templateData || {};
    }

    getNodeInfo() {
        const type = this.content.emailType === 'user' ? 'User' :
                    this.content.emailType === 'address' ? `Email: ${this.content.emailAddress}` :
                    this.content.emailType === 'customer' ? `Customer Field: ${this.content.customerField}` : 'Unknown';
        return `Email to: ${type}<br>Subject: ${this.content.subject}<br>Template: ${this.content.template || 'Custom Message'}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Email Type</div>
                    <div class="settings-field">
                        <select class="form-control"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'emailType', this.value)">
                            <option value="user" ${this.content.emailType === 'user' ? 'selected' : ''}>Send to User</option>
                            <option value="address" ${this.content.emailType === 'address' ? 'selected' : ''}>Send to Address</option>
                            <option value="customer" ${this.content.emailType === 'customer' ? 'selected' : ''}>Send to Customer</option>
                        </select>
                    </div>
                </div>
                ${this.content.emailType === 'address' ? `
                    <div class="settings-row">
                        <div class="settings-label">Email Address</div>
                        <div class="settings-field">
                            <input type="email" class="form-control"
                                placeholder="Enter email address..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'emailAddress', this.value)"
                                value="${this.content.emailAddress || ''}">
                        </div>
                    </div>
                ` : ''}
                ${this.content.emailType === 'customer' ? `
                    <div class="settings-row">
                        <div class="settings-label">Customer Field</div>
                        <div class="settings-field">
                            <input type="text" class="form-control"
                                placeholder="Enter customer field name (e.g., email)..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'customerField', this.value)"
                                value="${this.content.customerField || ''}">
                        </div>
                    </div>
                ` : ''}
                <div class="settings-row">
                    <div class="settings-label">Subject</div>
                    <div class="settings-field">
                        <input type="text" class="form-control"
                            placeholder="Enter email subject..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'subject', this.value)"
                            value="${this.content.subject || ''}">
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Template</div>
                    <div class="settings-field">
                        <select class="form-control"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'template', this.value)">
                            <option value="">Custom Message</option>
                            <option value="welcome" ${this.content.template === 'welcome' ? 'selected' : ''}>Welcome Email</option>
                            <option value="confirmation" ${this.content.template === 'confirmation' ? 'selected' : ''}>Confirmation</option>
                            <option value="support" ${this.content.template === 'support' ? 'selected' : ''}>Support Response</option>
                        </select>
                    </div>
                </div>
                ${!this.content.template ? `
                    <div class="settings-row">
                        <div class="settings-label">Message</div>
                        <div class="settings-field">
                            <textarea class="form-control" rows="5"
                                placeholder="Enter email message..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'message', this.value)">${this.content.message || ''}</textarea>
                        </div>
                    </div>
                ` : `
                    <div class="settings-row">
                        <div class="settings-label">Template Data</div>
                        <div class="settings-field">
                            <textarea class="form-control" rows="5"
                                placeholder="Enter template data as JSON..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'templateData', this.value)">${JSON.stringify(this.content.templateData || {}, null, 2)}</textarea>
                        </div>
                    </div>
                `}
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'templateData') {
            try {
                this.content[field] = JSON.parse(value);
            } catch (e) {
                console.error('Invalid JSON for template data');
            }
        } else {
            this.content[field] = value;
        }
    }

    validate() {
        if (!this.content.subject) return false;
        if (this.content.emailType === 'address' && !this.content.emailAddress) return false;
        if (this.content.emailType === 'customer' && !this.content.customerField) return false;
        if (!this.content.template && !this.content.message) return false;
        return true;
    }
}

if (typeof window !== 'undefined') {
    window.EmailActionNode = EmailActionNode;
}

