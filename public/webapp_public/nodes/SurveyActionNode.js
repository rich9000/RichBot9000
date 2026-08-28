class SurveyActionNode extends ActionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'survey' });
        this.type = 'action';
        this.subtype = 'survey';
    }
    getNodeInfo(surveys = [], phoneTrees = [], assistants = []) {
        console.log('getNodeInfo surveys:', surveys);
        console.log('this.content:', this.content);
        if (!Array.isArray(surveys)) {
            surveys = [];
        }
        if (!this.content || Object.keys(this.content).length === 0) {
            this.content = {
                surveyType: '',
                surveyId: null
            };
        }
        const survey = surveys.find(s => s.id === this.content.surveyId);
        console.log('found survey:', survey);
        return `Type: ${this.content.surveyType || 'No type'}, Survey: ${survey ? survey.name : 'None'} (ID: ${this.content.surveyId || 'None'})`;
    }
    getSettingsFormTemplate(nodeIndex, context = {}) {
        console.log('getSettingsFormTemplate context:', context);
        console.log('getSettingsFormTemplate this.content:', this.content);
        const surveys = context.surveys || [];
        const phoneTrees = context.phoneTrees || [];
        const assistants = context.assistants || [];
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">Survey Type</div>
                    <div class="settings-field">
                        <select class="form-control" name="surveyType"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'surveyType', this.value)">
                            <option value="">Select Survey Type</option>
                            <option value="phone_tree_survey" ${this.content.surveyType === 'phone_tree_survey' ? 'selected' : ''}>Phone Tree Survey</option>
                            <option value="ask_and_wait" ${this.content.surveyType === 'ask_and_wait' ? 'selected' : ''}>Ask and Wait</option>
                            <option value="survey_assistant" ${this.content.surveyType === 'survey_assistant' ? 'selected' : ''}>Survey Assistant</option>
                        </select>
                    </div>
                </div>
                ${this.content.surveyType === 'phone_tree_survey' ? `
                    <div class="settings-row">
                        <div class="settings-label">Phone Tree</div>
                        <div class="settings-field">
                            <select class="form-control" name="phoneTreeId"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'phoneTreeId', this.value)">
                                <option value="">Select Phone Tree</option>
                                ${phoneTrees.map(tree => `
                                    <option value="${tree.id}" ${this.content.phoneTreeId === tree.id ? 'selected' : ''}>
                                        ${tree.name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                ` : ''}
                ${this.content.surveyType === 'ask_and_wait' ? `
                    <div class="settings-row">
                        <div class="settings-label">Question</div>
                        <div class="settings-field">
                            <textarea class="form-control" name="question" rows="3"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'question', this.value)">${this.content.question || ''}</textarea>
                        </div>
                    </div>
                    <div class="settings-row">
                        <div class="settings-label">Timeout (seconds)</div>
                        <div class="settings-field">
                            <input type="number" class="form-control" name="timeout"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'timeout', this.value)"
                                value="${this.content.timeout || 30}">
                        </div>
                    </div>
                ` : ''}
                ${this.content.surveyType === 'survey_assistant' ? `
                    <div class="settings-row">
                        <div class="settings-label">Assistant</div>
                        <div class="settings-field">
                            <select class="form-control" name="assistantId"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'assistantId', this.value)">
                                <option value="">Select Assistant</option>
                                ${assistants.map(assistant => `
                                    <option value="${assistant.id}" ${this.content.assistantId === assistant.id ? 'selected' : ''}>
                                        ${assistant.name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="settings-row">
                        <div class="settings-label">Survey Instructions</div>
                        <div class="settings-field">
                            <textarea class="form-control" name="instructions" rows="3"
                                onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'instructions', this.value)">${this.content.instructions || ''}</textarea>
                        </div>
                    </div>
                ` : ''}
                <div class="settings-row">
                    <div class="settings-label">Survey</div>
                    <div class="settings-field">
                        <select class="form-control" name="surveyId"
                            onchange="window.pathBuilder.updateNodeContent(${nodeIndex}, 'surveyId', this.value)">
                            <option value="">Select Survey</option>
                            ${surveys.map(survey => `
                                <option value="${survey.id}" ${this.content.surveyId === survey.id ? 'selected' : ''}>
                                    ${survey.title}
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
        if (field === 'surveyId') this.content.surveyId = value ? parseInt(value) : null;
        else if (field === 'surveyType') this.content.surveyType = value;
        else if (field === 'question') this.content.question = value;
        else if (field === 'timeout') this.content.timeout = parseInt(value);
        else if (field === 'phoneTreeId') this.content.phoneTreeId = value ? parseInt(value) : null;
        else if (field === 'assistantId') this.content.assistantId = value ? parseInt(value) : null;
        else if (field === 'instructions') this.content.instructions = value;
    }
}

if (typeof window !== 'undefined') {
    window.SurveyActionNode = SurveyActionNode;
}

