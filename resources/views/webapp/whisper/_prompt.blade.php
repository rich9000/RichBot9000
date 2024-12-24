    <style>
        #output {
            width: 100%;
            height: 100%;
            margin-top: 10px;
            background-color: black;
            color: white;
            font-size: 10px;
            font-family: 'Lucida Console', Monaco, monospace;
            outline: none;
            white-space: pre;
            overflow-x: scroll;
            display: none; /* Hidden by default */
        }
        #debug-toggle {
            margin-top: 10px;
        }
        #status-indicator {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
        }
        #main-container {
            text-align: center;
        }
        #transcribed-text {
            width: 80%;
            height: 150px;
            margin-top: 10px;
        }
    </style>

    <div id="main-container">
    <h1>The Richbot 9000</h1>

    <div id="model-whisper">
        <span id="model-whisper-status">Loading model...</span>
    </div>

    <div id="input">
        <button id="start" onclick="onStart()" disabled>Start</button>
        <button id="stop" onclick="onStop()" disabled>Stop</button>
        <button id="clear" onclick="clearText()">Clear Text</button>
        <button id="send" onclick="sendText()">Send Text</button>
        <button id="debug-toggle" onclick="toggleDebug()">Toggle Debug</button>
        <span id="status-indicator" style="background-color: red;"></span>
    </div>

    <textarea id="transcribed-text" placeholder="Transcribed text will appear here..."></textarea>

    <div id="output"></div>
</div>

<script type="text/javascript" src="/whisper_public/stream/helpers.js"></script>
<script type='text/javascript'>
    // web audio context
    var context = null;

    // audio data
    var audio = null;
    var audio0 = null;

    // the stream instance
    var instance = null;

    // model name
    var model_whisper = 'tiny.en';

    var Module = {
        print: printTextarea,
        printErr: printTextarea,
        setStatus: function(text) {
            printTextarea('js: ' + text);
        },
        monitorRunDependencies: function(left) {},
        preRun: function() {
            printTextarea('js: Preparing ...');
        },
        postRun: function() {
            printTextarea('js: Initialized successfully!');
        }
    };



    let dbVersion = 1
    let dbName    = 'whisper.ggerganov.com';
    let indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB



    //
    // fetch model
    //
    function storeFS(fname, buf) {
        try {
            Module.FS_unlink(fname);
        } catch (e) {}
        Module.FS_createDataFile("/", fname, buf, true, true);
        printTextarea('storeFS: stored model: ' + fname + ' size: ' + buf.length);
        document.getElementById('model-whisper-status').innerHTML = 'Model loaded!';
        document.getElementById('start').disabled = false;
    }

    function loadWhisper() {
        let url = '/whisper_public/models/ggml-model-whisper-base.en.bin';
        let dst = 'whisper.bin';
        let size_mb = 75;

        document.getElementById('model-whisper-status').innerHTML = 'Loading model...';

        cbProgress = function(p) {
            let el = document.getElementById('model-whisper-status');
            el.innerHTML = 'Loading model... ' + Math.round(100 * p) + '%';
        };

        cbCancel = function() {
            let el = document.getElementById('model-whisper-status');
            if (el) el.innerHTML = 'Model loading cancelled.';
        };

        loadRemote(url, dst, size_mb, cbProgress, storeFS, cbCancel, printTextarea);
    }

    //
    // microphone
    //
    const kSampleRate = 16000;
    const kRestartRecording_s = 120;
    const kIntervalAudio_ms = 5000;

    var mediaRecorder = null;
    var doRecording = false;
    var startTime = 0;

    window.AudioContext = window.AudioContext || window.webkitAudioContext;

    function stopRecording() {
        Module.set_status("paused");
        doRecording = false;
        audio0 = null;
        audio = null;
        context = null;
        document.getElementById('status-indicator').style.backgroundColor = 'red';
    }

    function startRecording() {
        if (!context) {
            context = new AudioContext({
                sampleRate: kSampleRate,
                channelCount: 1,
                echoCancellation: false,
                autoGainControl: true,
                noiseSuppression: true,
            });
        }

        Module.set_status("");

        document.getElementById('start').disabled = true;
        document.getElementById('stop').disabled = false;
        document.getElementById('status-indicator').style.backgroundColor = 'green';

        doRecording = true;
        startTime = Date.now();

        var chunks = [];
        var stream = null;

        navigator.mediaDevices.getUserMedia({audio: true, video: false})
            .then(function(s) {
                stream = s;
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.ondataavailable = function(e) {
                    chunks.push(e.data);

                    var blob = new Blob(chunks, { 'type' : 'audio/ogg; codecs=opus' });
                    var reader = new FileReader();

                    reader.onload = function(event) {
                        var buf = new Uint8Array(reader.result);

                        if (!context) {
                            return;
                        }
                        context.decodeAudioData(buf.buffer, function(audioBuffer) {
                            var offlineContext = new OfflineAudioContext(audioBuffer.numberOfChannels, audioBuffer.length, audioBuffer.sampleRate);
                            var source = offlineContext.createBufferSource();
                            source.buffer = audioBuffer;
                            source.connect(offlineContext.destination);
                            source.start(0);

                            offlineContext.startRendering().then(function(renderedBuffer) {
                                audio = renderedBuffer.getChannelData(0);

                                var audioAll = new Float32Array(audio0 == null ? audio.length : audio0.length + audio.length);
                                if (audio0 != null) {
                                    audioAll.set(audio0, 0);
                                }
                                audioAll.set(audio, audio0 == null ? 0 : audio0.length);

                                if (instance) {
                                    Module.set_audio(instance, audioAll);
                                }
                            });
                        }, function(e) {
                            audio = null;
                        });
                    }

                    reader.readAsArrayBuffer(blob);
                };

                mediaRecorder.onstop = function(e) {
                    if (doRecording) {
                        setTimeout(function() {
                            startRecording();
                        });
                    }
                };

                mediaRecorder.start(kIntervalAudio_ms);
            })
            .catch(function(err) {
                printTextarea('js: error getting audio stream: ' + err);
            });

        var interval = setInterval(function() {
            if (!doRecording) {
                clearInterval(interval);
                mediaRecorder.stop();
                stream.getTracks().forEach(function(track) {
                    track.stop();
                });

                document.getElementById('start').disabled = false;
                document.getElementById('stop').disabled  = true;

                mediaRecorder = null;
            }

            if (audio != null && audio.length > kSampleRate * kRestartRecording_s) {
                if (doRecording) {
                    clearInterval(interval);
                    audio0 = audio;
                    audio = null;
                    mediaRecorder.stop();
                    stream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                }
            }
        }, 100);
    }

    //
    // main
    //
    var transcribedAll = '';

    function onStart() {
        if (!instance) {
            instance = Module.init('whisper.bin');

            if (instance) {
                printTextarea("js: whisper initialized, instance: " + instance);
            }
        }

        if (!instance) {
            printTextarea("js: failed to initialize whisper");
            return;
        }

        startRecording();

        intervalUpdate = setInterval(function() {
            var transcribed = Module.get_transcribed();

            if (transcribed != null && transcribed.length > 1) {
                transcribedAll += transcribed + '\n';

                // Update the text area
                document.getElementById('transcribed-text').value = transcribedAll;

                // Check for voice commands
                checkForKeywords(transcribed);
            }

            document.getElementById('status-indicator').style.backgroundColor = Module.get_status() === "paused" ? 'red' : 'green';
        }, 100);
    }

    function onStop() {
        stopRecording();
        clearInterval(intervalUpdate);
    }

    var keywords = ["richbot clear", "richbot send"];

    function checkForKeywords(transcribed) {
        const lowerTranscribed = transcribed.toLowerCase();

        keywords.forEach(function(keyword) {
            if (lowerTranscribed.includes(keyword)) {
                if (keyword === "richbot clear") {
                    clearText();
                } else if (keyword === "richbot send") {
                    sendText();
                }
            }
        });
    }

    function clearText() {
        transcribedAll = '';
        document.getElementById('transcribed-text').value = '';
        alert("Text area cleared!");
    }

    function sendText() {
        const textToSend = document.getElementById('transcribed-text').value;
        if (textToSend.trim() !== '') {
            alert("Text sent: " + textToSend);
            // Implement your sending logic here
            // For example, you could send the text to a server using fetch/AJAX
        } else {
            alert("Text area is empty!");
        }
    }

    function toggleDebug() {
        var output = document.getElementById('output');
        if (output.style.display === 'none') {
            output.style.display = 'block';
        } else {
            output.style.display = 'none';
        }
    }

    function printTextarea(text) {
        var output = document.getElementById('output');
        output.value += text + '\n';
        output.scrollTop = output.scrollHeight;
    }

    function clearCache() {
        // Implement cache clearing logic if needed
        alert("Cache cleared!");
    }

    // Load the model on page load
    window.onload = function() {
        loadWhisper();
    };
</script>
<script type="text/javascript" src="/whisper_public/stream/stream.js"></script>
