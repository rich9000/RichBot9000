<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Contacts</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContactModal">
            <i class="fas fa-plus"></i> Add Contact
        </button>
    </div>
    <div class="card-body">
        <table id="contactsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Allowed</th>
                    <th>Opt-in Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addContactForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-control" id="type" name="type">
                            <option value="contact">Contact</option>
                            <option value="lead">Lead</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="allowedToContact" name="allowed_to_contact" checked>
                        <label class="form-check-label" for="allowedToContact">Allowed to Contact</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveContactBtn">Save Contact</button>
            </div>
        </div>
    </div>
</div>

<script>
    const contactsTable = $('#contactsTable').DataTable({
        ajax: {
            url: '/api/contacts',
            headers: apiHeaders(),
            dataSrc: 'data'
        },
        columns: [
            { 
                data: 'contact_groups',
                render: function(data) {
                    return data && data.length > 0 ? data[0].name : 'N/A';
                }
            },
            { data: 'email' },
            { data: 'phone', defaultContent: '' },
            { 
                data: 'contact_groups',
                render: function(data) {
                    return data && data.length > 0 ? data[0].type : 'contact';
                }
            },
            { 
                data: 'contact_groups',
                render: function(data) {
                    return data && data.length > 0 ? 
                        (data[0].allowed_to_contact ? 
                            '<span class="badge bg-success">Yes</span>' : 
                            '<span class="badge bg-danger">No</span>') : 
                        '<span class="badge bg-secondary">N/A</span>';
                }
            },
            { 
                data: 'opt_in_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : 'Not opted in';
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    if (!data) return '';
                    let buttons = `
                        <button class="btn btn-sm btn-info" onclick="editContact(${data})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteContact(${data}, '${row.contact_groups[0]?.name || 'Contact'}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    
                    // Add opt-in button if not opted in
                    if (!row.opt_in_at) {
                        buttons += `
                            <button class="btn btn-sm btn-success" onclick="startOptIn(${data})">
                                <i class="fas fa-check"></i> Start Opt-In
                            </button>
                        `;
                    }
                    
                    return buttons;
                }
            }
        ],
        processing: true,
        language: {
            emptyTable: 'No contacts found',
            processing: 'Loading contacts...'
        },
        pageLength: 10,
        responsive: true
    });

    function editContact(id) {
        fetch(`/api/contacts/${id}`, {
            headers: apiHeaders()
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(contact => {
            const form = $('#addContactForm')[0];
            form.name.value = contact.contact_groups[0]?.name || '';
            form.email.value = contact.email;
            form.phone.value = contact.phone || '';
            form.type.value = contact.contact_groups[0]?.type || 'contact';
            form.allowed_to_contact.checked = contact.contact_groups[0]?.allowed_to_contact ?? true;
            
            $('#saveContactBtn')
                .data('mode', 'edit')
                .data('id', id);
            
            $('#addContactModal').modal('show');
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading contact details', 'danger');
        });
    }

    function deleteContact(id, name) {
        if (confirm(`Are you sure you want to remove "${name}" from your contacts? This action can't be undone.`)) {
            fetch(`/api/contacts/${id}`, {
                method: 'DELETE',
                headers: apiHeaders()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                contactsTable.ajax.reload();
                showAlert('Contact removed from your list', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error removing contact', 'danger');
            });
        }
    }

    function startOptIn(contactId) {
        fetch(`/api/contacts/${contactId}/start-opt-in`, {
            method: 'POST',
            headers: apiHeaders()
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.conversation_id) {
                // Start polling for conversation status
                pollConversationStatus(data.conversation_id, contactId);
                showAlert('Opt-in process started', 'info');
            } else {
                showAlert('Error starting opt-in process', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error starting opt-in process', 'danger');
        });
    }

    function stopOptIn(contactId) {
        if (confirm('Are you sure you want to opt-out this contact?')) {
            fetch(`/api/contacts/${contactId}/opt-out`, {
                method: 'POST',
                headers: apiHeaders()
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                contactsTable.ajax.reload();
                showAlert('Contact has been opted out', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error opting out contact', 'danger');
            });
        }
    }

    function pollConversationStatus(conversationId, contactId) {
        const pollInterval = setInterval(() => {
            fetch(`/api/conversations/${conversationId}`, {
                headers: apiHeaders()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    contactsTable.ajax.reload();
                    showAlert('Contact has been opted in successfully', 'success');
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    showAlert('Opt-in process failed', 'danger');
                }
            })
            .catch(error => {
                console.error('Error polling conversation status:', error);
                clearInterval(pollInterval);
                showAlert('Error checking opt-in status', 'danger');
            });
        }, 5000); // Poll every 5 seconds
    }

    $('#saveContactBtn').click(function() {
        const mode = $(this).data('mode') || 'create';
        const id = $(this).data('id');
        const formData = new FormData($('#addContactForm')[0]);
        
        const data = Object.fromEntries(formData);
        data.allowed_to_contact = $('#allowedToContact').is(':checked');
        
        const url = mode === 'edit' ? `/api/contacts/${id}` : '/api/contacts';
        const method = mode === 'edit' ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            headers: {
                ...apiHeaders(), 
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            $('#addContactModal').modal('hide');
            contactsTable.ajax.reload();
            showAlert(`Contact ${mode === 'edit' ? 'updated' : 'added'} successfully`, 'success');
            
            // Reset form and button state
            $('#addContactForm')[0].reset();
            $(this).removeData('mode').removeData('id');
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(`Error ${mode === 'edit' ? 'updating' : 'adding'} contact`, 'danger');
        });
    });

    // Reset form when "Add Contact" button is clicked
    $('[data-bs-target="#addContactModal"]').click(function() {
        $('#addContactForm')[0].reset();
        $('#saveContactBtn')
            .removeData('mode')
            .removeData('id');
        
        // Set default values
        $('input[name="allowed_to_contact"]').prop('checked', true);
        $('select[name="type"]').val('contact');
    });
</script>