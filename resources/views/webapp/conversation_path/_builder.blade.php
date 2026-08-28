<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h4 class="mb-0 path-title">New Path</h4>
                        <button class="btn btn-link ms-2 path-edit-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div>
                        <a class="btn btn-outline-secondary me-2 nav-content-loader" href="#" data-view="webapp.conversation_path._index" data-section="conversation-path-section">
                            <i class="fas fa-tags me-2"></i>Back to Paths
                        </a>
                        <button type="button" class="btn btn-primary save-path">
                            <i class="fas fa-save"></i> Save Path
                        </button>
                    </div>
                </div>
                <div class="path-edit-form" style="display: none;">
                    <div class="card-body border-bottom">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pathName">Path Name</label>
                                    <input type="text" class="form-control" id="pathName" placeholder="Enter path name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pathDescription">Description</label>
                                    <input type="text" class="form-control" id="pathDescription" placeholder="Enter path description">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="builder-container">
                        <!-- Flow Container -->
                        <div class="flow-container text-center mb-4">
                            <div id="conversation-path-builder"></div>
                        </div>

                        <!-- Node Palettes -->
                        <div class="node-palettes mt-4">
                            <div class="row">
                                <!-- Data Nodes -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">Data Nodes</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="node-palette data-palette"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Decision Nodes -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">Decision Nodes</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="node-palette decision-palette"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Action Nodes -->
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Action Nodes</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="node-palette action-palette"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Base Styles */
.conversation-path-builder {
    min-height: calc(100vh - 200px);
}

/* Path Edit Form */
.path-edit-toggle {
    padding: 0.25rem 0.5rem;
    color: #6c757d;
    transition: transform 0.2s;
}
.path-edit-toggle.expanded {
    transform: rotate(180deg);
}

/* Builder Layout */
.builder-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.flow-container {
    min-height: 200px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    position: relative;
}

/* Node Styles */
.node {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.node-header {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    cursor: pointer;
    user-select: none;
}

.node-header i {
    margin-right: 0.5rem;
}

.node-actions {
    margin-left: auto;
    display: flex;
    gap: 0.25rem;
}

.node-content {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    display: none;
}

.node-header.expanded + .node-content {
    display: block;
}

.node-connector {
    width: 2px;
    height: 20px;
    background: #dee2e6;
    margin: 0 auto;
}

/* Drop Indicator */
.drop-indicator {
    height: 4px;
    background: #007bff;
    margin: 8px 0;
    border-radius: 2px;
    animation: pulse 1.5s infinite;
}

.drop-indicator-empty {
    position: absolute;
    left: 20px;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

.palette-item.dragging {
    opacity: 0.5;
}

/* Node Palettes */
.node-palette {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.75rem;
    padding: 1rem;
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
    transition: all 0.2s;
}

.palette-item:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: translateY(-1px);
}

.palette-item i {
    width: 1.5rem;
    text-align: center;
}

/* Responsive Layout */
@media (min-width: 1600px) {
    .builder-container {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
    }
    .node-palettes {
        position: sticky;
        top: 1rem;
    }
}
</style>

<script>

    try {
        // Initialize the builder
        if (!window.pathBuilder) {
            console.log('Creating new PathBuilder instance');
            window.pathBuilder = new ConversationPathBuilderV2('conversation-path-builder');
        }
        
        // Path edit form toggle
        const editToggle = document.querySelector('.path-edit-toggle');
        const editForm = document.querySelector('.path-edit-form');
        
        if (editToggle && editForm) {
            editToggle.addEventListener('click', () => {
                const isExpanded = editToggle.classList.toggle('expanded');
                editForm.style.display = isExpanded ? 'block' : 'none';
                editToggle.querySelector('i').className = isExpanded ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
            });
        }

        // Path name update handler
        const pathName = document.getElementById('pathName');
        const pathTitle = document.querySelector('.path-title');
        
        if (pathName && pathTitle) {
            pathName.addEventListener('input', () => {
                pathTitle.textContent = pathName.value || 'New Path';
            });
        }

        console.log('Conversation path builder initialized successfully');
        
    } catch (error) {
        console.error('Error initializing conversation path builder:', error);
        toastr.error('Failed to initialize builder');
    }

</script>
