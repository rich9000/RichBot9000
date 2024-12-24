<!-- resources/views/webapp/project/_project_tasks.blade.php -->
<h4>Project Tasks</h4>

<ul>
    @foreach ($project->tasks as $task)
        @include('webapp.project._task', ['task' => $task])
    @endforeach
</ul>
