class SayActionNode extends ActionNode {
    constructor(data = {}) {
        super(data);
        console.log('SayActionNode instantiated', this);
        this.type = 'action';
        this.subtype = 'say';
        this.icon = 'fa-comment';
        this.name = 'Say';
        this.description = 'Speak text to the user';
    }

    getNodeInfo() {
        return this.content?.say_text ? 
            `Text: "${this.content.say_text.substring(0, 30)}${this.content.say_text.length > 30 ? '...' : ''}"${this.content?.voice ? `<br>Voice: ${this.content.voice}` : ''}` : 
            'No text set';
    }

    getDetailsHtml(nodeIndex, context = {}) {
        let html = `<div>${this.getNodeInfo()}</div>`;
        if (!context || !context.actionNodeList) {
            html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        }
        return html;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Text to Say</div>
                    <div class="settings-field">
                        <textarea class="form-control" name="say_text" rows="3" 
                            placeholder="Enter text to say..."
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'say_text', this.value)">${this.content?.say_text || ''}</textarea>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Voice</div>
                    <div class="settings-field">
                        <select class="form-control" name="voice"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'voice', this.value)">
                            <option value="alloy" ${this.content?.voice === 'alloy' ? 'selected' : ''}>Alloy</option>
                            <option value="echo" ${this.content?.voice === 'echo' ? 'selected' : ''}>Echo</option>
                            <option value="fable" ${this.content?.voice === 'fable' ? 'selected' : ''}>Fable</option>
                            <option value="onyx" ${this.content?.voice === 'onyx' ? 'selected' : ''}>Onyx</option>
                            <option value="nova" ${this.content?.voice === 'nova' ? 'selected' : ''}>Nova</option>
                            <option value="shimmer" ${this.content?.voice === 'shimmer' ? 'selected' : ''}>Shimmer</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'say_text') this.content.say_text = value;
        else if (field === 'voice') this.content.voice = value;
    }
}

if (typeof window !== 'undefined') {
    window.SayActionNode = SayActionNode;
} 