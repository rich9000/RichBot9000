<!-- Partial: Pull Model -->
<div id="pull-model-section" class="mb-5">
    <h2>Pull Model</h2>
    <form id="pull-model-form">
        <div class="mb-3">
            <label for="pull-model-name" class="form-label">Model Name</label>
            <input type="text" class="form-control" id="pull-model-name" name="name" required placeholder="e.g., llama3:latest">
        </div>
        <button type="submit" class="btn btn-primary">Pull Model</button>
    </form>
    <div id="pull-model-response" class="mt-3"></div>
</div>

<script>
    // Ensure this script runs after the partial is loaded

        const pullModelForm = document.getElementById('pull-model-form');
        const pullModelResponse = document.getElementById('pull-model-response');

        pullModelForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(pullModelForm);
            const modelName = formData.get('name').trim();

            if (!modelName) {
                showAlert('Model name cannot be empty.', 'warning');
                return;
            }

            try {
                const response = await fetch('/api/ollama/pull-model', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer '+appState.apiToken,
                    },
                    body: JSON.stringify({ name: modelName }),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showAlert(`Success: ${result.message}`, 'success');
                    pullModelForm.reset();
                    fetchStatus(); // Refresh the status
                } else {
                    showAlert(`Error: ${result.error || 'Unknown error'}`, 'danger');
                }
            } catch (error) {
                console.error('Error pulling model:', error);
                showAlert(`Error: ${error.message}`, 'danger');
            }

    });
</script>
