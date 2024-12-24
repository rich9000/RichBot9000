<div id="projectDetails" data-project-id="{{ $projectId }}">
    <h2 id="projectName"></h2>
    <p id="projectDescription"></p>
    <button class="btn btn-primary" id="editProjectButton">Edit Project</button>
    <button class="btn btn-danger" id="deleteProjectButton">Delete Project</button>
</div>

<h3>Tasks</h3>
<button class="btn btn-success mb-3" id="addTaskButton">Add Task</button>
<table id="tasksTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Title</th>
        <th>Description</th>
        <th>Assigned Users</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Task Modal (for creating/editing tasks) -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="taskForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalLabel">Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="taskId">
                    <div class="mb-3">
                        <label for="taskTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="taskTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="taskDescription"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="taskUsers" class="form-label">Assign Users</label>
                        <div id="taskUsers"></div>
                    </div>
                    <input type="hidden" id="taskProjectId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveTaskButton">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentProjectId = '{{ $projectId }}';

    document.addEventListener('DOMContentLoaded', () => {
        loadProjectDetails(currentProjectId);
        loadTasks(currentProjectId);

        // Handle form submission for creating/editing tasks
        document.getElementById('taskForm').addEventListener('submit', function(event) {
            event.preventDefault();
            saveTask();
        });

        document.getElementById('addTaskButton').addEventListener('click', function() {
            document.getElementById('taskModalLabel').textContent = 'Add Task';
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
            document.getElementById('taskProjectId').value = currentProjectId;
            loadUsersCheckboxes();
            new bootstrap.Modal(document.getElementById('taskModal')).show();
        });

        document.getElementById('editProjectButton').addEventListener('click', function() {
            // Implement edit project logic
            alert('Edit Project feature is not implemented yet.');
        });

        document.getElementById('deleteProjectButton').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this project?')) {
                deleteProject(currentProjectId);
            }
        });
    });

    function loadProjectDetails(projectId) {
        fetch(`/api/projects/${projectId}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(project => {
                document.getElementById('projectName').textContent = project.name;
                document.getElementById('projectDescription').textContent = project.description || '';
            });
    }

    function loadTasks(projectId) {
        fetch(`/api/tasks?project_id=${projectId}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(tasks => {
                const tbody = document.querySelector('#tasksTable tbody');
                tbody.innerHTML = tasks.map(task => `
            <tr>
                <td>${task.title}</td>
                <td>${task.description || '<span class="text-muted">No Description</span>'}</td>
                <td>${task.users.map(user => user.name).join(', ') || '<span class="text-muted">No Users</span>'}</td>
                <td>
                    <button class="btn btn-info btn-sm edit-task-btn" data-task-id="${task.id}">Edit</button>
                    <button class="btn btn-danger btn-sm delete-task-btn" data-task-id="${task.id}">Delete</button>
                </td>
            </tr>
        `).join('');
            });
    }

    // Event delegation for task actions
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('edit-task-btn')) {
            const taskId = event.target.getAttribute('data-task-id');
            editTask(taskId);
        } else if (event.target.classList.contains('delete-task-btn')) {
            const taskId = event.target.getAttribute('data-task-id');
            deleteTask(taskId);
        }
    });

    function editTask(taskId) {
        fetch(`/api/tasks/${taskId}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(task => {
                document.getElementById('taskModalLabel').textContent = 'Edit Task';
                document.getElementById('taskId').value = task.id;
                document.getElementById('taskTitle').value = task.title;
                document.getElementById('taskDescription').value = task.description;
                document.getElementById('taskProjectId').value = task.project_id;
                loadUsersCheckboxes(task.users);
                new bootstrap.Modal(document.getElementById('taskModal')).show();
            });
    }

    function saveTask() {
        const taskId = document.getElementById('taskId').value;
        const url = taskId ? `/api/tasks/${taskId}` : '/api/tasks';
        const method = taskId ? 'PUT' : 'POST';

        const selectedUsers = Array.from(document.querySelectorAll('#taskUsers input:checked')).map(checkbox => checkbox.value);

        const taskData = {
            title: document.getElementById('taskTitle').value,
            description: document.getElementById('taskDescription').value,
            project_id: document.getElementById('taskProjectId').value,
            user_ids: selectedUsers
        };

        fetch(url, {
            method: method,
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(taskData)
        })
            .then(response => response.json())
            .then(() => {
                new bootstrap.Modal(document.getElementById('taskModal')).hide();
                loadTasks(currentProjectId);
            })
            .catch(err => alert('Error saving task. Please try again.'));
    }

    function deleteTask(taskId) {
        if (confirm('Are you sure you want to delete this task?')) {
            fetch(`/api/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                    'Accept': 'application/json'
                }
            })
                .then(() => {
                    loadTasks(currentProjectId);
                })
                .catch(err => alert('Error deleting task. Please try again.'));
        }
    }

    function deleteProject(projectId) {
        fetch(`/api/projects/${projectId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(() => {
                alert('Project deleted successfully.');
                window.location.href = '/projects';
            })
            .catch(err => alert('Error deleting project. Please try again.'));
    }

    function loadUsersCheckboxes(selectedUsers = []) {
        const taskUsersDiv = document.getElementById('taskUsers');
        taskUsersDiv.innerHTML = '';

        fetch('/api/users', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(users => {
                users.forEach(user => {
                    const div = document.createElement('div');
                    div.classList.add('form-check');
                    const isChecked = selectedUsers.some(selectedUser => selectedUser.id === user.id);
                    div.innerHTML = `
                <input class="form-check-input" type="checkbox" value="${user.id}" id="user-${user.id}" ${isChecked ? 'checked' : ''}>
                <label class="form-check-label" for="user-${user.id}">${user.name}</label>
            `;
                    taskUsersDiv.appendChild(div);
                });
            });
    }
</script>
