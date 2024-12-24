<?php

    use App\Services\OpenAIAssistant;

$openAIAssistant = new OpenAIAssistant();
$assistants = $openAIAssistant->list_assistants();
    ?>
<div id="functions_create_section">
    <a class="nav-content-loader btn btn-primary mb-3"
       onclick="
           loadContent(appState.tokens.richbot,`/api/content/assistants.content._form`, 'targetCreateFunctionDiv');
           "


       href="#" data-section="assistant_functions.content._form" data-target="functions_create_section">Create New Assistant</a>
</div>
<div id="targetCreateFunctionDiv">

</div>

<div id="">

    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Model</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($assistants as $assistant)
            <tr data-bs-toggle="collapse" data-bs-target="#assistant-{{ $assistant['id'] }}" aria-expanded="false" aria-controls="assistant-{{ $assistant['id'] }}">
                <td>{{ $assistant['id'] }}</td>
                <td>{{ $assistant['name'] }}</td>
                <td>{{ $assistant['description'] }}</td>
                <td>{{ $assistant['model'] }}</td>
                <td>
                    <button class="btn btn-info btn-sm">Toggle Details</button>
                </td>
            </tr>
            <tr class="collapse" id="assistant-{{ $assistant['id'] }}">
                <td colspan="5">
                    <div class="p-3">
                        <strong>Functions:</strong>
                        <ul>
                            @foreach($assistant['tools'] as $tool)
                                @if($tool['type'] === 'function')
                                    <li>{{ $tool['function']['name'] }}: {{ $tool['function']['description'] }}</li>
                                @endif
                            @endforeach
                        </ul>
                        <strong>Additional Info:</strong>
                        <p>Version: {{ $assistant['version'] ?? 'N/A' }}</p>
                        <p>Status: {{ $assistant['status'] ?? 'N/A' }}</p>
                        <p>Instructions: {{ $assistant['instructions'] }}</p>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No assistants found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>


<script>
    $(document).ready(function() {
        // Handle row click to toggle collapse
        $('.table tbody').on('click', 'tr[data-bs-toggle="collapse"]', function() {
            const target = $(this).data('bs-target');
            $(target).collapse('toggle');
        });
    });
</script>
