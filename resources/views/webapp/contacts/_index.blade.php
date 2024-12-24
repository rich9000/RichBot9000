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
                    <th>Context</th>
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
                <h5 class="modal-title">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addContactForm">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="contact">Contact</option>
                            <option value="lead">Lead</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Context</label>
                        <input type="text" class="form-control" name="context" value="contact">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custom Name (Optional)</label>
                        <input type="text" class="form-control" name="custom_name" placeholder="Your name for this contact">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="allowed_to_contact" id="allowedToContact" checked>
                            <label class="form-check-label" for="allowedToContact">Allowed to Contact</label>
                        </div>
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
                data: null,
                defaultContent: '',
                render: function(data, type, row) {
                    const pivotName = row.pivot?.name ? ` (${row.pivot.name})` : '';
                    return row.name + pivotName;
                }
            },
            { data: 'email', defaultContent: '' },
            { data: 'phone', defaultContent: '' },
            { data: 'type', defaultContent: 'contact' },
            { 
                data: 'pivot.allowed_to_contact',
                defaultContent: '',
                render: function(data) {
                    return data ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                }
            },
            { 
                data: 'opt_in_at',
                defaultContent: 'Not opted in',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : 'Not opted in';
                }
            },
            { 
                data: 'pivot.context',
                defaultContent: 'contact'
            },
            {
                data: 'id',
                defaultContent: '',
                render: function(data, type, row) {
                    if (!data) return '';
                    return `
                        <button class="btn btn-sm btn-info" onclick="editContact(${data})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteContact(${data}, '${row.name}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        processing: true,
        language: {
            emptyTable: 'No contacts found',
            processing: 'Loading contacts...'
        },
        pageLength: 10,
        responsive: true,
        error: function(xhr, error, thrown) {
            console.error('DataTables error:', error, thrown);
        }
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

console.log(contact);
console.log(contact.users[0]);
console.log(contact.users[0].pivot.name);


            const form = $('#addContactForm')[0];
            form.name.value = contact.name;
            form.email.value = contact.email;
            form.phone.value = contact.phone || '';
            form.type.value = contact.type || 'contact';
            form.context.value = contact.users[0].pivot.context || 'contact';
            form.custom_name.value = contact.users[0].pivot.name || '';
            form.allowed_to_contact.checked = contact.users[0].pivot.allowed_to_contact ?? true;
            
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
        $('input[name="context"]').val('contact');
        $('select[name="type"]').val('contact');
    });

</script>