<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webcam Video Upload</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <div class="card mt-5">
        <div class="card-body">
            <h5 class="card-title">Webcam Video Recorder</h5>
            <video id="videoPlayer" width="640" height="360" controls autoplay muted></video>
            <div class="form-group mt-3">
                <label><input type="checkbox" id="audioOnlyCheckbox"> Audio Only</label>
            </div>
            <button id="startRecording" class="btn btn-primary">Start Recording</button>
            <button id="stopRecording" class="btn btn-secondary" disabled>Stop Recording</button>
        </div>
    </div>
</div>

<script>
    const video = document.getElementById('videoPlayer');
    const audioOnlyCheckbox = document.getElementById('audioOnlyCheckbox');
    const startRecordingButton = document.getElementById('startRecording');
    const stopRecordingButton = document.getElementById('stopRecording');
    let mediaRecorder;
    let recordedBlobs = [];
    let intervalId;
    let segmentIndex = 0;

    async function startRecording() {
        recordedBlobs = [];

        // Options for video or audio-only recording
        const constraints = audioOnlyCheckbox.checked
            ? { audio: true, video: false }
            : { audio: true, video: { width: 640, height: 360 } };

        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            video.play();

            let options = { mimeType: 'video/webm' };
            if (audioOnlyCheckbox.checked) {
                options = { mimeType: 'audio/webm' };
            }

            // Check if the codec is supported
            if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                console.warn(`${options.mimeType} is not supported, trying different codec.`);
                options = { mimeType: 'video/webm;codecs=vp8' };
                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    console.warn(`${options.mimeType} is not supported, trying video/mp4.`);
                    options = { mimeType: 'video/mp4' };
                    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                        console.error('None of the specified codecs are supported by this browser.');
                        return;
                    }
                }
            }

            mediaRecorder = new MediaRecorder(stream, options);

            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    recordedBlobs.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                uploadVideoSegment(new Blob(recordedBlobs, { type: options.mimeType }));
                recordedBlobs = []; // Clear the array after uploading the segment
            };

            mediaRecorder.start();

            intervalId = setInterval(() => {
                mediaRecorder.stop();
                mediaRecorder.start();
            }, 1000); // Record and upload every 3 seconds

            startRecordingButton.disabled = true;
            stopRecordingButton.disabled = false;

            console.log('Recording started');
        } catch (err) {
            console.error('Error accessing media devices.', err);
        }
    }

    function stopRecording() {
        clearInterval(intervalId);
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        if (video.srcObject) {
            const tracks = video.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            video.srcObject = null;
        }

        startRecordingButton.disabled = false;
        stopRecordingButton.disabled = true;

        console.log('Recording stopped');
    }

    function uploadVideoSegment(segmentBlob) {
        const formData = new FormData();
        formData.append('video', segmentBlob, `segment-${segmentIndex++}.webm`);

        ajaxRequest('/api/upload-video', 'POST', formData)
            .then(response => {
                console.log('Video segment uploaded successfully:', response);
            })
            .catch(error => {
                console.error('Error uploading video segment:', error);
            });
    }

    function ajaxRequest(url, method = 'GET', data = {}, token = null) {
        return new Promise((resolve, reject) => {
            if (!token) {
                token = localStorage.getItem('api_token');
            }

            const headers = {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token,
            };

            const options = {
                method: method,
                headers: headers,
            };

            if (data instanceof FormData) {
                delete headers['Content-Type']; // Let the browser set the correct Content-Type with boundary
                options.body = data;
            } else if (method === 'POST' || method === 'PUT' || method === 'PATCH') {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }

            fetch(url, options)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            reject(errorData);
                        });
                    }
                    return response.json();
                })
                .then(data => resolve(data))
                .catch(error => reject(error));
        });
    }

    startRecordingButton.addEventListener('click', startRecording);
    stopRecordingButton.addEventListener('click', stopRecording);
</script>
</body>
</html>
