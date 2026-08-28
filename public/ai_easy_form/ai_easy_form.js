// Version 2.01

class AiEasyForm {
    constructor(formId, assistantId) {
        this.formId = formId;
        this.assistantId = assistantId;
        this.form = document.getElementById(formId);
        this.websocket = null;
        this.mediaStream = null;
        this.mediaHandler = null;
        this.isActive = false;
        this.imageInterval = null;
        this.audioContext = null;
        this.audioQueue = [];
        this.isPlayingAudio = false;
        
        if (!this.form) {
            console.error(`Form with ID ${formId} not found`);
            return;
        }
        
        // Capture initial form state
        this.captureFormState();
        this.init();
    }

    init() {
        // Get button elements
        this.startAiButton = document.getElementById('startAiButton');
        this.stopAiButton = document.getElementById('stopAiButton');
        this.assistantResponse = document.getElementById('assistantResponse');
        this.responseText = document.getElementById('responseText');

        if (!this.startAiButton || !this.stopAiButton) {
            console.error('Required button elements not found');
            return;
        }

        // Initialize audio context
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();

        // Add event listeners
        this.startAiButton.addEventListener('click', () => this.startAssistant());
        this.stopAiButton.addEventListener('click', () => this.stopAssistant());
    }

    async startAssistant() {
        try {
            // Request media permissions
            this.mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: true
            });

            // Initialize audio handler
            this.mediaHandler = new MediaHandler();
            await this.mediaHandler.startRecording();

            // Connect to WebSocket
            const wsUrl = `${window.appConfig?.wsUrl || 'wss://richbot9000.com:9501'}/app/${encodeURIComponent(appState.apiToken)}/${encodeURIComponent(this.assistantId)}`;
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connection established');
                // Send start chat message
                this.websocket.send(JSON.stringify({
                    type: 'start_chat',
                    assistant_id: this.assistantId
                }));

                // Start sending audio and images
                this.isActive = true;
                this.startMediaStreaming();
                this.startImageCapture();

                // Update UI
                this.startAiButton.style.display = 'none';
                this.stopAiButton.style.display = 'block';
                this.assistantResponse.style.display = 'block';
            };

            this.websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };

            this.websocket.onclose = () => {
                console.log('WebSocket connection closed');
                this.resetInterface();
            };

        } catch (error) {
            console.error('Error starting AI assistant:', error);
            alert('Failed to start AI assistant. Please ensure you have granted camera and microphone permissions.');
        }
    }

    startMediaStreaming() {
        if (!this.mediaStream || !this.websocket || !this.isActive) return;

        // Create media recorder for audio
        const mediaRecorder = new MediaRecorder(this.mediaStream);
        
        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0 && this.websocket.readyState === WebSocket.OPEN && this.isActive) {
                // Convert to base64 and send
                const reader = new FileReader();
                reader.readAsDataURL(event.data);
                reader.onloadend = () => {
                    const base64data = reader.result.split(',')[1];
                    this.websocket.send(JSON.stringify({
                        type: 'audio',
                        data: base64data,
                        timestamp: Date.now()
                    }));
                };
            }
        };

        // Record in 100ms chunks
        mediaRecorder.start(100);
    }

    startImageCapture() {
        // Create video element for image capture
        const video = document.createElement('video');
        video.srcObject = this.mediaStream;
        video.play();

        // Capture image every 3 seconds
        this.imageInterval = setInterval(() => {
            if (!this.isActive) return;

            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert to base64 and send
            const imageData = canvas.toDataURL('image/jpeg', 0.8).split(',')[1];
            if (this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    type: 'image',
                    data: imageData,
                    timestamp: Date.now()
                }));
            }
        }, 3000);
    }

    stopAssistant() {
        this.isActive = false;
        this.resetInterface();
    }

    resetInterface() {
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
            this.mediaStream = null;
        }

        if (this.mediaHandler) {
            this.mediaHandler.stopRecording();
            this.mediaHandler = null;
        }

        if (this.websocket) {
            this.websocket.close();
            this.websocket = null;
        }

        if (this.imageInterval) {
            clearInterval(this.imageInterval);
            this.imageInterval = null;
        }

        // Clear audio queue
        this.audioQueue = [];
        this.isPlayingAudio = false;

        // Reset UI
        this.startAiButton.style.display = 'block';
        this.stopAiButton.style.display = 'none';
        this.assistantResponse.style.display = 'none';
        if (this.responseText) {
            this.responseText.textContent = '';
        }
    }

    handleWebSocketMessage(data) {
        console.log('Received message:', data);
        
        switch (data.type) {
            case 'form_fill':
                this.fillFormField(data.field, data.value);
                break;
            case 'error':
                console.error('AI Assistant error:', data.message);
                break;
            case 'assistant_text_delta':
                this.handleTextDelta(data.data.delta);
                break;
            case 'assistant_audio_delta':
                this.handleAudioDelta(data.data.delta);
                break;
        }
    }

    handleTextDelta(delta) {
        if (this.responseText) {
            this.responseText.textContent += delta;
        }
    }

    async handleAudioDelta(audioData) {
        try {
            // Convert base64 to ArrayBuffer
            const binaryString = atob(audioData);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }

            // Create audio context if not exists
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }

            // Create an audio buffer from the raw data
            const audioBuffer = await this.audioContext.decodeAudioData(bytes.buffer);
            
            // Create buffer source
            const source = this.audioContext.createBufferSource();
            source.buffer = audioBuffer;
            
            // Connect and play
            source.connect(this.audioContext.destination);
            source.start(0);

        } catch (error) {
            console.error('Error processing audio delta:', error);
        }
    }

    captureFormState() {
        const formData = {
            elements: {}
        };

        Array.from(this.form.elements).forEach(element => {
            if (element.id) {
                const elementData = {
                    id: element.id,
                    type: element.type,
                    value: element.value,
                    name: element.name
                };

                // Capture options for select elements
                if (element.tagName.toLowerCase() === 'select') {
                    elementData.options = Array.from(element.options).map(option => ({
                        value: option.value,
                        text: option.text,
                        selected: option.selected
                    }));
                }

                formData.elements[element.id] = elementData;
            }
        });

        // Send initial form state to server
        fetch('/api/ai-easy-form/store-form', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                formId: this.formId,
                formData: formData
            })
        }).catch(error => console.error('Error storing form state:', error));

        return formData;
    }

    async getFormElementValue(elementId) {
        try {
            const response = await fetch(`/api/ai-easy-form/get-element-value/${this.formId}/${elementId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            return data.value;
        } catch (error) {
            console.error('Error getting form element value:', error);
            return null;
        }
    }

    async getAllFormValues() {
        try {
            const response = await fetch(`/api/ai-easy-form/get-form-values/${this.formId}`, {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        } catch (error) {
            console.error('Error getting all form values:', error);
            return null;
        }
    }

    fillFormField(fieldName, value) {
        const field = this.form.elements[fieldName];
        if (field) {
            field.value = value;
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            field.dispatchEvent(event);

            // Update server state
            fetch('/api/ai-easy-form/update-element', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    formId: this.formId,
                    elementId: field.id,
                    value: value
                })
            }).catch(error => console.error('Error updating form element:', error));
        }
    }
}

// MediaHandler class from _client.blade.php
class MediaHandler {
    constructor() {
        this.isRecording = false;
        this.mediaStream = null;
        this.mediaRecorder = null;
    }

    async startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true
                }
            });

            this.mediaStream = stream;
            this.mediaRecorder = new MediaRecorder(stream);
            this.isRecording = true;

            return true;
        } catch (error) {
            console.error('Failed to start recording:', error);
            return false;
        }
    }

    stopRecording() {
        this.isRecording = false;
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
        }
        if (this.mediaRecorder) {
            this.mediaRecorder.stop();
        }
    }
} 