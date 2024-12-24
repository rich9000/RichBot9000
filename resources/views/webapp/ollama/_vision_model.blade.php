<!-- resources/views/webapp/ollama/_vision_model.blade.php -->
<style>
    #vision-model-response img {
        max-width: 100%;
        height: auto;
    }
</style>

<div id="vision-model-section" class="mb-5">
    <h2>Vision Model</h2>
    <form id="vision-model-form">
        <div class="mb-3">
            <label for="vision-model-select" class="form-label">Model</label>
            <select class="form-control model-dropdown" id="vision-model-select" name="model" required>
                <option value="" disabled selected>Select a model</option>
                <!-- Options will be populated dynamically -->
            </select>
        </div>
        <div class="mb-3">
            <label for="vision-model-prompt" class="form-label">Prompt</label>
            <textarea class="form-control" id="vision-model-prompt" name="prompt" rows="3" required placeholder="Enter your prompt here..."></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Image</label>
            <input type="file" class="form-control" id="image-file-input" accept="image/*">
        </div>
        <div class="mb-3">
            <label class="form-label">Or Capture Image from Webcam</label><br>
            <button type="button" class="btn btn-secondary" id="start-webcam-btn">Start Webcam</button>
            <button type="button" class="btn btn-secondary" id="capture-image-btn" disabled>Capture Image</button>
            <div id="webcam-container" style="display: none;">
                <video id="webcam-video" width="640" height="480" autoplay></video>
                <canvas id="webcam-canvas" width="640" height="480" style="display: none;"></canvas>
            </div>
        </div>
        <input type="hidden" id="captured-image-data" name="image_data">
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <div id="vision-model-response" class="mt-3"></div>
</div>

<script>

        const startWebcamBtn = document.getElementById('start-webcam-btn');
        const captureImageBtn = document.getElementById('capture-image-btn');
        const webcamContainer = document.getElementById('webcam-container');
        const webcamVideo = document.getElementById('webcam-video');
        const webcamCanvas = document.getElementById('webcam-canvas');
        const imageFileInput = document.getElementById('image-file-input');
        const capturedImageDataInput = document.getElementById('captured-image-data');
        let webcamStream = null;

        startWebcamBtn.addEventListener('click', function() {
            if (webcamStream) {
                // Stop webcam
                webcamStream.getTracks().forEach(track => track.stop());
                webcamStream = null;
                webcamContainer.style.display = 'none';
                startWebcamBtn.textContent = 'Start Webcam';
                captureImageBtn.disabled = true;
            } else {
                // Start webcam
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(stream => {
                        webcamStream = stream;
                        webcamVideo.srcObject = stream;
                        webcamContainer.style.display = 'block';
                        startWebcamBtn.textContent = 'Stop Webcam';
                        captureImageBtn.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error accessing webcam:', error);
                        alert('Could not access webcam.');
                    });
            }
        });

        captureImageBtn.addEventListener('click', function() {
            webcamCanvas.getContext('2d').drawImage(webcamVideo, 0, 0, webcamCanvas.width, webcamCanvas.height);
            const imageData = webcamCanvas.toDataURL('image/png');
            capturedImageDataInput.value = imageData;
            // Stop webcam after capturing image
            webcamStream.getTracks().forEach(track => track.stop());
            webcamStream = null;
            webcamContainer.style.display = 'none';
            startWebcamBtn.textContent = 'Start Webcam';
            captureImageBtn.disabled = true;
            showAlert('Image captured from webcam.', 'success');
        });

        const visionModelForm = document.getElementById('vision-model-form');
        const visionModelResponse = document.getElementById('vision-model-response');

        visionModelForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(visionModelForm);
            const model = formData.get('model');
            const prompt = formData.get('prompt').trim();

            if (!model || !prompt) {
                showAlert('Model and prompt are required.', 'warning');
                return;
            }

            let imageData = null;

            // Check if image was uploaded from file
            const imageFile = imageFileInput.files[0];
            if (imageFile) {
                const reader = new FileReader();
                reader.onload = async function(event) {
                    imageData = event.target.result;
                    await sendVisionModelRequest(model, prompt, imageData);
                };
                reader.readAsDataURL(imageFile);
            } else if (capturedImageDataInput.value) {
                // Image was captured from webcam
                imageData = capturedImageDataInput.value;
                await sendVisionModelRequest(model, prompt, imageData);
            } else {
                showAlert('Please upload an image or capture one from webcam.', 'warning');
                return;
            }
        });

        async function sendVisionModelRequest(model, prompt, imageData) {
            try {
                const data = {
                    model: model,
                    prompt: prompt,
                    image_data: imageData,
                };

                const response = await fetch('/api/ollama/vision-model', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${appState.apiToken}`,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Assuming the API returns the answer as text
                    const answer = result.data.response;
                    visionModelResponse.innerHTML = `
                    <h3>Response:</h3>
                    <p>${answer}</p>
                `;
                } else {
                    showAlert(`Error: ${result.error || 'Unknown error'}`, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert(`Error: ${error.message}`, 'danger');
            }
        }

        // Function to populate model dropdown (reuse from existing code)
        function populateModelDropdown() {
            const localModels = modelData.localModels;
            let modelOptions = '<option value="" disabled selected>Select a model</option>';
            localModels.forEach(model => {
                modelOptions += `<option value="${model.name}">${model.name}</option>`;
            });

            document.getElementById('vision-model-select').innerHTML = modelOptions;
        }

        // Assuming modelData is available globally
        if (typeof modelData !== 'undefined' && modelData.localModels) {
            populateModelDropdown();
        } else {
            // Fetch model data if not available
            fetch('/api/ollama/status', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${appState.apiToken}`,
                },
            })
                .then(response => response.json())
                .then(data => {
                    modelData = data;
                    populateModelDropdown();
                })
                .catch(error => {
                    console.error('Error fetching models:', error);
                });
        }

</script>
