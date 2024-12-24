<div class="container">
    <h1>Remote Richbots Dashboard</h1>
    <table class="table" id="richbotsTable">
        <thead>
        <tr>
            <th>Name</th>
            <th>Remote ID</th>
            <th>Status</th>
            <th>Last Seen</th>
            <th>Latest Image</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach(\App\Models\RemoteRichbot::all() as $richbot)
            <tr data-richbot-id="{{ $richbot->id }}">
                <td>{{ $richbot->name }}</td>
                <td>{{ $richbot->remote_richbot_id }}</td>
                <td>{{ $richbot->status }}</td>
                <td>{{ $richbot->last_seen }}</td>
                <td>
                        <?php $image = $richbot->media()->where('type', 'image')->latest()->first(); ?>
                    @if($image)
                        <img style="max-width: 200px;" src="/storage/{{ $image->file_path }}" alt="Latest Image"/>
                    @endif
                </td>
                <td>
                    <button class="btn btn-primary view-details-btn" data-richbot-id="{{ $richbot->id }}">View</button>
                </td>
            </tr>
            <!-- Expandable Details Row -->
            <tr class="details-row" id="details-{{ $richbot->id }}" style="display: none;">
                <td colspan="6">
                    <!-- Placeholder for AJAX-loaded content -->
                    <div id="details-content-{{ $richbot->id }}"></div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>



    document.addEventListener('click', function(event) {
        if (event.target && event.target.id.startsWith('add-trigger-btn-')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            showAddTriggerForm(richbotId);
        }
    });

    function showAddTriggerForm(richbotId) {
        // Implement a modal or form for adding triggers
        // For this example, we'll use simple prompts

        const type = prompt('Enter trigger type (e.g., image_notify, image_alarm, audio_note):');
        const promptText = prompt('Enter prompt (e.g., "Notify me if a dog is sitting on the couch"):');
        const action = prompt('Enter action (e.g., notify, alarm, email):');

        if (type && promptText && action) {
            const data = {
                type: type,
                prompt: promptText,
                action: action,
            };

            fetch(`/api/remote-richbot/${richbotId}/triggers`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
                .then(response => response.json())
                .then(trigger => {
                    alert('Trigger added successfully!');
                    // Reload triggers
                    const detailsContent = document.getElementById('details-content-' + richbotId);
                    loadRichbotDetails(richbotId, detailsContent);
                })
                .catch(err => {
                    console.error('Failed to add trigger:', err);
                    alert('Failed to add trigger.');
                });
        }
    }

    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('edit-trigger-btn')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            const triggerId = event.target.getAttribute('data-trigger-id');
            showEditTriggerForm(richbotId, triggerId);
        }
    });

    function showEditTriggerForm(richbotId, triggerId) {
        // Fetch the existing trigger data
        fetch(`/api/remote-richbot/${richbotId}/triggers/${triggerId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(trigger => {
                const type = prompt('Edit trigger type:', trigger.type);
                const promptText = prompt('Edit prompt:', trigger.prompt);
                const action = prompt('Edit action:', trigger.action);

                if (type && promptText && action) {
                    const data = {
                        type: type,
                        prompt: promptText,
                        action: action,
                    };

                    fetch(`/api/remote-richbot/${richbotId}/triggers/${triggerId}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': 'Bearer ' + appState.apiToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data),
                    })
                        .then(response => response.json())
                        .then(updatedTrigger => {
                            alert('Trigger updated successfully!');
                            // Reload triggers
                            const detailsContent = document.getElementById('details-content-' + richbotId);
                            loadRichbotDetails(richbotId, detailsContent);
                        })
                        .catch(err => {
                            console.error('Failed to update trigger:', err);
                            alert('Failed to update trigger.');
                        });
                }
            })
            .catch(err => {
                console.error('Failed to fetch trigger:', err);
            });
    }
    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('delete-trigger-btn')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            const triggerId = event.target.getAttribute('data-trigger-id');
            deleteTrigger(richbotId, triggerId);
        }
    });

    function deleteTrigger(richbotId, triggerId) {
        if (confirm('Are you sure you want to delete this trigger?')) {
            fetch(`/api/remote-richbot/${richbotId}/triggers/${triggerId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                },
            })
                .then(() => {
                    alert('Trigger deleted successfully!');
                    // Reload triggers
                    const detailsContent = document.getElementById('details-content-' + richbotId);
                    loadRichbotDetails(richbotId, detailsContent);
                })
                .catch(err => {
                    console.error('Failed to delete trigger:', err);
                    alert('Failed to delete trigger.');
                });
        }
    }



    // Event listener for the "View" buttons

    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('view-details-btn')) {
            const richbotId = event.target.getAttribute('data-richbot-id');
            toggleDetailsRow(richbotId);
        }
    });


    // Function to toggle the details row
    function toggleDetailsRow(richbotId) {
        const detailsRow = document.getElementById('details-' + richbotId);
        const detailsContent = document.getElementById('details-content-' + richbotId);

        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = '';
            // Load details via AJAX if not already loaded
            if (!detailsContent.innerHTML.trim()) {
                loadRichbotDetails(richbotId, detailsContent);
            }
        } else {
            detailsRow.style.display = 'none';
        }
    }

    function loadRichbotDetails(richbotId, container) {
        fetch(`/api/remote-richbot/${richbotId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {

                console.log('data',data);
                // Build the HTML content
                const content = buildDetailsContent(data);

                console.log('content',content);

                container.innerHTML = content;
                // Add event listener for the command form submission
                const commandForm = container.querySelector(`#command-form-${richbotId}`);
                commandForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    sendCommand(richbotId, commandForm);
                });
            })
            .catch(err => {
                console.error('Failed to load richbot details:', err);
            });
    }
    function buildDetailsContent(data) {
        // Build the tabs
        let content = `
    <ul class="nav nav-tabs" id="richbotTab-${data.id}" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="media-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#media-${data.id}" type="button" role="tab">Media</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="commands-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#commands-${data.id}" type="button" role="tab">Commands</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="events-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#events-${data.id}" type="button" role="tab">Event Logs</button>
        </li>
<li class="nav-item" role="presentation">
            <button class="nav-link" id="triggers-tab-${data.id}" data-bs-toggle="tab" data-bs-target="#triggers-${data.id}" type="button" role="tab">Triggers</button>
        </li>
    </ul>
    <div class="tab-content" id="richbotTabContent-${data.id}">
        <!-- Media Tab -->
        <div class="tab-pane fade show active" id="media-${data.id}" role="tabpanel">
            <div class="media-gallery row">
                ${data.media.map(media => {
            if (media.type === 'image') {
                return `<div class="col-md-3 mb-3"><img src="/storage/${media.file_path}" alt="Image" class="img-thumbnail"></div>`;
            } else if (media.type === 'audio') {
                return `<div class="col-md-3 mb-3">
                            <audio controls>
                                <source src="/storage/${media.file_path}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>`;
            }
            return '';
        }).join('')}
            </div>
        </div>
        <!-- Commands Tab -->
        <div class="tab-pane fade" id="commands-${data.id}" role="tabpanel">
            <h3>Send Command</h3>
            <form id="command-form-${data.id}">
                <div class="form-group">
                    <label for="command-${data.id}">Command</label>
                    <select name="command" id="command-${data.id}" class="form-control command-select" required>
                        <option value="" disabled selected>Select a command</option>
                        <option value="take_picture">Take Picture</option>
                        <option value="move">Move</option>
                        <option value="send_data">Send Data</option>
                        <option value="speak_text">Speak Text</option>
                        <option value="play_url">Play URL</option>
                    </select>
                </div>
                <div class="form-group parameters-group" id="parameters-group-${data.id}">
                    <!-- Parameters will be dynamically loaded here based on the selected command -->
                </div>
                <button type="submit" class="btn btn-success mt-2">Send Command</button>
            </form>
            <h3 class="mt-4">Command History</h3>
            <ul class="list-group">
                ${data.commands.map(command => `
                    <li class="list-group-item">
                        ${command.created_at} - <strong>${command.command}</strong>: ${JSON.stringify(command.parameters)}
                    </li>
                `).join('')}
            </ul>
        </div>
        <!-- Event Logs Tab -->
        <div class="tab-pane fade" id="events-${data.id}" role="tabpanel">
            <h3>Event Logs</h3>
            <ul class="list-group">
                ${data.events.map(event => `
                    <li class="list-group-item">
                        ${event.created_at} - ${event.event_type}: ${JSON.stringify(event.details)}
                    </li>
                `).join('')}
            </ul>
        </div>
      <!-- Triggers Tab -->
<div class="tab-pane fade" id="triggers-${data.id}" role="tabpanel">
            <h3>Media Triggers</h3>
            <button class="btn btn-primary mb-2" data-richbot-id="${data.id}" id="add-trigger-btn-${data.id}">Add Trigger</button>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Prompt</th>
                        <th>Action</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="trigger-list-${data.id}">
                    ${data.media_triggers.map(trigger => `
                        <tr data-trigger-id="${trigger.id}">
                            <td>${trigger.type}</td>
                            <td>${trigger.prompt}</td>
                            <td>${trigger.action}</td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-trigger-btn" data-richbot-id="${data.id}" data-trigger-id="${trigger.id}">Edit</button>
                                <button class="btn btn-sm btn-danger delete-trigger-btn" data-richbot-id="${data.id}" data-trigger-id="${trigger.id}">Delete</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    </div>
    `;

        return content;
    }

    // Event listener for the dynamic form changes based on selected command
    // Event listener for the dynamic form changes based on selected command
    document.addEventListener('change', function(event) {
        if (event.target && event.target.classList.contains('command-select')) {
            const richbotId = event.target.id.split('-')[1];
            const selectedCommand = event.target.value;
            const parametersGroup = document.getElementById('parameters-group-' + richbotId);

            // Clear the parameters group
            parametersGroup.innerHTML = '';

            // Add dynamic fields based on the selected command
            if (selectedCommand === 'take_picture') {
                parametersGroup.innerHTML = `
                <label>Resolution</label>
                <select name="parameters[resolution]" class="form-control">
                    <option value="1080p">1080p</option>
                    <option value="720p">720p</option>
                </select>
            `;
            } else if (selectedCommand === 'move') {
                parametersGroup.innerHTML = `
                <label>Direction</label>
                <select name="parameters[direction]" class="form-control">
                    <option value="forward">Forward</option>
                    <option value="backward">Backward</option>
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                </select>
                <label>Speed</label>
                <input type="number" name="parameters[speed]" class="form-control" placeholder="Enter speed (1-10)">
            `;
            } else if (selectedCommand === 'send_data') {
                parametersGroup.innerHTML = `
                <label>Data</label>
                <input type="text" name="parameters[data]" class="form-control" placeholder="Enter the data">
            `;
            } else if (selectedCommand === 'speak_text') {
                parametersGroup.innerHTML = `
                <label>Text to Speak</label>
                <input type="text" name="parameters[text]" class="form-control" placeholder="Enter the text to speak">
            `;
            } else if (selectedCommand === 'play_url') {
                parametersGroup.innerHTML = `
                <label>URL to Play</label>
                <input type="url" name="parameters[url]" class="form-control" placeholder="Enter the URL to play">
            `;
            }
        }
    });

    function sendCommand(richbotId, form) {
        const command = form.querySelector(`[name="command"]`).value;
        const parametersElements = form.querySelectorAll(`[name^="parameters"]`);
        let parameters = {};

        // Collect all the parameter inputs into the 'parameters' object
        parametersElements.forEach(element => {
            const nameMatch = element.name.match(/\[([^\]]+)\]/);
            if (nameMatch) {
                const name = nameMatch[1];
                parameters[name] = element.value;
            }
        });

        const data = {
            command: command,
            parameters: parameters,
        };

        fetch(`/api/remote-richbot/${richbotId}/send-command`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => response.json())
            .then(result => {
                alert('Command sent successfully!');
                // Optionally, update the command history
                // Reload the richbot details to refresh the command history
                const detailsContent = document.getElementById('details-content-' + richbotId);
                loadRichbotDetails(richbotId, detailsContent);
            })
            .catch(err => {
                console.error('Failed to send command:', err);
                alert('Failed to send command.');
            });
    }
</script>
