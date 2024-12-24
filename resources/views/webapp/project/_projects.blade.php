<h1>Projects</h1>
<button id="loadProjectsButton" class="btn btn-primary mb-3">Load Projects</button>
<table id="projectsTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Project Modal (for creating/editing projects) -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="projectForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalLabel">Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="projectId">
                    <div class="mb-3">
                        <label for="projectName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="projectName" required>
                    </div>
                    <div class="mb-3">
                        <label for="projectDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="projectDescription"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveProjectButton">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

        // Load projects when button is clicked
        document.getElementById('loadProjectsButton').addEventListener('click', loadProjectsDataTables);

        // Handle form submission for creating/editing projects
        document.getElementById('projectForm').addEventListener('submit', function(event) {
            event.preventDefault();
            saveProject();
        });


    function loadProjectsDataTables() {
        fetch("/api/projects", {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#projectsTable tbody');
                tbody.innerHTML = data.map(project => `
            <tr>
                <td>${project.name}</td>
                <td>${project.description || '<span class="text-muted">No Description</span>'}</td>
                <td>${new Date(project.created_at).toLocaleString()}</td>
                <td>
                    <button class="btn btn-info btn-sm view-project-btn" data-project-id="${project.id}">View</button>
                    <button class="btn btn-primary btn-sm edit-project-btn" data-project-id="${project.id}">Edit</button>
                    <button class="btn btn-danger btn-sm delete-project-btn" data-project-id="${project.id}">Delete</button>
                </td>
            </tr>
        `).join('');
            });
    }

    // Event delegation for project actions
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('edit-project-btn')) {
            const projectId = event.target.getAttribute('data-project-id');
            editProject(projectId);
        } else if (event.target.classList.contains('delete-project-btn')) {
            const projectId = event.target.getAttribute('data-project-id');
            deleteProject(projectId);
        } else if (event.target.classList.contains('view-project-btn')) {
            const projectId = event.target.getAttribute('data-project-id');
            viewProject(projectId);
        }
    });

    function editProject(projectId) {
        fetch(`/api/projects/${projectId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(project => {
                document.getElementById('projectModalLabel').textContent = 'Edit Project';
                document.getElementById('projectId').value = project.id;
                document.getElementById('projectName').value = project.name;
                document.getElementById('projectDescription').value = project.description;
                new bootstrap.Modal(document.getElementById('projectModal')).show();
            });
    }

    function saveProject() {
        const projectId = document.getElementById('projectId').value;
        const url = projectId ? `/api/projects/${projectId}` : '/api/projects';
        const method = projectId ? 'PUT' : 'POST';

        const projectData = {
            name: document.getElementById('projectName').value,
            description: document.getElementById('projectDescription').value
        };

        fetch(url, {
            method: method,
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(projectData)
        })
            .then(response => response.json())
            .then(() => {
                new bootstrap.Modal(document.getElementById('projectModal')).hide();
                loadProjectsDataTables();
            })
            .catch(err => alert('Error saving project. Please try again.'));
    }

    function deleteProject(projectId) {
        if (confirm('Are you sure you want to delete this project?')) {
            fetch(`/api/projects/${projectId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            })
                .then(() => {
                    loadProjectsDataTables();
                })
                .catch(err => alert('Error deleting project. Please try again.'));
        }
    }

    function viewProject(projectId) {
        // Implement view project details logic here, possibly opening a modal or redirecting
        alert('View Project feature is not implemented yet.');
    }
</script>
