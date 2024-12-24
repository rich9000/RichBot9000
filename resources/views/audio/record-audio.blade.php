@extends('layouts.dashboard')

@section('content')
    <div class="container mt-5">
        <h1>Record Audio</h1>
        <div class="mb-3">
            <button id="recordBtn" class="btn btn-primary">Start Recording</button>
        </div>
        <div id="recordingIndicator" class="mt-3 text-danger" style="display:none;">
            <strong>Recording...</strong>
        </div>
        <div class="mt-3">
            <h2>Recorded Audio</h2>
            <audio id="recordedAudio" controls></audio>
        </div>
        <div id="processingIndicator" class="mt-3 text-warning" style="display:none;">
            <strong>Processing...</strong>
        </div>
        <div class="mt-3">
            <h2>Transcription</h2>
            <p id="transcriptionText"></p>
        </div>
        <div class="mt-3">
            <h2>Text Response</h2>
            <p id="responseText"></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const recordBtn = $('#recordBtn');
            const recordedAudio = $('#recordedAudio')[0];
            const recordingIndicator = $('#recordingIndicator');
            const processingIndicator = $('#processingIndicator');
            const transcriptionText = $('#transcriptionText');
            const responseText = $('#responseText');

            let mediaRecorder;
            let recordedChunks = [];
            let isRecording = false;
            let uploadInterval;

            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    mediaRecorder = new MediaRecorder(stream);

                    mediaRecorder.ondataavailable = event => {
                        if (event.data.size > 0) {
                            recordedChunks.push(event.data);
                        }
                    };

                    mediaRecorder.onstop = () => {
                        clearInterval(uploadInterval);
                        const blob = new Blob(recordedChunks, { type: 'audio/webm' });
                        recordedChunks = [];
                        const url = URL.createObjectURL(blob);
                        recordedAudio.src = url;
                    };

                    recordBtn.click(() => {
                        if (isRecording) {
                            mediaRecorder.stop();
                            recordBtn.text('Start Recording').removeClass('btn-danger').addClass('btn-primary');
                            recordingIndicator.hide();
                        } else {
                            mediaRecorder.start(2000);
                            recordBtn.text('Stop Recording').removeClass('btn-primary').addClass('btn-danger');
                            recordingIndicator.show();
                            uploadInterval = setInterval(uploadChunks, 5000);
                        }
                        isRecording = !isRecording;
                    });
                })
                .catch(error => {
                    console.error('Error accessing media devices.', error);
                });

            function uploadChunks() {
                if (recordedChunks.length > 0) {
                    const blob = new Blob(recordedChunks, { type: 'audio/webm' });
                    recordedChunks = [];
                    uploadAudio(blob);
                }
            }

            function uploadAudio(blob) {
                const formData = new FormData();
                formData.append('audio', blob, 'recordedAudio.webm'); // Ensure the correct file extension

                // Show processing indicator
                processingIndicator.show();

                $.ajax({
                    url: '/upload-audio',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        console.log('Success:', data);
                        processingIndicator.hide();
                        if (data.audio) {
                            recordedAudio.src = data.audio;
                            recordedAudio.play();
                        }
                        if (data.transcription) {
                            transcriptionText.text(data.transcription);
                        }
                        if (data.response) {
                            responseText.text(data.response);
                        }
                    },
                    error: function(error) {
                        console.error('Error:', error);
                        processingIndicator.hide();
                        alert('Failed to upload audio.');
                    }
                });
            }
        });
    </script>
@endsection
