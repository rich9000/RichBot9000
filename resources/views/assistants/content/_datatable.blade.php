<table id="assistantFunctionsTable" class="table table-bordered table-striped">
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Status</th>
        <th>Version</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <!-- DataTables will automatically populate the body -->
    </tbody>
</table>

<script>
    $(document).ready(function() {
        $('#assistantFunctionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("api.assistant_functions.index") }}',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token')
                },
                dataSrc: function(json) {
                    return json.data;
                }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'description' },
                { data: 'status' },
                { data: 'version' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            columnDefs: [
                {
                    targets: 5,
                    render: function(data, type, row) {
                        return `
                            <a href="/assistant_functions/${row.id}" class="btn btn-info btn-sm">View</a>
                            <a href="/assistant_functions/${row.id}/edit" class="btn btn-warning btn-sm">Edit</a>
                            <button class="btn btn-danger btn-sm delete-button" data-id="${row.id}">Delete</button>
                        `;
                    }
                }
            ]
        });

        // Handle delete button clicks
        $('#assistantFunctionsTable').on('click', '.delete-button', function() {
            var id = $(this).data('id');
            if (confirm('Are you sure you want to delete this function?')) {
                $.ajax({
                    url: `/api/assistant_functions/${id}`,
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                        'Accept': 'application/json'
                    },
                    success: function() {
                        $('#assistantFunctionsTable').DataTable().ajax.reload();
                        alert('Function deleted successfully.');
                    },
                    error: function() {
                        alert('Failed to delete the function.');
                    }
                });
            }
        });
    });
</script>
