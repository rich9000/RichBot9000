@extends('layouts.dashboard')

@section('title', 'OpenAI Dashboard')

@section('content')
    <div class="m-0 m-sm-3">

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Errors:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('message'))
            <div class="alert alert-info">
                <strong>Response:</strong>
                <p>{{ session('message') }}</p>
            </div>
        @endif

        <div id="messages-container">
            @foreach($messages as $message)
                <div style="border:black 1px solid">
                    <h4>{{ $message['role'] }} ({{ \Carbon\Carbon::parse($message['created_at'])->toDateTimeString() }}) {{ \Carbon\Carbon::parse($message['created_at'])->diffForHumans() }}</h4>
                    Run ID: {{ $message['run_id'] }}
                    Thread ID: {{ $message['thread_id'] }}
                    Assistant ID: {{ $message['assistant_id'] }}<br/>


                    <pre>{{ $message['text'] }}</pre>
                </div>
            @endforeach
        </div>

        <button id="check-updates" class="btn btn-info mt-3">Check for Updates</button>

        <div>
            <form id="new-session-form" action="/chat/session/destroy" method="post">
                @csrf
                Session ID: {{ $sessionId }}<br>
                <input type="submit" onclick="return confirm('Are you sure you wish to start a new session?');" value="New Session">
            </form>
        </div>

        <form id="chat-form" method="post">
            @csrf
            <h2>Select Assistant</h2>
            <div class="form-group">
                <label for="assistantSelect">Choose an Assistant</label>
                <select class="form-control" id="assistantSelect" name="assistant">
                    @foreach($assistants as $assistant)
                        <option value="{{ $assistant['id'] }}">{{ $assistant['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div style="float:right;">
                <input type="submit" value="Submit" class="btn btn-primary mt-3">
            </div>

            <div>
                <label for="prompt">Prompt:</label><br/>
                <div class="audio-recorder" data-target-id="prompt">
                    <button class="btn btn-primary record-btn">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
                <textarea name="prompt" id="prompt" rows="3" cols="100"></textarea>
            </div>

            <h4>Rules and Info</h4>
            <textarea name="rules" rows="10" cols="100"></textarea>
        </form>

    </div>






    <style>
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1em;
            height: 1em;
            margin-top: -0.5em;
            margin-left: -0.5em;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>





    <script>
        $(document).ready(function() {
            $('#chat-form').on('submit', function(e) {
                e.preventDefault();

                const submitButton = $(this).find('input[type="submit"]');
                submitButton.addClass('btn-loading').val('Processing...');

                $.ajax({
                    url: '/chat/send',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#messages-container').empty();
                        submitButton.removeClass('btn-loading').val('Submit');

                        response.messages.forEach(function(message) {
                            const createdAt = moment.unix(message.created_at).toDate();
                            const formattedCreatedAt = moment(createdAt).format('YYYY-MM-DD HH:mm:ss');
                            const diffForHumans = moment(createdAt).fromNow();
                            const content = message.content.map(contentItem => contentItem.text.value).join('\n');

                            $('#messages-container').append(`
                            <div style="border:black 1px solid">
                                <h4>${message.role} (${formattedCreatedAt}) ${diffForHumans}</h4>
                                Run ID: ${message.run_id || ''}
                                Thread ID: ${message.thread_id || ''}
                                Assistant ID: ${message.assistant_id || ''}<br/>
                                <pre>${message.content[0]['text']['value']}</pre>
                            </div>
                        `);
                        });
                    },
                    error: function(response) {
                        alert('Failed to send the message.');
                        submitButton.removeClass('btn-loading').val('Submit');
                    }
                });
            });

            $('#check-updates').on('click', function() {
                $.ajax({
                    url: '/chat/updates',
                    type: 'GET',
                    success: function(response) {
                        $('#messages-container').empty();
                        response.messages.forEach(function(message) {
                            console.log(message);
                            $('#messages-container').append(`
                            <div style="border:black 1px solid">
                                <h4>${message.role} (${message.created_at})</h4>
                                Run ID: ${message.run_id}
                                Thread ID: ${message.thread_id}
                                Assistant ID: ${message.assistant_id}<br/>
                                <pre>${message.content[0]['text']['value']}</pre>
                            </div>
                        `);
                        });
                    },
                    error: function(response) {
                        alert('Failed to fetch updates.');
                    }
                });
            });

            function fetchUpdates() {
                $.ajax({
                    url: '/chat/updates',
                    type: 'GET',
                    success: function(response) {
                        $('#messages-container').empty();
                        response.messages.forEach(function(message) {
                            $('#messages-container').append(`
                            <div style="border:black 1px solid">
                                <h4>${message.role} (${message.created_at})</h4>
                                Run ID: ${message.run_id}
                                Thread ID: ${message.thread_id}
                                Assistant ID: ${message.assistant_id}<br/>
                                <pre>${message.content[0]['text']['value']}</pre>
                            </div>
                        `);
                        });
                    },
                    error: function(response) {
                        alert('Failed to fetch updates.');
                    }
                });
            }

            setInterval(fetchUpdates, 10000); // Check for updates every 10 seconds
        });
    </script>
@endsection
