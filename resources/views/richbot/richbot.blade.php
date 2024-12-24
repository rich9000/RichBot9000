@extends('layouts.content')

@section('content')
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
        <div id="conversation-info">
            Conversation ID: <span id="conversation-id">{{$conversation_id}}</span>
        </div>
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

        var conversation_id = '{{$conversation_id}}';

        // Web Audio Context
        var context = null;

        // Audio data
        var audio = null;
        var audio0 = null;

        // The stream instance
        var instance = null;

        // Model name
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
        // Fetch Model
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
        // Microphone Handling
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
                    alert("Unable to access the microphone. Please check your device settings and permissions.");
                });

            var interval = setInterval(function() {
                if (!doRecording) {
                    clearInterval(interval);
                    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                        mediaRecorder.stop();
                    }
                    if (stream) {
                        stream.getTracks().forEach(function(track) {
                            track.stop();
                        });
                    }

                    document.getElementById('start').disabled = false;
                    document.getElementById('stop').disabled  = true;

                    mediaRecorder = null;
                }

                if (audio != null && audio.length > kSampleRate * kRestartRecording_s) {
                    if (doRecording) {
                        clearInterval(interval);
                        audio0 = audio;
                        audio = null;
                        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                            mediaRecorder.stop();
                        }
                        if (stream) {
                            stream.getTracks().forEach(function(track) {
                                track.stop();
                            });
                        }
                    }
                }
            }, 100);
        }

        //
        // Main Functionality
        //
        var transcribedAll = '';

        // Update the keywords to simpler phrases
        var keywords = ["clear", "do it"];

        /**
         * Function to remove entire lines that contain only sound annotations.
         * Assumes that sound annotations are on separate lines and enclosed within (), [], or {}.
         *
         * @param {string} text - The transcribed text.
         * @returns {string} - The cleaned text without sound annotation lines.
         */
        function removeSoundWords(text) {
            // Split text into lines
            const lines = text.split('\n');
            // Filter out lines that contain only sound annotations
            const filteredLines = lines.filter(line => !/^[\(\[\{].*?[\)\]\}]$/.test(line.trim()));
            // Rejoin the filtered lines with spaces
            return filteredLines.join(' ').trim();
        }

        function onStart() {
            if (!instance) {
                instance = Module.init('whisper.bin');

                if (instance) {
                    printTextarea("js: whisper initialized, instance: " + instance);
                }
            }

            if (!instance) {
                printTextarea("js: failed to initialize whisper");
                alert("Failed to initialize the speech recognition model. Please try refreshing the page.");
                return;
            }

            startRecording();

            intervalUpdate = setInterval(function() {
                var transcribed = Module.get_transcribed();

                if (transcribed != null && transcribed.length > 1) {
                    // Remove entire lines that are sound annotations
                    var cleanedTranscribed = removeSoundWords(transcribed);

                    // Append the cleaned transcribed text
                    transcribedAll += cleanedTranscribed + ' ';

                    // Update the text area
                    document.getElementById('transcribed-text').value = transcribedAll.trim();

                    // Check for voice commands
                    checkForKeywords(cleanedTranscribed);
                }

                document.getElementById('status-indicator').style.backgroundColor = Module.get_status() === "paused" ? 'red' : 'green';
            }, 100);
        }

        function onStop() {
            stopRecording();
            clearInterval(intervalUpdate);
        }

        /**
         * Function to check for predefined keywords in the transcribed text.
         *
         * @param {string} transcribed - The latest transcribed text segment.
         */
        function checkForKeywords(transcribed) {
            const lowerTranscribed = transcribed.toLowerCase();

            keywords.forEach(function(keyword) {
                if (lowerTranscribed.includes(keyword)) {
                    if (keyword === "clear") {
                        clearText();
                    } else if (keyword === "do it") {
                        sendText();
                    }
                }
            });
        }

        /**
         * Clears the transcribed text area.
         */
        function clearText() {
            transcribedAll = '';
            document.getElementById('transcribed-text').value = '';

        }

        /**
         * Sends the transcribed text.
         * Currently implemented as an alert. Replace with actual sending logic as needed.
         */
        function sendText() {
            const textToSend = document.getElementById('transcribed-text').value;
            if (textToSend.trim() !== '') {

                var _token = '{{ csrf_token() }}';

             //   alert("Text sent: " + textToSend);
                // Implement your sending logic here
                // For example, you could send the text to a server using fetch/AJAX

                fetch('/richbot9000', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ text: textToSend,context: 'public_test', conversation_id: conversation_id,_token: _token })
                })
                .then(response => response.json())
                .then(data => {

                    lastElement = data.response[data.response.length - 1];

                    console.log('Success:', data);
                    //alert("Text successfully sent! response:" + data.response);
                    speakText(lastElement.content);

                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert("There was an error sending the text.");
                });

            } else {
                alert("Text area is empty!");
            }
        }

        /**
         * Toggles the visibility of the debug output area.
         */
        function toggleDebug() {
            var output = document.getElementById('output');
            if (output.style.display === 'none') {
                output.style.display = 'block';
            } else {
                output.style.display = 'none';
            }
        }

        /**
         * Appends text to the debug output area.
         *
         * @param {string} text - The text to append.
         */
        function printTextarea(text) {
            var output = document.getElementById('output');
            if (output.tagName.toLowerCase() === 'textarea') {
                output.value += text + '\n';
            } else {
                output.innerHTML += text + '<br>';
            }
            output.scrollTop = output.scrollHeight;
        }

        /**
         * Placeholder function for cache clearing logic.
         */
        function clearCache() {
            // Implement cache clearing logic if needed
            alert("Cache cleared!");
        }

        // Load the model on page load
        window.onload = function() {
            loadWhisper();
        };


        /**
         * Speaks the given text using the Web Speech API's SpeechSynthesis.
         *
         * @param {string} text - The text to be spoken.
         */
        function speakText(text) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                // Optional: Set voice parameters
                utterance.pitch = 1; // Range between 0 and 2
                utterance.rate = 1;  // Range between 0.1 and 10
                utterance.volume = 1; // Range between 0 and 1

                // Optional: Select a specific voice
                // const voices = window.speechSynthesis.getVoices();
                // utterance.voice = voices.find(voice => voice.name === 'Google US English');

                window.speechSynthesis.speak(utterance);
            } else {
                console.warn("Speech Synthesis not supported in this browser.");
            }
        }
    </script>
    <script type="text/javascript" src="/whisper_public/stream/stream.js"></script>



    @include('richbot._display',['display_name'=>'main'])

@endsection
