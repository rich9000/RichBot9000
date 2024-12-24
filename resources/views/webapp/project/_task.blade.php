<div id="taskDetails" data-task-id="{{ $taskId }}">
    <h2 id="taskTitle"></h2>
    <p id="taskDescription"></p>
    <p>Assigned to: <span id="taskAssignedUsers"></span></p>
    <button class="btn btn-primary" id="editTaskButton">Edit Task</button>
    <button class="btn btn-danger" id="deleteTaskButton">Delete Task</button>
</div>

<script>

    function loadTaskDetails(taskId) {
        fetch(`/api/tasks/${taskId}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(task => {
                document.getElementById('taskTitle').textContent = task.title;
                document.getElementById('taskDescription').textContent = task.description || '';
                document.getElementById('taskAssignedUsers').textContent = task.users.map(user => user.name).join(', ') || 'No Users Assigned';
            });
    }

    function deleteTask(taskId) {
        fetch(`/api/tasks/${taskId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(() => {
                alert('Task deleted successfully.');
                window.history.back();
            })
            .catch(err => alert('Error deleting task. Please try again.'));
    }


    let currentTaskId = '{{ $taskId }}';


    loadTaskDetails(currentTaskId);

    document.getElementById('editTaskButton').addEventListener('click', function() {
        // Implement edit task logic
        alert('Edit Task feature is not implemented yet.');
    });

    document.getElementById('deleteTaskButton').addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this task?')) {
            deleteTask(currentTaskId);
        }
    });


</script>
