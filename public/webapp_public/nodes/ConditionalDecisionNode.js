class ConditionalDecisionNode extends DecisionNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'conditional' });
        this.type = 'decision';
        this.subtype = 'conditional';
        this.content = {
            conditionType: data.content?.conditionType || 'valueExists',
            returnType: data.content?.returnType || 'boolean',
            script: data.content?.script || null,
            variable: data.content?.variable || '',
            value: data.content?.value || '',
            description: data.content?.description || ''
        };
        if (data.actions && typeof data.actions === 'object' && data.actions.constructor && data.actions.constructor.name === 'ActionNodeList') {
            this.actions = data.actions;
        } else if (Array.isArray(data.actions)) {
            this.actions = new window.ActionNodeList(data.actions);
        } else {
            this.actions = new window.ActionNodeList();
        }
    }

    static conditionTypes = {
        valueExists: {
            name: 'Value Exists',
            description: 'Check if a variable exists in path_state or conversation',
            parameters: ['variable']
        },
        hasProperty: {
            name: 'Has Property',
            description: 'Check if an object has a specific property',
            parameters: ['variable']
        },
        propertyEquals: {
            name: 'Property Equals',
            description: 'Check if a property equals a specific value',
            parameters: ['variable', 'value']
        },
        script: {
            name: 'Script',
            description: 'Execute a custom script for complex conditions',
            parameters: ['script']
        }
    };

    static operators = {
        equals: 'Equals',
        notEquals: 'Not Equals',
        greaterThan: 'Greater Than',
        lessThan: 'Less Than',
        greaterThanOrEqual: 'Greater Than or Equal',
        lessThanOrEqual: 'Less Than or Equal',
        contains: 'Contains',
        notContains: 'Does Not Contain',
        startsWith: 'Starts With',
        endsWith: 'Ends With'
    };

    getScripts(context = {}) {
        let scripts = context.scripts || [];
        if ((!scripts || scripts.length === 0) && window.appState && appState.data && Array.isArray(appState.data.scripts)) {
            scripts = appState.data.scripts;
        }
        return scripts;
    }

    getNodeInfo(context = {}) {
        const scripts = context.scripts || [];
        const selectedScript = scripts.find(s => s.id === this.content.script);

        console.log('get node infocontent', this.content);
        
        switch (this.content.conditionType) {
            case 'valueExists':
                return `Check if <strong>${this.content.variable || 'variable'}</strong> exists`;
            case 'hasProperty':
                return `Check if <strong>${this.content.variable || 'variable'}</strong> has property`;
            case 'propertyEquals':
                return `Check if <strong>${this.content.variable || 'variable'}</strong> equals <strong>${this.content.value || 'value'}</strong>`;
            case 'script':
                return selectedScript ? 
                    `Run script: <strong>${selectedScript.name}</strong>` :
                    'No script selected';
            default:
                return 'No condition type selected';
        }
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        const scripts = context.scripts || [];

        console.log('scripts getSettingsFormTemplate', scripts);
        console.log('content getSettingsFormTemplate', this.content);
        
        return `
            <div class="mb-3">
                <label class="form-label">Condition Type</label>
                <select class="form-select" name="conditionType" data-field="conditionType">
                    <option value="">Select a condition type</option>
                    <option value="valueExists" ${this.content.conditionType === 'valueExists' ? 'selected' : ''}>Value Exists</option>
                    <option value="hasProperty" ${this.content.conditionType === 'hasProperty' ? 'selected' : ''}>Has Property</option>
                    <option value="propertyEquals" ${this.content.conditionType === 'propertyEquals' ? 'selected' : ''}>Property Equals</option>
                    <option value="script" ${this.content.conditionType === 'script' ? 'selected' : ''}>Script</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Variable</label>
                <input type="text" class="form-control" name="variable" data-field="variable" value="${this.content.variable || ''}" placeholder="Enter variable name">
            </div>
            <div class="mb-3">
                <label class="form-label">Value</label>
                <input type="text" class="form-control" name="value" data-field="value" value="${this.content.value || ''}" placeholder="Enter value to compare">
            </div>
            <div class="mb-3">
                <label class="form-label">Script</label>
                <select class="form-select" name="script" data-field="script">
                    <option value="">Select a script</option>
                    ${scripts.map(script => `
                        <option value="${script.id}" ${this.content.script === script.id ? 'selected' : ''}>
                            ${script.name}
                        </option>
                    `).join('')}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Return Type</label>
                <select class="form-select" name="returnType" data-field="returnType">
                    <option value="boolean" ${this.content.returnType === 'boolean' ? 'selected' : ''}>Boolean</option>
                    <option value="index" ${this.content.returnType === 'index' ? 'selected' : ''}>Index</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="description" data-field="description" value="${this.content.description || ''}" placeholder="Enter description">
            </div>
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        this.content[field] = value;
        
        // If condition type changes, reset dependent fields
        if (field === 'conditionType') {
            if (value === 'script') {
                this.content.variable = '';
            } else if (value === 'valueExists' || value === 'hasProperty') {
                this.content.script = null;
            }
        }
    }

    validate() {
        const conditionType = this.content.conditionType;
        const typeInfo = ConditionalDecisionNode.conditionTypes[conditionType];
        
        if (!typeInfo) return false;
        
        switch (conditionType) {
            case 'valueExists':
            case 'hasProperty':
                return !!this.content.variable;
            case 'propertyEquals':
                return !!this.content.variable && !!this.content.value;
            case 'script':
                return !!this.content.script;
            default:
                return false;
        }
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: {
                ...this.content,
                conditionType: this.content.conditionType,
                returnType: this.content.returnType,
                script: this.content.script,
                variable: this.content.variable,
                value: this.content.value,
                description: this.content.description
            },
            actions: this.actions ? (typeof this.actions.toJSON === 'function' ? this.actions.toJSON() : this.actions) : []
        };
    }

    getDetailsHtml(nodeIndex, context = {}) {
        let html = `<div>${this.getNodeInfo(context)}</div>`;
        html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
        return html;
    }
}

if (typeof window !== 'undefined') {
    window.ConditionalDecisionNode = ConditionalDecisionNode;
}

