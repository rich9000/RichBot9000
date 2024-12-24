<!-- Partial: Create Model -->
<div id="create-model-section" class="mb-5">
    <h2>Create New Model</h2>
    <form id="create-model-form">
        <div class="mb-3">
            <label for="model-name" class="form-label">Model Name</label>
            <input type="text" class="form-control" id="model-name" name="name" required placeholder="Enter new model name">
        </div>
        <div class="mb-3">
            <label for="parent-model" class="form-label">Parent Model</label>
            <select class="form-control model-dropdown" id="parent-model" name="parent" required>
                <option value="" disabled selected>Select a parent model</option>
                <!-- Options will be populated dynamically -->
            </select>
            <div class="form-text">Select a parent model from the dropdown.</div>
        </div>
        <div class="mb-3">
            <label for="system-message" class="form-label">System Message</label>
            <textarea class="form-control" id="system-message" name="system" rows="3" placeholder="Enter system message"></textarea>
        </div>
        <div class="mb-3">
            <label for="template" class="form-label">Template</label>
            <textarea class="form-control" id="template" name="template" rows="3" placeholder="Enter template"></textarea>
        </div>
        <div class="mb-3">
            <label for="parameters" class="form-label">Parameters (JSON)</label>
            <textarea class="form-control" id="parameters" name="parameters" rows="3" placeholder='{"num_keep": 24, "stop": "\u003c/s\u003e"}'></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create Model</button>
    </form>
    <div id="create-model-response" class="mt-3"></div>
</div>

<script>

        const createModelForm = document.getElementById('create-model-form');
        const createModelResponse = document.getElementById('create-model-response');

        createModelForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(createModelForm);
            const data = {
                name: formData.get('name').trim(),
                parent: formData.get('parent'),
                system: formData.get('system').trim(),
                template: formData.get('template').trim(),
                parameters: formData.get('parameters') ? JSON.parse(formData.get('parameters')) : null,
            };

            // Basic validation
            if (!data.name || !data.parent) {
                showAlert('Model name and parent model are required.', 'warning');
                return;
            }

            try {
                const response = await fetch('/api/ollama/create-model', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${appState.apiToken}`,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showAlert('Model created successfully.', 'success');
                    createModelForm.reset();
                    fetchStatus(); // Refresh the status
                } else {
                    showAlert(`Error: ${result.error || 'Unknown error'}`, 'danger');
                }
            } catch (error) {
                console.error('Error creating model:', error);
                showAlert(`Error: ${error.message}`, 'danger');
            }
        });

</script>
