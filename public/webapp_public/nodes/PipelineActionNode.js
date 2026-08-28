class PipelineActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'pipeline' });
        this.type = 'action';
        this.subtype = 'pipeline';
    }
    getNodeInfo(pipelines = []) {
        const pipeline = pipelines.find(p => p.id === parseInt(this.content?.pipelineId));
        return pipeline ? `Pipeline: ${pipeline.name}` : 'No pipeline selected';
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        const pipelines = context.pipelines || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Pipeline</div>
                    <div class="settings-field">
                        <select class="form-control" name="pipelineId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'pipelineId', this.value)">
                            <option value="">Select Pipeline</option>
                            ${pipelines.map(pipeline => `
                                <option value="${pipeline.id}" ${parseInt(this.content.pipelineId) === pipeline.id ? 'selected' : ''}>
                                    ${pipeline.name}
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
        if (field === 'pipelineId') this.content.pipelineId = value ? parseInt(value) : null;
    }
}

if (typeof window !== 'undefined') {
    window.PipelineActionNode = PipelineActionNode;
}

