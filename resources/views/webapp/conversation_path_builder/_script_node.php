<?php
/**
 * Script Node Component
 * Allows execution of custom scripts in the conversation path
 */
?>

<div class="node script-node" data-node-type="script">
    <div class="node-header">
        <i class="fas fa-code"></i>
        <span>Script</span>
        <div class="node-actions">
            <button class="btn btn-sm btn-link delete-node" title="Delete Node">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="node-content">
        <div class="form-group">
            <label>Script</label>
            <select class="form-control script-select">
                <option value="">Select a script...</option>
            </select>
        </div>
        <div class="script-params"></div>
        <div class="form-group">
            <label>Next Node</label>
            <select class="form-control next-node">
                <option value="">Select next node...</option>
            </select>
        </div>
    </div>
</div>

<script>
class ScriptNode {
    constructor(node) {
        this.node = node;
        this.scriptSelect = node.querySelector('.script-select');
        this.scriptParams = node.querySelector('.script-params');
        this.nextNodeSelect = node.querySelector('.next-node');
        
        this.init();
    }

    async init() {
        await this.loadScripts();
        this.bindEvents();
    }

    async loadScripts() {
        try {
            const response = await fetch('/api/scripts', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load scripts');
            
            const scripts = await response.json();
            this.scriptSelect.innerHTML = '<option value="">Select a script...</option>' +
                scripts.map(script => 
                    `<option value="${script.id}" 
                        data-params='${JSON.stringify(script.parameters)}'
                        data-return-type="${script.return_type}">
                        ${script.name}
                    </option>`
                ).join('');
        } catch (error) {
            console.error('Error loading scripts:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to load scripts');
            }
        }
    }

    bindEvents() {
        this.scriptSelect.addEventListener('change', () => this.handleScriptChange());
        this.nextNodeSelect.addEventListener('change', () => this.handleNextNodeChange());
    }

    handleScriptChange() {
        const selectedOption = this.scriptSelect.selectedOptions[0];
        if (!selectedOption.value) {
            this.scriptParams.innerHTML = '';
            return;
        }

        const params = JSON.parse(selectedOption.dataset.params || '{}');
        const returnType = selectedOption.dataset.returnType || 'string';

        this.scriptParams.innerHTML = Object.entries(params).map(([key, value]) => `
            <div class="form-group">
                <label>${key}</label>
                <input type="text" class="form-control" 
                    data-param="${key}" 
                    value="${value}"
                    placeholder="Enter ${key}">
            </div>
        `).join('') + `
            <div class="form-group">
                <label>Return Type</label>
                <input type="text" class="form-control" value="${returnType}" readonly>
            </div>
        `;
    }

    handleNextNodeChange() {
        // Handle next node selection
        const nextNodeId = this.nextNodeSelect.value;
        // Update node connections in the path builder
        if (typeof updateNodeConnections === 'function') {
            updateNodeConnections(this.node, nextNodeId);
        }
    }

    getData() {
        const selectedOption = this.scriptSelect.selectedOptions[0];
        if (!selectedOption.value) return null;

        const params = {};
        this.scriptParams.querySelectorAll('[data-param]').forEach(input => {
            params[input.dataset.param] = input.value;
        });

        return {
            type: 'script',
            script_id: selectedOption.value,
            parameters: params,
            return_type: selectedOption.dataset.returnType,
            next_node: this.nextNodeSelect.value
        };
    }

    setData(data) {
        if (!data) return;
        
        this.scriptSelect.value = data.script_id;
        this.handleScriptChange();
        
        // Set parameter values
        Object.entries(data.parameters || {}).forEach(([key, value]) => {
            const input = this.scriptParams.querySelector(`[data-param="${key}"]`);
            if (input) input.value = value;
        });

        this.nextNodeSelect.value = data.next_node || '';
    }
}

// Register the node type
if (typeof registerNodeType === 'function') {
    registerNodeType('script', ScriptNode);
}
</script> 