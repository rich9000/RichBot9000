<!-- Partial: Run Completion -->
<div id="run-completion-section" class="mb-5">
    <h2>Run Completion</h2>
    <form id="run-completion-form">
        <div class="mb-3">
            <label for="completion-model" class="form-label">Model</label>
            <select class="form-control model-dropdown" id="completion-model" name="model" required>
                <option value="" disabled selected>Select a model</option>
                <!-- Options will be populated dynamically -->
            </select>
        </div>
        <div class="mb-3">
            <label for="completion-prompt" class="form-label">Prompt</label>
            <textarea class="form-control" id="completion-prompt" name="prompt" rows="3" required placeholder="Enter your prompt here..."></textarea>
        </div>
        <div class="mb-3">
            <label for="completion-options" class="form-label">Options (JSON)</label>
            <textarea class="form-control" id="completion-options" name="options" rows="3" placeholder='{"temperature": 0.7, "max_tokens": 100}'></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Run Completion</button>
    </form>
    <div id="run-completion-response" class="mt-3"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const runCompletionForm = document.getElementById('run-completion-form');
        const runCompletionResponse = document.getElementById('run-completion-response');

        runCompletionForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(runCompletionForm);
            const model = formData.get('model');
            const prompt = formData.get('prompt').trim();
            let options = null;

            if (formData.get('options').trim()) {
                try {
                    options = JSON.parse(formData.get('options'));
                } catch (error) {
                    showAlert('Options must be valid JSON.', 'warning');
                    return;
                }
            }

            if (!model || !prompt) {
                showAlert('Model and prompt are required.', 'warning');
                return;
            }

            const data = {
                model: model,
                prompt: prompt,
                options: options,
                stream: false,
            };

            try {
                const response = await fetch('/api/ollama/run-completion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${appState.apiToken}`,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    runCompletionResponse.innerHTML = `
                        <h3>Completion Result:</h3>
                        <pre>${JSON.stringify(result.data, null, 2)}</pre>
                    `;
                } else {
                    showAlert(`Error: ${result.error || 'Unknown error'}`, 'danger');
                }
            } catch (error) {
                console.error('Error running completion:', error);
                showAlert(`Error: ${error.message}`, 'danger');
            }
        });
    });
</script>
