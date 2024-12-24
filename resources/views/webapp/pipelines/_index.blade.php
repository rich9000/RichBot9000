<div class="container-fluid mt-4">
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5 mb-0">
                        <i class="fas fa-project-diagram me-3 text-primary"></i>Pipeline Manager
                    </h1>
                    <p class="text-muted mt-2 mb-0">Create and manage your automation pipelines</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="create-pipeline" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle me-2"></i>Create Pipeline
                    </button>
                    <button class="btn btn-outline-secondary btn-lg" onclick="loadPipelineData()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="pipelines-container" class="row">
        <!-- Pipelines will be rendered here -->
    </div>
</div>

    <!-- Modal for Stage -->
    <div class="modal fade" id="stageModal" tabindex="-1" aria-labelledby="stageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="stage-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="stageModalLabel">Add/Edit Stage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="pipeline-id">

                        <!-- Stage Name Input -->
                        <div class="mb-3">
                            <label for="stage-name" class="form-label">Name</label>
                            <input type="text" id="stage-name" class="form-control" required>
                        </div>

                        <!-- Stage Type Selection -->
                        <div class="mb-3">
                            <label for="stage-type" class="form-label">Type</label>
                            <select id="stage-type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="assistant">Assistant</option>
                                <option value="transform">Transform</option>
                                <option value="context">Context</option>
                                <!-- Add other types as needed -->
                            </select>
                        </div>

                        <!-- Success Tool Selection -->
                        <div class="mb-3">
                            <label for="success-tool-id" class="form-label">Success Tool</label>
                            <select id="success-tool-id" class="form-select">
                                <!-- Tools will be loaded here -->
                                <option value="">None</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Stage</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Pipeline Creation -->
    <div class="modal fade" id="pipelineModal" tabindex="-1" aria-labelledby="pipelineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="pipeline-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pipelineModalLabel">Create Pipeline</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pipeline-name" class="form-label">Name</label>
                            <input type="text" id="pipeline-name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="pipeline-description" class="form-label">Description</label>
                            <textarea id="pipeline-description" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Create Pipeline</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assistants Modal for Assigning and Sorting to a Stage -->
    <div class="modal fade" id="stageAssistantsModal" tabindex="-1" aria-labelledby="stageAssistantsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="stage-assistants-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="stageAssistantsModalLabel">Assign and Sort Assistants for Stage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Available Assistants Selection -->
                        <div class="mb-3">
                            <label for="stage-available-assistants" class="form-label">Available Assistants</label>
                            <select id="stage-available-assistants" class="form-select">
                                <!-- Assistants will be loaded here dynamically -->
                            </select>
                            <button type="button" class="btn btn-primary mt-2" id="stage-add-assistant-btn">Add Assistant</button>
                        </div>

                        <!-- List of Assigned Assistants -->
                        <ul id="stage-assigned-assistants" class="list-group mt-3">
                            <!-- Assigned assistants will be appended here for sorting -->
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Assistants Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Modal for Assigning Files -->
<div class="modal fade" id="stageFilesModal" tabindex="-1" aria-labelledby="stageFilesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stage-files-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="stageFilesModalLabel">Assign Files to Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">



                    <div class="container mt-5">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">File Browser</h5>
                            </div>
                            <div class="card-body file-browser-card">
                                <div id="file-browser"></div>
                            </div>
                        </div>
                    </div>



                    <div class="mb-3">
                        <label for="stage-available-files" class="form-label">Available Files</label>
                        <select id="stage-available-files" class="form-select" multiple>
                            <!-- Files will be loaded dynamically -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Files</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Assigning Tools -->
<div class="modal fade" id="stageToolsModal" tabindex="-1" aria-labelledby="stageToolsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stage-tools-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="stageToolsModalLabel">Assign Tools to Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="stage-available-tools" class="form-label">Available Tools</label>
                        <select id="stage-available-tools" class="form-select">
                            <!-- Tools will be loaded dynamically -->
                        </select>
                        <label class="form-label mt-2">Optional Success Stage</label>
                        <select id="stage-success-stage" class="form-select">
                            <!-- Stages for success_stage_id will be loaded dynamically -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Tools</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>

    function loadPipelineData() {
        loadData('pipelines', '/api/pipelines', true)
            .then(pipelines => {
                // Code to execute after pipelines are loaded
                console.log('Pipelines loaded:', pipelines);
                loadPipelines();
                loadToolsForSuccessTool();
                renderPipelines();
            })
            .catch(error => console.error('Error loading pipelines:', error));
    }

    loadPipelineData();

    // Script to handle pipelines, stages, and assistants
    const pipelinesContainer = document.getElementById('pipelines-container');
    const createPipelineBtn = document.getElementById('create-pipeline');
    const pipelineModalElement = document.getElementById('pipelineModal');
    const stageModalElement = document.getElementById('stageModal');
    const stageAssistantsModalElement = document.getElementById('stageAssistantsModal');

    let pipelineModal, stageModal, stageAssistantsModal;

    const pipelineForm = document.getElementById('pipeline-form');
    const stageForm = document.getElementById('stage-form');
    const successToolSelect = document.getElementById('success-tool-id');
    const stageTypeSelect = document.getElementById('stage-type');

    const stageAvailableAssistantsSelect = document.getElementById('stage-available-assistants');
    const stageAssignedAssistantsList = document.getElementById('stage-assigned-assistants');
    const stageAddAssistantBtn = document.getElementById('stage-add-assistant-btn');
    const stageAssistantsForm = document.getElementById('stage-assistants-form');

    let currentPipelineId = null;
    let currentStageId = null;



    function loadPipelines() {
        loadData('pipelines', '/api/pipelines', true)
            .then(pipelines => {
                // Code to execute after pipelines are loaded
                console.log('Pipelines loaded:', pipelines);

                renderPipelines();
            })
            .catch(error => console.error('Error loading pipelines:', error));
    }


    function loadToolsForSuccessTool() {

        console.log('load tools for success tool',appState.data.tools);

        successToolSelect.innerHTML = '<option value="">None</option>';

                appState.data.tools.forEach(tool => {
                    const option = document.createElement('option');
                    option.value = tool.id;
                    option.text = tool.name;
                    successToolSelect.add(option);
                });

    }

    function loadToolsForSuccessToolDropdown() {

                let tools =  appState.data.tools.map(tool => `<option value="${tool.id}">${tool.name}</option>`).join('');
                console.log('load tools for success tool',tools);

                return tools;

    }
    function loadStagesForStageSuccessToolDropdown(stages) {

        let optionList = stages.map(tool => `<option value="${stage.id}">${stage.name}</option>`).join('');
        console.log('load stages for success tool',stages);

        return optionList;

    }




    function renderPipelinesNew() {

        if(!appState.data.pipelines){

            loadPipelinesData();

        }

      let pipelines = appState.data.pipelines;


        console.log('Rendering Pipelines',pipelines);


        pipelinesContainer.innerHTML = '';
        pipelines.forEach(pipeline => {
            const pipelineCard = document.createElement('div');
            pipelineCard.classList.add('card', 'mb-3');
            pipelineCard.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>${pipeline.name}</h5>
                <div>
                    <button class="btn btn-success mb-2" data-action="show-pipeline" data-id="${pipeline.id}">Show Pipeline</button>
                    <button class="btn btn-success mb-2" data-action="execute-pipeline" data-id="${pipeline.id}">Execute Pipeline</button>
                    <button class="btn btn-danger btn-sm" data-action="delete-pipeline" data-id="${pipeline.id}">Delete</button>
                </div>
            </div>
            <div class="card-body">
                <p>${pipeline.description || ''}</p>
                <button class="btn btn-primary mb-2" data-action="add-stage" data-id="${pipeline.id}">Add Stage</button>
                <ul class="list-group" id="stages-${pipeline.id}">
                    ${pipeline.stages.map(stage => renderStage(stage)).join('')}
                </ul>
                <button class="btn btn-secondary mt-2" data-action="save-order" data-id="${pipeline.id}">Save Order</button>
            </div>
        `;
            pipelinesContainer.appendChild(pipelineCard);
        });
    }

    function renderStage(stage) {

        console.log('render stage',stage);

        const assistantsNames = stage.assistants.map(assistant => assistant.name).join(', ') || 'No Assistants';
        const successToolName = stage.success_tool ? stage.success_tool.name : '<span class="text-muted">No Success Tool</span>';

        return `
        <li class="list-group-item d-flex justify-content-between align-items-center" data-stage-id="${stage.id}">
            <span>
                <strong>Order xdvzxcv ${stage.order}:</strong> ${stage.name} (${stage.type}) - Assistants: ${assistantsNames} - Success Tool: ${successToolName}
            </span>
            <div>
                <button class="btn btn-warning btn-sm" data-action="edit-stage" data-pipeline-id="${stage.pipeline_id}" data-id="${stage.id}">Edit</button>
                <button class="btn btn-danger btn-sm" data-action="delete-stage" data-pipeline-id="${stage.pipeline_id}" data-id="${stage.id}">Delete</button>
                <button class="btn btn-primary btn-sm" data-action="assign-assistants" data-pipeline-id="${stage.pipeline_id}" data-id="${stage.id}">Assign Assistants</button>
            </div>
        </li>
    `;
    }



    function renderPipelines() {



        pipelines = appState.data.pipelines;


        console.log('Rendering Pipelines',pipelines);


        pipelinesContainer.innerHTML = '';
        appState.data.pipelines.forEach(pipeline => {
            const pipelineCard = document.createElement('div');
            pipelineCard.classList.add('card', 'mb-4', 'shadow-sm');
        pipelineCard.innerHTML = `
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0">${pipeline.name}</h5>
                    ${pipeline.description ? `<small class="text-muted">${pipeline.description}</small>` : ''}
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary btn-sm" data-action="show-pipeline" data-id="${pipeline.id}">
                        <i class="fas fa-eye me-1"></i>View
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this Pipeline?')" data-action="delete-pipeline" data-id="${pipeline.id}">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button class="btn btn-primary btn-sm" data-action="add-stage" data-id="${pipeline.id}">
                        <i class="fas fa-plus-circle me-1"></i>Add Stage
                    </button>
                </div>
                <div class="stages-container">
                    <ul class="list-group list-group-flush" id="stages-${pipeline.id}">
                        <!-- Stages will be rendered here -->
                    </ul>
                </div>
                <div class="mt-3">
                    <button class="btn btn-outline-secondary btn-sm" data-action="save-order" data-id="${pipeline.id}">
                        <i class="fas fa-save me-1"></i>Save Order
                    </button>
                </div>
            </div>
        `;
            pipelinesContainer.appendChild(pipelineCard);
            renderStages(pipeline);
        });

        // Attach event listeners for pipeline actions
        pipelinesContainer.querySelectorAll('[data-action="delete-pipeline"]').forEach(button => {
            button.addEventListener('click', deletePipeline);
        });

        // Attach event listeners for pipeline actions
        pipelinesContainer.querySelectorAll('[data-action="execute-pipeline"]').forEach(button => {
            button.addEventListener('click', deletePipeline);
        });

        // Attach event listeners for pipeline actions
        pipelinesContainer.querySelectorAll('[data-action="show-pipeline"]').forEach(button => {
            button.addEventListener('click', showPipeline);
        });


        pipelinesContainer.querySelectorAll('[data-action="add-stage"]').forEach(button => {
            button.addEventListener('click', showAddStageModal);
        });

        pipelinesContainer.querySelectorAll('[data-action="save-order"]').forEach(button => {
            button.addEventListener('click', () => {
                const pipelineId = button.getAttribute('data-id');
                saveStageOrder(pipelineId);
            });
        });

        pipelinesContainer.querySelectorAll('[data-action="assign-files"]').forEach(button => {
            button.addEventListener('click', showStageFilesModal);
        });
        pipelinesContainer.querySelectorAll('[data-action="assign-tools"]').forEach(button => {
            button.addEventListener('click', showStageToolsModal);
        });


    }
    function saveAssignedFiles(stageId, filePaths) {

        fetch(`/api/stages/${stageId}/update_files`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
            },
            body: JSON.stringify({ file_paths: filePaths })
        })
            .then(response => response.json())
            .then(data => {
                console.log('Files assigned:', data);
                const stageFilesModal = bootstrap.Modal.getInstance(document.getElementById('stageFilesModal'));
                stageFilesModal.hide();
                loadPipelines();
            });
    }

    function saveAssignedTool(stageId, toolId, successStageId) {
        fetch(`/api/stages/${stageId}/assign-tool`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
            },
            body: JSON.stringify({ tool_id: toolId, success_stage_id: successStageId })
        })
            .then(response => response.json())
            .then(data => {
                console.log('Tool assigned:', data);
                const stageToolsModal = bootstrap.Modal.getInstance(document.getElementById('stageToolsModal'));
                stageToolsModal.hide();
                loadPipelines();
            });
    }



    function showStageFilesModal(event) {
        currentPipelineId = event.target.getAttribute('data-pipeline-id');
        currentStageId = event.target.getAttribute('data-id');
        loadFilesForStage(currentPipelineId, currentStageId);
        const stageFilesModal = new bootstrap.Modal(document.getElementById('stageFilesModal'));
        stageFilesModal.show();
    }

    function showStageToolsModalOldMaynbe(event) {
        currentPipelineId = event.target.getAttribute('data-pipeline-id');
        currentStageId = event.target.getAttribute('data-id');
        loadToolsForStage(currentPipelineId, currentStageId);
        const stageToolsModal = new bootstrap.Modal(document.getElementById('stageToolsModal'));
        stageToolsModal.show();
    }



    function renderStages(pipeline) {
    const stagesList = document.getElementById(`stages-${pipeline.id}`);
    stagesList.innerHTML = ''; // Clear previous content

    pipeline.stages.forEach(stage => {
        const assistantsNames = stage.assistants.length
            ? stage.assistants.map(assistant => assistant.name).join(', ')
            : 'No Assistants';
        const successToolName = stage.success_tool
            ? stage.success_tool.name
            : 'No Success Tool';

        const stageItem = document.createElement('li');
        stageItem.classList.add('list-group-item', 'border-0', 'py-2');
        stageItem.setAttribute('data-stage-id', stage.id);

        stageItem.innerHTML = `
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-grip-vertical me-2 text-muted stage-drag-handle"></i>
                        <h6 class="mb-0">Stage ${stage.order + 1}: ${stage.name}</h6>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" 
                                data-bs-target="#stage-${stage.id}-content">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="stage-${stage.id}-content">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Stage Details -->
                                <div class="mb-2">
                                    <span class="badge bg-secondary">${stage.type}</span>
                                    <span class="badge bg-info ms-1">${successToolName}</span>
                                </div>
                                
                                <!-- Assistants Section -->
                                <div class="mt-3">
                                    <h6 class="text-muted mb-2 small">
                                        <i class="fas fa-users me-1"></i> Assistants
                                    </h6>
                                    <div class="assistant-list">
                                        ${stage.assistants.length ? 
                                            stage.assistants.map(assistant => `
                                                <div class="assistant-item mb-1 d-flex align-items-center">
                                                    <i class="fas fa-user-circle me-2 text-muted"></i>
                                                    <span>${assistant.name}</span>
                                                </div>
                                            `).join('') : 
                                            '<div class="text-muted small">No assistants assigned</div>'
                                        }
                                    </div>
                                </div>
                                
                                <!-- Files Section -->
                                <div class="mt-3">
                                    <h6 class="text-muted mb-2 small">
                                        <i class="fas fa-file me-1"></i> Files
                                    </h6>
                                    <div class="files-list">
                                        ${stage.files && stage.files.length ? 
                                            stage.files.map(file => `
                                                <div class="file-item small mb-1">
                                                    <i class="fas fa-file-alt me-1"></i>
                                                    ${file.file_path}
                                                </div>
                                            `).join('') : 
                                            '<div class="text-muted small">No files assigned</div>'
                                        }
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Action Buttons -->
                                <div class="d-flex flex-column gap-2">
                                    <button class="btn btn-outline-primary btn-sm" data-action="edit-stage" 
                                            data-pipeline-id="${pipeline.id}" data-id="${stage.id}">
                                        <i class="fas fa-edit me-1"></i>Edit Stage
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" data-action="assign-assistants" 
                                            data-pipeline-id="${pipeline.id}" data-id="${stage.id}">
                                        <i class="fas fa-user-plus me-1"></i>Manage Assistants
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" data-action="assign-files" 
                                            data-pipeline-id="${pipeline.id}" data-id="${stage.id}">
                                        <i class="fas fa-file-upload me-1"></i>Manage Files
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" data-action="assign-tools" 
                                            data-pipeline-id="${pipeline.id}" data-id="${stage.id}">
                                        <i class="fas fa-tools me-1"></i>Manage Tools
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" data-action="delete-stage" 
                                            data-pipeline-id="${pipeline.id}" data-id="${stage.id}">
                                        <i class="fas fa-trash me-1"></i>Delete Stage
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        stagesList.appendChild(stageItem);
    });

    // Initialize Sortable for stage order
    new Sortable(stagesList, {
        animation: 150,
        handle: '.stage-drag-handle',
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            saveStageOrder(pipeline.id);
        }
    });

    // Attach event listeners
    attachStageEventListeners(stagesList);
}
// Helper function to attach event listeners
function attachStageEventListeners(stagesList) {
    stagesList.querySelectorAll('[data-action]').forEach(button => {
        const action = button.getAttribute('data-action');
        switch(action) {
            case 'delete-stage':
                button.addEventListener('click', deleteStage);
                break;
            case 'edit-stage':
                button.addEventListener('click', showEditStageModal);
                break;
            case 'assign-assistants':
                button.addEventListener('click', showStageAssistantsModal);
                break;
            case 'assign-files':
                button.addEventListener('click', showStageFilesModal);
                break;
            case 'assign-tools':
                button.addEventListener('click', showStageToolsModal);
                break;
        }
    });
}
    // Helper function to render files list
    function renderFilesList(stage) {
        return `
        <ul class="list-group mt-2" id="files-${stage.id}">
            ${
            stage.files.length
                ? stage.files.map(file => renderFile(file, stage.id)).join('')
                : '<li class="list-group-item">No Files</li>'
        }
        </ul>
    `;
    }

    // Helper function to render individual file items
    function renderFile(file, stageId) {
        return `
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>${file.file_path}</span>
            <button class="btn btn-danger btn-sm" data-action="delete-file" data-stage-id="${stageId}" data-file-id="${file.id}" data-file-path="${file.file_path}">
                Delete
            </button>
        </li>
    `;
    }

    // Helper function to render each assistant with tool and stage options
    function renderAssistantWithOptions(assistant) {
        const toolOptions = loadToolsForSuccessToolDropdown();
        const stageOptions = loadToolsForSuccessToolDropdown();
        const successStageId = assistant.pivot.success_stage_id || '';
        const successToolId = assistant.pivot.success_tool_id || '';

        return `
        <li class="list-group-item d-flex justify-content-between align-items-center" data-assistant-id="${assistant.id}">
            <span>${assistant.name}</span>
            <div>
                <label class="form-label me-1 mb-0">Success Stage</label>
                <select class="form-select form-select-sm me-1" data-field="success_stage_id">
                    <option value="">None</option>
                    ${stageOptions.replace(`value="${successStageId}"`, `value="${successStageId}" selected`)}
                </select>
                <label class="form-label me-1 mb-0">Success Tool</label>
                <select class="form-select form-select-sm me-1" data-field="success_tool_id">
                    <option value="">None</option>
                    ${toolOptions.replace(`value="${successToolId}"`, `value="${successToolId}" selected`)}
                </select>
                <button class="btn btn-danger btn-sm" data-action="remove-stage-assistant" data-id="${assistant.id}">Remove</button>
            </div>
        </li>
    `;
    }



    function deleteFileFromStage(stageId, fileId) {
        fetch(`/api/stages/${stageId}/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
        })
            .then(response => {
                if (response.ok) {
                    console.log('File deleted successfully');
                    loadPipelines(); // Reload pipelines to refresh the file list
                } else {
                    console.error('Error deleting file');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }


    document.addEventListener('click', function(event) {
        if (event.target.matches('[data-action="delete-file"]')) {
            const stageId = event.target.getAttribute('data-stage-id');
            const fileId = event.target.getAttribute('data-file-id');

            // Confirm deletion
            if (confirm(`Are you sure you want to delete this file?`)) {
                deleteFileFromStage(stageId, fileId);
            }
        }
    });







    // Helper function to render assistant details
    function renderAssistant(assistant) {
        return `
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <strong>Name:</strong> ${assistant.name} <br>
                <strong>Type:</strong> ${assistant.interactive ? 'Interactive' : 'Non-Interactive'}
            </div>
            <div>
                <button class="btn btn-sm btn-secondary" data-action="view-details" data-id="${assistant.id}">View Details</button>
            </div>
        </li>
    `;
    }


    function saveStageOrder(pipelineId) {
        const stagesList = document.getElementById(`stages-${pipelineId}`);
        const stageIds = Array.from(stagesList.children).map((li, index) => {
            return {
                id: li.getAttribute('data-stage-id'),
                order: index
            };
        });

        console.log('Saving stage order:', stageIds);

        fetch(`/api/pipelines/${pipelineId}/stages/reorder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ stages: stageIds }),
        })
            .then(response => response.json())
            .then(data => {
                console.log('Stage order updated:', data);
                loadPipelines();
            });
    }



    function showPipeline(event) {
        const pipelineId = event.target.getAttribute('data-id');


        console.log(pipelineId);
        appState.current_id = pipelineId;
        createAndLoadSection('webapp.pipelines._show', 'pipeline_' + pipelineId + '_section', 'Pipeline: '+ pipelineId);


    }

    function deletePipeline(event) {
        const pipelineId = event.target.getAttribute('data-id');
        fetch(`/api/pipelines/${pipelineId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
        })
            .then(response => {
                console.log('Delete pipeline response:', response);

                loadPipelines();
            });
    }

    function deleteStage(event) {
        const pipelineId = event.target.getAttribute('data-pipeline-id');
        const stageId = event.target.getAttribute('data-id');
        fetch(`/api/stages/${stageId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
            },
        })
            .then(response => {
                console.log('Delete stage response:', response);

                loadPipelines();
            });
    }

    function showAddStageModal(event) {
        currentPipelineId = event.target.getAttribute('data-id');
        stageForm.reset();
        currentStageId = null;
        stageModal.show();
    }

    function showEditStageModal(event) {
        currentPipelineId = event.target.getAttribute('data-pipeline-id');
        currentStageId = event.target.getAttribute('data-id');
        stageForm.reset();
        loadStageDetails(currentPipelineId, currentStageId);
        stageModal.show();
    }


    function loadStageDetails(pipelineId, stageId) {

        console.log('loadStageDetails',pipelineId ,stageId );

        fetch(`/api/pipelines/${pipelineId}/stages/${stageId}`, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
            },
        })
            .then(response => response.json())
            .then(stage => {
                console.log('Stage details:', stage);
                document.getElementById('stage-name').value = stage.name || '';
                document.getElementById('stage-type').value = stage.type;
                successToolSelect.value = stage.success_tool_id || '';
            });
    }
    function loadStagesForSuccessStage(pipelineId) {
        const stagesSelect = document.getElementById('stage-success-stage');
        const pipeline = appState.data.pipelines.find(p => p.id === pipelineId);

        stagesSelect.innerHTML = `<option value="">None</option>` +
            pipeline.stages.map(stage => `<option value="${stage.id}">${stage.name}</option>`).join('');
    }
    function loadToolsForStage(stageId) {
        const toolsSelect = document.getElementById('stage-available-tools');
        toolsSelect.innerHTML = appState.data.tools
            .map(tool => `<option value="${tool.id}">${tool.name}</option>`)
            .join('');
    }




    function getCheckedFiles() {
        // Select all checked files and map to the correct format
        const checkedFiles = Array.from(document.querySelectorAll('.stage-file-checkbox:checked'))
            .filter(checkbox => checkbox.hasAttribute('value'))
            .map(checkbox => ({ file_path: checkbox.getAttribute('value') })); // Format as { file_path: "path/to/file" }



        console.log('checked files',checkedFiles);

        return checkedFiles;
    }
    document.getElementById('stage-files-form').addEventListener('submit', (event) => {
        event.preventDefault();

        const selectedFiles = getCheckedFiles();
        console.log('Selected files:', selectedFiles);

        saveAssignedFiles(currentStageId, selectedFiles);
    });






    function showStageToolsModal(event) {
        currentPipelineId = event.target.getAttribute('data-pipeline-id');
        currentStageId = event.target.getAttribute('data-id');

        // Load tools and stages for the dropdowns
        loadToolsForStage(currentStageId);
        loadStagesForSuccessStage(currentPipelineId);

        const stageToolsModal = new bootstrap.Modal(document.getElementById('stageToolsModal'));
        stageToolsModal.show();
    }
    document.getElementById('stage-tools-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const toolId = document.getElementById('stage-available-tools').value;
        const successStageId = document.getElementById('stage-success-stage').value || null;
        saveAssignedTool(currentStageId, toolId, successStageId);
    });

    document.getElementById('stage-tools-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const toolId = document.getElementById('stage-available-tools').value;
        const successStageId = document.getElementById('stage-success-stage').value || null;
        saveAssignedTool(currentStageId, toolId, successStageId);
    });
    stageForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const name = document.getElementById('stage-name').value;
        const type = document.getElementById('stage-type').value;
        const successToolId = document.getElementById('success-tool-id').value || null;

        const data = {
            name,
            type,
            success_tool_id: successToolId,
            order: 0,
        };

        const url = currentStageId ? `/api/stages/${currentStageId}` : `/api/pipelines/${currentPipelineId}/stages`;
        const method = currentStageId ? 'PUT' : 'POST';

        console.log('url',url);

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => response.json())
            .then(data => {
                console.log('Stage form submission response:', data);
                stageModal.hide();
                loadPipelines();
            });
    });

    createPipelineBtn.addEventListener('click', () => {
        pipelineForm.reset();
        pipelineModal.show();
    });
    function saveAssignedTool(stageId, toolId, successStageId) {
        fetch(`/api/stages/${stageId}/assign-tool`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ tool_id: toolId, success_stage_id: successStageId })
        })
            .then(response => response.json())
            .then(data => {
                console.log('Tool assigned:', data);
                const stageToolsModal = bootstrap.Modal.getInstance(document.getElementById('stageToolsModal'));
                stageToolsModal.hide();
                loadPipelines(); // Refresh pipelines if needed
            });
    }


    function loadFilesForStage(pipelineId, stageId) {


    }


    pipelineForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const name = document.getElementById('pipeline-name').value;
        const description = document.getElementById('pipeline-description').value;

        fetch('/api/pipelines', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name, description }),
        })
            .then(response => response.json())
            .then(data => {
                console.log('Pipeline creation response:', data);
                pipelineModal.hide();
                loadPipelines();
            });
    });

    // Handle Assigning Assistants to a Stage
    function showStageAssistantsModal(event) {
        const pipelineId = event.target.getAttribute('data-pipeline-id');
        const stageId = event.target.getAttribute('data-id');
        currentPipelineId = pipelineId;
        currentStageId = stageId;

        // Load available and assigned assistants for the stage
        loadAssistantsForStage(currentPipelineId, currentStageId);

        stageAssistantsModal.show();
    }

    function loadAssistantsForStage(pipelineId, stageId) {
        // Fetch pipeline stages for the Success Stage dropdown
        fetch(`/api/pipelines/${pipelineId}`, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            },
        })
            .then(response => response.json())
            .then(pipeline => {
                const stagesOptions = pipeline.stages
                    .map(stage => `<option value="${stage.id}">Stage ${stage.order}: ${stage.name} (${stage.type})</option>`)
                    .join('');
                console.log('Pipeline stages for Success Stage dropdown:', pipeline.stages);

                // Load available assistants
                fetch('/api/ollama_assistants', {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + appState.apiToken,
                        'Accept': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Available assistants data:', data);
                        stageAvailableAssistantsSelect.innerHTML = '';
                        data.assistants.forEach(assistant => {
                            const option = document.createElement('option');
                            option.value = assistant.id;
                            option.text = assistant.name;
                            stageAvailableAssistantsSelect.appendChild(option);
                        });
                    });

                // Load assigned assistants for the stage
                fetch(`/api/pipelines/${pipelineId}/stages/${stageId}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + appState.apiToken,
                        'Accept': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(stage => {
                        console.log('Stage data with assistants:', stage);
                        stageAssignedAssistantsList.innerHTML = '';

                            toolOptions = loadToolsForSuccessToolDropdown();
                            stageOptions = loadToolsForSuccessToolDropdown();

                            stage.assistants.sort((a, b) => a.pivot.order - b.pivot.order);
                            stage.assistants.forEach(assistant => {
                                const listItem = document.createElement('li');
                                listItem.classList.add('list-group-item');
                                listItem.setAttribute('data-assistant-id', assistant.id);

                                // Set the success_stage_id and success_tool_id from pivot table values
                                const successStageId = assistant.pivot.success_stage_id || '';
                                const successToolId = assistant.pivot.success_tool_id || '';

                                listItem.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${assistant.name}</span>
                            <div>
                                <label class="form-label me-1 mb-0">Success Stage</label>
                                <select class="form-select form-select-sm me-1" data-field="success_stage_id">
                                    <option value="">None</option>
                                    ${stageOptions.replace(`value="${successStageId}"`, `value="${successStageId}" selected`)}
                                </select>
                                <label class="form-label me-1 mb-0">Success Tool</label>
                                <select class="form-select form-select-sm me-1" data-field="success_tool_id">
                                    <option value="">None</option>
                                    ${toolOptions.replace(`value="${successToolId}"`, `value="${successToolId}" selected`)}
                                </select>
                                <button class="btn btn-danger btn-sm" data-action="remove-stage-assistant" data-id="${assistant.id}">Remove</button>
                            </div>
                        </div>
                    `;


                                console.log('list item', listItem);
                                let assDiv = document.getElementById('assistants-' + `${stage.id}`    + '-div');

                              //  assDiv.appendChild(listItem);
                                stageAssignedAssistantsList.appendChild(listItem);
                            });


                        // Initialize Sortable on the assigned assistants list
                        new Sortable(stageAssignedAssistantsList, {
                            animation: 150,
                        });
                    });
            });
    }


    stageAssistantsForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const assistantsData = Array.from(stageAssignedAssistantsList.children).map((li, index) => {

            const successStageId = li.querySelector('[data-field="success_stage_id"]').value || null;
            const successToolId = li.querySelector('[data-field="success_tool_id"]').value || null;

            return {
                assistant_id: li.getAttribute('data-assistant-id'),
                order: index,
                success_stage_id: successStageId,
                success_tool_id: successToolId,
            };
        });

        console.log('assistantData',assistantsData);

        // Prepare data for API
        const data = {
            assistants: assistantsData,
        };

        // Update the stage with the new assistants data
        //fetch(`/api/pipelines/${currentPipelineId}/stage_assistants/${currentStageId}`, {
        fetch(`/api/stages/${currentStageId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                credentials: 'omit',
            },
            body: JSON.stringify(data),

        })
        .then(response => response.json())
        .then(data => {
            console.log('Update stage with assistants response:', data);
            stageAssistantsModal.hide();


            loadData('pipelines', '/api/pipelines');
            loadPipelines();
        });
    });

    stageAddAssistantBtn.addEventListener('click', () => {
        const selectedAssistantId = stageAvailableAssistantsSelect.value;
        const selectedAssistantText = stageAvailableAssistantsSelect.options[stageAvailableAssistantsSelect.selectedIndex].text;

        if (selectedAssistantId) {
            // Check if assistant is already added
            const exists = Array.from(stageAssignedAssistantsList.children).some(li => li.getAttribute('data-assistant-id') === selectedAssistantId);
            if (!exists) {
              toolOptions =  loadToolsForSuccessToolDropdown();


                    console.log('Tool options for assistant:', toolOptions);
                    const listItem = document.createElement('li');
                    listItem.classList.add('list-group-item');
                    listItem.setAttribute('data-assistant-id', selectedAssistantId);
                    listItem.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                        <span>${selectedAssistantText}</span>
                        <div>
                        <select class="form-select form-select-sm me-1" data-field="success_stage_id">
                        <option value="">None</option>

                        </select>
                        <select class="form-select form-select-sm me-1" data-field="success_tool_id">
                        <option value="">None</option>
                        ${toolOptions}
                        </select>
                        <button class="btn btn-danger btn-sm" data-action="remove-stage-assistant" data-id="${selectedAssistantId}">Remove</button>
                        </div>
                        </div>
                        `;
                    stageAssignedAssistantsList.appendChild(listItem);

            }
        }
    });

    stageAssignedAssistantsList.addEventListener('click', (event) => {
        if (event.target.getAttribute('data-action') === 'remove-stage-assistant') {
            event.target.closest('li').remove();
        }
    });

    // Initialize modals
    pipelineModal = new bootstrap.Modal(pipelineModalElement);
    stageModal = new bootstrap.Modal(stageModalElement);
    stageAssistantsModal = new bootstrap.Modal(stageAssistantsModalElement);


</script>



<script>

    const token = appState.apiToken;  // Replace with the actual token

    async function fetchDirectoryContents(path = '/', container = document.getElementById('file-browser')) {
        const response = await fetch(`/api/files?path=${encodeURIComponent(path)}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            console.error("Failed to fetch directory contents");
            return;
        }

        const data = await response.json();
        displayDirectoryContents(data, path, container);
    }

    function displayDirectoryContents(data, path, container) {
        const dirContainer = document.createElement('div');
        dirContainer.classList.add('directory-container');

        data.directories.forEach(directory => {
            const dirElement = document.createElement('div');
            dirElement.className = 'directory';

            dirElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="file-checkbox me-2" data-path="${directory}">
                    <span class="directory-name d-flex align-items-center" data-path="${directory}">
                        <i class="fas fa-folder folder-icon me-2"></i> ${directory}
                    </span>
                </div>
            `;

            dirElement.querySelector('.directory-name').addEventListener('click', function () {
                // Clear any existing sub-directory content
                const existingSubDirContainer = dirElement.querySelector('.nested');
                if (existingSubDirContainer) {
                    existingSubDirContainer.remove();
                }

                // Create a new sub-directory container and fetch new contents
                const subDirContainer = document.createElement('div');
                subDirContainer.classList.add('nested', 'directory-container');
                fetchDirectoryContents(directory, subDirContainer);
                dirElement.appendChild(subDirContainer);
            });

            dirContainer.appendChild(dirElement);
        });

        data.files.forEach(file => {
            const fileElement = document.createElement('div');
            fileElement.className = 'file';
            fileElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="stage-file-checkbox form-check-input me-2" data-path="${file}" value="${file}">
                    <span class="file-name d-flex align-items-center" data-path="${file}">
                        <i class="fas fa-file-alt me-2"></i> ${file}
                    </span>
                </div>
            `;

            fileElement.querySelector('.file-name').addEventListener('click', async function () {
                const filePath = this.getAttribute('data-path');
                await previewFile(filePath);
            });

            dirContainer.appendChild(fileElement);
        });

        container.appendChild(dirContainer);
    }

    async function previewFile(filePath) {
        const response = await fetch(`/api/download?file=${encodeURIComponent(filePath)}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            console.error("Failed to download file");
            return;
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const filePreviewWindow = window.open();
        filePreviewWindow.document.write(`<iframe src="${url}" style="width:100%; height:100%;" frameborder="0"></iframe>`);
    }

    fetchDirectoryContents('/');
</script>
