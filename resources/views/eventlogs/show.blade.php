@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Event Log Details</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ $eventLog->event_type }}</h5>
                <p class="card-text">{{ $eventLog->description }}</p>
                <p class="card-text"><strong>User:</strong> {{ $eventLog->user->name ?? 'N/A' }}</p>
                <p class="card-text"><strong>Date:</strong> {{ $eventLog->created_at->format('Y-m-d H:i:s') }}</p>
                @if($eventLog->data)
                    <p class="card-text"><strong>Data:</strong></p>
                    <pre>{{ json_encode($eventLog->data, JSON_PRETTY_PRINT) }}</pre>
                @endif
                <a href="{{ route('eventlogs.index') }}" class="btn btn-secondary">Back to Event Logs</a>
            </div>
        </div>
    </div>
@endsection
