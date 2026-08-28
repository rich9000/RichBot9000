<!-- Main Container -->
<div class="row">
    <!-- Generated Form Card -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Generated Form</h5>
                <div>
                    <span class="badge bg-light text-dark" id="form-display-status">Waiting for Content</span>
                </div>
            </div>
            <div class="card-body">
                <div id="easy-ai-form-content" class="border rounded p-3 bg-light">
                    <!-- Form content will be updated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- RichbotWidget Container -->
    <div class="col-md-6">
        <div id="easy-ai-richbot-widget"></div>
    </div>
</div>

<script>
    // Initialize the RichbotDisplay for form content
    const display = new RichbotDisplay('easy-ai-form-content', {
        displayName: 'easy_ai_form_maker_content',
        pollInterval: 5000, // Poll every 5 seconds
        autoStart: true,
        showControls: false // Hide controls since we don't need them for the form
    });

    // Initialize the RichbotWidget with form controls
    const widget = new RichbotWidget('easy-ai-richbot-widget', {
        wsUrl: `${window.appConfig.wsUrlAlt}/webclient/42`,
        apiToken: appState.apiToken,
        assistantId: 42,
        autoConnect: true,
        showFormControls: true,
        showChatLog: true
    });

    // Handle text updates for the form content
    widget.on('textDelta', (delta) => {
        console.log('Text delta received:', delta);
    });

    // Handle form status updates
    widget.on('textComplete', () => {
        const formStatus = document.getElementById('form-display-status');
        if (formStatus) {
            formStatus.textContent = 'Form Generated';
            formStatus.className = 'badge bg-success text-white';
        }
    });

    // Handle connection status
    widget.on('connected', () => {
        const formStatus = document.getElementById('form-display-status');
        if (formStatus) {
            formStatus.textContent = 'Ready';
            formStatus.className = 'badge bg-light text-dark';
        }
    });

    widget.on('error', (error) => {
        const formStatus = document.getElementById('form-display-status');
        if (formStatus) {
            formStatus.textContent = 'Error';
            formStatus.className = 'badge bg-danger text-white';
        }
        console.error('Widget error:', error);
    });

    // Handle display updates
    display.on('contentUpdated', ({ content }) => {
        console.log('Display content updated:', content);
        const formStatus = document.getElementById('form-display-status');
        if (formStatus && content) {
            formStatus.textContent = 'Updated';
            formStatus.className = 'badge bg-info text-white';
            // Reset back to normal after a brief delay
            setTimeout(() => {
                formStatus.textContent = 'Ready';
                formStatus.className = 'badge bg-light text-dark';
            }, 2000);
        }
    });

    display.on('error', (error) => {
        console.error('Display error:', error);
        const formStatus = document.getElementById('form-display-status');
        if (formStatus) {
            formStatus.textContent = 'Display Error';
            formStatus.className = 'badge bg-warning text-dark';
        }
    });

    // Store references globally
    window.easyAiWidget = widget;
    window.easyAiDisplay = display;
</script>

