class SMSActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'sms' });
        this.type = 'action';
        this.subtype = 'sms';
        if (!this.content) this.content = {};
        this.content.smsType = data.content?.smsType || 'user';
        this.content.phoneNumber = data.content?.phoneNumber || '';
        this.content.customerField = data.content?.customerField || '';
        this.content.message = data.content?.message || '';
    }

    getNodeInfo() {
        const type = this.content.smsType === 'user' ? 'User' :
                    this.content.smsType === 'phone' ? `Phone: ${this.content.phoneNumber}` :
                    this.content.smsType === 'customer' ? `Customer Field: ${this.content.customerField}` : 'Unknown';
        return `SMS to: ${type}<br>Message: "${this.content.message?.substring(0, 30)}${this.content.message?.length > 30 ? '...' : ''}"`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">SMS Type</div>
                    <div class="settings-field">
                        <select class="form-control" name="smsType"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'smsType', this.value)">
                            <option value="user" ${this.content.smsType === 'user' ? 'selected' : ''}>Send to User</option>
                            <option value="phone" ${this.content.smsType === 'phone' ? 'selected' : ''}>Send to Number</option>
                            <option value="customer" ${this.content.smsType === 'customer' ? 'selected' : ''}>Send to Customer</option>
                        </select>
                    </div>
                </div>
                ${this.content.smsType === 'phone' ? `
                    <div class="settings-row">
                        <div class="settings-label">Phone Number</div>
                        <div class="settings-field">
                            <input type="text" class="form-control" name="phoneNumber"
                                placeholder="Enter phone number..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'phoneNumber', this.value)"
                                value="${this.content.phoneNumber || ''}">
                        </div>
                    </div>
                ` : ''}
                ${this.content.smsType === 'customer' ? `
                    <div class="settings-row">
                        <div class="settings-label">Customer Field</div>
                        <div class="settings-field">
                            <input type="text" class="form-control" name="customerField"
                                placeholder="Enter customer field name (e.g., phone_number)..."
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'customerField', this.value)"
                                value="${this.content.customerField || ''}">
                        </div>
                    </div>
                ` : ''}
                <div class="settings-row">
                    <div class="settings-label">Message</div>
                    <div class="settings-field">
                        <textarea class="form-control" name="message" rows="3"
                            placeholder="Enter SMS message..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'message', this.value)">${this.content.message || ''}</textarea>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        this.content[field] = value;
    }

    validate() {
        if (!this.content.message) return false;
        if (this.content.smsType === 'phone' && !this.content.phoneNumber) return false;
        if (this.content.smsType === 'customer' && !this.content.customerField) return false;
        return true;
    }
}

if (typeof window !== 'undefined') {
    window.SmsActionNode = SMSActionNode;
}

