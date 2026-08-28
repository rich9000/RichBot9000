<!-- resources/views/webapp/phone-tree/index.blade.php -->
<style>
    .custom-tab-button {
        color: #212529 !important; /* Bootstrap's dark color */
    }
    .custom-tab-button:hover {
        color: #0d6efd !important; /* Bootstrap's primary color on hover */
    }
    .custom-tab-button.active {
        color: #0d6efd !important; /* Bootstrap's primary color for active state */
    }
</style>

<div class="container-fluid pb-5" id="phone-tree-management">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Phone Tree Management</h2>
        <div class="d-flex align-items-center">
            <button class="btn btn-info me-2" type="button" data-bs-toggle="collapse" data-bs-target="#phoneTreeInfo" aria-expanded="false" aria-controls="phoneTreeInfo">
                <i class="fas fa-info-circle"></i> How It Works
            </button>
            <button class="btn btn-primary" id="create-phone-tree-btn">
                <i class="fas fa-plus"></i> Create Phone Tree
            </button>
        </div>
    </div>

    <!-- Phone Tree Information Panel -->
    <div class="collapse mb-4" id="phoneTreeInfo">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Understanding Phone Tree Menus</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-sitemap me-2"></i>Menu Flow</h6>
                        <ol class="list-group list-group-numbered mb-4">
                            <li class="list-group-item">Welcome Message/Audio plays first</li>
                            <li class="list-group-item">Prompt Message/Audio describes available options</li>
                            <li class="list-group-item">System speaks menu options (if enabled)</li>
                            <li class="list-group-item">User input is collected</li>
                            <li class="list-group-item">Selected option action is executed</li>
                            <li class="list-group-item">Finish Message/Audio plays (if configured)</li>
                            <li class="list-group-item">Call transfers to finish menu or disconnects</li>
                        </ol>

                        <h6><i class="fas fa-cog me-2"></i>Menu Settings</h6>
                        <ul class="list-group mb-4">
                            <li class="list-group-item"><i class="fas fa-clock me-2"></i>Timeout: Maximum wait time for user input</li>
                            <li class="list-group-item"><i class="fas fa-redo me-2"></i>Max Retries: Number of invalid input attempts allowed</li>
                            <li class="list-group-item"><i class="fas fa-volume-up me-2"></i>Speak Options: System announces available options</li>
                            <li class="list-group-item"><i class="fas fa-phone-slash me-2"></i>Disconnect on Finish: End call after menu completion</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-list-ol me-2"></i>Option Types</h6>
                        <div class="list-group mb-4">
                            <div class="list-group-item">
                                <h6 class="mb-1"><i class="fas fa-list me-2"></i>Menu</h6>
                                <small>Navigate to another menu in the phone tree</small>
                            </div>
                            <div class="list-group-item">
                                <h6 class="mb-1"><i class="fas fa-music me-2"></i>Audio File</h6>
                                <small>Play a pre-recorded audio message</small>
                            </div>
                            <div class="list-group-item">
                                <h6 class="mb-1"><i class="fas fa-code me-2"></i>Script</h6>
                                <small>Execute a custom script for complex logic</small>
                            </div>
                            <div class="list-group-item">
                                <h6 class="mb-1"><i class="fas fa-plug me-2"></i>WebSocket</h6>
                                <small>Connect to a real-time service or agent</small>
                            </div>
                            <div class="list-group-item">
                                <h6 class="mb-1"><i class="fas fa-phone me-2"></i>Transfer</h6>
                                <small>Transfer call to another phone number</small>
                            </div>
                        </div>

                        <h6><i class="fas fa-lightbulb me-2"></i>Best Practices</h6>
                        <ul class="list-group">
                            <li class="list-group-item">Keep menu options simple and clear</li>
                            <li class="list-group-item">Use consistent messaging across menus</li>
                            <li class="list-group-item">Provide an option to return to previous menu</li>
                            <li class="list-group-item">Test all paths through your phone tree</li>
                            <li class="list-group-item">Consider adding a direct-to-agent option</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phone Trees List -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Your Phone Trees</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="phone-trees-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Numbers</th>
                            <th>Menus</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="phone-trees-list">
                        <!-- Phone trees will be populated here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Phone Tree Detail Section -->
    <div id="phone-tree-detail-section" class="d-none">
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0" id="phone-tree-detail-title">Phone Tree Details</h5>
                    <small class="text-muted" id="phone-tree-detail-description"></small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary" id="edit-phone-tree-btn">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="delete-phone-tree-btn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="back-to-phone-trees-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Phone Tree Info Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        <span id="details-status" class="badge bg-success">Active</span>
                                    </dd>
                                    <dt class="col-sm-4">Max Retries</dt>
                                    <dd class="col-sm-8" id="details-max-retries">3</dd>
                                    <dt class="col-sm-4">Timeout</dt>
                                    <dd class="col-sm-8" id="details-timeout-seconds">5 seconds</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Welcome Message</dt>
                                    <dd class="col-sm-8" id="details-welcome-message">Loading...</dd>
                                    <dt class="col-sm-4">Timeout Message</dt>
                                    <dd class="col-sm-8" id="details-timeout-message">Loading...</dd>
                                    <dt class="col-sm-4">Invalid Input</dt>
                                    <dd class="col-sm-8" id="details-invalid-input-message">Loading...</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="phoneTreeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active custom-tab-button" id="numbers-tab" data-bs-toggle="tab" data-bs-target="#numbers" type="button" role="tab">
                            Numbers <span class="badge bg-primary" id="numbers-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link custom-tab-button" id="menus-tab" data-bs-toggle="tab" data-bs-target="#menus" type="button" role="tab">
                            Menus <span class="badge bg-primary" id="menus-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link custom-tab-button" id="websockets-tab" data-bs-toggle="tab" data-bs-target="#websockets" type="button" role="tab">
                            WebSockets <span class="badge bg-primary" id="websockets-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link custom-tab-button" id="calls-tab" data-bs-toggle="tab" data-bs-target="#calls" type="button" role="tab">
                            Calls <span class="badge bg-primary" id="calls-count">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link custom-tab-button" id="scripts-tab" data-bs-toggle="tab" data-bs-target="#scripts" type="button" role="tab">
                            Scripts <span class="badge bg-primary" id="scripts-count">0</span>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="phoneTreeTabsContent">
                    <!-- Numbers Tab -->
                    <div class="tab-pane fade show active" id="numbers" role="tabpanel" aria-labelledby="numbers-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Phone Numbers</h6>
                            <button class="btn btn-sm btn-success" id="add-number-btn">
                                <i class="fas fa-plus"></i> Add Number
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="numbers-list">
                                    <!-- Numbers will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Menus Tab -->
                    <div class="tab-pane fade" id="menus" role="tabpanel" aria-labelledby="menus-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Menu Structure</h6>
                            <button class="btn btn-sm btn-success" id="add-menu-btn">
                                <i class="fas fa-plus"></i> Add Menu
                            </button>
                        </div>
                        <div id="menus-container" class="list-group">
                            <!-- Menus will be populated here -->
                        </div>
                    </div>

                    <!-- WebSockets Tab -->
                    <div class="tab-pane fade" id="websockets" role="tabpanel" aria-labelledby="websockets-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">WebSocket Connections</h6>
                            <button class="btn btn-sm btn-success" id="add-websocket-btn">
                                <i class="fas fa-plus"></i> Add Connection
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Endpoint</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="websockets-list">
                                    <!-- WebSockets will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Calls Tab -->
                    <div class="tab-pane fade" id="calls" role="tabpanel" aria-labelledby="calls-tab">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Start Time</th>
                                        <th>Status</th>
                                        <th>Current Menu</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="calls-list">
                                    <!-- Calls will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Scripts Tab -->
                    <div class="tab-pane fade" id="scripts" role="tabpanel" aria-labelledby="scripts-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Scripts <span class="badge bg-secondary" id="scripts-count">0</span></h5>
                            <button class="btn btn-primary" onclick="phoneTreeManager.showScriptModal()">
                                <i class="fas fa-plus"></i> Add Script
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Path</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="scripts-list">
                                    <tr>
                                        <td colspan="5" class="text-center">No scripts found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Phone Tree Modal -->
<div class="modal fade" id="phone-tree-modal" tabindex="-1" aria-labelledby="phone-tree-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="phone-tree-modal-label">Create Phone Tree</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="phone-tree-form">
                    <input type="hidden" id="phone-tree-id">
                    <div class="mb-3">
                        <label for="phone-tree-name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="phone-tree-name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-description" class="form-label">Description</label>
                        <textarea class="form-control" id="phone-tree-description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-root-menu" class="form-label">Root Menu</label>
                        <select class="form-select" id="phone-tree-root-menu">
                            <option value="">Select Root Menu</option>
                        </select>
                        <small class="form-text text-muted">The starting menu for this phone tree</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-welcome-message" class="form-label">Welcome Message</label>
                        <textarea class="form-control" id="phone-tree-welcome-message" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-timeout-message" class="form-label">Timeout Message</label>
                        <textarea class="form-control" id="phone-tree-timeout-message" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-invalid-input-message" class="form-label">Invalid Input Message</label>
                        <textarea class="form-control" id="phone-tree-invalid-input-message" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-max-retries" class="form-label">Max Retries</label>
                        <input type="number" class="form-control" id="phone-tree-max-retries" min="1" value="3" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-timeout-seconds" class="form-label">Timeout (seconds)</label>
                        <input type="number" class="form-control" id="phone-tree-timeout-seconds" min="1" value="5" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="phone-tree-is-active">
                            <label class="form-check-label" for="phone-tree-is-active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="phone-tree-is-default">
                            <label class="form-check-label" for="phone-tree-is-default">
                                Default Phone Tree
                            </label>
                            <small class="form-text text-muted">Use this phone tree when no specific tree is found</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone-tree-welcome-audio" class="form-label">Welcome Audio</label>
                        <select class="form-select" id="phone-tree-welcome-audio">
                            <option value="">None</option>
                            <!-- Audio files will be populated here via JavaScript -->
                        </select>
                        <small class="form-text text-muted">Optional audio file to play when call starts</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-phone-tree-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Number Modal -->
<div class="modal fade" id="number-modal" tabindex="-1" aria-labelledby="number-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="number-modal-label">Add Phone Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="number-form">
                    <input type="hidden" id="number-id">
                    <div class="mb-3">
                        <label for="phone-number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone-number" required>
                    </div>
                    <div class="mb-3">
                        <label for="number-description" class="form-label">Description</label>
                        <textarea class="form-control" id="number-description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="number-is-active">
                            <label class="form-check-label" for="number-is-active">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-number-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Menu Modal -->
<div class="modal fade" id="menu-modal" tabindex="-1" aria-labelledby="menu-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menu-modal-label">Add Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="menu-form">
                    <input type="hidden" id="menu-id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menu-parent" class="form-label">Parent Menu</label>
                                <select class="form-select" id="menu-parent">
                                    <option value="">Root Menu</option>
                                    <!-- Parent menus will be populated here via JavaScript -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="menu-name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="menu-name" required>
                            </div>
                            <div class="mb-3">
                                <label for="menu-description" class="form-label">Description</label>
                                <textarea class="form-control" id="menu-description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="menu-welcome-message" class="form-label">Welcome Message</label>
                                <textarea class="form-control" id="menu-welcome-message" rows="2"></textarea>
                                <label for="menu-welcome-audio" class="form-label mt-2">Welcome Audio</label>
                                <select class="form-select" id="menu-welcome-audio">
                                    <option value="">None</option>
                                    <!-- Audio files will be populated here via JavaScript -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="menu-prompt" class="form-label">Prompt Message</label>
                                <textarea class="form-control" id="menu-prompt" rows="2" required></textarea>
                                <label for="menu-prompt-audio" class="form-label mt-2">Prompt Audio</label>
                                <select class="form-select" id="menu-prompt-audio">
                                    <option value="">None</option>
                                    <!-- Audio files will be populated here via JavaScript -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="menu-finish-message" class="form-label">Finish Message</label>
                                <textarea class="form-control" id="menu-finish-message" rows="2"></textarea>
                                <label for="menu-finish-audio" class="form-label mt-2">Finish Audio</label>
                                <select class="form-select" id="menu-finish-audio">
                                    <option value="">None</option>
                                    <!-- Audio files will be populated here via JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="menu-timeout-message" class="form-label">Timeout Message</label>
                                <textarea class="form-control" id="menu-timeout-message" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="menu-invalid-input-message" class="form-label">Invalid Input Message</label>
                                <textarea class="form-control" id="menu-invalid-input-message" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="menu-max-retries" class="form-label">Max Retries</label>
                                <input type="number" class="form-control" id="menu-max-retries" min="1" value="3" required>
                            </div>
                            <div class="mb-3">
                                <label for="menu-timeout-seconds" class="form-label">Timeout (seconds)</label>
                                <input type="number" class="form-control" id="menu-timeout-seconds" min="1" value="5" required>
                            </div>
                            <div class="mb-3">
                                <label for="menu-order" class="form-label">Order</label>
                                <input type="number" class="form-control" id="menu-order" min="0" value="0" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="menu-is-active">
                                    <label class="form-check-label" for="menu-is-active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="menu-speak-options" checked>
                                    <label class="form-check-label" for="menu-speak-options">
                                        Speak Option Numbers
                                    </label>
                                    <small class="form-text text-muted">When enabled, will say "Press X for [option]" for each menu option</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="menu-disconnect-on-finish">
                                    <label class="form-check-label" for="menu-disconnect-on-finish">
                                        Disconnect on Finish
                                    </label>
                                    <small class="form-text text-muted">End the call after menu completion</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="menu-websocket" class="form-label">WebSocket Connection</label>
                                <select class="form-select" id="menu-websocket">
                                    <option value="">None</option>
                                    <!-- WebSocket connections will be populated here via JavaScript -->
                                </select>
                                <small class="form-text text-muted">Optional WebSocket connection for real-time call handling</small>
                            </div>
                            <div class="mb-3">
                                <label for="menu-finish-menu" class="form-label">Finish Menu</label>
                                <select class="form-select" id="menu-finish-menu">
                                    <option value="">None</option>
                                    <!-- Finish menus will be populated here via JavaScript -->
                                </select>
                                <small class="form-text text-muted">Optional menu to go to after completion</small>
                            </div>
                            <div class="mb-3">
                                <label for="menu-websocket-fail-menu" class="form-label">WebSocket Fail Menu</label>
                                <select class="form-select" id="menu-websocket-fail-menu">
                                    <option value="">None</option>
                                    <!-- Fail menus will be populated here via JavaScript -->
                                </select>
                                <small class="form-text text-muted">Menu to go to if WebSocket connection fails</small>
                            </div>
                            <div class="mb-3">
                                <label for="menu-transfer-number" class="form-label">Transfer Number</label>
                                <input type="tel" class="form-control" id="menu-transfer-number">
                                <small class="form-text text-muted">Optional number to transfer to after menu completion</small>
                            </div>
                            <div class="mb-3">
                                <label for="menu-script" class="form-label">Script</label>
                                <select class="form-select" id="menu-script">
                                    <option value="">None</option>
                                </select>
                                <small class="form-text text-muted">Optional script for menu handling</small>
                            </div>
                            <div class="mb-3">
                                <label for="menu-assistant" class="form-label">Assistant</label>
                                <select class="form-select" id="menu-assistant">
                                    <option value="">None</option>
                                </select>
                                <small class="form-text text-muted">Optional assistant for menu handling</small>
                            </div>
                            <div class="mb-3">
                                <label for="menu-pipeline-id" class="form-label">Pipeline</label>
                                <select class="form-select" id="menu-pipeline-id">
                                    <option value="">None</option>
                                </select>
                                <small class="form-text text-muted">Optional pipeline for assistant</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-menu-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Option Modal -->
<div class="modal fade" id="option-modal" tabindex="-1" aria-labelledby="option-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="option-modal-label">Add Option</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="option-form">
                    <input type="hidden" id="option-id">
                    <input type="hidden" id="option-menu-id">
                    <div class="mb-3">
                        <small class="text-muted">Option ID: <span id="display-option-id">New Option</span></small>
                    </div>
                    <div class="mb-3">
                        <label for="option-digit" class="form-label">Digit</label>
                        <input type="text" class="form-control" id="option-digit" maxlength="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="option-action-type" class="form-label">Action Type</label>
                        <select class="form-select" id="option-action-type" required>
                            <option value="menu">Go to Menu</option>
                            <option value="audio_file">Play Audio</option>
                            <option value="script">Run Script</option>
                            <option value="websocket">WebSocket</option>
                            <option value="number">Transfer to Number</option>
                        </select>
                    </div>
                    <div class="mb-3" id="target-menu-container">
                        <label for="option-target-menu" class="form-label">Target Menu</label>
                        <select class="form-select" id="option-target-menu">
                            <!-- Target menus will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3" id="target-websocket-container" style="display: none;">
                        <label for="option-target-websocket" class="form-label">Target WebSocket</label>
                        <select class="form-select" id="option-target-websocket">
                            <!-- Target WebSockets will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3" id="target-script-container" style="display: none;">
                        <label for="option-target-script" class="form-label">Target Script</label>
                        <select class="form-select" id="option-target-script">
                            <!-- Target scripts will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3" id="target-number-container" style="display: none;">
                        <label for="option-target-number" class="form-label">Target Number</label>
                        <select class="form-select" id="option-target-number">
                            <!-- Target numbers will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3" id="target-audio-container" style="display: none;">
                        <label for="option-target-audio" class="form-label">Target Audio</label>
                        <select class="form-select" id="option-target-audio">
                            <!-- Target audio files will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3" id="target-assistant-container" style="display: none;">
                        <label for="option-target-assistant" class="form-label">Target Assistant</label>
                        <select class="form-select" id="option-target-assistant">
                            <!-- Target assistants will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3" id="pipeline-container" style="display: none;">
                        <label for="option-pipeline-id" class="form-label">Pipeline ID</label>
                        <input type="text" class="form-control" id="option-pipeline-id">
                        <small class="form-text text-muted">Optional pipeline ID for assistant</small>
                    </div>
                    <div class="mb-3">
                        <label for="option-description" class="form-label">Description</label>
                        <textarea class="form-control" id="option-description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="option-welcome-message" class="form-label">Welcome Message</label>
                        <textarea class="form-control" id="option-welcome-message" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="option-welcome-audio" class="form-label">Welcome Audio</label>
                        <select class="form-select" id="option-welcome-audio">
                            <!-- Audio files will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="option-finish-menu" class="form-label">Finish Menu</label>
                        <select class="form-select" id="option-finish-menu">
                            <!-- Finish menus will be populated here via JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="option-order" class="form-label">Order</label>
                        <input type="number" class="form-control" id="option-order" min="0" value="0" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="option-is-active">
                            <label class="form-check-label" for="option-is-active">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-option-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit WebSocket Modal -->
<div class="modal fade" id="websocket-modal" tabindex="-1" aria-labelledby="websocket-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="websocket-modal-label">Add WebSocket Connection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="websocket-form">
                    <input type="hidden" id="websocket-id">
                    <div class="mb-3">
                        <label for="websocket-endpoint" class="form-label">Endpoint URL</label>
                        <input type="url" class="form-control" id="websocket-endpoint" required>
                    </div>
                    <div class="mb-3">
                        <label for="websocket-connection-type" class="form-label">Connection Type</label>
                        <select class="form-select" id="websocket-connection-type" required>
                            <option value="public">Public</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="websocket-auth-type" class="form-label">Authentication Type</label>
                        <select class="form-select" id="websocket-auth-type" required>
                            <option value="none">None</option>
                            <option value="token">Token</option>
                            <option value="basic">Basic Auth</option>
                        </select>
                    </div>
                    
                    <!-- Token Auth Fields -->
                    <div id="websocket-token-container" style="display: none;">
                        <div class="mb-3">
                            <label for="websocket-token" class="form-label">Token</label>
                            <input type="text" class="form-control" id="websocket-token">
                        </div>
                    </div>
                    
                    <!-- Basic Auth Fields -->
                    <div id="websocket-basic-auth-container" style="display: none;">
                        <div class="mb-3">
                            <label for="websocket-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="websocket-username">
                        </div>
                        <div class="mb-3">
                            <label for="websocket-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="websocket-password">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="websocket-is-active">
                            <label class="form-check-label" for="websocket-is-active">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-websocket-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Call Details Modal -->
<div class="modal fade" id="call-details-modal" tabindex="-1" aria-labelledby="call-details-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="call-details-modal-label">Call Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>From:</strong> <span id="call-from-number"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>To:</strong> <span id="call-to-number"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Start Time:</strong> <span id="call-start-time"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>End Time:</strong> <span id="call-end-time"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Status:</strong> <span id="call-status"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Current Menu:</strong> <span id="call-current-menu"></span>
                    </div>
                </div>
                
                <hr>
                
                <h6>Recordings</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm" id="recordings-table">
                        <thead>
                            <tr>
                                <th>Duration</th>
                                <th>Start Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recordings-list">
                            <!-- Recordings will be populated here via JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <h6>Transcriptions</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="transcriptions-table">
                        <thead>
                            <tr>
                                <th>Text</th>
                                <th>Language</th>
                                <th>Confidence</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="transcriptions-list">
                            <!-- Transcriptions will be populated here via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirm-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-delete-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirm-delete-message">
                Are you sure you want to delete this item? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Container -->
<div id="error-container" class="position-fixed top-0 end-0 p-3" style="z-index: 5"></div>

<!-- Success Container -->
<div id="success-container" class="position-fixed top-0 end-0 p-3" style="z-index: 5"></div>

<!-- Script Modal -->
<div class="modal fade" id="script-modal" tabindex="-1" aria-labelledby="script-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="script-modal-label">Add Script</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="script-form">
                    <input type="hidden" id="script-id">
                    <div class="mb-3">
                        <label for="script-name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="script-name" required>
                    </div>
                    <div class="mb-3">
                        <label for="script-description" class="form-label">Description</label>
                        <textarea class="form-control" id="script-description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="script-path" class="form-label">Path</label>
                        <input type="text" class="form-control" id="script-path" required>
                        <div class="form-text">Path to the script file (e.g. /scripts/custom/script.py)</div>
                    </div>
                    <div class="mb-3">
                        <label for="script-parameters" class="form-label">Parameters</label>
                        <textarea class="form-control" id="script-parameters" rows="3">{"key": "value"}</textarea>
                        <div class="form-text">JSON object containing script parameters</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="script-is-active" checked>
                            <label class="form-check-label" for="script-is-active">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="phoneTreeManager.saveScript()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize the PhoneTreeManager when the DOM is ready
  
        // Create the manager instance
        window.phoneTreeManager = new PhoneTreeManager();
        
        // Initialize the manager
        phoneTreeManager.initialize();
 
</script> 