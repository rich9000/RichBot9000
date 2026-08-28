<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>Audio Recorder Widget</h1>
            
            <!-- Container for the audio recorder widget -->
            <div id="audio-recorder-container"></div>
        </div>
    </div>
</div>

<style>
    .recording-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        background-color: red;
        border-radius: 50%;
        margin-right: 5px;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
</style>

<script>
    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the audio recorder widget
        const audioRecorder = new AudioRecorderWidget('audio-recorder-container', {
            // Optional configuration
            onSave: function(result) {
                console.log('Audio saved successfully:', result);
                // You can add custom logic here when the audio is saved
            },
            onError: function(error) {
                console.error('Error:', error);
                // You can add custom error handling here
            }
        });
    });
</script> 