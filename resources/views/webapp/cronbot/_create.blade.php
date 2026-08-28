
<div class="container-fluid" id="cronbotBuilderContainer">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="cronbot-title">Cronbot: New Cronbot</h1>
                <div>
                    <button class="btn btn-outline-secondary me-2" onclick="window.location.href='/webapp/cronbot'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                    <button class="btn btn-primary save-cronbot">
                        <i class="fas fa-save"></i> Save Cronbot
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Builder Area -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Cronbot Configuration</h5>
                        <button class="btn btn-sm btn-outline-info" onclick="window.cronbotBuilder.toggleDebugArea(this)">
                            <i class="fas fa-chevron-down"></i> Debug
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Main Configuration Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0">Cronbot Configuration</h6>
                                <button class="btn btn-sm btn-outline-secondary" id="toggleConfigEdit" onclick="toggleConfigEdit()">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                        
                        <!-- Configuration Preview (shown when not editing) -->
                        <div class="col-12" id="configPreview">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Name:</strong> <span id="configNamePreview">New Cronbot</span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Description:</strong> <span id="configDescriptionPreview">No description</span>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <strong>Assistant:</strong> <span id="configAssistantPreview">Not selected</span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Prompt:</strong> <span id="configPromptPreview">No prompt</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuration Form (hidden when not editing) -->
                        <div class="col-12" id="configForm" style="display: none;">
                            <!-- Cronbot Basic Info -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="cronbotName" class="form-label">Cronbot Name</label>
                                    <input type="text" class="form-control" id="cronbotName" placeholder="Enter cronbot name">
                                </div>
                                <div class="col-md-6">
                                    <label for="cronbotDescription" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="cronbotDescription" placeholder="Enter description">
                                </div>
                            </div>

                            <!-- Assistant and Prompt Configuration -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="cronbotAssistantId" class="form-label">Assistant</label>
                                    <select class="form-select" id="cronbotAssistantId">
                                        <option value="">Select an Assistant</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="cronbotPrompt" class="form-label">Prompt</label>
                                    <textarea class="form-control" id="cronbotPrompt" rows="3" placeholder="Enter the prompt for the assistant"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduling Configuration -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0">Scheduling Configuration</h6>
                                <button class="btn btn-sm btn-outline-secondary" id="toggleSchedulingEdit" onclick="toggleSchedulingEdit()">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                        
                        <!-- Scheduling Preview (shown when not editing) -->
                        <div class="col-12" id="schedulingPreview">
                            <div class="card">
                                <div class="card-body">
                                    <div id="schedulePreview" class="text-muted">
                                        <i class="fas fa-info-circle"></i> Configure schedule to see preview
                                    </div>
                                    <div id="cronPreview" class="mt-2" style="display: none;">
                                        <small class="text-muted">
                                            <strong>Cron Expression:</strong> <code id="cronExpressionPreview"></code>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Scheduling Form (hidden when not editing) -->
                        <div class="col-12" id="schedulingForm" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="isRepeating">
                                        <label class="form-check-label" for="isRepeating">
                                            Enable Scheduling
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="isActive" checked>
                                        <label class="form-check-label" for="isActive">
                                            Active (Enable this cronbot)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Options -->
                            <div class="row mb-4" id="scheduleOptions">
                                <div class="col-md-6">
                                    <label class="form-label" id="nextRunLabel">Next Run</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="date" class="form-control" id="nextRunDate">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="time" class="form-control" id="nextRunTime" value="09:00">
                                        </div>
                                    </div>
                                    <div class="form-text" id="nextRunHelp">When the cronbot should start running</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="endAt" class="form-label">End Date (Optional)</label>
                                    <input type="datetime-local" class="form-control" id="endAt">
                                    <div class="form-text">Leave empty to run indefinitely</div>
                                </div>
                            </div>

                            <!-- Intuitive Schedule Builder -->
                            <div class="row mb-4" id="intuitiveScheduleBuilder">
                                <div class="col-12">
                                    <label class="form-label">Schedule Frequency</label>
                                    <div class="card">
                                        <div class="card-body">
                                            <!-- Frequency Type Selection -->
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="btn-group w-100" role="group">
                                                        <input type="radio" class="btn-check" name="frequencyType" id="frequencyOneTime" value="oneTime">
                                                        <label class="btn btn-outline-primary" for="frequencyOneTime">
                                                            <i class="fas fa-calendar-check"></i> One Time
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" name="frequencyType" id="frequencyHourly" value="hourly" checked>
                                                        <label class="btn btn-outline-primary" for="frequencyHourly">
                                                            <i class="fas fa-clock"></i> Hourly
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" name="frequencyType" id="frequencyDaily" value="daily">
                                                        <label class="btn btn-outline-primary" for="frequencyDaily">
                                                            <i class="fas fa-sun"></i> Daily
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" name="frequencyType" id="frequencyWeekly" value="weekly">
                                                        <label class="btn btn-outline-primary" for="frequencyWeekly">
                                                            <i class="fas fa-calendar-week"></i> Weekly
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" name="frequencyType" id="frequencyMonthly" value="monthly">
                                                        <label class="btn btn-outline-primary" for="frequencyMonthly">
                                                            <i class="fas fa-calendar-alt"></i> Monthly
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" name="frequencyType" id="frequencyCustom" value="custom">
                                                        <label class="btn btn-outline-primary" for="frequencyCustom">
                                                            <i class="fas fa-cog"></i> Custom
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- One Time Options -->
                                            <div class="frequency-options" id="oneTimeOptions" style="display: none;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle"></i>
                                                            <strong>One Time Execution:</strong> This cronbot will run once at the specified start date and time, then stop.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Hourly Options -->
                                            <div class="frequency-options" id="hourlyOptions">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Run every</label>
                                                        <select class="form-select" id="hourlyInterval">
                                                            <option value="1">1 hour</option>
                                                            <option value="2">2 hours</option>
                                                            <option value="3">3 hours</option>
                                                            <option value="4">4 hours</option>
                                                            <option value="6">6 hours</option>
                                                            <option value="8">8 hours</option>
                                                            <option value="12">12 hours</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">At minute</label>
                                                        <select class="form-select" id="hourlyMinute">
                                                            <option value="0">0 (start of hour)</option>
                                                            <option value="15">15</option>
                                                            <option value="30">30</option>
                                                            <option value="45">45</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Daily Options -->
                                            <div class="frequency-options" id="dailyOptions" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Run at</label>
                                                        <select class="form-select" id="dailyTime">
                                                            <option value="00:00">Midnight (12:00 AM)</option>
                                                            <option value="06:00">6:00 AM</option>
                                                            <option value="09:00">9:00 AM</option>
                                                            <option value="12:00">Noon (12:00 PM)</option>
                                                            <option value="18:00">6:00 PM</option>
                                                            <option value="21:00">9:00 PM</option>
                                                            <option value="custom">Custom time</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6" id="dailyCustomTime" style="display: none;">
                                                        <label class="form-label">Custom time</label>
                                                        <input type="time" class="form-control" id="dailyCustomTimeInput" value="09:00">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Weekly Options -->
                                            <div class="frequency-options" id="weeklyOptions" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Day of week</label>
                                                        <select class="form-select" id="weeklyDay">
                                                            <option value="1">Monday</option>
                                                            <option value="2">Tuesday</option>
                                                            <option value="3">Wednesday</option>
                                                            <option value="4">Thursday</option>
                                                            <option value="5">Friday</option>
                                                            <option value="6">Saturday</option>
                                                            <option value="0">Sunday</option>
                                                            <option value="1-5">Weekdays (Mon-Fri)</option>
                                                            <option value="0,6">Weekends (Sat-Sun)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Time</label>
                                                        <select class="form-select" id="weeklyTime">
                                                            <option value="09:00">9:00 AM</option>
                                                            <option value="12:00">Noon (12:00 PM)</option>
                                                            <option value="18:00">6:00 PM</option>
                                                            <option value="custom">Custom time</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mt-2" id="weeklyCustomTime" style="display: none;">
                                                    <div class="col-md-6 offset-md-6">
                                                        <input type="time" class="form-control" id="weeklyCustomTimeInput" value="09:00">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Monthly Options -->
                                            <div class="frequency-options" id="monthlyOptions" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Day of month</label>
                                                        <select class="form-select" id="monthlyDay">
                                                            <option value="1">1st</option>
                                                            <option value="15">15th</option>
                                                            <option value="28">28th</option>
                                                            <option value="last">Last day</option>
                                                            <option value="custom">Custom day</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6" id="monthlyCustomDay" style="display: none;">
                                                        <label class="form-label">Custom day</label>
                                                        <input type="number" class="form-control" id="monthlyCustomDayInput" min="1" max="31" value="1">
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Time</label>
                                                        <select class="form-select" id="monthlyTime">
                                                            <option value="09:00">9:00 AM</option>
                                                            <option value="12:00">Noon (12:00 PM)</option>
                                                            <option value="custom">Custom time</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6" id="monthlyCustomTime" style="display: none;">
                                                        <input type="time" class="form-control" id="monthlyCustomTimeInput" value="09:00">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Custom Options -->
                                            <div class="frequency-options" id="customOptions" style="display: none;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <label for="cronExpression" class="form-label">Cron Expression</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="cronExpression" placeholder="* * * * *" value="0 * * * *">
                                                            <button class="btn btn-outline-secondary" type="button" onclick="validateCronExpression()">
                                                                <i class="fas fa-check"></i> Validate
                                                            </button>
                                                        </div>
                                                        <div class="form-text">
                                                            Format: Minute Hour Day Month DayOfWeek (e.g., "0 9 * * 1-5" = Weekdays at 9 AM)
                                                        </div>
                                                        <div id="cronValidation" class="mt-2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flow Container -->
                    <div class="flow-container-wrapper">
                        <div class="flow-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-project-diagram text-primary"></i>
                                    Workflow Builder
                                </h5>
                                <small class="text-muted">Configure your cronbot workflow</small>
                            </div>
                            <!-- Nodes will be rendered here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Area -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card debug-area" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0">Debug Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Cronbot Data</h6>
                            <pre id="cronbotDebug" class="debug-content"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Nodes Data</h6>
                            <pre id="nodesDebug" class="debug-content"></pre>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Node Types</h6>
                            <pre id="nodeTypesDebug" class="debug-content"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>State</h6>
                            <pre id="stateDebug" class="debug-content"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Tool Selection Modal -->
<div class="modal fade" id="tool-selection-modal" tabindex="-1" aria-labelledby="tool-selection-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tool-selection-modal-label">Select Tool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="toolSearchInput" placeholder="Search tools by name, description, or parameters...">
                </div>
                <div class="tool-list" style="max-height: 500px; overflow-y: auto;">
                    <!-- Tools will be populated here dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>


<style>
    .builder-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 1rem;
        min-height: 600px;
    }

    .node-menu-sidebar {
        background: #f8f9fa;
        border-right: 1px solid #dee2e6;
        padding: 1rem;
        height: 100%;
        overflow-y: auto;
    }

    .node-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
    }

    .flow-container-wrapper {
        padding: 1rem;
        overflow-y: auto;
    }

    .flow-container {
        min-height: 600px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
    }

    .node-wrapper {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .node-controls {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .node-controls button {
        padding: 0.25rem;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .conversation-node {
        flex: 1;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .conversation-node .node-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
    }

    .conversation-node .node-body {
        padding: 1rem;
        display: none;
    }

    .conversation-node.expanded .node-body {
        display: block;
    }

    .conversation-node.data { border-left: 4px solid #17a2b8; }
    .conversation-node.action { border-left: 4px solid #20c997; }
    .conversation-node.decision { border-left: 4px solid #6610f2; }

    .node-connector {
        width: 2px;
        height: 2rem;
        background: #dee2e6;
        position: relative;
        margin: 0 auto;
    }

    .node-connector::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #dee2e6;
    }

    .debug-content {
        font-family: monospace;
        font-size: 0.875rem;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        max-height: 300px;
        overflow-y: auto;
        margin: 0;
    }

    @media (max-width: 992px) {
        .builder-layout {
            grid-template-columns: 1fr;
        }

        .node-menu-sidebar {
            border-right: none;
            border-bottom: 1px solid #dee2e6;
        }
    }

    /* Scheduling Section Styles */
    .schedule-preset-btn {
        transition: all 0.2s ease;
        border: 1px solid #dee2e6;
    }

    .schedule-preset-btn:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
        transform: translateY(-1px);
    }

    .schedule-preset-btn.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .cron-builder-card {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
    }

    .cron-builder-card .card-body {
        padding: 1.5rem;
    }

    .schedule-preview-card {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        min-height: 120px;
    }

    .schedule-preview-card .card-body {
        padding: 1rem;
    }

    .cron-validation-success {
        color: #198754;
        font-weight: 500;
    }

    .cron-validation-error {
        color: #dc3545;
        font-weight: 500;
    }

    .cron-validation-warning {
        color: #ffc107;
        font-weight: 500;
    }

    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-group-vertical .btn {
        margin-bottom: 0.5rem;
        text-align: left;
        padding: 0.75rem 1rem;
    }

    .btn-group-vertical .btn:last-child {
        margin-bottom: 0;
    }

    .btn-group-vertical .btn i {
        margin-right: 0.5rem;
        width: 16px;
    }

    /* Responsive adjustments for scheduling */
    @media (max-width: 768px) {
        .cron-builder-card .row > div {
            margin-bottom: 1rem;
        }
        
        .cron-builder-card .row > div:last-child {
            margin-bottom: 0;
        }
    }
</style>

<script>


    // Function to populate assistant dropdown
    async function populateAssistantDropdown() {
        try {
            const response = await fetch('/api/user_assistants', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load assistants');
            }
            
            const result = await response.json();
            const assistants = result.assistants || [];
            
            const assistantSelect = document.getElementById('cronbotAssistantId');
            assistantSelect.innerHTML = '<option value="">Select an Assistant</option>';
            
            assistants.forEach(assistant => {
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = assistant.name;
                assistantSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading assistants:', error);
            toastr.error('Failed to load assistants');
        }
    }


  

    function initializeScheduling() {
        // Set default next run time to current time + 1 hour
        const now = new Date();
        now.setHours(now.getHours() + 1);
        now.setMinutes(0);
        now.setSeconds(0);
        document.getElementById('nextRunDate').value = now.toISOString().slice(0, 10);
        document.getElementById('nextRunTime').value = now.toISOString().slice(11, 16);

        // Event listeners
        document.getElementById('isRepeating').addEventListener('change', function() {
            console.log('isRepeating checkbox changed, checked:', this.checked);
            toggleScheduleOptions();
        });
        document.getElementById('nextRunDate').addEventListener('change', updateSchedulePreview);
        document.getElementById('nextRunTime').addEventListener('change', updateSchedulePreview);
        document.getElementById('endAt').addEventListener('change', updateSchedulePreview);

        // Frequency type radio buttons
        document.querySelectorAll('input[name="frequencyType"]').forEach(radio => {
            radio.addEventListener('change', handleFrequencyTypeChange);
        });

        // Hourly options
        document.getElementById('hourlyInterval').addEventListener('change', updateSchedulePreview);
        document.getElementById('hourlyMinute').addEventListener('change', updateSchedulePreview);

        // Daily options
        document.getElementById('dailyTime').addEventListener('change', handleDailyTimeChange);
        document.getElementById('dailyCustomTimeInput').addEventListener('change', updateSchedulePreview);

        // Weekly options
        document.getElementById('weeklyDay').addEventListener('change', updateSchedulePreview);
        document.getElementById('weeklyTime').addEventListener('change', handleWeeklyTimeChange);
        document.getElementById('weeklyCustomTimeInput').addEventListener('change', updateSchedulePreview);

        // Monthly options
        document.getElementById('monthlyDay').addEventListener('change', handleMonthlyDayChange);
        document.getElementById('monthlyCustomDayInput').addEventListener('change', updateSchedulePreview);
        document.getElementById('monthlyTime').addEventListener('change', handleMonthlyTimeChange);
        document.getElementById('monthlyCustomTimeInput').addEventListener('change', updateSchedulePreview);

        // Custom cron expression
        document.getElementById('cronExpression').addEventListener('input', updateSchedulePreview);

        // Configuration form event listeners
        document.getElementById('cronbotName').addEventListener('input', updateConfigPreview);
        document.getElementById('cronbotDescription').addEventListener('input', updateConfigPreview);
        document.getElementById('cronbotAssistantId').addEventListener('change', updateConfigPreview);
        document.getElementById('cronbotPrompt').addEventListener('input', updateConfigPreview);

        // Initialize
        toggleScheduleOptions();
        updateSchedulePreview();
        updateConfigPreview();
    }

    function toggleScheduleOptions() {
        const isRepeating = document.getElementById('isRepeating').checked;
        const scheduleOptions = document.getElementById('scheduleOptions');
        const intuitiveScheduleBuilder = document.getElementById('intuitiveScheduleBuilder');
        const nextRunLabel = document.getElementById('nextRunLabel');
        const nextRunHelp = document.getElementById('nextRunHelp');

        console.log('toggleScheduleOptions called, isRepeating:', isRepeating);
        console.log('scheduleOptions element:', scheduleOptions);
        console.log('intuitiveScheduleBuilder element:', intuitiveScheduleBuilder);

        if (isRepeating) {
            console.log('Showing scheduling form');
            scheduleOptions.style.display = 'block';
            intuitiveScheduleBuilder.style.display = 'block';
            nextRunLabel.textContent = 'Start Date';
            nextRunHelp.textContent = 'When the cronbot should start running (first execution)';
        } else {
            console.log('Hiding scheduling form');
            scheduleOptions.style.display = 'none';
            intuitiveScheduleBuilder.style.display = 'none';
            nextRunLabel.textContent = 'Next Run';
            nextRunHelp.textContent = 'When the cronbot should run';
        }
        
        // Immediately update the preview when checkbox changes
        updateSchedulePreview();
    }

    function toggleSchedulingEdit() {
        const schedulingPreview = document.getElementById('schedulingPreview');
        const schedulingForm = document.getElementById('schedulingForm');
        const toggleButton = document.getElementById('toggleSchedulingEdit');
        
        if (schedulingForm.style.display === 'none') {
            schedulingPreview.style.display = 'none';
            schedulingForm.style.display = 'block';
            toggleButton.innerHTML = '<i class="fas fa-eye"></i> View';
        } else {
            schedulingPreview.style.display = 'block';
            schedulingForm.style.display = 'none';
            toggleButton.innerHTML = '<i class="fas fa-edit"></i> Edit';
        }
    }

    function toggleConfigEdit() {
        const configPreview = document.getElementById('configPreview');
        const configForm = document.getElementById('configForm');
        const toggleButton = document.getElementById('toggleConfigEdit');
        
        if (configForm.style.display === 'none') {
            configPreview.style.display = 'none';
            configForm.style.display = 'block';
            toggleButton.innerHTML = '<i class="fas fa-eye"></i> View';
        } else {
            configPreview.style.display = 'block';
            configForm.style.display = 'none';
            toggleButton.innerHTML = '<i class="fas fa-edit"></i> Edit';
            updateConfigPreview();
        }
    }

    function updateConfigPreview() {
        const name = document.getElementById('cronbotName').value || 'New Cronbot';
        const description = document.getElementById('cronbotDescription').value || 'No description';
        const assistantSelect = document.getElementById('cronbotAssistantId');
        const assistant = assistantSelect.options[assistantSelect.selectedIndex]?.text || 'Not selected';
        const prompt = document.getElementById('cronbotPrompt').value || 'No prompt';
        
        document.getElementById('configNamePreview').textContent = name;
        document.getElementById('configDescriptionPreview').textContent = description;
        document.getElementById('configAssistantPreview').textContent = assistant;
        document.getElementById('configPromptPreview').textContent = prompt;
    }

    function handleFrequencyTypeChange() {
        const frequencyType = document.querySelector('input[name="frequencyType"]:checked').value;
        
        // Hide all frequency options
        document.querySelectorAll('.frequency-options').forEach(option => {
            option.style.display = 'none';
        });
        
        // Show selected frequency options
        document.getElementById(frequencyType + 'Options').style.display = 'block';
        
        updateSchedulePreview();
    }

    function handleDailyTimeChange() {
        const dailyTime = document.getElementById('dailyTime').value;
        const dailyCustomTime = document.getElementById('dailyCustomTime');
        
        if (dailyTime === 'custom') {
            dailyCustomTime.style.display = 'block';
        } else {
            dailyCustomTime.style.display = 'none';
        }
        
        updateSchedulePreview();
    }

    function handleWeeklyTimeChange() {
        const weeklyTime = document.getElementById('weeklyTime').value;
        const weeklyCustomTime = document.getElementById('weeklyCustomTime');
        
        if (weeklyTime === 'custom') {
            weeklyCustomTime.style.display = 'block';
        } else {
            weeklyCustomTime.style.display = 'none';
        }
        
        updateSchedulePreview();
    }

    function handleMonthlyDayChange() {
        const monthlyDay = document.getElementById('monthlyDay').value;
        const monthlyCustomDay = document.getElementById('monthlyCustomDay');
        
        if (monthlyDay === 'custom') {
            monthlyCustomDay.style.display = 'block';
        } else {
            monthlyCustomDay.style.display = 'none';
        }
        
        updateSchedulePreview();
    }

    function handleMonthlyTimeChange() {
        const monthlyTime = document.getElementById('monthlyTime').value;
        const monthlyCustomTime = document.getElementById('monthlyCustomTime');
        
        if (monthlyTime === 'custom') {
            monthlyCustomTime.style.display = 'block';
        } else {
            monthlyCustomTime.style.display = 'none';
        }
        
        updateSchedulePreview();
    }

    function generateCronExpression() {
        const frequencyType = document.querySelector('input[name="frequencyType"]:checked').value;
        
        switch (frequencyType) {
            case 'oneTime':
                return null; // No cron expression for one-time execution
            case 'hourly':
                return generateHourlyCron();
            case 'daily':
                return generateDailyCron();
            case 'weekly':
                return generateWeeklyCron();
            case 'monthly':
                return generateMonthlyCron();
            case 'custom':
                return document.getElementById('cronExpression').value;
            default:
                return '0 * * * *';
        }
    }

    function generateHourlyCron() {
        const hourlyInterval = document.getElementById('hourlyInterval').value;
        const hourlyMinute = document.getElementById('hourlyMinute').value;
        
        if (hourlyInterval === '1') {
            return `${hourlyMinute} * * * *`;
        } else {
            return `${hourlyMinute} */${hourlyInterval} * * *`;
        }
    }

    function generateDailyCron() {
        const dailyTimeSelect = document.getElementById('dailyTime').value;
        let dailyTime = dailyTimeSelect;
        
        if (dailyTimeSelect === 'custom') {
            dailyTime = document.getElementById('dailyCustomTimeInput').value;
        }
        
        const [dailyHour, dailyMinute] = dailyTime.split(':');
        return `${dailyMinute} ${dailyHour} * * *`;
    }

    function generateWeeklyCron() {
        const weeklyDay = document.getElementById('weeklyDay').value;
        const weeklyTimeSelect = document.getElementById('weeklyTime').value;
        let weeklyTime = weeklyTimeSelect;
        
        if (weeklyTimeSelect === 'custom') {
            weeklyTime = document.getElementById('weeklyCustomTimeInput').value;
        }
        
        const [weeklyHour, weeklyMinute] = weeklyTime.split(':');
        return `${weeklyMinute} ${weeklyHour} * * ${weeklyDay}`;
    }

    function generateMonthlyCron() {
        const monthlyDay = document.getElementById('monthlyDay').value;
        const monthlyTimeSelect = document.getElementById('monthlyTime').value;
        let monthlyTime = monthlyTimeSelect;
        
        if (monthlyTimeSelect === 'custom') {
            monthlyTime = document.getElementById('monthlyCustomTimeInput').value;
        }
        
        const [monthlyHour, monthlyMinute] = monthlyTime.split(':');
        
        if (monthlyDay === 'last') {
            // For last day of month, we'll use a simple approach
            return `${monthlyMinute} ${monthlyHour} 28 * *`;
        } else if (monthlyDay === 'custom') {
            const monthlyCustomDay = document.getElementById('monthlyCustomDayInput').value;
            return `${monthlyMinute} ${monthlyHour} ${monthlyCustomDay} * *`;
        } else {
            return `${monthlyMinute} ${monthlyHour} ${monthlyDay} * *`;
        }
    }

    function updateSchedulePreview() {
        const isRepeating = document.getElementById('isRepeating').checked;
        const nextRunDate = document.getElementById('nextRunDate').value;
        const nextRunTime = document.getElementById('nextRunTime').value;
        const endAt = document.getElementById('endAt').value;
        const previewDiv = document.getElementById('schedulePreview');
        const cronPreviewDiv = document.getElementById('cronPreview');
        const cronExpressionPreview = document.getElementById('cronExpressionPreview');
        
        console.log('updateSchedulePreview called, isRepeating:', isRepeating);
        
        if (!isRepeating) {
            console.log('Scheduling disabled, showing "Never"');
            previewDiv.innerHTML = '<div class="text-muted"><i class="fas fa-info-circle"></i> <strong>Next run:</strong> Never</div>';
            cronPreviewDiv.style.display = 'none';
            return;
        }
        
        const frequencyType = document.querySelector('input[name="frequencyType"]:checked').value;
        const cronExpression = generateCronExpression();
        
        let preview = '';
        
        // Generate human-readable description
        const humanDesc = getHumanReadableSchedule(frequencyType);
        
        if (nextRunDate && nextRunTime) {
            const nextRunDateTime = new Date(`${nextRunDate}T${nextRunTime}`);
            const nextRunStr = nextRunDateTime.toLocaleString();
            preview = `<div><strong>Next run:</strong> ${nextRunStr}</div>`;
            preview += `<div><strong>Schedule:</strong> ${humanDesc}</div>`;
        } else {
            preview = `<div class="text-warning"><i class="fas fa-exclamation-triangle"></i> Start date and time not set</div>`;
        }
        
        if (endAt) {
            const endDate = new Date(endAt);
            const endStr = endDate.toLocaleString();
            preview += `<div><strong>Ends:</strong> ${endStr}</div>`;
        }
        
        previewDiv.innerHTML = preview;
        
        // Show cron expression in preview only for repeating schedules
        if (frequencyType !== 'oneTime' && cronExpression) {
            cronExpressionPreview.textContent = cronExpression;
            cronPreviewDiv.style.display = 'block';
        } else {
            cronPreviewDiv.style.display = 'none';
        }
    }

    function getHumanReadableSchedule(frequencyType) {
        switch (frequencyType) {
            case 'oneTime':
                return 'One-time execution';
                
            case 'hourly':
                const hourlyInterval = document.getElementById('hourlyInterval').value;
                const hourlyMinute = document.getElementById('hourlyMinute').value;
                const hourlyMinuteText = hourlyMinute === '0' ? 'at the start of the hour' : `at minute ${hourlyMinute}`;
                return hourlyInterval === '1' ? `Every hour ${hourlyMinuteText}` : `Every ${hourlyInterval} hours ${hourlyMinuteText}`;
                
            case 'daily':
                const dailyTimeSelect = document.getElementById('dailyTime').value;
                let dailyTime = dailyTimeSelect;
                if (dailyTimeSelect === 'custom') {
                    dailyTime = document.getElementById('dailyCustomTimeInput').value;
                }
                const [dailyHour, dailyMinute] = dailyTime.split(':');
                const dailyTimeStr = formatTime(dailyHour, dailyMinute);
                return `Daily at ${dailyTimeStr}`;
                
            case 'weekly':
                const weeklyDay = document.getElementById('weeklyDay').value;
                const weeklyTimeSelect = document.getElementById('weeklyTime').value;
                let weeklyTime = weeklyTimeSelect;
                if (weeklyTimeSelect === 'custom') {
                    weeklyTime = document.getElementById('weeklyCustomTimeInput').value;
                }
                const [weeklyHour, weeklyMinute] = weeklyTime.split(':');
                const weeklyTimeStr = formatTime(weeklyHour, weeklyMinute);
                const weeklyDayStr = getDayName(weeklyDay);
                return `Weekly on ${weeklyDayStr} at ${weeklyTimeStr}`;
                
            case 'monthly':
                const monthlyDay = document.getElementById('monthlyDay').value;
                const monthlyTimeSelect = document.getElementById('monthlyTime').value;
                let monthlyTime = monthlyTimeSelect;
                if (monthlyTimeSelect === 'custom') {
                    monthlyTime = document.getElementById('monthlyCustomTimeInput').value;
                }
                const [monthlyHour, monthlyMinute] = monthlyTime.split(':');
                const monthlyTimeStr = formatTime(monthlyHour, monthlyMinute);
                const monthlyDayStr = getMonthlyDayName(monthlyDay);
                return `Monthly on ${monthlyDayStr} at ${monthlyTimeStr}`;
                
            case 'custom':
                const customCronExpression = document.getElementById('cronExpression').value;
                return getHumanReadableCron(customCronExpression) || 'Custom schedule';
                
            default:
                return 'Unknown schedule';
        }
    }

    function formatTime(hour, minute) {
        const hourNum = parseInt(hour);
        const minuteNum = parseInt(minute);
        
        if (hourNum === 0) {
            return minuteNum === 0 ? 'midnight' : `12:${minute.toString().padStart(2, '0')} AM`;
        } else if (hourNum === 12) {
            return minuteNum === 0 ? 'noon' : `12:${minute.toString().padStart(2, '0')} PM`;
        } else if (hourNum > 12) {
            return `${hourNum - 12}:${minute.toString().padStart(2, '0')} PM`;
        } else {
            return `${hourNum}:${minute.toString().padStart(2, '0')} AM`;
        }
    }

    function getDayName(day) {
        const days = {
            '0': 'Sunday',
            '1': 'Monday',
            '2': 'Tuesday',
            '3': 'Wednesday',
            '4': 'Thursday',
            '5': 'Friday',
            '6': 'Saturday',
            '1-5': 'weekdays (Monday-Friday)',
            '0,6': 'weekends (Saturday-Sunday)'
        };
        return days[day] || day;
    }

    function getMonthlyDayName(day) {
        if (day === 'last') {
            return 'the last day';
        } else if (day === 'custom') {
            const customDay = document.getElementById('monthlyCustomDayInput').value;
            return `the ${customDay}${getOrdinalSuffix(customDay)}`;
        } else {
            return `the ${day}${getOrdinalSuffix(day)}`;
        }
    }

    function getOrdinalSuffix(day) {
        const dayNum = parseInt(day);
        if (dayNum >= 11 && dayNum <= 13) return 'th';
        switch (dayNum % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    }

    function getHumanReadableCron(cronExpression) {
        const parts = cronExpression.split(' ');
        if (parts.length !== 5) return null;
        
        const [minute, hour, day, month, dayOfWeek] = parts;
        
        let description = '';
        
        // Minute
        if (minute === '*') {
            description += 'Every minute';
        } else if (minute === '0') {
            description += 'At the start of the hour';
        } else {
            description += `At minute ${minute}`;
        }
        
        // Hour
        if (hour === '*') {
            description += ' of every hour';
        } else if (hour === '0') {
            description += ' at midnight';
        } else if (hour === '12') {
            description += ' at noon';
        } else {
            description += ` at ${hour}:00`;
        }
        
        // Day
        if (day !== '*') {
            description += ` on day ${day}`;
        }
        
        // Month
        if (month !== '*') {
            const months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
            description += ` in ${months[parseInt(month)]}`;
        }
        
        // Day of week
        if (dayOfWeek !== '*') {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            if (dayOfWeek.includes(',')) {
                const dayNums = dayOfWeek.split(',');
                const dayNames = dayNums.map(d => days[parseInt(d)]).join(', ');
                description += ` on ${dayNames}`;
            } else if (dayOfWeek === '1-5') {
                description += ' on weekdays';
            } else if (dayOfWeek === '0,6') {
                description += ' on weekends';
            } else {
                description += ` on ${days[parseInt(dayOfWeek)]}`;
            }
        }
        
        return description;
    }

    function validateCronExpression() {
        const cronExpression = document.getElementById('cronExpression').value;
        const validationDiv = document.getElementById('cronValidation');
        
        if (!cronExpression) {
            validationDiv.innerHTML = '<div class="text-danger"><i class="fas fa-times"></i> Cron expression is required</div>';
            return false;
        }

        // Basic cron validation regex
        const cronRegex = /^(\*|([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])|\*\/([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])) (\*|([0-9]|1[0-9]|2[0-3])|\*\/([0-9]|1[0-9]|2[0-3])) (\*|([1-9]|1[0-9]|2[0-9]|3[0-1])|\*\/([1-9]|1[0-9]|2[0-9]|3[0-1])) (\*|([1-9]|1[0-2])|\*\/([1-9]|1[0-2])) (\*|([0-6])|\*\/([0-6]))$/;
        
        if (cronRegex.test(cronExpression)) {
            validationDiv.innerHTML = '<div class="text-success"><i class="fas fa-check"></i> Valid cron expression</div>';
            updateSchedulePreview();
            return true;
        } else {
            validationDiv.innerHTML = '<div class="text-danger"><i class="fas fa-times"></i> Invalid cron expression format</div>';
            return false;
        }
    }

    // Override the saveCronbot function to include scheduling data
    const originalSaveCronbot = window.cronbotBuilder?.saveCronbot;
    if (originalSaveCronbot) {
        window.cronbotBuilder.saveCronbot = async function() {
            try {
                const frequencyType = document.querySelector('input[name="frequencyType"]:checked').value;
                const isSchedulingEnabled = document.getElementById('isRepeating').checked;
                
                // Get scheduling metadata
                const schedulingMetadata = {
                    frequency_type: frequencyType,
                    is_scheduling_enabled: isSchedulingEnabled,
                    human_readable_schedule: getHumanReadableSchedule(frequencyType)
                };
                
                const cronbotData = {
                    name: document.getElementById('cronbotName').value,
                    description: document.getElementById('cronbotDescription').value,
                    assistant_id: document.getElementById('cronbotAssistantId').value,
                    prompt: document.getElementById('cronbotPrompt').value,
                    nodes: this.nodes.map(node => node.toJSON()),
                    // Add scheduling data
                    is_repeating: isSchedulingEnabled && frequencyType !== 'oneTime',
                    is_active: document.getElementById('isActive').checked,
                    schedule: (isSchedulingEnabled && frequencyType !== 'oneTime') ? generateCronExpression() : null,
                    next_run_at: document.getElementById('nextRunDate').value + 'T' + document.getElementById('nextRunTime').value,
                    end_at: document.getElementById('endAt').value || null,
                    // Add scheduling metadata
                    scheduling_metadata: schedulingMetadata
                };

                console.log('[Builder] saveCronbot: cronbotData', JSON.stringify(cronbotData));

                const response = await fetch('/api/scheduled-cronbots', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(cronbotData)
                });

                if (!response.ok) {
                    throw new Error('Failed to save cronbot');
                }

                const result = await response.json();
                
                if (typeof toastr !== 'undefined') {
                    toastr.success('Cronbot saved successfully');
                } else {
                    alert('Cronbot saved successfully');
                }

                this.showCronbotList();
            } catch (error) {
                console.error('Error saving cronbot:', error);
                toastr.error('Failed to save cronbot');
            }
        };
    }

        // Initialize the cronbot builder when the page loads
        // Scheduling functionality
    
        initializeScheduling();
        // Populate assistant dropdown
        populateAssistantDropdown();
        
        // Initialize the cronbot builder
        window.cronbotBuilder = new CronbotBuilder('cronbotBuilderContainer');
    
</script>

