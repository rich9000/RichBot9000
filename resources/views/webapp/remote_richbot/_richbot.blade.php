
    <div class="container">
        <h1>{{ $richbot->name }}</h1>
        <p><strong>Remote ID:</strong> {{ $richbot->remote_richbot_id }}</p>
        <p><strong>Status:</strong> {{ $richbot->status }}</p>
        <p><strong>Last Seen:</strong> {{ $richbot->last_seen }}</p>

        <!-- Media Gallery -->
        <h2>Media Gallery</h2>
        <div class="media-gallery">
            @foreach($richbot->media as $media)
                @if($media->type == 'image')
                    <img src="{{ asset('storage/' . $media->file_path) }}" alt="Image" width="200">
                @elseif($media->type == 'audio')
                    <audio controls>
                        <source src="{{ asset('storage/' . $media->file_path) }}" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                @endif
            @endforeach
        </div>

        <!-- Event Logs -->
        <h2>Event Logs</h2>
        <ul>
            @foreach($richbot->events as $event)
                <li>{{ $event->created_at }} - {{ $event->event_type }}: {{ json_encode($event->details) }}</li>
            @endforeach
        </ul>

        <!-- Send Command -->
        <h2>Send Command</h2>
        <form action="{{ route('remote-richbot.sendCommand', $richbot->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="command">Command</label>
                <input type="text" name="command" id="command" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="parameters">Parameters (JSON)</label>
                <textarea name="parameters" id="parameters" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Send Command</button>
        </form>
    </div>
