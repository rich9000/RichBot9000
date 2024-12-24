<div class="card">
    <div class="card-header">Audio and Video Dashboard</div>
    <div class="card-body">
        <h2 class="warning">This probably doesn't work, I will get to it.</h2>

        <div class="container mt-5">
            <h2>WebRTC Video Recorder</h2>
            <div class="row">
                <div class="col-md-12">
                    <!-- Video Preview -->
                    <div class="card mb-4">
                        <div class="card-header">Video Preview</div>
                        <div class="card-body">
                            <video id="localVideo" autoplay playsinline controls muted class="w-100" style="max-height: 400px;"></video>
                        </div>
                    </div>

                    <!-- Video Recording Controls -->
                    <div class="mb-3">
                        <button id="startVideoRecordingBtn" class="btn btn-success">Start Recording</button>
                        <button id="stopVideoRecording" class="btn btn-danger" disabled>Stop Recording</button>
                    </div>

                    <!-- Status and Upload Result for Video -->
                    <div id="videoStatusMessage" class="alert d-none"></div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            <h2>Record Audio</h2>
            <div class="mb-3">
                <button id="startAudioRecordingBtn" class="btn btn-primary">Start Recording</button>
            </div>
            <div id="audioRecordingIndicator" class="mt-3 text-danger" style="display:none;">
                <strong>Recording...</strong>
            </div>
            <div class="mt-3">
                <h2>Recorded Audio</h2>
                <audio id="recordedAudio" controls></audio>
            </div>
            <div id="audioProcessingIndicator" class="mt-3 text-warning" style="display:none;">
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

        <!-- JavaScript -->
        <script>
            $(document).ready(function() {
                // Video Recording Setup
                const startVideoRecordingButton = $('#startVideoRecordingBtn');
                const stopVideoRecordingButton = $('#stopVideoRecording');
                const videoStatusMessage = $('#videoStatusMessage');
                const localVideo = $('#localVideo')[0];

                let videoMediaRecorder;
                let videoRecordedChunks = [];

                navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                    .then(stream => {
                        localVideo.srcObject = stream;
                        videoMediaRecorder = new MediaRecorder(stream);

                        videoMediaRecorder.ondataavailable = event => {
                            if (event.data.size > 0) {
                                videoRecordedChunks.push(event.data);
                            }
                        };

                        videoMediaRecorder.onstop = () => {
                            const blob = new Blob(videoRecordedChunks, { type: 'video/webm' });
                            videoRecordedChunks = [];

                            // Show uploading status
                            videoStatusMessage.removeClass('d-none alert-success alert-danger').addClass('alert-info');
                            videoStatusMessage.text('Uploading video...');

                            const formData = new FormData();
                            formData.append('video', blob, 'recording.webm');

                            fetch('{{ route('upload.video') }}', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        videoStatusMessage.removeClass('alert-info').addClass('alert-success');
                                        videoStatusMessage.text('Video uploaded successfully! File path: ' + data.path);
                                    } else {
                                        throw new Error(data.message);
                                    }
                                })
                                .catch(error => {
                                    videoStatusMessage.removeClass('alert-info').addClass('alert-danger');
                                    videoStatusMessage.text('Upload failed: ' + error.message);
                                });
                        };
                    })
                    .catch(error => {
                        console.error('Error accessing media devices.', error);
                        alert('Error accessing camera and microphone.');
                    });

                startVideoRecordingButton.click(function() {
                    videoMediaRecorder.start();
                    startVideoRecordingButton.prop('disabled', true);
                    stopVideoRecordingButton.prop('disabled', false);
                    videoStatusMessage.addClass('d-none');
                });

                stopVideoRecordingButton.click(function() {
                    videoMediaRecorder.stop();
                    startVideoRecordingButton.prop('disabled', false);
                    stopVideoRecordingButton.prop('disabled', true);
                });

                // Audio Recording Setup
                const startAudioRecordingButton = $('#startAudioRecordingBtn');
                const recordedAudio = $('#recordedAudio')[0];
                const audioRecordingIndicator = $('#audioRecordingIndicator');
                const audioProcessingIndicator = $('#audioProcessingIndicator');
                const transcriptionText = $('#transcriptionText');
                const responseText = $('#responseText');

                let audioMediaRecorder;
                let audioRecordedChunks = [];
                let isAudioRecording = false;
                let uploadInterval;

                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        audioMediaRecorder = new MediaRecorder(stream);

                        audioMediaRecorder.ondataavailable = event => {
                            if (event.data.size > 0) {
                                audioRecordedChunks.push(event.data);
                            }
                        };

                        audioMediaRecorder.onstop = () => {
                            clearInterval(uploadInterval);
                            const blob = new Blob(audioRecordedChunks, { type: 'audio/webm' });
                            audioRecordedChunks = [];
                            const url = URL.createObjectURL(blob);
                            recordedAudio.src = url;
                        };

                        startAudioRecordingButton.click(function() {
                            if (isAudioRecording) {
                                audioMediaRecorder.stop();
                                startAudioRecordingButton.text('Start Recording').removeClass('btn-danger').addClass('btn-primary');
                                audioRecordingIndicator.hide();
                            } else {
                                audioMediaRecorder.start(2000);
                                startAudioRecordingButton.text('Stop Recording').removeClass('btn-primary').addClass('btn-danger');
                                audioRecordingIndicator.show();
                                uploadInterval = setInterval(uploadChunks, 5000);
                            }
                            isAudioRecording = !isAudioRecording;
                        });
                    })
                    .catch(error => {
                        console.error('Error accessing media devices.', error);
                        alert('Error accessing microphone.');
                    });

                function uploadChunks() {
                    if (audioRecordedChunks.length > 0) {
                        const blob = new Blob(audioRecordedChunks, { type: 'audio/webm' });
                        audioRecordedChunks = [];
                        uploadAudio(blob);
                    }
                }

                function uploadAudio(blob) {
                    const formData = new FormData();
                    formData.append('audio', blob, 'recordedAudio.webm');

                    // Show processing indicator
                    audioProcessingIndicator.show();

                    $.ajax({
                        url: '/upload-audio',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                        },
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            console.log('Success:', data);
                            audioProcessingIndicator.hide();
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
                            audioProcessingIndicator.hide();
                            alert('Failed to upload audio.');
                        }
                    });
                }
            });
        </script>
    </div>
</div>
