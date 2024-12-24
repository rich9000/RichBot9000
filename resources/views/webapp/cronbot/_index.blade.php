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

                    <!-- Assistant Selection -->
                    <div class="mb-3">
                        <label for="assistantId" class="form-label">Select Assistant</label>
                        <select id="assistantId" name="assistant_id" class="form-select" required>
                            <option value="">Choose an Assistant</option>
                        </select>
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
                <th>Prompt</th>
                <th>Interval</th>
                <th>Schedule</th>
                <th>Next Run</th>
                <th>Last Run</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.map((cronbot, index) => `
                <tr>
                  <td>${index + 1}</td>
                  <td>${getAssistantName(cronbot.assistant_id)}</td>
                  <td>${cronbot.prompt}</td>
                  <td>${cronbot.is_repeating ? cronbot.repeat_interval : 'One-time'}</td>
                  <td><code>${cronbot.schedule || 'N/A'}</code></td>
                  <td>${formatDateTime(cronbot.next_run_at)}</td>
                  <td>${formatDateTime(cronbot.last_run_at) || 'Never'}</td>
                  <td>${cronbot.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</td>
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
        console.log('Editing cronbot:', cronbotId); // Debug
        const modalTitle = document.getElementById('cronbotModalLabel');
        const cronbotForm = document.getElementById('cronbotForm');

        // Load dropdowns first
        Promise.all([
            populateCronbotAssistantDropdown(),
            populateToolDropdowns()
        ]).then(() => {
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
                document.getElementById('assistantId').value = cronbot.assistant_id || '';
                document.getElementById('prompt').value = cronbot.prompt;
                document.getElementById('isRepeating').checked = cronbot.is_repeating;
                toggleRepeatFields();
                if (cronbot.is_repeating) {
                    document.getElementById('repeatInterval').value = cronbot.repeat_interval || '';
                }
                document.getElementById('startTime').value = formatDatetimeForInput(cronbot.next_run_at);
                document.getElementById('endTime').value = formatDatetimeForInput(cronbot.end_at);
                document.getElementById('failToolId').value = cronbot.fail_tool_id || '';
                document.getElementById('successToolId').value = cronbot.success_tool_id || '';
                document.getElementById('pauseToolId').value = cronbot.pause_tool_id || '';
            })
            .catch(error => {
                console.error('Failed to fetch cronbot details:', error);
                alert('Error loading cronbot data');
            });
        });
    }

    function addCronbot() {
        const modalTitle = document.getElementById('cronbotModalLabel');
        const cronbotForm = document.getElementById('cronbotForm');
        
        Promise.all([
            populateCronbotAssistantDropdown(),
            populateToolDropdowns()
        ]).then(() => {
            modalTitle.textContent = 'Create Cronbot';
            cronbotForm.reset();
            document.getElementById('cronbotId').value = '';
            toggleRepeatFields();
        });
    }

    document.getElementById('cronbotForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(event.target);
        const id = formData.get('id');
        const data = {
            prompt: formData.get('prompt'),
            assistant_id: formData.get('assistant_id'),
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
        const assistantSelect = document.getElementById('assistantId');
        assistantSelect.innerHTML = '<option value="">Choose an Assistant</option>'; // Reset options

        appState.data.assistants.forEach(assistant => {
            if (assistant.type === 'cron') {
                const option = document.createElement('option');
                option.value = assistant.id;
                option.textContent = assistant.name;
                assistantSelect.appendChild(option); 
            }
        });
    }
    // Toggle visibility of repeat interval fields based on the repeating checkbox
    function toggleRepeatFields() {
        const isRepeating = document.getElementById('isRepeating').checked;
        const repeatIntervalGroup = document.getElementById('repeatIntervalGroup');
        repeatIntervalGroup.style.display = isRepeating ? 'block' : 'none';
    }

    // Populate tool dropdowns
    function populateToolDropdowns() {
        const failToolSelect = document.getElementById('failToolId');
        const successToolSelect = document.getElementById('successToolId');
        const pauseToolSelect = document.getElementById('pauseToolId');

        // Clear existing options
        [failToolSelect, successToolSelect, pauseToolSelect].forEach(select => {
            select.innerHTML = '<option value="">None</option>';
        });

        appState.data.tools.forEach(tool => {
            const option = document.createElement('option');
            option.value = tool.id;
            option.textContent = tool.name;

            failToolSelect.appendChild(option.cloneNode(true));
            successToolSelect.appendChild(option.cloneNode(true));
            pauseToolSelect.appendChild(option.cloneNode(true));
        });
    }

    function formatDatetimeForInput(datetime) {
        if (!datetime) return '';
        const date = new Date(datetime);
        return date.toISOString().slice(0, 16); // Format as 'YYYY-MM-DDTHH:MM'
    }

    // Add helper functions
    function getAssistantName(assistantId) {
        const assistant = appState.data.assistants.find(a => a.id === assistantId);
        return assistant ? assistant.name : 'Unknown';
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

    // Load cronbots on page load
    loadCronbots();
    
</script>
