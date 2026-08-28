class PipelineBuilder {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.pipelines = [];
        this.selectedPipeline = null;
        this.assistants = [];
        this.tools = [];
        this.nodeTypes = {
            assistant: {
                icon: 'fa-robot',
                name: 'Assistant',
                color: '#007bff'
            },
            file: {
                icon: 'fa-file',
                name: 'File',
                color: '#28a745'
            },
            transcript: {
                icon: 'fa-comment',
                name: 'Transcript',
                color: '#6c757d'
            }
        };
        this.createUI();
        this.loadAssistants();
        this.loadTools();
    }

    async loadAssistants() {
        try {
            const response = await fetch('/api/user_assistants', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load assistants');
            }
            
            const result = await response.json();
            this.assistants = result.assistants || [];
            this.renderPipelineList();
            this.renderAssistantPalette();
        } catch (error) {
            console.error('Error loading assistants:', error);
        }
    }

    async loadTools() {
        try {
            const response = await fetch('/api/tools', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load tools');
            }
            
            const result = await response.json();
            this.tools = result.tools || [];
        } catch (error) {
            console.error('Error loading tools:', error);
        }
    }

    createUI() {
        this.container.innerHTML = `
            <div class="pipeline-builder">
                <div class="row">
                    <div class="col-md-3">
                        <div class="pipeline-palette">
                            <h4>Pipelines</h4>
                            <div class="pipeline-list"></div>
                            <button class="btn btn-primary btn-sm mt-3" id="add-pipeline">
                                <i class="fas fa-plus"></i> New Pipeline
                            </button>
                            
                            <h4 class="mt-4">Nodes</h4>
                            <div class="node-palette">
                                ${Object.entries(this.nodeTypes).map(([type, info]) => `
                                    <div class="node-item" draggable="true" data-node-type="${type}">
                                        <i class="fas ${info.icon}"></i>
                                        <span>${info.name}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="pipeline-canvas">
                            <div class="canvas-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.container.querySelector('#add-pipeline').addEventListener('click', () => this.addPipeline());
        this.setupPaletteEventListeners();
    }

    renderAssistantPalette() {
        const palette = this.container.querySelector('.assistant-palette');
        palette.innerHTML = this.assistants.map(assistant => `
            <div class="assistant-item" draggable="true" data-assistant-id="${assistant.id}">
                <i class="fas fa-robot"></i>
                <span>${assistant.name}</span>
            </div>
        `).join('');
    }

    setupPaletteEventListeners() {
        this.container.querySelectorAll('.node-palette .node-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.nodeType);
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
            });
        });
    }

    renderPipelineList() {
        const pipelineList = this.container.querySelector('.pipeline-list');
        pipelineList.innerHTML = this.pipelines.map(pipeline => `
            <div class="pipeline-item ${pipeline.id === this.selectedPipeline?.id ? 'active' : ''}" 
                 data-pipeline-id="${pipeline.id}">
                <i class="fas fa-project-diagram"></i>
                <span>${pipeline.name}</span>
            </div>
        `).join('');

        // Add event listeners
        pipelineList.querySelectorAll('.pipeline-item').forEach(item => {
            item.addEventListener('click', () => {
                const pipelineId = parseInt(item.dataset.pipelineId);
                this.selectPipeline(pipelineId);
            });
        });
    }

    renderPipelineCanvas() {
        const canvas = this.container.querySelector('.canvas-container');
        if (!this.selectedPipeline) {
            canvas.innerHTML = '<div class="text-center">Select or create a pipeline to begin</div>';
            return;
        }

        canvas.innerHTML = `
            <div class="stage-list">
                ${this.selectedPipeline.stages.map((stage, index) => `
                    <div class="stage" data-stage-index="${index}">
                        <div class="stage-header">
                            <h5>Stage ${index + 1}</h5>
                            <div class="stage-actions">
                                <button class="btn btn-sm btn-outline-primary edit-stage" data-stage-index="${index}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-stage" data-stage-index="${index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="stage-content">
                            <div class="nodes-container" data-stage-index="${index}">
                                ${stage.nodes.map((node, nIndex) => `
                                    <div class="node-item ${node.type}" data-node-index="${nIndex}">
                                        <div class="node-header">
                                            <i class="fas ${this.nodeTypes[node.type].icon}"></i>
                                            <span>${this.nodeTypes[node.type].name}</span>
                                            <div class="node-actions">
                                                <button class="btn btn-sm btn-outline-primary edit-node" data-node-index="${nIndex}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-node" data-node-index="${nIndex}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="node-content">
                                            ${this.renderNodeContent(node, nIndex)}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `).join('')}
                <button class="btn btn-outline-primary btn-sm add-stage">
                    <i class="fas fa-plus"></i> Add Stage
                </button>
            </div>
        `;

        this.setupCanvasEventListeners();
    }

    renderNodeContent(node, nodeIndex) {
        switch (node.type) {
            case 'assistant':
                return `
                    <div class="form-group">
                        <label>Assistant</label>
                        <select class="form-select assistant-select">
                            <option value="">Select Assistant</option>
                            ${this.assistants.map(assistant => `
                                <option value="${assistant.id}" ${node.assistantId === assistant.id ? 'selected' : ''}>
                                    ${assistant.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Script</label>
                        <textarea class="form-control" rows="3" placeholder="Enter script for this assistant...">${node.script || ''}</textarea>
                    </div>
                    <div class="form-group mt-2">
                        <label>Transcript</label>
                        <textarea class="form-control transcript" rows="2" placeholder="What the assistant will say...">${node.transcript || ''}</textarea>
                    </div>
                    <div class="form-group mt-2">
                        <label>Tools</label>
                        <select class="form-select" multiple>
                            ${this.tools.map(tool => `
                                <option value="${tool.id}" ${node.tools.includes(tool.id) ? 'selected' : ''}>
                                    ${tool.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                `;
            case 'file':
                return `
                    <div class="form-group">
                        <label>File Type</label>
                        <select class="form-select file-type-select">
                            <option value="text" ${node.fileType === 'text' ? 'selected' : ''}>Text</option>
                            <option value="json" ${node.fileType === 'json' ? 'selected' : ''}>JSON</option>
                            <option value="csv" ${node.fileType === 'csv' ? 'selected' : ''}>CSV</option>
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Content</label>
                        <textarea class="form-control" rows="3" placeholder="Enter file content...">${node.content || ''}</textarea>
                    </div>
                `;
            case 'transcript':
                return `
                    <div class="form-group">
                        <label>Transcript</label>
                        <textarea class="form-control" rows="3" placeholder="Enter transcript text...">${node.content || ''}</textarea>
                    </div>
                `;
            default:
                return '';
        }
    }

    setupCanvasEventListeners() {
        const canvas = this.container.querySelector('.canvas-container');

        // Add Stage button
        canvas.querySelector('.add-stage')?.addEventListener('click', () => {
            this.addStage();
        });

        // Node containers for drag and drop
        canvas.querySelectorAll('.nodes-container').forEach(container => {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                container.classList.add('drag-over');
            });

            container.addEventListener('dragleave', (e) => {
                container.classList.remove('drag-over');
            });

            container.addEventListener('drop', (e) => {
                e.preventDefault();
                container.classList.remove('drag-over');
                const nodeType = e.dataTransfer.getData('nodeType');
                const stageIndex = parseInt(container.dataset.stageIndex);
                this.addNode(stageIndex, nodeType);
            });
        });

        // Assistant nodes for transcript drops
        canvas.querySelectorAll('.node-item.assistant').forEach(assistantNode => {
            assistantNode.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                assistantNode.classList.add('drag-over');
            });

            assistantNode.addEventListener('dragleave', (e) => {
                assistantNode.classList.remove('drag-over');
            });

            assistantNode.addEventListener('drop', (e) => {
                e.preventDefault();
                assistantNode.classList.remove('drag-over');
                const nodeType = e.dataTransfer.getData('nodeType');
                
                if (nodeType === 'transcript') {
                    const stageIndex = parseInt(assistantNode.closest('.stage').dataset.stageIndex);
                    const nodeIndex = parseInt(assistantNode.dataset.nodeIndex);
                    const transcriptContent = e.dataTransfer.getData('content') || '';
                    this.updateNodeTranscript(stageIndex, nodeIndex, transcriptContent);
                }
            });
        });

        // Transcript nodes for dragging
        canvas.querySelectorAll('.node-item.transcript').forEach(transcriptNode => {
            transcriptNode.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', 'transcript');
                e.dataTransfer.setData('content', transcriptNode.querySelector('textarea').value);
                transcriptNode.classList.add('dragging');
            });

            transcriptNode.addEventListener('dragend', (e) => {
                transcriptNode.classList.remove('dragging');
            });
        });

        // Node content changes
        canvas.querySelectorAll('.assistant-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const stageIndex = parseInt(e.target.closest('.stage').dataset.stageIndex);
                const nodeIndex = parseInt(e.target.closest('.node-item').dataset.nodeIndex);
                this.updateNodeAssistant(stageIndex, nodeIndex, parseInt(e.target.value));
            });
        });

        canvas.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('change', (e) => {
                const stageIndex = parseInt(e.target.closest('.stage').dataset.stageIndex);
                const nodeIndex = parseInt(e.target.closest('.node-item').dataset.nodeIndex);
                const node = this.selectedPipeline.stages[stageIndex].nodes[nodeIndex];
                
                if (node.type === 'assistant') {
                    if (e.target.classList.contains('transcript')) {
                        this.updateNodeTranscript(stageIndex, nodeIndex, e.target.value);
                    } else {
                        this.updateNodeScript(stageIndex, nodeIndex, e.target.value);
                    }
                } else {
                    this.updateNodeContent(stageIndex, nodeIndex, e.target.value);
                }
            });
        });

        canvas.querySelectorAll('.file-type-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const stageIndex = parseInt(e.target.closest('.stage').dataset.stageIndex);
                const nodeIndex = parseInt(e.target.closest('.node-item').dataset.nodeIndex);
                this.updateNodeFileType(stageIndex, nodeIndex, e.target.value);
            });
        });
    }

    addPipeline() {
        const pipeline = {
            id: Date.now(),
            name: 'New Pipeline',
            stages: []
        };

        this.pipelines.push(pipeline);
        this.renderPipelineList();
        this.selectPipeline(pipeline.id);
    }

    selectPipeline(pipelineId) {
        this.selectedPipeline = this.pipelines.find(p => p.id === pipelineId);
        this.renderPipelineList();
        this.renderPipelineCanvas();
    }

    addStage() {
        if (!this.selectedPipeline) return;

        this.selectedPipeline.stages.push({
            nodes: []
        });

        this.renderPipelineCanvas();
    }

    addNode(stageIndex, nodeType) {
        const stage = this.selectedPipeline.stages[stageIndex];
        if (!stage) return;

        const node = {
            type: nodeType,
            ...(nodeType === 'assistant' ? {
                assistantId: null,
                script: '',
                transcript: '',
                tools: []
            } : nodeType === 'file' ? {
                fileType: 'text',
                content: ''
            } : {
                content: ''
            })
        };

        stage.nodes.push(node);
        this.renderPipelineCanvas();
    }

    updateNodeAssistant(stageIndex, nodeIndex, assistantId) {
        const stage = this.selectedPipeline.stages[stageIndex];
        if (!stage) return;

        const node = stage.nodes[nodeIndex];
        if (!node || node.type !== 'assistant') return;

        node.assistantId = assistantId;
    }

    updateNodeScript(stageIndex, nodeIndex, script) {
        const stage = this.selectedPipeline.stages[stageIndex];
        if (!stage) return;

        const node = stage.nodes[nodeIndex];
        if (!node || node.type !== 'assistant') return;

        node.script = script;
    }

    updateNodeTranscript(stageIndex, nodeIndex, transcript) {
        const stage = this.selectedPipeline.stages[stageIndex];
        if (!stage) return;

        const node = stage.nodes[nodeIndex];
        if (!node || node.type !== 'assistant') return;

        node.transcript = transcript;
    }

    updateNodeContent(stageIndex, nodeIndex, content) {
        const stage = this.selectedPipeline.stages[stageIndex];
        if (!stage) return;

        const node = stage.nodes[nodeIndex];
        if (!node) return;

        node.content = content;
    }

    updateNodeFileType(stageIndex, nodeIndex, fileType) {
        const stage = this.selectedPipeline.stages[stageIndex];
        if (!stage) return;

        const node = stage.nodes[nodeIndex];
        if (!node || node.type !== 'file') return;

        node.fileType = fileType;
        if (fileType === 'json' && !node.content) {
            node.content = '{}';
        }
    }

    async savePipeline() {
        if (!this.selectedPipeline) return;

        try {
            const response = await fetch('/api/pipelines', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.selectedPipeline)
            });

            if (!response.ok) {
                throw new Error('Failed to save pipeline');
            }

            const result = await response.json();
            console.log('Pipeline saved:', result);
        } catch (error) {
            console.error('Error saving pipeline:', error);
        }
    }
}

// Add styles
const style = document.createElement('style');
style.textContent = `
    .pipeline-builder {
        height: 100%;
        padding: 1rem;
    }
    .pipeline-palette {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        height: 100%;
    }
    .pipeline-palette h4 {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    .pipeline-list, .node-palette {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .pipeline-item, .node-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
    }
    .pipeline-item:hover, .node-item:hover {
        background: #e9ecef;
    }
    .pipeline-item.active {
        background: #007bff;
        color: white;
        border-color: #0056b3;
    }
    .node-item {
        cursor: move;
    }
    .pipeline-canvas {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        height: 100%;
        min-height: 600px;
        position: relative;
    }
    .canvas-container {
        position: relative;
        width: 100%;
        height: 100%;
        padding: 1rem;
    }
    .stage {
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .stage-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .stage-content {
        display: flex;
        gap: 1rem;
    }
    .nodes-container {
        flex: 1;
        min-height: 100px;
        border: 1px dashed #dee2e6;
        border-radius: 4px;
        padding: 0.5rem;
        background: #f8f9fa;
    }
    .nodes-container.drag-over {
        background: #e9ecef;
        border-color: #007bff;
    }
    .node-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .node-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .node-content {
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 4px;
    }
    .form-group {
        margin-bottom: 0.5rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    .dragging {
        opacity: 0.5;
    }
    .node-item.assistant {
        border-left: 4px solid #007bff;
    }
    .node-item.file {
        border-left: 4px solid #28a745;
    }
    .node-item.transcript {
        border-left: 4px solid #6c757d;
    }
`;
document.head.appendChild(style); 