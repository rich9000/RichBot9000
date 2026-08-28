class PhoneTreeActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'phoneTree' });
        this.type = 'action';
        this.subtype = 'phoneTree';
    }
    getNodeInfo(phoneTrees = []) {
        const phoneTree = phoneTrees.find(p => p.id === this.content.phoneTreeId);
        return phoneTree ? `Phone Tree: ${phoneTree.name}` : 'No phone tree selected';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        const phoneTrees = context.phoneTrees || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Phone Tree</div>
                    <div class="settings-field">
                        <select class="form-control" name="phoneTreeId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'phoneTreeId', this.value)">
                            <option value="">Select Phone Tree</option>
                            ${phoneTrees.map(tree => `
                                <option value="${tree.id}" ${this.content.phoneTreeId == tree.id ? 'selected' : ''}>
                                    ${tree.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }
    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'phoneTreeId') this.content.phoneTreeId = value ? parseInt(value) : null;
    }
}

if (typeof window !== 'undefined') {
    window.PhoneTreeActionNode = PhoneTreeActionNode;
}

