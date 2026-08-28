<div class="container-fluid">
    <!-- Info Area -->
    <div class="mb-3">
        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#conversationPathInfo" aria-expanded="false" aria-controls="conversationPathInfo">
            <i class="fas fa-info-circle fa-lg"></i> <span class="align-middle">About Conversation Paths</span>
        </button>
        <div class="collapse mt-2" id="conversationPathInfo">


        <div class="card mt-3">
    <div class="card-body">
        <h4 class="card-title">Conversation Paths Documentation</h4>
        <p class="card-text text-muted">A comprehensive guide to understanding and implementing conversation paths.</p>

        <h5 class="mt-4">System Architecture</h5>
        <div class="row">
            <div class="col-md-6">
                <h6>Frontend (JavaScript)</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><code>ConversationPathBuilderV2.js</code>: Visual builder UI with node rendering and drag-and-drop functionality.</li>
                    <li class="list-group-item"><code>nodes/</code>: Contains node type classes (e.g., <code>SayActionNode.js</code>, <code>DecisionNode.js</code>).</li>
                    <li class="list-group-item"><code>NodeFactory.js</code>: Dynamically instantiates node classes based on type/subtype.</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Backend (PHP)</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><code>ConversationPath.php</code>: Eloquent model for conversation paths.</li>
                    <li class="list-group-item"><code>ConversationPathService.php</code>: Manages flow logic, node execution, and TwiML generation.</li>
                    <li class="list-group-item"><code>ConversationPathCallController.php</code>: API endpoints for initiating and continuing calls.</li>
                    <li class="list-group-item"><code>routes/api.php</code>: Defines API routes for conversation path calls.</li>
                </ul>
            </div>
        </div>

        <h5 class="mt-4">Key Concepts</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Conversation Path</strong>: A sequence of nodes directing user interactions, primarily via phone, with support for SMS, chat, and email.</li>
            <li class="list-group-item"><strong>Conversation</strong>: Tracks a user's position and state within a conversation path.</li>
            <li class="list-group-item"><strong>Assistant</strong>: An AI tailored for specific or general tasks.</li>
            <li class="list-group-item"><strong>Pipeline</strong>: A set of tools for managing staged assistant processes.</li>
            <li class="list-group-item"><strong>Path State (<code>path_state</code>)</strong>: A key-value array storing conversation data (e.g., Twilio call details like <code>From</code>, <code>To</code>, <code>CallSid</code>) accessible to nodes, assistants, and scripts.</li>
        </ul>

        <h5 class="mt-4">Node Class Reference</h5>
        <p class="text-muted">Below is a reference of all main Node classes, their attributes, and methods as implemented in <code>public/webapp_public/nodes/</code>.</p>

        <details>
          <summary><strong>BaseNode</strong></summary>
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Attribute</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td>id</td><td>Unique node ID</td></tr>
              <tr><td>type</td><td>Node type (e.g., action, decision, data, entry)</td></tr>
              <tr><td>subtype</td><td>Subtype for further classification</td></tr>
              <tr><td>options</td><td>Node options (object, rarely used except Entry)</td></tr>
              <tr><td>settings</td><td>Node settings (object, rarely used)</td></tr>
              <tr><td>content</td><td>Main node data (object)</td></tr>
              <tr><td>icon</td><td>FontAwesome icon class</td></tr>
              <tr><td>name</td><td>Display name</td></tr>
              <tr><td>color</td><td>Color for UI</td></tr>
              <tr><td>description</td><td>Node description</td></tr>
            </tbody>
          </table>
          <ul>
            <li>constructor(data)</li>
            <li>getMenuItemTemplate()</li>
            <li>getCompactTemplate(nodeIndex)</li>
            <li>getNodeActions(nodeIndex)</li>
            <li>getNodeInfo(assistants, scripts)</li>
            <li>getEditFormTemplate(nodeIndex)</li>
            <li>getSettingsFormTemplate(nodeIndex, context)</li>
            <li>validate()</li>
            <li>updateContent(field, value)</li>
            <li>toJSON()</li>
            <li>getNodeCardHtml(nodeIndex, context, options)</li>
            <li>getDetailsHtml(nodeIndex, context)</li>
            <li>showNodeControls(nodeIndex)</li>
            <li>getDetailedInfo(nodeIndex, context)</li>
            <li>generateId()</li>
          </ul>
        </details>

        <details>
          <summary><strong>ActionNode</strong></summary>
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Attribute</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td>static nodeTypes</td><td>Map of action subtypes to icon, name, color</td></tr>
              <tr><td>type</td><td>Always 'action'</td></tr>
              <tr><td>subtype</td><td>Action subtype (say, play, etc.)</td></tr>
              <tr><td>icon, name, color, description</td><td>UI display</td></tr>
              <tr><td>content</td><td>Action-specific data</td></tr>
            </tbody>
          </table>
          <ul>
            <li>constructor(data)</li>
            <li>getNodeInfo()</li>
            <li>getSettingsFormTemplate(nodeIndex, context)</li>
            <li>validate()</li>
            <li>updateContent(field, value)</li>
          </ul>
        </details>

        <details>
          <summary><strong>DecisionNode</strong></summary>
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Attribute</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td>static nodeTypes</td><td>Map of decision subtypes</td></tr>
              <tr><td>actions</td><td>ActionNodeList of possible actions</td></tr>
              <tr><td>message, audioFileId, userDecisionType, smsTo, smsBody, emailTo, emailSubject, emailBody, assistantId, prompt, script, returnType, description</td><td>Decision-specific fields</td></tr>
            </tbody>
          </table>
          <ul>
            <li>constructor(data)</li>
            <li>getNodeInfo(assistants, scripts, audioFiles)</li>
            <li>getSettingsFormTemplate(nodeIndex, context)</li>
            <li>updateContent(field, value)</li>
            <li>validate()</li>
            <li>toJSON()</li>
            <li>getNodeCardHtml(nodeIndex, context)</li>
          </ul>
        </details>

        <details>
          <summary><strong>DataNode</strong></summary>
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Attribute</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td>static nodeTypes</td><td>Map of data subtypes</td></tr>
              <tr><td>contextKey, prompt, script, assistantId, script_id</td><td>Data-specific fields</td></tr>
              <tr><td>content</td><td>Data-specific content (API config, etc.)</td></tr>
            </tbody>
          </table>
          <ul>
            <li>constructor(data)</li>
            <li>getNodeInfo(assistants, scripts)</li>
            <li>getSettingsFormTemplate(nodeIndex, assistants, scripts, context)</li>
            <li>updateContent(field, value)</li>
            <li>validate()</li>
            <li>toJSON()</li>
            <li>getNodeCardHtml(nodeIndex, context)</li>
          </ul>
        </details>

        <details>
          <summary><strong>EntryNode</strong></summary>
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Attribute</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td>static nodeTypes</td><td>Map of entry subtypes</td></tr>
              <tr><td>options</td><td>Entry configuration (chat, twilioInbound, twilioOutbound)</td></tr>
              <tr><td>type, icon, name, color, description</td><td>UI display</td></tr>
            </tbody>
          </table>
          <ul>
            <li>constructor(data)</li>
            <li>getNodeInfo()</li>
            <li>getSettingsFormTemplate(nodeIndex)</li>
            <li>validate()</li>
            <li>updateContent(field, value)</li>
            <li>toJSON()</li>
            <li>getNodeActions(nodeIndex)</li>
          </ul>
        </details>

        <details>
          <summary><strong>RootEntryNode</strong></summary>
          <table class="table table-sm table-bordered mt-2">
            <thead><tr><th>Attribute</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td>options</td><td>Entry configuration (chat, twilioInbound, twilioOutbound)</td></tr>
              <tr><td>content.settings</td><td>Root-level settings (maxTurns, timeout, language, etc.)</td></tr>
              <tr><td>type, subtype, icon, name, description</td><td>UI display</td></tr>
            </tbody>
          </table>
          <ul>
            <li>constructor(data)</li>
            <li>getNodeInfo()</li>
            <li>getDetailsHtml(nodeIndex)</li>
            <li>getSettingsFormTemplate(nodeIndex)</li>
            <li>updateContent(field, value)</li>
            <li>validate()</li>
            <li>toJSON()</li>
            <li>showNodeControls(nodeIndex)</li>
          </ul>
        </details>

        <h5 class="mt-4">Supported Node Types</h5>
        <div class="accordion" id="nodeTypesAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="actionNodesHeading">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#actionNodes" aria-expanded="true" aria-controls="actionNodes">
                        Action Nodes
                    </button>
                </h2>
                <div id="actionNodes" class="accordion-collapse collapse show" aria-labelledby="actionNodesHeading">
                    <div class="accordion-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>say</strong>: Delivers a spoken message to the user.</li>
                            <li class="list-group-item"><strong>play</strong>: Plays an audio file or URL.</li>
                            <li class="list-group-item"><strong>assistant</strong>: Initiates an assistant process with audio streaming.</li>
                            <li class="list-group-item"><strong>pipeline</strong>: Starts a pipeline process with audio streaming.</li>
                            <li class="list-group-item"><strong>script</strong>: Executes a PHP script and performs the returned action (e.g., say, redirect).</li>
                            <li class="list-group-item"><strong>websocket</strong>: Connects to a custom WebSocket endpoint.</li>
                            <li class="list-group-item"><strong>sms/email</strong>: Sends SMS or email via Twilio or Mail.</li>
                            <li class="list-group-item"><strong>hangup/transfer/route</strong>: Executes standard TwiML actions.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="decisionNodesHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#decisionNodes" aria-expanded="false" aria-controls="decisionNodes">
                        Decision Nodes
                    </button>
                </h2>
                <div id="decisionNodes" class="accordion-collapse collapse" aria-labelledby="decisionNodesHeading">
                    <div class="accordion-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>user</strong>: Prompts the user for input via DTMF, speech, or real-time assistant prompts.</li>
                            <li class="list-group-item"><strong>assistant</strong>: Allows the assistant to choose an option based on context and a prompt (e.g., routing to a department).</li>
                            <li class="list-group-item"><strong>conditional</strong>: Executes a script to select an option (e.g., True/False or an index from a list).</li>
                            <li class="list-group-item"><em>Other types</em>: API-based, multi-step, or external system decisions can be added.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="dataNodesHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dataNodes" aria-expanded="false" aria-controls="dataNodes">
                        Data Nodes
                    </button>
                </h2>
                <div id="dataNodes" class="accordion-collapse collapse" aria-labelledby="dataNodesHeading">
                    <div class="accordion-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>custom</strong>: Runs a script to inject dynamic data into <code>path_state</code>.</li>
                            <li class="list-group-item"><strong>contextAssistant</strong>: Uses an AI assistant to add context to the conversation or <code>path_state</code>.</li>
                            <li class="list-group-item"><strong>APIData, outageCheck, customerLookup</strong>: Reserved for future or custom data logic.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-4">Adding a New Node Type</h5>
        <ol>
            <li><strong>Frontend (JavaScript)</strong>:
                <ol type="a">
                    <li>Create a new file in <code>public/webapp_public/nodes/</code> (e.g., <code>MyCustomActionNode.js</code>).</li>
                    <li>Extend <code>BaseNode</code>, <code>ActionNode</code>, <code>DecisionNode</code>, or <code>DataNode</code>.</li>
                    <li>Register the class on <code>window</code> (e.g., <code>window.MyCustomActionNode = MyCustomActionNode;</code>).</li>
                    <li>Update <code>NodeFactory.js</code> and <code>NodeLoader.js</code> to include the new subtype.</li>
                </ol>
            </li>
            <li><strong>Backend (PHP)</strong>:
                <ol type="a">
                    <li>Add a <code>case</code> for the new node type/subtype in <code>ConversationPathService.php</code>.</li>
                    <li>Implement the node's logic (e.g., generate TwiML, call an API, update state).</li>
                </ol>
            </li>
        </ol>

        <h5 class="mt-4">Phone Call Endpoints</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <strong>POST /api/conversation-path-call/start/{conversationPathId}</strong><br>
                <strong>Purpose:</strong> Initiates a new phone call for a specified conversation path ID.<br>
                <strong>Request:</strong> Accepts Twilio call parameters (e.g., <code>From</code>, <code>To</code>, <code>CallSid</code>) as POST data.<br>
                <strong>Response:</strong> Returns TwiML XML to redirect to the next node.<br>
                <strong>Auth:</strong> Open for Twilio webhooks; future authentication planned.
            </li>
            <li class="list-group-item">
                <strong>POST /api/conversation-path-call/continue/{conversationId}</strong><br>
                <strong>Purpose:</strong> Continues an active phone call by conversation ID.<br>
                <strong>Request:</strong> Accepts Twilio POST data (e.g., DTMF, speech).<br>
                <strong>Response:</strong> Returns TwiML XML for the next node/action.<br>
                <strong>Auth:</strong> Open for Twilio webhooks; future authentication planned.
            </li>
        </ul>

        <h5 class="mt-4">Authentication Notes</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Current:</strong> Endpoints are open for Twilio webhooks without authentication.</li>
            <li class="list-group-item"><strong>Planned:</strong> Implement JWT-based authentication for secure, time-limited access:
                <ul>
                    <li>Generate short-lived JWT tokens (5-15 minutes) with allowed conversation path ID and actions.</li>
                    <li>Pass tokens to clients (e.g., Twilio) for inclusion in API calls.</li>
                    <li>Validate JWT signature, expiry, and actions on each request.</li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<style>
    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .list-group-item {
        border: none;
        padding: 0.5rem 1rem;
    }
    .accordion-button {
        background-color: #f8f9fa;
        font-weight: 500;
    }
    .accordion-button:not(.collapsed) {
        background-color: #e9ecef;
    }
    code {
        background-color: #f1f3f5;
        padding: 2px 4px;
        border-radius: 4px;
    }
    .card-header,
    .node-header {
        padding: 0.5rem 0.75rem 0.5rem 0.75rem !important;
    }

    .conversation-node {
        margin-bottom: 1rem;
    }

    .flow-container {
        padding: 0.5rem;
    }

    .node-header-action-info {
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .node-connector {
        height: 24px;
        margin-bottom: 8px;
    }
</style>
            
        </div>
    </div>

    <!-- Path List View -->
    <div class="card" id="pathListView">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Conversation Paths</h3>
            <button class="btn btn-primary" onclick="document.getElementById('pathName').value = ''; document.getElementById('pathDescription').value = ''; document.querySelector('.path-title').textContent = 'Conversation Path: New Path'; window.pathBuilder.showPath(null, true)">
                <i class="fas fa-plus"></i> New Path
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pathsList">
                        <!-- Paths will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Path Builder View -->
    <div class="card mt-4" id="pathBuilderView" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h4 class="mb-0 path-title">Conversation Path: New Path</h4>
                <button class="btn btn-link ms-2 conversation-path-edit" onclick="window.pathBuilder.togglePathEdit(this)">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="window.pathBuilder.showPathList()">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
                <button class="btn btn-primary save-path" onclick="window.pathBuilder.savePath()">
                    <i class="fas fa-save"></i> Save Path
                </button>
            </div>
        </div>

        <!-- Path Edit Form -->
        <div class="card-body border-bottom path-edit" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pathName">Path Name</label>
                        <input type="text" class="form-control" id="pathName" 
                            onchange="window.pathBuilder.updatePathName(this.value)">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pathDescription">Description</label>
                        <input type="text" class="form-control" id="pathDescription" 
                            onchange="window.pathBuilder.updatePathDescription(this.value)">
                    </div>
                </div>
            </div>
        </div>

        <div class="builder-layout">
            <!-- Node Menu Sidebar -->
            <div class="node-menu-sidebar">
                <div class="accordion" id="nodeMenuAccordion">
                    <!-- Action Nodes -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#actionNodesCollapse">
                                <i class="fas fa-cog me-2"></i>Action Nodes
                            </button>
                        </h2>
                        <div id="actionNodesCollapse" class="accordion-collapse collapse show" data-bs-parent="#nodeMenuAccordion">
                            <div class="accordion-body">
                                <div class="node-grid action-nodes">
                                    <!-- Action nodes will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Nodes -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dataNodesCollapse">
                                <i class="fas fa-database me-2"></i>Data Nodes
                            </button>
                        </h2>
                        <div id="dataNodesCollapse" class="accordion-collapse collapse" data-bs-parent="#nodeMenuAccordion">
                            <div class="accordion-body">
                                <div class="node-grid data-nodes">
                                    <!-- Data nodes will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Decision Nodes -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#decisionNodesCollapse">
                                <i class="fas fa-code-branch me-2"></i>Decision Nodes
                            </button>
                        </h2>
                        <div id="decisionNodesCollapse" class="accordion-collapse collapse" data-bs-parent="#nodeMenuAccordion">
                            <div class="accordion-body">
                                <div class="node-grid decision-nodes">
                                    <!-- Decision nodes will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flow Container -->
            <div class="flow-container-wrapper">
                <div class="flow-container">
                    <!-- Nodes will be rendered here -->
                </div>
            </div>
        </div>

        <!-- Debug Area -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Debug Information</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.pathBuilder.toggleDebugArea(this)">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="card-body debug-area" style="display: none;">
                <div class="accordion" id="debugAccordion">
                    <!-- Path Data -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pathDataCollapse">
                                Path Data
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="window.pathBuilder.updateDebugInfo('path'); event.stopPropagation();">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </button>
                        </h2>
                        <div id="pathDataCollapse" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                            <div class="accordion-body">
                                <pre id="pathDebug" class="debug-content"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Nodes Data -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nodesDataCollapse">
                                Nodes Data
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="window.pathBuilder.updateDebugInfo('nodes'); event.stopPropagation();">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </button>
                        </h2>
                        <div id="nodesDataCollapse" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                            <div class="accordion-body">
                                <pre id="nodesDebug" class="debug-content"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Node Types Data -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nodeTypesCollapse">
                                Node Types Info
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="window.pathBuilder.updateDebugInfo('nodeTypes'); event.stopPropagation();">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </button>
                        </h2>
                        <div id="nodeTypesCollapse" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                            <div class="accordion-body">
                                <pre id="nodeTypesDebug" class="debug-content"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- State Data -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#stateDataCollapse">
                                State Data
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="window.pathBuilder.updateDebugInfo('state'); event.stopPropagation();">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </button>
                        </h2>
                        <div id="stateDataCollapse" class="accordion-collapse collapse" data-bs-parent="#debugAccordion">
                            <div class="accordion-body">
                                <pre id="stateDebug" class="debug-content"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add flowchart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowchart/1.15.0/flowchart.min.js"></script>

<script>
// Initialize immediately
console.log('Initializing builder...');
window.pathBuilder = new ConversationPathBuilderV2('pathBuilderView');


// alert if action node list is not loaded
if (typeof window.ActionNodeList === 'undefined') {
    console.error('ActionNodeList is not loaded line 397 in _index.blade.php');
}



// Add event listeners after initialization
document.querySelector('.path-edit-toggle')?.addEventListener('click', function() {
    const form = document.querySelector('.path-edit-form');
    const isExpanded = this.classList.toggle('expanded');
    form.style.display = isExpanded ? 'block' : 'none';
});

document.getElementById('pathName')?.addEventListener('input', function() {
    document.querySelector('.path-title').textContent = this.value || 'New Path';
});

// Patch showPathTextBreakdown to also render the flowchart
(function() {
    const origShowPathTextBreakdown = window.pathBuilder.showPathTextBreakdown;
    window.pathBuilder.showPathTextBreakdown = function(pathId) {
        origShowPathTextBreakdown.call(this, pathId);
        // After text, render flowchart
        const path = this.paths.find(p => p.id == pathId);
        if (!path || !path.nodes) return;
        const nodes = path.nodes.map(nodeData => this.createNode(nodeData));
        // Generate flowchart.js definition
        let def = '';
        nodes.forEach((node, idx) => {
            let label = node.name || node.subtype || node.type;
            let color = 'lightblue';
            if (node.type === 'action') color = 'orange';
            if (node.type === 'decision') color = 'purple';
            if (node.type === 'data') color = 'green';
            def += `n${idx}=>operation: ${label}|${color}\n`;
        });
        for (let i = 0; i < nodes.length - 1; i++) {
            def += `n${i}->n${i+1}\n`;
        }
        // Render
        document.getElementById('pathFlowchart').innerHTML = '';
        try {
            flowchart.parse(def).drawSVG('pathFlowchart', {
                'line-width': 2,
                'maxWidth': 600,
                'line-length': 40,
                'text-margin': 10,
                'font-size': 14,
                'font-color': 'black',
                'element-color': '#222',
                'fill': 'white',
                'yes-text': 'yes',
                'no-text': 'no',
                'arrow-end': 'block',
                'scale': 1,
                'symbols': {
                    'operation': {
                        'font-color': 'white',
                        'element-color': '#222',
                        'fill': 'orange'
                    }
                }
            });
        } catch (e) {
            document.getElementById('pathFlowchart').innerHTML = '<div class="text-danger">Could not render flowchart.</div>';
        }
    };
})();
</script>

<style>
/* Builder Layout */
.builder-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1rem;
    min-height: 600px;
}

/* Node Menu Sidebar */
.node-menu-sidebar {
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    padding: 1rem;
    height: calc(100vh - 140px); /* fits under header, adjust offset as needed */
    max-height: calc(100vh - 140px);
    overflow-y: auto;
    min-width: 260px;
    box-sizing: border-box;
}

/* Node Connector and Arrow for Flow UI */
.node-connector {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 40px;
    position: relative;
    margin-bottom: 16px; /* Add margin below the arrow for visual balance */
}
.node-connector .arrow {
    width: 2px;
    height: 32px;
    background: linear-gradient(to bottom, #007bff 60%, #007bff 80%, transparent 100%);
    position: relative;
}
.node-connector .arrow::after {
    content: '';
    display: block;
    position: absolute;
    left: 50%;
    bottom: -6px;
    transform: translateX(-50%);
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 8px solid #007bff;
}

/* Flow Container */
.flow-container-wrapper {
    padding: 1rem;
    overflow-y: auto;
    height: calc(100vh - 140px); /* fits under header, adjust offset as needed */
    max-height: calc(100vh - 140px);
}

.flow-container {
    min-height: 600px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    width: 100%;
    max-width: none;
    margin: 0 auto;
}

/* Node Grid */
.node-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.palette-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: move;
    transition: all 0.2s ease;
}

.palette-item:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: translateY(-1px);
}

/* Debug Area */
.debug-content {
    font-family: monospace;
    font-size: 0.875rem;
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    max-height: 300px;
    overflow-y: auto;
    margin: 0;
}

/* Accordion Customization */
.accordion-button {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.accordion-button:not(.collapsed) {
    background-color: #e9ecef;
    color: #212529;
}

.accordion-body {
    padding: 1rem;
    background: #fff;
}

/* Responsive Layout */
@media (max-width: 992px) {
    .builder-layout {
        grid-template-columns: 1fr;
    }

    .node-menu-sidebar {
        border-right: none;
        border-bottom: 1px solid #dee2e6;
    }
}

.accordion-button .btn {
    z-index: 2;
}
.accordion-button .btn:hover {
    background-color: #e9ecef;
}

.conversation-node {
    width: 100%;
    margin: 0 auto 1.5rem auto;
    flex-direction: column;
    display: flex;
    align-items: stretch;
}
.conversation-node.action,
.conversation-node.data {
    max-width: 540px;
    margin-left: auto;
    margin-right: auto;
}

.conversation-node .node-header {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 1.25rem 1rem 1rem 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    cursor: pointer;
    flex-direction: row;
    justify-content: space-between;
}

.conversation-node .node-header-main {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    flex: 1;
    gap: 0.25rem;
}

.conversation-node .node-header-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.conversation-node .node-header-actions {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.conversation-node .node-header-actions button {
    width: 32px;
    height: 32px;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.conversation-node .node-header-controls .move-up,
.conversation-node .node-header-controls .move-down {
    width: 32px;
    height: 32px;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.node-header-action-grid {
    display: grid;
    grid-template-columns: 1.2fr 1.8fr 40px;
    grid-template-rows: 38px 38px;
    align-items: center;
    gap: 0.25rem;
    width: 100%;
    min-height: 76px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
}
.node-header-action-cell {
    /* For possible future use */
}
.node-header-action-icon {
    font-size: 1.2rem;
    font-weight: 500;
}
.node-header-action-info {
    font-size: 0.95rem;
    color: #555;
    padding-left: 0.5rem;
}
.node-header-action-controls {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
    justify-content: flex-start;
    grid-row: 2;
    grid-column: 1 / span 3;
}
.node-header-action-up, .node-header-action-down {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<!-- Add a reusable Bootstrap modal for delete confirmation -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this item?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div> 