<!-- resources/views/webapp/ollama/_generate_image.blade.php -->
<style>
    #generate-image-response img {
        max-width: 100%;
        height: auto;
    }
</style>

<div id="generate-image-section" class="mb-5">
    <h2>Generate Image</h2>
    <form id="generate-image-form">
        <div class="mb-3">
            <label for="generate-image-select" class="form-label">Model</label>
            <select class="form-control model-dropdown" id="generate-image-select" name="model" required>
                <option value="" disabled selected>Select a model</option>
                <!-- Options will be populated dynamically -->
            </select>
        </div>
        <div class="mb-3">
            <label for="generate-image-prompt" class="form-label">Prompt</label>
            <textarea class="form-control" id="generate-image-prompt" name="prompt" rows="3" required placeholder="Enter your image prompt here..."></textarea>
        </div>
        <div class="mb-3">
            <label for="generate-image-options" class="form-label">Options (JSON)</label>
            <textarea class="form-control" id="generate-image-options" name="options" rows="3" placeholder='{"resolution": "512x512"}'></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Generate Image</button>
    </form>
    <div id="generate-image-response" class="mt-3"></div>
</div>

<script>

        const generateImageForm = document.getElementById('generate-image-form');
        const generateImageResponse = document.getElementById('generate-image-response');

        generateImageForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(generateImageForm);
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
                const response = await fetch('/api/ollama/stable-diffusion/generate-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${appState.apiToken}`,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Assuming the API returns the image in Base64
                    const base64Image = result.data.response; // Adjust based on actual API response
                    generateImageResponse.innerHTML = `
                    <h3>Generated Image:</h3>
                    <img src="data:image/png;base64,${base64Image}" alt="Generated Image" class="img-fluid">
                `;
                } else {
                    showAlert(`Error: ${result.error || 'Unknown error'}`, 'danger');
                }
            } catch (error) {
                console.error('Error generating image:', error);
                showAlert(`Error: ${error.message}`, 'danger');
            }
        });

        // Function to populate the model dropdown (reuse from existing code)
        function populateModelDropdown() {
            const localModels = modelData.localModels;
            let modelOptions = '<option value="" disabled selected>Select a model</option>';
            localModels.forEach(model => {
                modelOptions += `<option value="${model.name}">${model.name}</option>`;
            });

            document.getElementById('generate-image-select').innerHTML = modelOptions;
        }


</script>
