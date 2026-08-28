class ConversationPathActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'conversationPath' });
        this.type = 'action';
        this.subtype = 'conversationPath';
    }
    getNodeInfo(context = {}) {
        let paths = context.paths || [];
        let targetPath = paths.find(p => String(p.id) === String(this.content.targetPathId));
        if (!targetPath) {
            const pathsArr = (window.appState && appState.data && Array.isArray(appState.data.conversation_paths))
                ? appState.data.conversation_paths
                : [];
            targetPath = pathsArr.find(p => String(p.id) === String(this.content.targetPathId));
        }
        return targetPath ? `Path: ${targetPath.name}` : 'No target path selected';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        console.log('ConversationPathActionNode.getSettingsFormTemplate context:', context);
        console.log('ConversationPathActionNode.getSettingsFormTemplate context.paths:', context.paths);
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Target Path</div>
                    <div class="settings-field">
                        <select class="form-control" name="targetPathId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'targetPathId', this.value)">
                            <option value="">Select target path</option>
                            ${(context.paths || []).map(path => `
                                <option value="${path.id}" ${this.content.targetPathId == path.id ? 'selected' : ''}>
                                    ${path.name}
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
        console.log('[ConversationPathActionNode] updateContent', field, value, JSON.stringify(this.content));
        if (!this.content) this.content = {};
        if (field === 'targetPathId') this.content.targetPathId = value ? parseInt(value) : null;
    }
    getDetailsHtml(nodeIndex, context = {}) {
        let html = `<div>${this.getNodeInfo(context)}</div>`;
        if (!context || !context.actionNodeList) {
            html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        }
        return html;
    }
}

if (typeof window !== 'undefined') {
    window.ConversationPathActionNode = ConversationPathActionNode;
}

