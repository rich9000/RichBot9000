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
alert('contacts index');
    const contactsTable = $('#contactsTable').DataTable({
        ajax: {
            url: '/api/contacts',
            headers: apiHeaders(),
            dataSrc: 'data'
        },
        columns: [
            { data: 'name', defaultContent: '' },
            { data: 'email', defaultContent: '' },
            { data: 'phone', defaultContent: '' },
            { data: 'type', defaultContent: 'contact' },
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

    // Add these functions outside of the DataTable configuration
    function editContact(id) {
        fetch(`/api/contacts/${id}`, {
            headers: apiHeaders()
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(contact => {
            // Populate form
            const form = document.querySelector('#addContactForm');
            form.name.value = contact.name;
            form.email.value = contact.email;
            form.phone.value = contact.phone || '';
            form.type.value = contact.type || 'contact';
            form.context.value = contact.pivot?.context || 'contact';
            
            // Change save button action
            const saveBtn = document.querySelector('#saveContactBtn');
            saveBtn.dataset.mode = 'edit';
            saveBtn.dataset.id = id;
            
            // Show modal
            const modal = new bootstrap.Modal(document.querySelector('#addContactModal'));
            modal.show();
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

    // Save button handler (for both create and edit)
    document.querySelector('#saveContactBtn').addEventListener('click', function() {
        const form = document.querySelector('#addContactForm');
        const formData = new FormData(form);
        const mode = this.dataset.mode || 'create';
        const id = this.dataset.id;
        
        const url = mode === 'edit' ? `/api/contacts/${id}` : '/api/contacts';
        const method = mode === 'edit' ? 'PUT' : 'POST';
        
        const data = Object.fromEntries(formData);
        
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
            const modal = document.querySelector('#addContactModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();
            
            contactsTable.ajax.reload();
            showAlert(`Contact ${mode === 'edit' ? 'updated' : 'added'} successfully`, 'success');
            
            // Reset form and button state
            form.reset();
            delete this.dataset.mode;
            delete this.dataset.id;
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(`Error ${mode === 'edit' ? 'updating' : 'adding'} contact`, 'danger');
        });
    }); 

    alert('contacts index');
</script>   