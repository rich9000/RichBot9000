@extends('layouts.dashboard')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h4>Select Files</h4>
                <div id="file-tree" class="bg-light p-2 rounded" style="height: 250px; overflow-y: scroll; font-size: 0.9rem;">
                    <!-- File tree will be loaded here -->
                </div>
            </div>
            <div class="col-md-6">
                <h4>Select Assistant</h4>
                <select id="assistant-select" class="form-select">
                    <!-- Assistants will be loaded here -->
                </select>
                <h4>Select Instructions</h4>
                <textarea id="instructions" class="form-control" rows="5"></textarea>
                <h4>Select Functions</h4>
                <div id="functions-list">
                    <!-- Function checkboxes will be loaded here -->
                </div>
            </div>
        </div>
        <div class="mt-2">
            <button id="sendRequestBtn" class="btn btn-primary btn-sm">Send Request</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load file tree
            function loadFileTree(root) {
                $.ajax({
                    url: '{{ route('chat.listFiles') }}',
                    type: 'GET',
                    data: { root: root },
                    success: function(data) {
                        $('#file-tree').empty();
                        data.files.forEach(function(file) {
                            $('#file-tree').append(`
                            <div>
                                <input type="checkbox" class="file-checkbox" value="${file.path}"> ${file.name}
                            </div>
                        `);
                        });
                    }
                });
            }

            // Load assistants
            function loadAssistants() {
                $.ajax({
                    url: '{{ route('chat.listAssistants') }}',
                    type: 'GET',
                    success: function(data) {
                        $('#assistant-select').empty();
                        data.assistants.forEach(function(assistant) {
                            $('#assistant-select').append(`
                            <option value="${assistant.id}">${assistant.name}</option>
                        `);
                        });
                    }
                });
            }

            // Load functions (dummy functions for demonstration)
            function loadFunctions() {
                const functions = ['function1', 'function2', 'function3'];
                $('#functions-list').empty();
                functions.forEach(function(func) {
                    $('#functions-list').append(`
                    <div>
                        <input type="checkbox" class="function-checkbox" value="${func}"> ${func}
                    </div>
                `);
                });
            }

            // Initialize
            loadFileTree('/');
            loadAssistants();
            loadFunctions();

            // Handle request submission
            $('#sendRequestBtn').click(function() {
                const selectedFiles = [];
                $('.file-checkbox:checked').each(function() {
                    selectedFiles.push($(this).val());
                });

                const selectedAssistant = $('#assistant-select').val();
                const instructions = $('#instructions').val();
                const selectedFunctions = [];
                $('.function-checkbox:checked').each(function() {
                    selectedFunctions.push($(this).val());
                });

                const requestData = {
                    files: selectedFiles,
                    assistant: selectedAssistant,
                    instructions: instructions,
                    functions: selectedFunctions
                };

                // Send request (update with appropriate route and method)
                console.log('Request Data:', requestData);
            });
        });
    </script>
@endsection
