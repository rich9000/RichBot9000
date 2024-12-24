
<style>
    /* Custom styles if needed */
    #calendar {
        max-width: 900px;
        margin: 40px auto;
    }
</style>

<div class="container mt-5">
    <h1>Appointment Calendar</h1>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary add-appointment-btn" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
            <i class="fas fa-plus"></i> Add Appointment
        </button>
    </div>
    <div id='calendar'></div>
</div>
<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addAppointmentForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAppointmentModalLabel">Add New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="appointmentTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="appointmentTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="appointmentDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="appointmentDescription" name="description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="appointmentStart" class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="appointmentStart" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="appointmentEnd" class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="appointmentEnd" name="end_time" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="appointmentAllDay" name="all_day">
                        <label class="form-check-label" for="appointmentAllDay">All Day</label>
                    </div>
                    <div id="addAppointmentErrors" class="alert alert-danger d-none"></div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Appointment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Appointment Details Modal -->
<div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-labelledby="appointmentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <!-- Appointment Title -->
                <h5 class="modal-title" id="appointmentDetailsModalLabel">Appointment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Modal Body with Appointment Details -->
            <div class="modal-body">
                <p><strong>Title:</strong> <span id="detailTitle"></span></p>
                <p><strong>Description:</strong> <span id="detailDescription"></span></p>
                <p><strong>Start Time:</strong> <span id="detailStart"></span></p>
                <p><strong>End Time:</strong> <span id="detailEnd"></span></p>
                <p><strong>All Day:</strong> <span id="detailAllDay"></span></p>
            </div>
            <!-- Modal Footer with Delete Button -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="deleteAppointmentBtn">Delete Appointment</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>


        var calendarEl = document.getElementById('calendar');

        // Initialize FullCalendar
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Options: dayGridMonth, timeGridWeek, timeGridDay
            selectable: false, // Disable date selection
            editable: true,
            headerToolbar: {
                left: 'prev,next today addAppointmentBtn',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            customButtons: {
                addAppointmentBtn: {
                    text: '<i class="fas fa-plus"></i>',
                    click: function() {
                        // Open the Add Appointment Modal
                        var addModal = new bootstrap.Modal(document.getElementById('addAppointmentModal'));
                        addModal.show();
                    }
                }
            },
            // Define a custom event source to include headers
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch('/api/appointments', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + window.appState.apiToken,
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 401) {
                                throw new Error('Unauthorized. Please log in.');
                            }
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Fetched Appointments:', data); // Debugging

                        // Map the API data to FullCalendar's expected event format
                        var events = data.map(appointment => ({
                            id: appointment.id,
                            title: appointment.title,
                            start: appointment.start_time,
                            end: appointment.end_time,
                            allDay: appointment.all_day,
                            extendedProps: {
                                description: appointment.description,
                                user_id: appointment.user_id
                            }
                        }));

                        console.log('Mapped Events for FullCalendar:', events); // Debugging

                        successCallback(events);
                    })
                    .catch(error => {
                        console.error('Error fetching events:', error);
                        failureCallback(error);
                    });
            },
            eventClick: function(info) {
                // Prevent the default behavior
                info.jsEvent.preventDefault();

                var appointmentId = info.event.id;

                // Fetch appointment details (optional if all details are already fetched)
                fetch('/api/appointments/' + appointmentId, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + window.appState.apiToken,
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 401) {
                                throw new Error('Unauthorized. Please log in.');
                            }
                            throw new Error('Failed to fetch appointment details.');
                        }
                        return response.json();
                    })
                    .then(appointment => {
                        // Populate the Appointment Details Modal
                        document.getElementById('detailTitle').innerText = appointment.title;
                        document.getElementById('detailDescription').innerText = appointment.description || 'N/A';
                        document.getElementById('detailStart').innerText = new Date(appointment.start_time).toLocaleString();
                        document.getElementById('detailEnd').innerText = new Date(appointment.end_time).toLocaleString();
                        document.getElementById('detailAllDay').innerText = appointment.all_day ? 'Yes' : 'No';

                        // Store the appointment ID in the delete button for reference
                        document.getElementById('deleteAppointmentBtn').dataset.appointmentId = appointment.id;

                        // Show the Appointment Details Modal
                        var detailsModal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
                        detailsModal.show();
                    })
                    .catch(error => {
                        alert(error.message);
                        console.error('Error:', error);
                    });
            },
            eventDrop: function(info) {
                // Handle event drag & drop (update appointment)
                var appointmentId = info.event.id;
                var updatedData = {
                    start_time: info.event.start.toISOString(),
                    end_time: info.event.end ? info.event.end.toISOString() : null,
                    all_day: info.event.allDay
                };

                console.log('Updating Appointment:', appointmentId, updatedData); // Debugging

                // Update appointment via API using Fetch
                fetch('/api/appointments/' + appointmentId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + window.appState.apiToken,
                    },
                    body: JSON.stringify(updatedData)
                })
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 401) {
                                throw new Error('Unauthorized. Please log in.');
                            }
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Appointment updated:', data); // Debugging
                        alert('Appointment updated successfully!');
                    })
                    .catch(error => {
                        if (error.errors) {
                            alert('Failed to update appointment: ' + JSON.stringify(error.errors));
                        } else {
                            alert('Failed to update appointment.');
                        }
                        console.error('Error:', error);
                        info.revert(); // Revert the event's position
                    });
            },
            eventResize: function(info) {
                // Handle event resize (update appointment duration)
                var appointmentId = info.event.id;
                var updatedData = {
                    end_time: info.event.end ? info.event.end.toISOString() : null
                };

                console.log('Updating Appointment Duration:', appointmentId, updatedData); // Debugging

                // Update appointment via API using Fetch
                fetch('/api/appointments/' + appointmentId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + window.appState.apiToken,
                    },
                    body: JSON.stringify(updatedData)
                })
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 401) {
                                throw new Error('Unauthorized. Please log in.');
                            }
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Appointment duration updated:', data); // Debugging
                        alert('Appointment duration updated successfully!');
                    })
                    .catch(error => {
                        if (error.errors) {
                            alert('Failed to update appointment duration: ' + JSON.stringify(error.errors));
                        } else {
                            alert('Failed to update appointment duration.');
                        }
                        console.error('Error:', error);
                        info.revert(); // Revert the event's duration
                    });
            },
            eventDidMount: function(info) {
                // Add Bootstrap 5 tooltips for appointment descriptions
                if (info.event.extendedProps.description) {
                    var tooltip = new bootstrap.Tooltip(info.el, {
                        title: info.event.extendedProps.description,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            },
            // Enable dateClick to drill down to day view
            dateClick: function(info) {
                // Navigate to day view when a day is clicked
                calendar.changeView('timeGridDay', info.dateStr);
            }
        });

        calendar.render();

        // Handle Add Appointment Form Submission
        document.getElementById('addAppointmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            var title = document.getElementById('appointmentTitle').value.trim();
            var description = document.getElementById('appointmentDescription').value.trim();
            var startInput = document.getElementById('appointmentStart').value;
            var endInput = document.getElementById('appointmentEnd').value;
            var allDay = document.getElementById('appointmentAllDay').checked;

            // Validate date inputs
            if (new Date(startInput) > new Date(endInput)) {
                var errorDiv = document.getElementById('addAppointmentErrors');
                errorDiv.innerHTML = 'End Time must be after Start Time.';
                errorDiv.classList.remove('d-none');
                return;
            }

            var appointmentData = {
                title: title,
                description: description,
                start_time: new Date(startInput).toISOString(),
                end_time: new Date(endInput).toISOString(),
                all_day: allDay
            };

            console.log('Submitting Add Appointment Form:', appointmentData); // Debugging

            // Create appointment via API using Fetch
            fetch('/api/appointments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + window.appState.apiToken,
                },
                body: JSON.stringify(appointmentData)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw err; });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Created Appointment:', data); // Debugging
                    // Close the modal
                    var addModal = bootstrap.Modal.getInstance(document.getElementById('addAppointmentModal'));
                    addModal.hide();
                    // Refresh the calendar events
                    calendar.refetchEvents();
                    alert('Appointment created successfully!');
                    // Reset the form
                    document.getElementById('addAppointmentForm').reset();
                    document.getElementById('addAppointmentErrors').classList.add('d-none');
                    document.getElementById('addAppointmentErrors').innerHTML = '';
                })
                .catch(error => {
                    if (error.errors) {
                        // Display validation errors
                        var errorMessages = Object.values(error.errors).flat().join('<br>');
                        var errorDiv = document.getElementById('addAppointmentErrors');
                        errorDiv.innerHTML = errorMessages;
                        errorDiv.classList.remove('d-none');
                    } else if (error.message) {
                        var errorDiv = document.getElementById('addAppointmentErrors');
                        errorDiv.innerHTML = error.message;
                        errorDiv.classList.remove('d-none');
                    } else {
                        alert('Failed to create appointment.');
                    }
                    console.error('Error:', error);
                });
        });

        // Handle Delete Appointment Button Click
        document.getElementById('deleteAppointmentBtn').addEventListener('click', function() {
            var appointmentId = this.dataset.appointmentId;
            var confirmDelete = confirm('Are you sure you want to delete this appointment?');
            if (confirmDelete) {
                console.log('Deleting Appointment ID:', appointmentId); // Debugging

                // Delete appointment via API using Fetch
                fetch('/api/appointments/' + appointmentId, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + window.appState.apiToken,
                    }
                })
                    .then(response => {
                        if (response.status === 204) {
                            console.log('Deleted Appointment ID:', appointmentId); // Debugging
                            // Close the modal
                            var detailsModal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal'));
                            detailsModal.hide();
                            // Refresh the calendar events
                            calendar.refetchEvents();
                            alert('Appointment deleted successfully!');
                        } else if (response.status === 401) {
                            throw new Error('Unauthorized. Please log in.');
                        } else {
                            return response.json().then(err => { throw err; });
                        }
                    })
                    .catch(error => {
                        if (error.errors) {
                            alert('Failed to delete appointment: ' + JSON.stringify(error.errors));
                        } else if (error.message) {
                            alert('Failed to delete appointment: ' + error.message);
                        } else {
                            alert('Failed to delete appointment.');
                        }
                        console.error('Error:', error);
                    });
            }
        });

</script>
