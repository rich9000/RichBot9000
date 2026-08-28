<div class="container my-4">
    <h1 class="text-center">Scheduled Cronbots</h1>
    <div class="d-flex justify-content-between mb-3">
        <h3>List of Scheduled Cronbots</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cronbotModal" onclick="addCronbot()">+ Add Cronbot</button>
    </div>
    <div id="cronbot-list">
        <p>Loading...</p>
    </div>
</div>
<!-- Modal -->
<!-- Modal -->
<div class="modal fade" id="cronbotModal" tabindex="-1" aria-labelledby="cronbotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cronbotForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="cronbotModalLabel">Create/Edit Cronbot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cronbotId" name="id" value="">

                    <!-- Type Selection -->
                    <div class="mb-3">
                        <label for="cronbotType" class="form-label">Type</label>
                        <select id="cronbotType" name="type" class="form-select" required onchange="toggleTypeFields()">
                            <option value="assistant">Assistant</option>
                            <option value="conversation_path">Conversation Path</option>
                        </select>
                    </div>

                    <!-- Assistant Selection -->
                    <div class="mb-3" id="assistantField">
                        <label for="cronbotAssistantId" class="form-label">Select Assistant</label>
                        <select id="cronbotAssistantId" name="assistant_id" class="form-select">
                            <option value="">Choose an Assistant</option>
                        </select>
                    </div>

                    <!-- Conversation Path Selection -->
                    <div class="mb-3" id="conversationPathField" style="display: none;">
                        <label for="cronbotConversationPathId" class="form-label">Select Conversation Path</label>
                        <select id="cronbotConversationPathId" name="conversation_path_id" class="form-select">
                            <option value="">Choose a Conversation Path</option>
                        </select>
                    </div>

                    <!-- Active Checkbox -->
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="isActive" name="is_active" checked>
                        <label for="isActive" class="form-check-label">Active</label>
                    </div>

                    <!-- Prompt -->
                    <div class="mb-3">
                        <label for="prompt" class="form-label">Prompt</label>
                        <textarea id="prompt" name="prompt" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Repeating Checkbox -->
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="isRepeating" name="is_repeating" onchange="toggleRepeatFields()">
                        <label for="isRepeating" class="form-check-label">Repeating Task</label>
                    </div>

                    <!-- Repeat Interval -->
                    <div class="mb-3" id="repeatIntervalGroup" style="display: none;">
                        <label for="repeatInterval" class="form-label">Repeat Interval</label>
                        <select id="repeatInterval" name="repeat_interval" class="form-select">
                            <option value="hourly">Hourly</option>
                            <option value="twice_daily">Twice a Day</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <!-- Start Time -->
                    <div class="mb-3">
                        <label for="startTime" class="form-label">Start Time</label>
                        <input type="datetime-local" id="startTime" name="start_time" class="form-control" required>
                    </div>

                    <!-- End Time -->
                    <div class="mb-3">
                        <label for="endTime" class="form-label">End Time</label>
                        <input type="datetime-local" id="endTime" name="end_time" class="form-control">
                    </div>

                    <!-- Tool Selection -->
                    <div class="mb-3">
                        <label for="failToolId" class="form-label">Fail Tool</label>
                        <select id="failToolId" name="fail_tool_id" class="form-select">
                            <option value="">None</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="successToolId" class="form-label">Success Tool</label>
                        <select id="successToolId" name="success_tool_id" class="form-select">
                            <option value="">None</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pauseToolId" class="form-label">Pause Tool</label>
                        <select id="pauseToolId" name="pause_tool_id" class="form-select">
                            <option value="">None</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    const apiEndpoint = '/api/scheduled-cronbots';
    const bearerToken = appState.apiToken;

    console.log('=== CRONBOT SCRIPT INITIALIZATION ===');
    console.log('appState:', appState);
    console.log('appState.data:', appState.data);
    console.log('appState.data.assistants:', appState.data.assistants);
    console.log('appState.data.tools:', appState.data.tools);
    console.log('bearerToken:', bearerToken);

    // Debug function to manually populate dropdowns
    window.debugPopulateDropdowns = function() {
        console.log('=== MANUAL DROPDOWN POPULATION DEBUG ===');
        populateCronbotAssistantDropdown();
        populateToolDropdowns();
        populateConversationPathsDropdown();
    };

    // Load cronbots and render in the table
    async function loadCronbots() {
        const cronbotList = document.getElementById('cronbot-list');
        cronbotList.innerHTML = '<p>Loading...</p>';

        try {
            const response = await fetch(apiEndpoint, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${bearerToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            const data = await response.json();
            cronbotList.innerHTML = `
          <table class="table table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Assistant</th>
                <th>Type</th>
                <th>Status</th>
                <th>Active</th>
                <th>Prompt</th>
                <th>Schedule</th>
                <th>Next Run</th>
                <th>Last Run</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.map((cronbot, index) => `
                <tr>
                  <td>${index + 1}</td>
                  <td>${getCronbotName(cronbot)}</td>
                  <td><span class="badge bg-info">${cronbot.type || 'assistant'}</span></td>
                  <td>${cronbot.status === 'enabled' ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-secondary">Disabled</span>'}</td>
                  <td>${cronbot.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</td>
                  <td>${cronbot.prompt}</td>
                  <td><code>${cronbot.schedule || 'N/A'}</code></td>
                  <td>${formatDateTime(cronbot.next_run_at)}</td>
                  <td>${formatDateTime(cronbot.last_run_at) || 'Never'}</td>
                  <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#cronbotModal" onclick="editCronbot(${cronbot.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCronbot(${cronbot.id})">Delete</button>
                    <button class="btn btn-sm btn-primary" onclick="triggerCronbot(${cronbot.id})">Run Now</button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
        } catch (error) {
            cronbotList.innerHTML = `<p class="text-danger">Failed to load cronbots: ${error.message}</p>`;
        }
    }
    function editCronbot(cronbotId) {
        console.log('=== editCronbot START ===');
        console.log('Editing cronbot:', cronbotId); // Debug
        const modalTitle = document.getElementById('cronbotModalLabel');
        const cronbotForm = document.getElementById('cronbotForm');

        console.log('About to populate dropdowns...');
        // Load dropdowns first
        Promise.all([
            populateCronbotAssistantDropdown(),
            populateToolDropdowns(),
            populateConversationPathsDropdown()
        ]).then(() => {
            console.log('Dropdowns populated successfully, now fetching cronbot data...');
            // Then fetch and populate cronbot data
            fetch(`${apiEndpoint}/${cronbotId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${bearerToken}`,
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(cronbot => {
                console.log('Loaded cronbot data:', cronbot); // Debug
                modalTitle.textContent = 'Edit Cronbot';
                document.getElementById('cronbotId').value = cronbot.id;
                document.getElementById('cronbotType').value = cronbot.type || 'assistant';
                document.getElementById('cronbotAssistantId').value = cronbot.assistant_id || '';
                document.getElementById('cronbotConversationPathId').value = cronbot.conversation_path_id || '';
                document.getElementById('isActive').checked = cronbot.is_active !== false;
                document.getElementById('prompt').value = cronbot.prompt;
                document.getElementById('isRepeating').checked = cronbot.is_repeating;
                toggleRepeatFields();
                toggleTypeFields(); // Set the correct field visibility
                if (cronbot.is_repeating) {
                    document.getElementById('repeatInterval').value = cronbot.repeat_interval || '';
                }
                document.getElementById('startTime').value = formatDatetimeForInput(cronbot.next_run_at);
                document.getElementById('endTime').value = formatDatetimeForInput(cronbot.end_at);
                document.getElementById('failToolId').value = cronbot.fail_tool_id || '';
                document.getElementById('successToolId').value = cronbot.success_tool_id || '';
                document.getElementById('pauseToolId').value = cronbot.pause_tool_id || '';
                console.log('=== editCronbot END ===');
            })
            .catch(error => {
                console.error('Failed to fetch cronbot details:', error);
                alert('Error loading cronbot data');
            });
        }).catch(error => {
            console.error('Error populating dropdowns:', error);
        });
    }

    function addCronbot() {
        console.log('=== addCronbot START ===');
        const modalTitle = document.getElementById('cronbotModalLabel');
        const cronbotForm = document.getElementById('cronbotForm');
        
        console.log('appState.data.assistants', appState.data.assistants);
        console.log('appState.data.tools', appState.data.tools);
        console.log('assistantpoplulated');
        
        console.log('About to populate dropdowns...');
        Promise.all([
            populateCronbotAssistantDropdown(),
            populateToolDropdowns(),
            populateConversationPathsDropdown()
        ]).then(() => {
            console.log('Dropdowns populated successfully');
            modalTitle.textContent = 'Create Cronbot';
            cronbotForm.reset();
            document.getElementById('cronbotId').value = '';
            toggleRepeatFields();
            toggleTypeFields(); // Set initial field visibility
            console.log('=== addCronbot END ===');
        }).catch(error => {
            console.error('Error populating dropdowns:', error);
        });
    }

    document.getElementById('cronbotForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(event.target);
        const id = formData.get('id');
        const data = {
            prompt: formData.get('prompt'),
            type: formData.get('type'),
            assistant_id: formData.get('type') === 'assistant' ? formData.get('assistant_id') : null,
            conversation_path_id: formData.get('type') === 'conversation_path' ? formData.get('conversation_path_id') : null,
            is_active: document.getElementById('isActive').checked,
            is_repeating: document.getElementById('isRepeating').checked,
            repeat_interval: formData.get('repeat_interval'),
            start_time: formatDatetimeForServer(formData.get('start_time')),
            end_time: formatDatetimeForServer(formData.get('end_time')),
            fail_tool_id: formData.get('fail_tool_id') || null,
            success_tool_id: formData.get('success_tool_id') || null,
            pause_tool_id: formData.get('pause_tool_id') || null
        };

        const method = id ? 'PUT' : 'POST';
        const url = id ? `${apiEndpoint}/${id}` : apiEndpoint;

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${bearerToken}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save cronbot.');
            }

            await loadCronbots(); // Refresh the list
            const modal = bootstrap.Modal.getInstance(document.getElementById('cronbotModal'));
            modal.hide();
        } catch (error) {
            console.error('Error saving cronbot:', error);
            alert(error.message);
        }
    });
// Update the formatDatetimeForServer function
function formatDatetimeForServer(datetime) {
    if (!datetime) return null;
    return new Date(datetime).toISOString();
}

    function formatDatetimeForInput(datetime) {
        if (!datetime) return '';
        const date = new Date(datetime);
        return date.toISOString().slice(0, 16); // Format as 'YYYY-MM-DDTHH:MM'
    }

    // Delete a cronbot
    async function deleteCronbot(cronbotId) {
        if (!confirm('Are you sure you want to delete this cronbot?')) return;

        try {
            const response = await fetch(`${apiEndpoint}/${cronbotId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${bearerToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                loadCronbots(); // Refresh the list
            } else {
                throw new Error('Failed to delete cronbot.');
            }
        } catch (error) {
            console.error('Error deleting cronbot:', error);
        }
    }

    // Populate assistant dropdown
    function populateCronbotAssistantDropdown() {
        console.log('=== populateCronbotAssistantDropdown START ===');
        console.log('appState.data.assistants', appState.data.assistants);
        console.log('appState.data.assistants length:', appState.data.assistants ? appState.data.assistants.length : 'undefined');

        const assistantSelect = document.getElementById('cronbotAssistantId');
        console.log('assistantSelect element found:', assistantSelect);
        
        if (!assistantSelect) {
            console.error('ERROR: cronbotAssistantId element not found!');
            return;
        }

        assistantSelect.innerHTML = '<option value="">Choose an Assistant</option>'; // Reset options
        console.log('Reset dropdown options');

        if (!appState.data.assistants || !Array.isArray(appState.data.assistants)) {
            console.error('ERROR: appState.data.assistants is not an array or is undefined');
            console.log('appState.data:', appState.data);
            return;
        }

        let cronAssistantsCount = 0;
        appState.data.assistants.forEach((assistant, index) => {
            console.log(`Assistant ${index}:`, assistant);
            console.log(`Assistant ${index} type:`, assistant.type);
            console.log(`Assistant ${index} type === 'cron':`, assistant.type === 'cron');
            
            if (assistant.type === 'cron' || assistant.type === 'Cron' || assistant.type.toLowerCase() === 'assistant') {
                cronAssistantsCount++;
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = assistant.name;
                assistantSelect.appendChild(option);
                console.log(`Added cron assistant: ${assistant.name} (ID: ${assistant.id})`);
            }
        });

        console.log(`Total assistants processed: ${appState.data.assistants.length}`);
        console.log(`Cron assistants found: ${cronAssistantsCount}`);
        console.log('Final dropdown options count:', assistantSelect.options.length);
        console.log('=== populateCronbotAssistantDropdown END ===');
    }
    // Toggle visibility of repeat interval fields based on the repeating checkbox
    function toggleRepeatFields() {
        const isRepeating = document.getElementById('isRepeating').checked;
        const repeatIntervalGroup = document.getElementById('repeatIntervalGroup');
        repeatIntervalGroup.style.display = isRepeating ? 'block' : 'none';
    }

    // Populate tool dropdowns
    function populateToolDropdowns() {
        console.log('=== populateToolDropdowns START ===');
        console.log('appState.data.tools:', appState.data.tools);
        console.log('appState.data.tools length:', appState.data.tools ? appState.data.tools.length : 'undefined');

        const failToolSelect = document.getElementById('failToolId');
        const successToolSelect = document.getElementById('successToolId');
        const pauseToolSelect = document.getElementById('pauseToolId');

        console.log('Tool select elements found:', {
            failToolSelect: failToolSelect,
            successToolSelect: successToolSelect,
            pauseToolSelect: pauseToolSelect
        });

        // Clear existing options
        [failToolSelect, successToolSelect, pauseToolSelect].forEach(select => {
            select.innerHTML = '<option value="">None</option>';
        });
        console.log('Cleared tool dropdowns');

        if (!appState.data.tools || !Array.isArray(appState.data.tools)) {
            console.error('ERROR: appState.data.tools is not an array or is undefined');
            return;
        }

        let toolsAdded = 0;
        appState.data.tools.forEach((tool, index) => {
            console.log(`Tool ${index}:`, tool);
            const option = document.createElement('option');
            option.value = tool.id;
            option.textContent = tool.name;

            failToolSelect.appendChild(option.cloneNode(true));
            successToolSelect.appendChild(option.cloneNode(true));
            pauseToolSelect.appendChild(option.cloneNode(true));
            toolsAdded++;
        });

        console.log(`Tools added to dropdowns: ${toolsAdded}`);
        console.log('Final tool dropdown options count:', {
            failTool: failToolSelect.options.length,
            successTool: successToolSelect.options.length,
            pauseTool: pauseToolSelect.options.length
        });
        console.log('=== populateToolDropdowns END ===');
    }

    function formatDatetimeForInput(datetime) {
        if (!datetime) return '';
        const date = new Date(datetime);
        return date.toISOString().slice(0, 16); // Format as 'YYYY-MM-DDTHH:MM'
    }

    // Add helper functions
    function getCronbotName(cronbot) {
        if (cronbot.type === 'assistant') {
            const assistant = appState.data.assistants.find(a => a.id === cronbot.assistant_id);
            return assistant ? assistant.name : 'Unknown';
        } else if (cronbot.type === 'conversation_path') {
            const conversationPath = appState.data.conversationPaths.find(p => p.id === cronbot.conversation_path_id);
            return conversationPath ? conversationPath.name : 'Unknown';
        }
        return 'Unknown';
    }

    function formatDateTime(datetime) {
        if (!datetime) return '';
        return new Date(datetime).toLocaleString();
    }

    // Add trigger function
    async function triggerCronbot(cronbotId) {
        try {
            const response = await fetch(`${apiEndpoint}/${cronbotId}/trigger`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${bearerToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to trigger cronbot');
            
            const result = await response.json();
            alert(`Cronbot triggered: ${result.message}`);
            loadCronbots(); // Refresh the list
        } catch (error) {
            console.error('Error triggering cronbot:', error);
            alert('Failed to trigger cronbot');
        }
    }

    // Toggle fields based on type selection
    function toggleTypeFields() {
        const type = document.getElementById('cronbotType').value;
        const assistantField = document.getElementById('assistantField');
        const conversationPathField = document.getElementById('conversationPathField');
        
        if (type === 'assistant') {
            assistantField.style.display = 'block';
            conversationPathField.style.display = 'none';
            document.getElementById('cronbotAssistantId').required = true;
            document.getElementById('cronbotConversationPathId').required = false;
        } else if (type === 'conversation_path') {
            assistantField.style.display = 'none';
            conversationPathField.style.display = 'block';
            document.getElementById('cronbotAssistantId').required = false;
            document.getElementById('cronbotConversationPathId').required = true;
        }
    }

    // Populate conversation paths dropdown
    async function populateConversationPathsDropdown() {
        console.log('=== populateConversationPathsDropdown START ===');
        
        const conversationPathSelect = document.getElementById('cronbotConversationPathId');
        if (!conversationPathSelect) {
            console.error('ERROR: cronbotConversationPathId element not found!');
            return;
        }

        conversationPathSelect.innerHTML = '<option value="">Choose a Conversation Path</option>';

        try {
            const response = await fetch('/api/conversation-paths', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${bearerToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const conversationPaths = await response.json();
            console.log('Conversation paths loaded:', conversationPaths);

            // Store conversation paths in appState for use in getCronbotName
            if (!appState.data) appState.data = {};
            appState.data.conversationPaths = conversationPaths;

            conversationPaths.forEach(path => {
                const option = document.createElement('option');
                option.value = path.id;
                option.textContent = path.name || `Path ${path.id}`;
                conversationPathSelect.appendChild(option);
            });

            console.log(`Conversation paths added: ${conversationPaths.length}`);
        } catch (error) {
            console.error('Error loading conversation paths:', error);
            conversationPathSelect.innerHTML = '<option value="">Error loading conversation paths</option>';
        }
        
        console.log('=== populateConversationPathsDropdown END ===');
    }

    // Load cronbots on page load
    loadCronbots();
    
</script>
