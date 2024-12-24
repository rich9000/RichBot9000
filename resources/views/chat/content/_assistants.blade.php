<?php

use App\Services\OpenAIAssistant;

$gpt = new OpenAIAssistant();
$onlineFiles = [];

?>
<div class="container mt-5 " style="background-color: antiquewhite">
    <h2>Create GPT Assistant</h2>

    <form action="" method="POST">

        <div class="form-group">
            <label for="name">Assistant Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" class="form-control" id="model" name="model" value="gpt-3.5-turbo" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" class="form-control" id="description" name="description" required>
        </div>
        <div class="form-group">
            <label for="instructions">Instructions</label>
            <textarea class="form-control" id="instructions" name="instructions" rows="4" required></textarea>
        </div>


        <div class="form-group">
            <label for="files">Select Files</label>
            <div>
                <h5>Available Files</h5>
                @foreach($onlineFiles as $file)
                    <div class="form-check">
                        @dump($file)
                        <input class="form-check-input" type="checkbox" name="onlineFiles[]" value="{{ $file['id'] }}"
                               id="file_{{ $file['id'] }}">
                        <label class="form-check-label"
                               for="file_{{ $file['id'] }}">{{ $file['filename'] }} {{ $file['id'] }}</label>
                    </div>
                @endforeach
            </div>
        </div>


        <div class="form-group">
            <label class="checkbox-inline"><input type="checkbox" name="json_only" value="1"> Generate JSON Only</label>
        </div>

        <div class="form-group">
            <label for="functions">Select Functions</label>
            <div>
                <!-- File Management Functions -->
                <h5>File Management Functions</h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="download_file"
                           id="download_file">
                    <label class="form-check-label" for="download_file">Download File</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_file"
                           id="delete_file">
                    <label class="form-check-label" for="delete_file">Delete File</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_files"
                           id="list_files">
                    <label class="form-check-label" for="list_files">List Files</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_folders"
                           id="list_folders">
                    <label class="form-check-label" for="list_folders">List Folders</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="create_directory"
                           id="create_directory">
                    <label class="form-check-label" for="create_directory">Create Directory</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_directory"
                           id="delete_directory">
                    <label class="form-check-label" for="delete_directory">Delete Directory</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="put_text" id="put_text">
                    <label class="form-check-label" for="put_text">Put Text</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="append_text"
                           id="append_text">
                    <label class="form-check-label" for="append_text">Append Text</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="edit" id="edit">
                    <label class="form-check-label" for="edit">Edit</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="send_email"
                           id="send_email">
                    <label class="form-check-label" for="send_email">Send Email</label>
                </div>
            </div>
            <div>
                <!-- Project Management Functions -->
                <h5>Project Management Functions</h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_users"
                           id="list_users">
                    <label class="form-check-label" for="list_users">List Users</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="add_user" id="add_user">
                    <label class="form-check-label" for="add_user">Add User</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="view_user" id="view_user">
                    <label class="form-check-label" for="view_user">View User</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_user"
                           id="delete_user">
                    <label class="form-check-label" for="delete_user">Delete User</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_projects"
                           id="list_projects">
                    <label class="form-check-label" for="list_projects">List Projects</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="add_project"
                           id="add_project">
                    <label class="form-check-label" for="add_project">Add Project</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="view_project"
                           id="view_project">
                    <label class="form-check-label" for="view_project">View Project</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_project"
                           id="delete_project">
                    <label class="form-check-label" for="delete_project">Delete Project</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_goals"
                           id="list_goals">
                    <label class="form-check-label" for="list_goals">List Goals</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="add_goal" id="add_goal">
                    <label class="form-check-label" for="add_goal">Add Goal</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="view_goal" id="view_goal">
                    <label class="form-check-label" for="view_goal">View Goal</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_goal"
                           id="delete_goal">
                    <label class="form-check-label" for="delete_goal">Delete Goal</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_tasks"
                           id="list_tasks">
                    <label class="form-check-label" for="list_tasks">List Tasks</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="add_task" id="add_task">
                    <label class="form-check-label" for="add_task">Add Task</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="view_task" id="view_task">
                    <label class="form-check-label" for="view_task">View Task</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_task"
                           id="delete_task">
                    <label class="form-check-label" for="delete_task">Delete Task</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_issues"
                           id="list_issues">
                    <label class="form-check-label" for="list_issues">List Issues</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="add_issue" id="add_issue">
                    <label class="form-check-label" for="add_issue">Add Issue</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="view_issue"
                           id="view_issue">
                    <label class="form-check-label" for="view_issue">View Issue</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_issue"
                           id="delete_issue">
                    <label class="form-check-label" for="delete_issue">Delete Issue</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="list_deadlines"
                           id="list_deadlines">
                    <label class="form-check-label" for="list_deadlines">List Deadlines</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="add_deadline"
                           id="add_deadline">
                    <label class="form-check-label" for="add_deadline">Add Deadline</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="view_deadline"
                           id="view_deadline">
                    <label class="form-check-label" for="view_deadline">View Deadline</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="delete_deadline"
                           id="delete_deadline">
                    <label class="form-check-label" for="delete_deadline">Delete Deadline</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="assign" id="assign">
                    <label class="form-check-label" for="assign">Assign</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="functions[]" value="unassign" id="unassign">
                    <label class="form-check-label" for="unassign">Unassign</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Create Assistant</button>
    </form>
</div>


