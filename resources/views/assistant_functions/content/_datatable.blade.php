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
    <tbody id="table-body">

    @foreach(\App\Models\AssistantFunction::all() as $row)
        <tr>


        <td>{{$row->id}}</td>
        <td>{{$row->name}}</td>
        <td>{{$row->description}}</td>
        <td>{{$row->status}}</td>
        <td>{{$row->version}}</td>
        <td>
            <button class="btn btn-info btn-sm" onclick="viewFunction({{$row->id}})">View</button>
            <button class="btn btn-warning btn-sm" onclick="editFunction({{$row->id}})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteFunction({{$row->id}})">Delete</button>
        </td>

        </tr>
    @endforeach


    </tbody>
</table>



<script>



        // Delete function (confirm and call DELETE API)
        window.deleteFunction = async function (id) {
            if (confirm('Are you sure you want to delete this function?')) {
                try {
                    const response = await fetch(`/api/assistant_functions/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken,
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        alert('Function deleted successfully.');
                        fetchData(currentPage);  // Reload current page after deletion
                    } else {
                        alert('Failed to delete the function.');
                    }
                } catch (error) {
                    console.error('Error deleting function:', error);
                    alert('Failed to delete the function.');
                }
            }
        };

        // View and Edit actions
        window.viewFunction = function (id) {
            window.location.href = `/assistant_functions/${id}`;
        };

        window.editFunction = function (id) {
            window.location.href = `/assistant_functions/${id}/edit`;
        };

</script>
