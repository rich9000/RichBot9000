@extends('layouts.base')

@section('content')
    <div class="container mt-4">
        <h1>Assistant Function: {{ $assistantFunction->name }}</h1>
        <p><strong>Description:</strong> {{ $assistantFunction->description }}</p>
        <p><strong>Status:</strong> {{ ucfirst($assistantFunction->status) }}</p>
        <p><strong>Version:</strong> {{ $assistantFunction->version }}</p>
        <p><strong>Execution Count:</strong> {{ $assistantFunction->execution_count }}</p>
        <p><strong>Last Executed:</strong> {{ $assistantFunction->last_executed_at ?? 'Never' }}</p>
        <a href="{{ route('assistant_functions.edit', $assistantFunction) }}" class="btn btn-warning">Edit</a>
        <a href="{{ route('assistant_functions.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
@endsection
