@extends('layouts.base')

@section('content')
    <div class="container mt-4">
        <h1>Assistant Functions</h1>
        <a href="{{ route('assistant_functions.create') }}" class="btn btn-primary mb-3">Create New Function</a>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($functions as $function)
                <tr>
                    <td>{{ $function->name }}</td>
                    <td>{{ $function->description }}</td>
                    <td>{{ ucfirst($function->status) }}</td>
                    <td>
                        <a href="{{ route('assistant_functions.show', $function) }}" class="btn btn-info btn-sm">View</a>
                        <a href="{{ route('assistant_functions.edit', $function) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('assistant_functions.destroy', $function) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
