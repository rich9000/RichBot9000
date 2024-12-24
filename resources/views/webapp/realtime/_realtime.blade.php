<style>
    /* Button styles */
    .record-btn {
        width: 100px;
        height: 40px;
        font-size: 16px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s, box-shadow 0.3s;
    }

    .btn-start {
        background-color: green;
    }

    .btn-stop {
        background-color: #b22222; /* Muted red color */
        box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.4);
        animation: pulse-red 1s infinite;
    }

    .btn-play {
        background-color: blue;
    }

    .btn-stop-playing {
        background-color: #1e90ff; /* Light blue color */
        box-shadow: 0 0 0 0 rgba(30, 144, 255, 0.4);
        animation: pulse-blue 1s infinite;
    }

    .btn-camera {
        background-color: purple;
    }

    .btn-stop-camera {
        background-color: #9932cc; /* Light purple color */
        box-shadow: 0 0 0 0 rgba(153, 50, 204, 0.4);
        animation: pulse-purple 1s infinite;
    }

    /* Pulse effect for different buttons */
    @keyframes pulse-red {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.4);
        }
        50% {
            box-shadow: 0 0 10px 5px rgba(255, 0, 0, 0.6);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.4);
        }
    }

    @keyframes pulse-blue {
        0% {
            box-shadow: 0 0 0 0 rgba(30, 144, 255, 0.4);
        }
        50% {
            box-shadow: 0 0 10px 5px rgba(30, 144, 255, 0.6);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(30, 144, 255, 0.4);
        }
    }

    @keyframes pulse-purple {
        0% {
            box-shadow: 0 0 0 0 rgba(153, 50, 204, 0.4);
        }
        50% {
            box-shadow: 0 0 10px 5px rgba(153, 50, 204, 0.6);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(153, 50, 204, 0.4);
        }
    }

    .spinner-border {
        position: absolute;
        right: 10px;
        width: 1.5rem;
        height: 1.5rem;
        visibility: hidden;
    }

    .webcam-container {
        margin-top: 20px;
        display: flex;
        justify-content: center;
    }

    .webcam-preview {
        display: none;
        width: 320px;
        height: 240px;
        border: 1px solid #ccc;
    }


    .btn-push-to-talk {
        background-color: orange;
    }

    .btn-push-to-talk-active {
        background-color: darkorange;
        box-shadow: 0 0 10px 5px rgba(255, 165, 0, 0.6);
    }
    .tools-checkbox-group {
        max-height: 300px; /* Adjust the height as needed */
        overflow-y: auto;  /* Enable vertical scrolling */
        border: 1px solid #ccc; /* Optional: border for visibility */
        padding: 10px;     /* Optional: padding for better layout */
        margin-top: 5px;   /* Optional: space from label */
    }
</style>



<div class="container mt-5 text-center">


    <h3>Webcam & Audio Recorder</h3>

    <!-- Push-to-Talk Button -->
    <button id="pushToTalkBtn" class="record-btn btn-push-to-talk">
        <span class="button-text">Push to Talk</span>
    </button>


    <!-- Recording Button -->
    <button id="recordBtn" class="record-btn btn-start">
        <span class="button-text">Start</span>
        <span class="spinner-border text-primary" id="uploadIndicator" role="status">
            <span class="visually-hidden">Uploading...</span>
        </span>
    </button>

    <!-- Play Button -->
    <button id="playBtn" class="record-btn btn-play">
        <span class="button-text">Play</span>
    </button>

    <!-- Webcam Button -->
    <button id="cameraBtn" class="record-btn btn-camera">
        <span class="button-text">Start Camera</span>
    </button>

    <!-- Webcam Preview -->
    <div class="webcam-container">
        <video id="webcamPreview" class="webcam-preview" autoplay></video>
    </div>
</div>
















<script>
class MediaHandler {
    constructor() {
        this.mediaRecorders = new Map();
        this.streams = new Map();
        this.chunks = new Map();
        this.uploadQueue = [];
        this.isProcessingQueue = false;
    }

    async startRecording(type, options = {}) {
        const constraints = {
            audio: type === 'audio' || type === 'both',
            video: type === 'video' || type === 'both'
        };

        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            const recorder = new MediaRecorder(stream, {
                mimeType: this.getSupportedMimeType(type)
            });

            this.setupRecorder(recorder, type);
            this.streams.set(type, stream);
            this.mediaRecorders.set(type, recorder);
            this.chunks.set(type, []);

            recorder.start(options.timeSlice || 4000);
            return true;
        } catch (error) {
            console.error(`Failed to start ${type} recording:`, error);
            return false;
        }
    }

    stopRecording(type) {
        const recorder = this.mediaRecorders.get(type);
        const stream = this.streams.get(type);
        
        if (recorder && recorder.state !== 'inactive') {
            recorder.stop();
            stream.getTracks().forEach(track => track.stop());
            this.mediaRecorders.delete(type);
            this.streams.delete(type);
        }
    }

    private setupRecorder(recorder, type) {
        recorder.ondataavailable = async (event) => {
            this.chunks.get(type).push(event.data);
            await this.queueUpload(type, event.data);
        };

        recorder.onstop = () => {
            this.processRemainingChunks(type);
        };
    }

    private async queueUpload(type, data) {
        this.uploadQueue.push({
            type,
            data,
            timestamp: Date.now()
        });

        if (!this.isProcessingQueue) {
            this.processUploadQueue();
        }
    }

    private async processUploadQueue() {
        this.isProcessingQueue = true;

        while (this.uploadQueue.length > 0) {
            const { type, data } = this.uploadQueue.shift();
            await this.uploadToServer(type, data);
        }

        this.isProcessingQueue = false;
    }

    private async uploadToServer(type, data) {
        const formData = new FormData();
        formData.append(type, new Blob([data]));

        try {
            const response = await fetch(`/api/upload-${type}-stream`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                },
                body: formData
            });

            return await response.json();
        } catch (error) {
            console.error(`Failed to upload ${type}:`, error);
            throw error;
        }
    }

    private getSupportedMimeType(type) {
        const mimeTypes = {
            audio: ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus'],
            video: ['video/webm;codecs=vp8,opus', 'video/webm']
        };

        return mimeTypes[type].find(mimeType => 
            MediaRecorder.isTypeSupported(mimeType)
        ) || '';
    }
}

class MediaHandler {
    constructor() {
        this.mediaRecorders = new Map();
        this.streams = new Map();
        this.chunks = new Map();
        this.uploadQueue = [];
        this.isProcessingQueue = false;
    }

    async startRecording(type, options = {}) {
        const constraints = {
            audio: type === 'audio' || type === 'both',
            video: type === 'video' || type === 'both'
        };

        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            const recorder = new MediaRecorder(stream, {
                mimeType: this.getSupportedMimeType(type)
            });

            this.setupRecorder(recorder, type);
            this.streams.set(type, stream);
            this.mediaRecorders.set(type, recorder);
            this.chunks.set(type, []);

            recorder.start(options.timeSlice || 4000);
            return true;
        } catch (error) {
            console.error(`Failed to start ${type} recording:`, error);
            return false;
        }
    }

    stopRecording(type) {
        const recorder = this.mediaRecorders.get(type);
        const stream = this.streams.get(type);
        
        if (recorder && recorder.state !== 'inactive') {
            recorder.stop();
            stream.getTracks().forEach(track => track.stop());
            this.mediaRecorders.delete(type);
            this.streams.delete(type);
        }
    }

    private setupRecorder(recorder, type) {
        recorder.ondataavailable = async (event) => {
            this.chunks.get(type).push(event.data);
            await this.queueUpload(type, event.data);
        };

        recorder.onstop = () => {
            this.processRemainingChunks(type);
        };
    }

    private async queueUpload(type, data) {
        this.uploadQueue.push({
            type,
            data,
            timestamp: Date.now()
        });

        if (!this.isProcessingQueue) {
            this.processUploadQueue();
        }
    }

    private async processUploadQueue() {
        this.isProcessingQueue = true;

        while (this.uploadQueue.length > 0) {
            const { type, data } = this.uploadQueue.shift();
            await this.uploadToServer(type, data);
        }

        this.isProcessingQueue = false;
    }

    private async uploadToServer(type, data) {
        const formData = new FormData();
        formData.append(type, new Blob([data]));

        try {
            const response = await fetch(`/api/upload-${type}-stream`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                },
                body: formData
            });

            return await response.json();
        } catch (error) {
            console.error(`Failed to upload ${type}:`, error);
            throw error;
        }
    }

    private getSupportedMimeType(type) {
        const mimeTypes = {
            audio: ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus'],
            video: ['video/webm;codecs=vp8,opus', 'video/webm']
        };

        return mimeTypes[type].find(mimeType => 
            MediaRecorder.isTypeSupported(mimeType)
        ) || '';
    }
}











    let pushToTalkBtn = document.getElementById('pushToTalkBtn');
    let isPushToTalkRecording = false;
    let pushToTalkMediaRecorder;
    let pushToTalkStream;

    // Event listener for the push-to-talk button
    pushToTalkBtn.addEventListener('click', async () => {
        if (isPushToTalkRecording) {
            // Stop recording
            pushToTalkMediaRecorder.stop();
            pushToTalkStream.getTracks().forEach(track => track.stop());
            isPushToTalkRecording = false;
            toggleButtonState(pushToTalkBtn, 'Push to Talk', 'btn-push-to-talk', 'btn-push-to-talk-active');
        } else {
            // Start recording
            pushToTalkStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const options = { mimeType: 'audio/webm; codecs=opus' };
            pushToTalkMediaRecorder = MediaRecorder.isTypeSupported(options.mimeType) ?
                new MediaRecorder(pushToTalkStream, options) :
                new MediaRecorder(pushToTalkStream);

            pushToTalkMediaRecorder.start();
            isPushToTalkRecording = true;
            toggleButtonState(pushToTalkBtn, 'Recording...', 'btn-push-to-talk-active', 'btn-push-to-talk');

            let chunks = [];

            pushToTalkMediaRecorder.ondataavailable = event => {
                chunks.push(event.data);
            };

            pushToTalkMediaRecorder.onstop = async () => {
                const audioBlob = new Blob(chunks, { type: 'audio/webm; codecs=opus' });
                chunks = []; // Reset chunks for the next recording

                const formData = new FormData();
                formData.append('audio', audioBlob);

                // Optionally, show an uploading indicator
                uploadIndicator.style.visibility = 'visible';

                try {
                    const response = await fetch('/api/upload-audio', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + appState.apiToken,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();
                    console.log(data.status === 'success' ? 'Audio uploaded successfully' : `Upload error: ${data.message}`);
                } catch (err) {
                    console.error('Upload error:', err);
                } finally {
                    // Hide the uploading indicator
                    uploadIndicator.style.visibility = 'hidden';
                    toggleButtonState(pushToTalkBtn, 'Push to Talk', 'btn-push-to-talk', 'btn-push-to-talk-active');
                }
            };
        }
    });

    function toggleButtonState(button, text, addClass, removeClass) {
        const buttonText = button.querySelector('.button-text');
        buttonText.textContent = text;
        button.classList.remove(removeClass);
        button.classList.add(addClass);
    }

</script>
<script>
    let mediaRecorder;
    let isRecording = false;
    let isUploading = false;

    let isPlaying = false;
    let pollInterval;

    let isCameraActive = false;
    let cameraStream;
    let webcamInterval;

    const recordBtn = document.getElementById('recordBtn');
    const uploadIndicator = document.getElementById('uploadIndicator');
    const recordBtnText = recordBtn.querySelector('.button-text');

    const playBtn = document.getElementById('playBtn');
    const playBtnText = playBtn.querySelector('.button-text');

    const cameraBtn = document.getElementById('cameraBtn');
    const cameraBtnText = cameraBtn.querySelector('.button-text');
    const webcamPreview = document.getElementById('webcamPreview');

    // Event listener for the record button
    recordBtn.addEventListener('click', async () => {
        if (isRecording) {
            mediaRecorder.stop();
            isRecording = false;
            toggleButtonState(recordBtn, 'Start', 'btn-start', 'btn-stop');
        } else {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const options = { mimeType: 'audio/ogg; codecs=opus' };
            mediaRecorder = MediaRecorder.isTypeSupported(options.mimeType) ?
                new MediaRecorder(stream, options) :
                new MediaRecorder(stream);

            mediaRecorder.start(4000); // Emit chunks every 4 seconds
            isRecording = true;
            toggleButtonState(recordBtn, 'Stop', 'btn-stop', 'btn-start');

            mediaRecorder.ondataavailable = async (event) => {
                if (isUploading) return;

                const audioBlob = new Blob([event.data]);
                const formData = new FormData();
                formData.append('audio', audioBlob);

                uploadIndicator.style.visibility = 'visible';
                isUploading = true;

                try {
                    const response = await fetch('/api/upload-audio-stream', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + appState.apiToken,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();
                    console.log(data.status === 'success' ? 'Upload successful' : `Upload error: ${data.message}`);
                } catch (err) {
                    console.error('Fetch error:', err);
                } finally {
                    isUploading = false;
                    uploadIndicator.style.visibility = 'hidden';
                }
            };

            mediaRecorder.onstop = () => {
                isRecording = false;
                toggleButtonState(recordBtn, 'Start', 'btn-start', 'btn-stop');
            };
        }
    });

    // Event listener for the play button
    playBtn.addEventListener('click', () => {
        if (isPlaying) {
            clearInterval(pollInterval);
            isPlaying = false;
            toggleButtonState(playBtn, 'Play', 'btn-play', 'btn-stop-playing');
        } else {
            pollServer();
            pollInterval = setInterval(pollServer, 5000);
            isPlaying = true;
            toggleButtonState(playBtn, 'Stop', 'btn-stop-playing', 'btn-play');
        }
    });

    // Event listener for the camera button
    cameraBtn.addEventListener('click', async () => {
        if (isCameraActive) {
            clearInterval(webcamInterval);
            cameraStream.getTracks().forEach(track => track.stop());
            webcamPreview.style.display = 'none';
            isCameraActive = false;
            toggleButtonState(cameraBtn, 'Start Camera', 'btn-camera', 'btn-stop-camera');
        } else {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
            webcamPreview.srcObject = cameraStream;
            webcamPreview.style.display = 'block';
            isCameraActive = true;
            toggleButtonState(cameraBtn, 'Stop Camera', 'btn-stop-camera', 'btn-camera');

            webcamInterval = setInterval(captureAndUploadImage, 5000);
        }
    });

    // Function to toggle button states
    function toggleButtonState(button, text, addClass, removeClass) {
        const buttonText = button.querySelector('.button-text');
        buttonText.textContent = text;
        button.classList.remove(removeClass);
        button.classList.add(addClass);
    }

    // Function to capture and upload images
    async function captureAndUploadImage() {
        const canvas = document.createElement('canvas');
        canvas.width = 320;
        canvas.height = 240;

        const context = canvas.getContext('2d');
        context.drawImage(webcamPreview, 0, 0, canvas.width, canvas.height);

        const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg'));
        const formData = new FormData();
        formData.append('image', imageBlob);

        try {
            const response = await fetch('/api/upload-image-stream', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();
            console.log(data.status === 'success' ? 'Image upload successful' : `Image upload error: ${data.message}`);
        } catch (err) {
            console.error('Image fetch error:', err);
        }
    }

    // Polling function for audio playback
    async function pollServer() {
        // Placeholder function for polling server for audio
        console.log('Polling server for audio...');
    }
</script>
