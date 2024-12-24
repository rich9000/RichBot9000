@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Event Logs</h1>
        <table class="table">
            <thead>
            <tr>
                <th>Event Type</th>
                <th>Description</th>
                <th>User</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($eventLogs as $eventLog)
                <tr>
                    <td>{{ $eventLog->event_type }}</td>
                    <td>{{ $eventLog->description }}</td>
                    <td>{{ $eventLog->user->name ?? 'N/A' }}</td>
                    <td>{{ $eventLog->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        <a href="{{ route('eventlogs.show', $eventLog->id) }}" class="btn btn-primary btn-sm">View</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $eventLogs->links() }}
    </div>
@endsection
