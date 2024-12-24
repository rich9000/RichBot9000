<!-- resources/views/functions.blade.php -->
@extends('layouts.dashboard')

@section('content')
    <div class="container">
        <h1>Functions</h1>

        <form id="createFunctionForm">
            @csrf
            <div class="form-group">
                <label for="name">Function Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" class="form-control" id="description" name="description" required>
            </div>
            <div class="form-group">
                <label for="parameters">Parameters (JSON)</label>
                <textarea class="form-control" id="parameters" name="parameters" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create Function</button>
        </form>

        <h2 class="mt-4">Existing Functions</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Parameters</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody id="functionsTableBody">
            @foreach ($functions as $function)
                <tr id="function-{{ $function->id }}">
                    <td>{{ $function->name }}</td>
                    <td>{{ $function->description }}</td>
                    <td><pre>{{ json_encode(json_decode($function->parameters), JSON_PRETTY_PRINT) }}</pre></td>
                    <td>
                        <button class="btn btn-danger deleteFunction" data-id="{{ $function->id }}">Delete</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle form submission
            $('#createFunctionForm').submit(function(event) {
                event.preventDefault();
                $.ajax({
                    url: '/chat/functions',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Add new function to the table
                        $('#functionsTableBody').append(`
                    <tr id="function-${response.id}">
                        <td>${response.name}</td>
                        <td>${response.description}</td>
                        <td><pre>${JSON.stringify(JSON.parse(response.parameters), null, 2)}</pre></td>
                        <td>
                            <button class="btn btn-danger deleteFunction" data-id="${response.id}">Delete</button>
                        </td>
                    </tr>
                `);
                        // Clear the form
                        $('#createFunctionForm')[0].reset();
                    },
                    error: function(response) {
                        alert('Error: ' + response.responseJSON.message);
                    }
                });
            });

            // Handle delete button click
            $(document).on('click', '.deleteFunction', function() {
                var functionId = $(this).data('id');
                $.ajax({
                    url: '/chat/functions/' + functionId,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#function-' + functionId).remove();
                    },
                    error: function(response) {
                        alert('Error: ' + response.responseJSON.message);
                    }
                });
            });
        });
    </script>
@endsection
