<div class="conversation-path-builder-container" id="conversation-path-builder">
    <div class="row">
        <div class="col-md-3">
            <div class="path-palette">
                <h4>Conversation Paths</h4>
                <div class="path-list"></div>
                <button class="btn btn-primary btn-sm mt-3" id="add-path">
                    <i class="fas fa-plus"></i> New Path
                </button>
                
                <h4 class="mt-4">Data Nodes</h4>
                <div class="node-palette data-palette">
                    <div class="palette-item" draggable="true" data-node-type="data" data-node-subtype="outageCheck">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Outage Check</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="data" data-node-subtype="customerLookup">
                        <i class="fas fa-user"></i>
                        <span>Customer Lookup</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="data" data-node-subtype="custom">
                        <i class="fas fa-code"></i>
                        <span>Custom Script</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="data" data-node-subtype="contextAssistant">
                        <i class="fas fa-robot"></i>
                        <span>Context Assistant</span>
                    </div>
                </div>

                <h4 class="mt-4">Decision Nodes</h4>
                <div class="node-palette decision-palette">
                    <div class="palette-item" draggable="true" data-node-type="decision" data-node-subtype="user">
                        <i class="fas fa-user"></i>
                        <span>User Decision</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="decision" data-node-subtype="assistant">
                        <i class="fas fa-robot"></i>
                        <span>Assistant Decision</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="decision" data-node-subtype="conditional">
                        <i class="fas fa-code-branch"></i>
                        <span>Conditional Decision</span>
                    </div>
                </div>

                <h4 class="mt-4">Action Nodes</h4>
                <div class="node-palette action-palette">
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="assistant">
                        <i class="fas fa-robot"></i>
                        <span>Assistant</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="pipeline">
                        <i class="fas fa-project-diagram"></i>
                        <span>Pipeline</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="phoneTree">
                        <i class="fas fa-sitemap"></i>
                        <span>Phone Tree</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="survey">
                        <i class="fas fa-poll"></i>
                        <span>Survey</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="script">
                        <i class="fas fa-code"></i>
                        <span>Script</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="say">
                        <i class="fas fa-comment"></i>
                        <span>Say</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="play">
                        <i class="fas fa-play-circle"></i>
                        <span>Play</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="hangup">
                        <i class="fas fa-phone-slash"></i>
                        <span>Hang Up</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="voiceMail">
                        <i class="fas fa-microphone"></i>
                        <span>Voice Mail</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="transfer">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Phone Transfer</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="route">
                        <i class="fas fa-random"></i>
                        <span>Route to Conversation Node</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="conversationPath">
                        <i class="fas fa-project-diagram"></i>
                        <span>Route to Conversation Path</span>
                    </div>
                    <div class="palette-item" draggable="true" data-node-type="action" data-node-subtype="websocket">
                        <i class="fas fa-plug"></i>
                        <span>WebSocket Transfer</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="path-canvas">
                <div class="canvas-container"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .conversation-path-builder-container {
        height: 100%;
        padding: 1rem;
    }
    .path-palette {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        height: 100%;
    }
    .path-palette h4 {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    .path-list, .node-palette {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .path-item, .palette-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
    }
    .path-item:hover, .palette-item:hover {
        background: #e9ecef;
    }
    .path-item.active {
        background: #007bff;
        color: white;
        border-color: #0056b3;
    }
    .palette-item {
        cursor: move;
    }
    .path-canvas {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        height: 100%;
        min-height: 600px;
        position: relative;
    }
    .canvas-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 2rem;
    }
    .nodes-container {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .nodes-container .row {
        margin: 0 -0.5rem;
        display: flex;
        flex-wrap: wrap;
    }
    .nodes-container .col-md-6 {
        padding: 0 0.5rem;
        width: 50%;
        min-width: 300px;
    }
    .nodes-container.drag-over {
        background: #e9ecef;
        border-color: #007bff;
    }
    .conversation-node {
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: white;
        display: flex;
        flex-direction: column;
        position: relative;
        width: 100%;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    .conversation-node:not(:last-child)::after {
        content: '';
        position: absolute;
        bottom: -1rem;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 1rem;
        background: #dee2e6;
    }
    .conversation-node.decision {
        border-left: 4px solid #6610f2;
        max-width: 800px;
    }
    .conversation-node.decision::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border: 2px dashed #6610f2;
        border-radius: 4px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .conversation-node.decision.drag-over::before {
        opacity: 1;
    }
    .conversation-node.entry {
        border-left: 4px solid #007bff;
    }
    .conversation-node.data {
        border-left: 4px solid #ffc107;
    }
    .conversation-node.action {
        border-left: 4px solid #20c997;
    }
    .form-check {
        padding: 0.15rem 0.35rem;
        margin: 0;
    }
    .form-check-input {
        margin-top: 0.15rem;
        width: 0.9rem;
        height: 0.9rem;
    }
    .form-check-label {
        font-size: 0.9rem;
        padding-top: 0.15rem;
    }
    .decision-container {
        background: #f8f9fa;
        border-radius: 4px;
        padding: 1rem;
        border: 1px solid #dee2e6;
    }
    .settings-section {
        background: white;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        margin-bottom: 1rem;
    }
    .settings-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    .settings-header h6 {
        margin: 0;
        font-weight: 600;
    }
    .settings-summary {
        padding: 0.75rem 1rem;
        color: #6c757d;
    }
    .settings-form {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
    }
    .settings-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .settings-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 1rem;
        align-items: start;
    }
    .settings-label {
        font-weight: 600;
        color: #495057;
        padding-top: 0.5rem;
    }
    .settings-field {
        flex: 1;
    }
    .settings-field .form-control {
        width: 100%;
    }
    .decision-actions-container {
        background: white;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }
    .decision-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    .decision-header h6 {
        margin: 0;
        font-weight: 600;
    }
    .decision-actions {
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-height: 100px;
        position: relative;
    }
    .decision-actions.drag-over {
        background: rgba(102, 16, 242, 0.1);
        border: 2px dashed #6610f2;
        border-radius: 4px;
    }
    .decision-action-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        position: relative;
        cursor: move;
    }
    .action-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
    }
    .action-number {
        background: #6610f2;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .action-toggle {
        margin-left: auto;
        margin-right: 0.5rem;
        color: #6c757d;
        transition: transform 0.2s ease;
    }
    .decision-action-item.expanded .action-toggle {
        transform: rotate(180deg);
    }
    .action-content {
        padding: 1rem;
        display: none;
    }
    .decision-action-item.expanded .action-content {
        display: block;
    }
    .decision-action-drop-zone {
        border: 2px dashed #6610f2;
        border-radius: 4px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #6610f2;
        background: rgba(102, 16, 242, 0.05);
        transition: all 0.2s ease;
    }
    .decision-action-drop-zone:hover {
        background: rgba(102, 16, 242, 0.1);
    }
    .decision-action-drop-zone i {
        font-size: 2rem;
    }
    .decision-action-drop-zone span {
        font-size: 1rem;
        font-weight: 500;
    }
    .decision-action-drop-zone small {
        font-size: 0.8rem;
    }
    .decision-description {
        margin-bottom: 1rem;
    }
    .entry-point-options {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .entry-point-option {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 1rem;
        width: 100%;
    }
    .entry-point-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .entry-point-header i:first-child {
        font-size: 1.2rem;
        width: 24px;
        color: #007bff;
    }
    .entry-point-header span {
        font-weight: 500;
        flex: 1;
    }
    .entry-point-toggle {
        margin-left: auto;
        color: #6c757d;
        transition: transform 0.2s ease;
    }
    .entry-point-option.expanded .entry-point-toggle {
        transform: rotate(180deg);
    }
    .entry-point-fields {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
        display: none;
    }
    .entry-point-option.expanded .entry-point-fields {
        display: block;
    }
    .entry-point-option .form-check {
        margin: 0;
        padding: 0;
    }
    .entry-point-option .form-check-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: 0.5rem;
    }
    .entry-point-option .form-group {
        margin-bottom: 1rem;
    }
    .entry-point-option .form-group:last-child {
        margin-bottom: 0;
    }
    .entry-point-option.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
    @media (max-width: 992px) {
        .entry-point-options {
            gap: 0.75rem;
        }
    }
    @media (max-width: 768px) {
        .settings-row {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        .settings-label {
            padding-top: 0;
        }
    }
    .save-button-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
    }
    .save-button-container .btn {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .node-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.5rem;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
        min-height: 24px;
    }
    .node-header i {
        font-size: 1rem;
    }
    .node-header span {
        font-weight: 500;
        font-size: 0.9rem;
    }
    .node-toggle {
        margin-left: auto;
        color: #6c757d;
        transition: transform 0.2s ease;
        font-size: 0.8rem;
    }
    .conversation-node.expanded .node-toggle {
        transform: rotate(180deg);
    }
    .node-body {
        padding: 0.75rem;
        background: white;
        border-radius: 4px;
        display: none;
    }
    .conversation-node.expanded .node-body {
        display: block;
    }
    .node-actions {
        margin-left: auto;
        display: flex;
        gap: 0.25rem;
    }
    .node-actions button {
        padding: 0.15rem 0.35rem;
        font-size: 0.8rem;
    }
    .path-actions {
        margin-left: auto;
        display: flex;
        gap: 0.25rem;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .path-item:hover .path-actions {
        opacity: 1;
    }
    .path-actions button {
        padding: 0.15rem 0.35rem;
        font-size: 0.8rem;
    }
    .entry-point-icon-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 48px;
    }
    .entry-point-icon-group .form-check-input {
        margin: 0;
        cursor: pointer;
        position: relative;
        top: 1px;
    }
    .entry-point-icon-group i {
        font-size: 1.2rem;
        color: #007bff;
    }
    .entry-point-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        cursor: pointer;
    }
    .entry-point-header span {
        font-weight: 500;
        flex: 1;
        line-height: 1;
    }
</style>

<script>
    // Initialize after the DOM is ready
    const container = document.getElementById('conversation-path-builder');
    if (container) {
        window.pathBuilder = new ConversationPathBuilder('conversation-path-builder');
    }
</script>

<div class="save-button-container">
    <button class="btn btn-primary btn-lg" onclick="window.pathBuilder.savePath()">
        <i class="fas fa-save"></i> Save Path
    </button>
</div> 