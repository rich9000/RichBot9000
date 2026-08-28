class MonitorCallActionNode extends ActionNode {
    constructor(data = {}) {
        super(data);
        this.type = 'action';
        this.subtype = 'monitorCall';
        this.icon = 'fa-phone';
        this.name = 'Monitor Call';
        this.description = 'Monitor a call with an assistant';
    }

    getNodeInfo(assistantsOrContext = []) {
        console.log('[MonitorCallActionNode] getNodeInfo content:', this.content);
        console.log('[MonitorCallActionNode] getNodeInfo input:', assistantsOrContext);
        
        // Handle both array of assistants and context object
        const assistants = Array.isArray(assistantsOrContext) ? assistantsOrContext : 
                         (assistantsOrContext?.assistants || []);
        
        const assistantId = this.content?.assistantId;
        if (!assistantId) return 'No assistant selected';
        
        console.log('[MonitorCallActionNode] Looking for assistant with ID:', assistantId, 'in:', assistants.map(a => ({ id: a.id, name: a.name })));
        
        if (assistants.length === 0) {
            console.log('[MonitorCallActionNode] Assistants not loaded yet');
            return 'Loading assistants...';
        }
        
        const assistant = assistants.find(a => a.id === assistantId || a.id === parseInt(assistantId));
        if (!assistant) {
            console.log('[MonitorCallActionNode] No assistant found for ID:', assistantId);
            return 'No assistant found for that id.';
        }

        const options = [];
        if (this.content?.startInteractive === true) options.push('Interactive');
        if (this.content?.recordAudio === true) options.push('Record');
        if (this.content?.transcribeAudio === true) options.push('Transcribe');

        console.log('[MonitorCallActionNode] getNodeInfo options:', options);
        return `Assistant: ${assistant.name}${options.length > 0 ? ` (${options.join(', ')})` : ''}`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        console.log('[MonitorCallActionNode] getSettingsFormTemplate content:', this.content);
        const assistants = context.assistants || [];
        const assistantId = this.content?.assistantId;
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Assistant</div>
                    <div class="settings-field">
                        <select class="form-control" name="assistantId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                            <option value="">Select Assistant</option>
                            ${assistants.map(assistant => `
                                <option value="${assistant.id}" ${assistantId == assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Monitoring Options</div>
                    <div class="settings-field">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="startInteractive"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'startInteractive', this.checked)"
                                ${this.content?.startInteractive === true ? 'checked' : ''}>
                            <label class="form-check-label">Start Interactive</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="recordAudio"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'recordAudio', this.checked)"
                                ${this.content?.recordAudio === true ? 'checked' : ''}>
                            <label class="form-check-label">Record Audio</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="transcribeAudio"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'transcribeAudio', this.checked)"
                                ${this.content?.transcribeAudio === true ? 'checked' : ''}>
                            <label class="form-check-label">Transcribe Audio</label>
                        </div>
                    </div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        console.log(`[MonitorCallActionNode] Updating ${field} with value:`, value, typeof value);
        
        // Preserve existing content values
        const existingContent = { ...this.content };
        
        if (field === 'assistantId') {
            this.content = {
                ...existingContent,
                assistantId: value ? parseInt(value) : null
            };
        } else if (field === 'startInteractive') {
            this.content = {
                ...existingContent,
                startInteractive: value === true || value === 'true'
            };
            console.log(`[MonitorCallActionNode] startInteractive set to:`, this.content.startInteractive);
        } else if (field === 'recordAudio') {
            this.content = {
                ...existingContent,
                recordAudio: value === true || value === 'true'
            };
            console.log(`[MonitorCallActionNode] recordAudio set to:`, this.content.recordAudio);
        } else if (field === 'transcribeAudio') {
            this.content = {
                ...existingContent,
                transcribeAudio: value === true || value === 'true'
            };
            console.log(`[MonitorCallActionNode] transcribeAudio set to:`, this.content.transcribeAudio);
        }
        
        console.log('[MonitorCallActionNode] Updated content:', this.content);
    }
}

if (typeof window !== 'undefined') {
    window.MonitorCallActionNode = MonitorCallActionNode;
} 