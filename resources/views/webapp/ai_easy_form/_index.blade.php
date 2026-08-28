    <div class="container mt-5">
        <h1 class="mb-4">Job Application Form</h1>
        
        <div id="aiControls" class="mb-4">
            <button id="startAiButton" class="btn btn-primary">🎥 Start AI Assistant</button>
            <button id="stopAiButton" class="btn btn-danger" style="display: none;">Stop AI Assistant</button>
        </div>

        <!-- Assistant Response Area -->
        <div id="assistantResponse" class="mb-4 p-3 bg-light rounded" style="display: none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-robot me-2"></i>
                <span>AI Assistant</span>
            </div>
            <div id="responseText" class="chat-content"></div>
        </div>
        
        <form id="jobApplicationForm123" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="fullName" class="form-label">Full Name:</label>
                <input type="text" class="form-control" id="fullName" name="fullName" required>
                <div class="invalid-feedback">
                    Please enter your full name.
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">
                    Please enter a valid email address.
                </div>
            </div>

            <div class="mb-3">
                <label for="position" class="form-label">Position Applied For:</label>
                <select class="form-select" id="position" name="position" required>
                    <option value="">Select a position</option>
                    <option value="developer">Software Developer</option>
                    <option value="designer">UI/UX Designer</option>
                    <option value="manager">Project Manager</option>
                </select>
                <div class="invalid-feedback">
                    Please select a position.
                </div>
            </div>

            <div class="mb-3">
                <label for="experience" class="form-label">Years of Experience:</label>
                <input type="number" class="form-control" id="experience" name="experience" min="0" required>
                <div class="invalid-feedback">
                    Please enter your years of experience.
                </div>
            </div>

            <div class="mb-3">
                <label for="coverLetter" class="form-label">Cover Letter:</label>
                <textarea class="form-control" id="coverLetter" name="coverLetter" rows="5" required></textarea>
                <div class="invalid-feedback">
                    Please enter your cover letter.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit Application</button>
        </form>
    </div>

    <div class="container mt-5 mb-5">
        <h2 class="mb-4">AI Form Assistant Process Flow</h2>
        
        <div class="accordion" id="processFlow">
            <!-- Form State Management -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#formState">
                        1. Form State Management
                    </button>
                </h2>
                <div id="formState" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="card mb-3">
                            <div class="card-header">Initial Form Capture</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">On initialization, captures complete form structure</li>
                                    <li class="list-group-item">Stores element IDs, types, names, and current values</li>
                                    <li class="list-group-item">For select elements, captures all options and their states</li>
                                    <li class="list-group-item">Sends to server via <code>/api/ai-easy-form/store-form</code></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">State Updates</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">Real-time updates when form fields change</li>
                                    <li class="list-group-item">Server syncs via <code>/api/ai-easy-form/update-element</code></li>
                                    <li class="list-group-item">Redis storage for fast access and persistence</li>
                                    <li class="list-group-item">Maintains form state across page reloads</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Initialization Process -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#initProcess">
                        2. Initialization Process
                    </button>
                </h2>
                <div id="initProcess" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">User clicks "Start AI Assistant" button</li>
                            <li class="list-group-item">System requests camera and microphone permissions</li>
                            <li class="list-group-item">WebSocket connection established to: <code>wss://{domain}:{port}/app/{token}/{assistant_id}</code></li>
                            <li class="list-group-item">Sends initial <code>start_chat</code> message with assistant ID</li>
                            <li class="list-group-item">Initializes audio context and media handlers</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Audio Flow: Client to Server -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#audioToServer">
                        3. Audio Flow: Client to Server
                    </button>
                </h2>
                <div id="audioToServer" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div class="card mb-3">
                            <div class="card-header">Client-side Audio Processing</div>
                            <div class="card-body">
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Captures audio at 16kHz sample rate (mono)</li>
                                    <li class="list-group-item">Processes in 16384-byte chunks</li>
                                    <li class="list-group-item">Resamples to 24kHz using linear interpolation</li>
                                    <li class="list-group-item">Converts to base64 format</li>
                                    <li class="list-group-item">Sends every 100ms in WebSocket message</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Server Response Types -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#serverResponse">
                        4. Server Response Types
                    </button>
                </h2>
                <div id="serverResponse" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Message Type</th>
                                        <th>Purpose</th>
                                        <th>Format</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>assistant_text_delta</code></td>
                                        <td>Incremental text responses</td>
                                        <td>Text chunks for real-time display</td>
                                    </tr>
                                    <tr>
                                        <td><code>assistant_audio_delta</code></td>
                                        <td>Audio responses</td>
                                        <td>Base64 encoded 24kHz mono PCM</td>
                                    </tr>
                                    <tr>
                                        <td><code>form_fill</code></td>
                                        <td>Form field updates</td>
                                        <td>Field name and value pairs</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Data Access -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#formAccess">
                        5. Form Data Access
                    </button>
                </h2>
                <div id="formAccess" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div class="card mb-3">
                            <div class="card-header">Available Endpoints</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <code>GET /api/ai-easy-form/get-element-value/{formId}/{elementId}</code>
                                        <small class="d-block text-muted">Retrieve single element value</small>
                                    </li>
                                    <li class="list-group-item">
                                        <code>GET /api/ai-easy-form/get-form-values/{formId}</code>
                                        <small class="d-block text-muted">Retrieve all form values</small>
                                    </li>
                                    <li class="list-group-item">
                                        <code>POST /api/ai-easy-form/update-element</code>
                                        <small class="d-block text-muted">Update single element value</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Handling -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#errorHandling">
                        6. Error Handling & Recovery
                    </button>
                </h2>
                <div id="errorHandling" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <ul class="list-group">
                            <li class="list-group-item list-group-item-warning">Connection loss triggers automatic cleanup</li>
                            <li class="list-group-item">All media streams are properly closed</li>
                            <li class="list-group-item">Audio queues are cleared</li>
                            <li class="list-group-item">Form state persists in Redis storage</li>
                            <li class="list-group-item list-group-item-info">User can restart the process without losing form data</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const aiForm = new AiEasyForm('jobApplicationForm123', 41);
        
        // Form validation
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>


