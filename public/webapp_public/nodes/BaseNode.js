// BaseNode.js - Base class for all nodes
if (typeof window.BaseNode === 'undefined') {
    window.BaseNode = class BaseNode {
        constructor(data = {}) {
            this.id = data.id || this.generateId();
            this.type = data.type || '';
            this.subtype = data.subtype || '';
            this.options = data.options || {};
            this.settings = data.settings || {};
            this.content = data.content || {};
            // Ensure content is always an object
            if (!this.content || typeof this.content !== 'object' || Array.isArray(this.content)) {
                this.content = {};
            }
            this.icon = '';
            this.name = '';
            this.color = '';
            this.description = '';
        }

        getMenuItemTemplate() {
            return `
                <div class="palette-item" draggable="true" data-node-type="${this.type}" data-node-subtype="${this.subtype}">
                    <i class="fas ${this.icon}"></i>
                    <span>${this.name}</span>
                </div>
            `;
        }

        getCompactTemplate(nodeIndex) {
            return `
                <div class="conversation-node ${this.type} ${this.subtype}" data-node-index="${nodeIndex}">
                    <div class="node-header" onclick="window.pathBuilder.toggleNodeForm(this.parentElement)">
                        <i class="fas ${this.icon}"></i>
                        <span>${this.name}</span>
                        <span class="node-info">${this.getNodeInfo()}</span>
                        <i class="fas fa-chevron-down node-toggle"></i>
                        ${this.getNodeActions(nodeIndex)}
                    </div>
                    <div class="node-body" style="display: none;">
                        ${this.getEditFormTemplate(nodeIndex)}
                    </div>
                </div>
            `;
        }

        getNodeActions(nodeIndex) {
            return `
                <div class="node-actions">
                    <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${nodeIndex}">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${nodeIndex}">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
            `;
        }

        getNodeInfo(assistants = [], scripts = []) {
            return '';
        }

        getEditFormTemplate(nodeIndex, context = {}) {
            return `
                <div class="settings-section card">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0">Settings</h6>
                        <button class="btn btn-sm btn-outline-primary edit-settings" onclick="window.pathBuilder.toggleNodeSettings(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="settings-summary">
                            ${this.getNodeInfo(context)}
                        </div>
                        <div class="settings-form" style="display: none;">
                            ${this.getSettingsFormTemplate(nodeIndex, context)}
                        </div>
                    </div>
                </div>
            `;
        }

        getSettingsFormTemplate(nodeIndex, context = {}) {
            // By default, just return a Save button. Subclasses should append their custom fields above this button.
            if (context && context.inlineEdit) return '';
            return `
                <button class="btn btn-sm btn-success save-node mt-2" type="button">
                    <i class="fas fa-save"></i> Save
                </button>
            `;
        }

        validate() {
            return true;
        }

        updateContent(field, value) {
            if (!this.content) this.content = {};
            this.content[field] = value;
        }

        toJSON() {
            return {
                id: this.id,
                type: this.type,
                subtype: this.subtype,
                content: this.content
            };
        }

        // New: Returns the full node card HTML for use in the flow chain
        getNodeCardHtml(nodeIndex, context = {}, options = {}) {
            let displayName = this.name;
            if (this.type === 'decision') {
                if (this.subtype === 'user') displayName = 'User Decision';
                else if (this.subtype === 'assistant') displayName = 'Assistant Decision';
                else if (this.subtype === 'conditional') displayName = 'Conditional Decision';
                else displayName = this.name || 'Decision';
            }
            if (this.type === 'entry') {
                // Entry node: info button (and delete) on right, icon/title on left, info centered below
                return `
                <div class="conversation-node card mb-3 ${this.type} ${this.subtype}" data-node-index="${nodeIndex}">
                    <div class="card-header node-header" style="display:flex;flex-direction:column;align-items:stretch;">
                        <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <i class="fas ${this.icon}"></i>
                                <span class="node-title">${displayName}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                                ${this.showNodeControls(nodeIndex) ? `
                                    <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}" title="Delete"><i class="fas fa-trash"></i></button>
                                ` : ''}
                            </div>
                        </div>
                        <span class="node-brief-info ms-2" style="display:block;text-align:center;width:100%;margin-top:0.5rem;">${this.getNodeInfo(context.assistants, context.scripts)}</span>
                    </div>
                    <div class="card-body node-body" style="display:none;">
                        <div class="node-detailed-info">${this.getDetailsHtml(nodeIndex, context)}</div>
                        <form class="node-edit-form" style="display:none;">${this.getSettingsFormTemplate(nodeIndex, context)}</form>
                    </div>
                </div>
                `;
            } else if (this.type === 'action') {
                // Action node: single row, 3-column grid
                return `
                <div class="conversation-node card mb-3 ${this.type} ${this.subtype}" data-node-index="${nodeIndex}" style="max-width:540px;margin-left:auto;margin-right:auto;">
                    <div class="card-header node-header node-header-action-grid" style="display:grid;grid-template-columns:1fr 2fr 1fr;grid-template-rows:38px;align-items:center;gap:0.25rem;width:100%;background:#f8f9fa;border-bottom:1px solid #dee2e6;border-radius:8px 8px 0 0;">
                        <div class="node-header-action-cell node-header-action-icon" style="grid-row:1;grid-column:1;display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas ${this.icon}"></i>
                            <span class="node-title">${displayName}</span>
                        </div>
                        <div class="node-header-action-cell node-header-action-info" style="grid-row:1;grid-column:2;align-self:center;text-align:center;display:flex;align-items:center;justify-content:center;">
                            <span class="node-brief-info">${this.getNodeInfo(context.assistants, context.scripts)}</span>
                        </div>
                        <div class="node-header-action-cell node-header-action-controls" style="grid-row:1;grid-column:3;display:flex;flex-direction:row;gap:0.5rem;justify-content:flex-end;align-items:center;">
                            <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                            <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${nodeIndex}" title="Move Up" ${nodeIndex <= 1 ? 'disabled' : ''}><i class="fas fa-arrow-up"></i></button>
                            <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${nodeIndex}" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="card-body node-body" style="display:none;">
                        <div class="node-detailed-info">${this.getDetailsHtml(nodeIndex, context)}</div>
                        <form class="node-edit-form" style="display:none;">${this.getSettingsFormTemplate(nodeIndex, context)}</form>
                    </div>
                </div>
                `;
            } else if (this.type === 'data' || this.type === 'decision') {
                // Data and decision nodes: same layout as action nodes
                return `
                <div class="conversation-node card mb-3 ${this.type} ${this.subtype}" data-node-index="${nodeIndex}" style="max-width:540px;margin-left:auto;margin-right:auto;">
                    <div class="card-header node-header node-header-action-grid" style="display:grid;grid-template-columns:1fr 2fr 1fr;grid-template-rows:38px 38px;align-items:center;gap:0.25rem;width:100%;min-height:76px;background:#f8f9fa;border-bottom:1px solid #dee2e6;border-radius:8px 8px 0 0;">
                        <div class="node-header-action-cell node-header-action-icon" style="grid-row:1;grid-column:1;display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas ${this.icon}"></i>
                            <span class="node-title">${displayName}</span>
                        </div>
                        <div class="node-header-action-cell node-header-action-info" style="grid-row:1/span 2;grid-column:2;align-self:center;text-align:center;display:flex;align-items:center;justify-content:center;">
                            <span class="node-brief-info">${this.getNodeInfo(context.assistants, context.scripts)}</span>
                        </div>
                        <div class="node-header-action-cell node-header-action-up" style="grid-row:1;grid-column:3;align-self:center;justify-self:end;">
                            <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${nodeIndex}" title="Move Up" ${nodeIndex <= 1 ? 'disabled' : ''}><i class="fas fa-arrow-up"></i></button>
                        </div>
                        <div class="node-header-action-cell node-header-action-controls" style="grid-row:2;grid-column:1;display:flex;flex-direction:row;gap:0.5rem;justify-content:flex-start;align-items:center;">
                            <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="node-header-action-cell node-header-action-down" style="grid-row:2;grid-column:3;align-self:center;justify-self:end;">
                            <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${nodeIndex}" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                        </div>
                    </div>
                    <div class="card-body node-body" style="display:none;">
                        <div class="node-detailed-info">${this.getDetailsHtml(nodeIndex, context)}</div>
                        <form class="node-edit-form" style="display:none;">${this.getSettingsFormTemplate(nodeIndex, context)}</form>
                    </div>
                </div>
                `;
            } else {
                // Default: full width for entry/decision/data
                return `
                <div class="conversation-node card mb-3 ${this.type} ${this.subtype}" data-node-index="${nodeIndex}">
                    <div class="card-header node-header">
                        <div class="node-header-main">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas ${this.icon}"></i>
                                <span class="node-title">${displayName}</span>
                            </div>
                            <span class="node-brief-info ms-2">${this.getNodeInfo(context.assistants, context.scripts)}</span>
                            <div class="node-header-actions">
                                <button class="btn btn-sm btn-outline-info info-toggle" type="button" title="Show Info"><i class="fas fa-info-circle"></i></button>
                                ${this.showNodeControls(nodeIndex) ? `
                                    <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nodeIndex}" title="Delete"><i class="fas fa-trash"></i></button>
                                ` : ''}
                            </div>
                        </div>
                        <div class="node-header-controls">
                            <button class="btn btn-sm btn-outline-secondary move-up" data-node-index="${nodeIndex}" title="Move Up"><i class="fas fa-arrow-up"></i></button>
                            <button class="btn btn-sm btn-outline-secondary move-down" data-node-index="${nodeIndex}" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                        </div>
                    </div>
                    <div class="card-body node-body" style="display:none;">
                        <div class="node-detailed-info">${this.getDetailsHtml(nodeIndex, context)}</div>
                        <form class="node-edit-form" style="display:none;">${this.getSettingsFormTemplate(nodeIndex, context)}</form>
                    </div>
                </div>
                `;
            }
        }

        /**
         * Node implementers: Override getDetailsHtml(nodeIndex) to provide custom details HTML for the main details area.
         * By default, it calls getDetailedInfo(nodeIndex).
         */
        getDetailsHtml(nodeIndex, context = {}) {
            let html = this.getDetailedInfo(nodeIndex, context);
            // Only show edit button if not in ActionNodeList context
            if (!context || !context.actionNodeList) {
                html += '<button class="btn btn-sm btn-outline-primary mt-2 edit-toggle" type="button"><i class="fas fa-edit"></i> Edit</button>';
            }
            return html;
        }

        /**
         * Node implementers: All <input>, <select>, <textarea> in getSettingsFormTemplate should have a name attribute matching the content/option field.
         * Example: <input name="say_text" ...> or <textarea name="welcomeMessage" ...>
         */

        // By default, show node controls (up/down/delete)
        showNodeControls(nodeIndex) {
            return true;
        }

        // Returns detailed info for the node (can be overridden by subclasses)
        getDetailedInfo(nodeIndex, context = {}) {
            let briefInfoHtml;
            if (this.type === 'action' && this.subtype === 'route') {
                briefInfoHtml = this.getNodeInfo(context.nodes, context.nodeTypes);
            } else {
                briefInfoHtml = this.getNodeInfo(context.assistants, context.scripts);
            }
            // Special case for RouteActionNode
            if (this.type === 'action' && this.subtype === 'route') {
                return `<div>${briefInfoHtml}</div>`;
            }
            // By default, just repeat getNodeInfo
            return `<div>${briefInfoHtml}</div>`;
        }

        generateId() {
            return 'node-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2, 9);
        }

        /**
         * Template for displaying this node as a row in an ActionNodeList (e.g., as a child of a DecisionNode).
         * Includes number badge, info, edit, up, down, delete controls.
         */
        actionNodeListRowTemplate(nodeIndex, context = {}) {
            return `
                <div class="action-node-list-row d-flex align-items-center" data-node-index="${nodeIndex}">
                    <span class="badge bg-primary me-2" style="min-width:2em;">${nodeIndex + 1}</span>
                    <span class="flex-grow-1">
                      <strong>${this.name || this.type}</strong>
                      <span class="ms-2 text-muted small">${this.getNodeInfo(context)}</span>
                    </span>
                    <button class="btn btn-xs btn-outline-info ms-2 info-action-list-node" title="Info"><i class="fas fa-info-circle"></i></button>
                    <button class="btn btn-xs btn-outline-secondary ms-1 move-up-node" title="Move Up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-xs btn-outline-secondary ms-1 move-down-node" title="Move Down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-xs btn-outline-danger ms-1 delete-action-list-node" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            `;
        }

        /**
         * Template for displaying node details/info inline in ActionNodeList.
         */
        actionNodeListInfoTemplate(nodeIndex, context = {}) {
            return `
                <div class="action-node-list-info-row bg-light p-2 border rounded mb-2">
                    <div class="fw-bold mb-2">Action Details</div>
                    ${this.getDetailsHtml(nodeIndex, { ...context, actionNodeList: true })}
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary edit-action-list-node" type="button"><i class="fas fa-edit"></i> Edit</button>
                    </div>
                </div>
            `;
        }

        /**
         * Returns the form for editing this node in an ActionNodeList context. Subclasses can override for custom forms.
         */
        actionNodeListFormTemplate(nodeIndex, context = {}) {
            return `
                <div class="fw-bold mb-2">Edit Action</div>
                <form>
                    ${this.getSettingsFormTemplate(nodeIndex, { ...context, inlineEdit: true })}
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm save-action-node">Save</button>
                        <button type="button" class="btn btn-secondary btn-sm cancel-action-node">Cancel</button>
                    </div>
                </form>
            `;
        }

        actionNodeListEditTemplate(nodeIndex, context = {}) {
            return `
                <div class="action-node-list-edit-form-row bg-light p-2 border rounded mb-2">
                    ${this.actionNodeListFormTemplate(nodeIndex, context)}
                </div>
            `;
        }

        actionNodeListGetSmallDetailsHtml(nodeIndex, context = {}) {
            return this.getDetailsHtml(nodeIndex, context);
        }

        actionNodeListGetSmallSettingsFormTemplate(nodeIndex, context = {}) {
            return this.getSettingsFormTemplate(nodeIndex, context);
        }

    };
    console.log('BaseNode class registered');
} 