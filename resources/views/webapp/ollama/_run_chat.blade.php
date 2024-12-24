<!-- Partial: Run Chat -->
<div id="run-chat-section" class="mb-5">
    <h2>Run Chat</h2>
    <form id="run-chat-form">
        <div class="mb-3">
            <label for="chat-model" class="form-label">Model</label>
            <select class="form-control model-dropdown" id="chat-model" name="model" required>
                <option value="" disabled selected>Select a model</option>
                <!-- Options will be populated dynamically -->
            </select>
        </div>
        <div class="mb-3">
            <label for="chat-messages" class="form-label">Messages (JSON Array)</label>
            <textarea class="form-control" id="chat-messages" name="messages" rows="3" placeholder='[{"role": "user", "content": "Tell me a joke."}]' required></textarea>
        </div>
        <div class="mb-3">
            <label for="chat-tools" class="form-label">Tools (JSON Array)</label>
            <textarea class="form-control" id="chat-tools" name="tools" rows="3" placeholder='[{"type": "function", "function": {"name": "get_current_weather", "description": "Get the current weather for a location", "parameters": {"type": "object", "properties": {"location": {"type": "string", "description": "The location to get the weather for, e.g., San Francisco, CA"}, "format": {"type": "string", "description": "The format to return the weather in, e.g., "celsius" or "fahrenheit"", "enum": ["celsius", "fahrenheit"]}}, "required": ["location", "format"]}}}]'></textarea>
        </div>
        <div class="mb-3">
            <label for="chat-options" class="form-label">Options (JSON)</label>
            <textarea class="form-control" id="chat-options" name="options" rows="3" placeholder='{"temperature": 0.7}'></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Run Chat</button>
    </form>
    <div id="run-chat-response" class="mt-3"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const runChatForm = document.getElementById('run-chat-form');
        const runChatResponse = document.getElementById('run-chat-response');

        runChatForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(runChatForm);
            const model = formData.get('model');
            const messagesInput = formData.get('messages').trim();
            const toolsInput = formData.get('tools').trim();
            let options = null;

            // Validate and parse messages
            let messages;
            try {
                messages = JSON.parse(messagesInput);
                if (!Array.isArray(messages)) {
                    throw new Error('Messages must be a JSON array.');
                }
            } catch (error) {
                showAlert(`Invalid JSON in messages: ${error.message}`, 'warning');
                return;
            }

            // Validate and parse tools if provided
            let tools = null;
            if (toolsInput) {
                try {
                    tools = JSON.parse(toolsInput);
                    if (!Array.isArray(tools)) {
                        throw new Error('Tools must be a JSON array.');
                    }
                } catch (error) {
                    showAlert(`Invalid JSON in tools: ${error.message}`, 'warning');
                    return;
                }
            }

            // Parse options if provided
            if (formData.get('options').trim()) {
                try {
                    options = JSON.parse(formData.get('options'));
                } catch (error) {
                    showAlert('Options must be valid JSON.', 'warning');
                    return;
                }
            }

            if (!model || !messages) {
                showAlert('Model and messages are required.', 'warning');
                return;
            }

            const data = {
                model: model,
                messages: messages,
                tools: tools,
                options: options,
                stream: false,
            };

            try {
                const response = await fetch('/api/ollama/run-chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${appState.apiToken}`,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    runChatResponse.innerHTML = `
                        <h3>Chat Result:</h3>
                        <pre>${JSON.stringify(result.data, null, 2)}</pre>
                    `;
                } else {
                    showAlert(`Error: ${result.error || 'Unknown error'}`, 'danger');
                }
            } catch (error) {
                console.error('Error running chat:', error);
                showAlert(`Error: ${error.message}`, 'danger');
            }
        });
    });
</script>
