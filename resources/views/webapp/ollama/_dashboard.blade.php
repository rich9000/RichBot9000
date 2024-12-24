
<style>
    #status {
        margin-top: 20px;
    }
    pre {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        overflow: auto;
    }
    /* Adjust tab content padding */
    .tab-content {
        padding-top: 20px;
    }
</style>
<div class="container mt-5">
    <h1>Ollama Dashboard</h1>

    <!-- Refresh Button -->
    <button id="refresh-button" class="btn btn-secondary mb-3">Refresh Models</button>

    <!-- Status Section -->
    <div id="status" class="mb-5">
        <h2>Status</h2>
        <p>Loading...</p>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="ollamaTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="pull-model-tab" data-bs-toggle="tab" href="#pull-model" role="tab" aria-controls="pull-model" aria-selected="true">Pull Model</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="create-model-tab" data-bs-toggle="tab" href="#create-model" role="tab" aria-controls="create-model" aria-selected="false">Create Model</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="run-completion-tab" data-bs-toggle="tab" href="#run-completion" role="tab" aria-controls="run-completion" aria-selected="false">Run Completion</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="run-chat-tab" data-bs-toggle="tab" href="#run-chat" role="tab" aria-controls="run-chat" aria-selected="false">Run Chat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="generate-image-tab" data-bs-toggle="tab" href="#generate-image" role="tab" aria-controls="generate-image" aria-selected="false">Generate Image</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="vision-model-tab" data-bs-toggle="tab" href="#vision-model" role="tab" aria-controls="vision-model" aria-selected="false">Vision Model</a>
        </li>
    </ul>

    <div class="tab-content" id="ollamaTabsContent">
        <!-- Pull Model Tab -->
        <div class="tab-pane fade show active" id="pull-model" role="tabpanel" aria-labelledby="pull-model-tab">
            <!-- Pull Model Content Partial -->

            @include('webapp.ollama._pull_model')
        </div>
        <!-- Create Model Tab -->
        <div class="tab-pane fade" id="create-model" role="tabpanel" aria-labelledby="create-model-tab">
            <!-- Create Model Content Partial -->
            @include('webapp.ollama._create_model')
        </div>
        <!-- Run Completion Tab -->
        <div class="tab-pane fade" id="run-completion" role="tabpanel" aria-labelledby="run-completion-tab">
            <!-- Run Completion Content Partial -->

            @include('webapp.ollama._run_completion')
        </div>
        <!-- Run Chat Tab -->
        <div class="tab-pane fade" id="run-chat" role="tabpanel" aria-labelledby="run-chat-tab">
            <!-- Run Chat Content Partial -->
            @include('webapp.ollama._run_chat')
        </div>
        <!-- Generate Image Tab -->
        <div class="tab-pane fade" id="generate-image" role="tabpanel" aria-labelledby="generate-image-tab">
            <!-- Generate Image Content Partial -->
            @include('webapp.ollama._generate_image')
        </div>
        <!-- Vision Model Tab -->
        <div class="tab-pane fade" id="vision-model" role="tabpanel" aria-labelledby="vision-model-tab">
            <!-- Vision Model Content Partial -->
            @include('webapp.ollama._vision_model')
        </div>
    </div>
</div>


<script>
    // Assuming appState is already defined in webapp.js

    // Replace this with your actual Bearer token retrieval method
    const bearerToken = appState.apiToken; // Example placeholder

    let modelData = {}; // Global variable to store model data

    // Fetch model data and update UI
    async function fetchStatus() {
        try {
            const response = await fetch('/api/ollama/status', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${bearerToken}`,
                },
            });

            if (!response.ok) {
                const errorData = await response.json();
                showAlert(`<strong>Error:</strong> ${errorData.error || 'Unknown error'}`, 'danger');
                return;
            }

            const data = await response.json();

            if (data.success) {
                modelData = data; // Store model data globally
                updateStatusUI(data);
                populateModelDropdowns();
            } else {
                showAlert(`<strong>Error:</strong> ${data.error}`, 'danger');
            }
        } catch (error) {
            console.error('Error fetching status:', error);
            showAlert(`<strong>Error:</strong> ${error.message}`, 'danger');
        }
    }

    // Update the status UI
    function updateStatusUI(data) {
        // Display Running Models
        const runningModels = data.runningModels;
        let runningModelsHTML = '<h2>Running Models:</h2>';

        if (runningModels.length > 0) {
            runningModelsHTML += `
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Model</th>
                            <th>Size (MB)</th>
                            <th>Expires At</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            runningModels.forEach(model => {
                runningModelsHTML += `
                    <tr>
                        <td>${model.name}</td>
                        <td>${model.model}</td>
                        <td>${(model.size / (1024 * 1024)).toFixed(2)}</td>
                        <td>${model.expires_at || 'N/A'}</td>
                        <td><pre>${JSON.stringify(model.details, null, 2)}</pre></td>
                        <td>
                            <button class="btn btn-danger btn-sm delete-model" data-model-name="${model.name}">Delete</button>
                        </td>
                    </tr>
                `;
            });

            runningModelsHTML += `
                    </tbody>
                </table>
            `;
        } else {
            runningModelsHTML += `<p>No models are currently running.</p>`;
        }

        // Display Local Models
        const localModels = data.localModels;
        let localModelsHTML = '<h2>Available Models:</h2>';

        if (localModels.length > 0) {
            localModelsHTML += `
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Size (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            localModels.forEach(model => {
                localModelsHTML += `
                    <tr>
                        <td>${model.name}</td>
                        <td>${(model.size / (1024 * 1024)).toFixed(2)}</td>
                    </tr>
                `;
            });

            localModelsHTML += `
                    </tbody>
                </table>
            `;
        } else {
            localModelsHTML += `<p>No local models found.</p>`;
        }

        // Update the statusDiv with both tables
        document.getElementById('status').innerHTML = runningModelsHTML + localModelsHTML;
    }

    // Populate model dropdowns in forms
    function populateModelDropdowns() {
        const localModels = modelData.localModels;
        let modelOptions = '<option value="" disabled selected>Select a model</option>';
        localModels.forEach(model => {
            modelOptions += `<option value="${model.name}">${model.name}</option>`;
        });

        // Update all select elements with class 'model-dropdown'
        document.querySelectorAll('.model-dropdown').forEach(select => {
            select.innerHTML = modelOptions;
        });
    }

    // Initial fetch
    fetchStatus();

    // Refresh Button
    document.getElementById('refresh-button').addEventListener('click', fetchStatus);

    // Delete Model Handler
    document.addEventListener('click', async function(event) {
        if (event.target && event.target.classList.contains('delete-model')) {
            const modelName = event.target.getAttribute('data-model-name');
            if (confirm(`Are you sure you want to delete model '${modelName}'?`)) {
                try {
                    const response = await fetch('/api/ollama/delete-model', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${bearerToken}`,
                        },
                        body: JSON.stringify({ name: modelName }),
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        showAlert(result.message, 'success');
                        fetchStatus(); // Refresh the status
                    } else {
                        showAlert(`<strong>Error:</strong> ${result.error || 'Unknown error'}`, 'danger');
                    }
                } catch (error) {
                    console.error('Error deleting model:', error);
                    showAlert(`<strong>Error:</strong> ${error.message}`, 'danger');
                }
            }
        }
    });

    // Load content partials


</script>
