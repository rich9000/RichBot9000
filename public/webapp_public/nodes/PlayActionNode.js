class PlayActionNode extends ActionNode {
    constructor(data = {}) {
        super(data);
        this.type = 'action';
        this.subtype = 'play';
        this.icon = 'fa-play-circle';
        this.name = 'Play';
        this.description = 'Play an audio file';
        // Ensure content is always an object
        if (!this.content || typeof this.content !== 'object' || Array.isArray(this.content)) {
            this.content = {};
        }
    }

    getNodeInfo() {
        if (this.content?.audioFileId) {
            const audioFile = window.pathBuilder.audioFiles?.find(a => a.id === parseInt(this.content.audioFileId));
            return audioFile ? 
                `Audio: ${audioFile.name}${this.content.loopCount ? `<br>Loop: ${this.content.loopCount}` : ''}` : 
                'Audio file not found';
        } else if (this.content?.audioUrl) {
            return `URL: ${this.content.audioUrl}${this.content.loopCount ? `<br>Loop: ${this.content.loopCount}` : ''}`;
        }
        return 'No audio selected';
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const audioFiles = context.audioFiles || [];
        const showSave = !(context && context.inlineEdit);
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Audio File</div>
                    <div class="settings-field">
                        <select class="form-control" name="audioFileId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'audioFileId', this.value)">
                            <option value="">Select Audio File</option>
                            ${audioFiles.map(audio => `
                                <option value="${audio.id}" ${this.content.audioFileId == audio.id ? 'selected' : ''}>
                                    ${audio.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Or External URL</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" name="audioUrl"
                            placeholder="Enter audio URL..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'audioUrl', this.value)"
                            value="${this.content.audioUrl || ''}">
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Loop Count</div>
                    <div class="settings-field">
                        <input type="number" class="form-control" name="loopCount"
                            placeholder="Number of times to play (0 for infinite)"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'loopCount', this.value)"
                            value="${this.content.loopCount || 1}">
                    </div>
                </div>
            </div>
            ${showSave ? super.getSettingsFormTemplate(nodeIndex, context) : ''}
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        console.log('[PlayActionNode] updateContent:', field, value, 'before:', JSON.stringify(this.content));
        switch (field) {
            case 'audioFileId':
                this.content.audioFileId = value ? parseInt(value) : null;
                if (value) this.content.audioUrl = ''; // Clear URL if file is selected
                break;
            case 'audioUrl':
                this.content.audioUrl = value;
                if (value) this.content.audioFileId = null; // Clear file if URL is entered
                break;
            case 'loopCount':
                this.content.loopCount = parseInt(value);
                break;
        }
        console.log('[PlayActionNode] updateContent: after', JSON.stringify(this.content));
    }
}

if (typeof window !== 'undefined') {
    window.PlayActionNode = PlayActionNode;
}

