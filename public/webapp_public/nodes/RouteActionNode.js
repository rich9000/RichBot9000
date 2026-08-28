class RouteActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'route' });
        this.type = 'action';
        this.subtype = 'route';
    }

    getNodeInfo(nodes = [], nodeTypes = {}) {
        if (!Array.isArray(nodes) && nodes && nodes.nodes) nodes = nodes.nodes;
        // Check for conversation path routing
        const pathsArr = (window.appState && appState.data && Array.isArray(appState.data.conversation_paths))
            ? appState.data.conversation_paths
            : [];
        if (this.content.targetPathId) {
            const path = pathsArr.find(p => String(p.id) === String(this.content.targetPathId));
            if (path) {
                return `Route to Path: ${path.name}`;
            }
        }
        if ((!nodes || nodes.length === 0) && pathsArr.length > 0) {
            const path = pathsArr.find(p => Array.isArray(p.nodes) && p.nodes.length > 0);
            if (path && Array.isArray(path.nodes)) nodes = path.nodes;
        }
        if (this.content.targetNode !== null && this.content.targetNode !== undefined) {
            console.log('targetNode', this.content.targetNode);
            const targetNode = nodes[this.content.targetNode];
            if (targetNode) {
                const typeObj = nodeTypes[targetNode.type];
                const subtypeObj = typeObj ? typeObj[targetNode.subtype] : null;
                if (subtypeObj && subtypeObj.name) {
                    return `Route to: ${subtypeObj.name}`;
                } else {
                    // Fallback: show type/subtype or a generic label
                    return `Route to: ${targetNode.name || targetNode.subtype || targetNode.type || 'Unknown Node'}`;
                }
            }
        }
        return 'No target node selected';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {


        

        const nodes = context.nodes || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Route To</div>
                    <div class="settings-field">
                        <select class="form-control route-target" name="targetNode"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'targetNode', this.value)">
                            <option value="">Select Target Node</option>
                            ${nodes.map((targetNode, index) => {
                                if (index === nodeIndex) return '';
                                const label = `${targetNode.name || targetNode.subtype || 'Node'} (${targetNode.type})`;
                                return `<option value="${index}" ${this.content.targetNode === index ? 'selected' : ''}>${label}</option>`;
                            }).join('')}
                        </select>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }
    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'targetNode') this.content.targetNode = value ? parseInt(value) : null;
    }
}

if (typeof window !== 'undefined') {
    window.RouteActionNode = RouteActionNode;
}

