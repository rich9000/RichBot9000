<div class="easy-mode">
    <button class="btn btn-primary easy-mode-btn">EasyMode</button>
    <div class="easy-mode-prompt" style="display: none;">
        <form id="easy-mode-form">
            <textarea id="prompt" name="prompt" rows="3" class="form-control"></textarea>
            <div class="audio-recorder" data-target-id="prompt">
                <button type="button" class="btn btn-primary record-btn">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-success">Submit</button>
        </form>
        <div class="loading-spinner" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div class="easy-mode-result" style="display: none;">
            <p class="result-message"></p>
            <button class="btn btn-primary refresh-btn">Refresh Page</button>
        </div>
    </div>
</div>

<script>



    const apiToken = appState.apiToken;

    document.querySelector('.easy-mode-btn').addEventListener('click', function () {
        const promptDiv = document.querySelector('.easy-mode-prompt');
        promptDiv.style.display = promptDiv.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('easy-mode-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const easyModePrompt = document.querySelector('.easy-mode-prompt');
        const loadingSpinner = document.querySelector('.loading-spinner');
        const promptValue = document.getElementById('prompt').value;

        easyModePrompt.style.opacity = '0.5';
        loadingSpinner.style.display = 'block';

        const url = '{{ route("api.openai.easy-mode") }}'; // Adjust route to your Laravel route
        const method = 'POST';
        const data = {
            prompt: promptValue
        };

        const headers = {
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + apiToken, // Use your apiToken here
            'Content-Type': 'application/json'
        };

        const options = {
            method: method,
            headers: headers,
            body: JSON.stringify(data)
        };

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(response => {
                easyModePrompt.style.opacity = '1';
                loadingSpinner.style.display = 'none';

                const resultMessage = document.querySelector('.easy-mode-result .result-message');
                if (response.length > 0 && response[0].content[0].type === 'text') {
                    const extractedText = response[0].content[0].text.value;
                    resultMessage.textContent = extractedText;
                } else {
                    resultMessage.textContent = 'No text content found.';
                }
                document.querySelector('.easy-mode-result').style.display = 'block';
            })
            .catch(error => {
                easyModePrompt.style.opacity = '1';
                loadingSpinner.style.display = 'none';
                alert('An error occurred: ' + error.message);
            });
    });

    document.querySelector('.refresh-btn').addEventListener('click', function () {
        location.reload();
    });

    document.querySelector('.record-btn').addEventListener('click', function () {
        const targetId = this.parentElement.getAttribute('data-target-id');
        // Implement your audio recording logic here
        alert('Microphone button clicked for ' + targetId);
    });
</script>
