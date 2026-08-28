class RichbotDisplay {
    #eventListeners = {};

    constructor(targetElementId, config = {}) {
        this.config = {
            displayName: config.displayName || 'default',
            pollInterval: config.pollInterval || 10000,
            maxFontSize: config.maxFontSize || 72,
            minFontSize: config.minFontSize || 16,
            autoStart: config.autoStart || false,
            showControls: config.showControls || true,
            ...config
        };

        this.targetElement = document.getElementById(targetElementId);
        if (!this.targetElement) {
            throw new Error(`Target element with id "${targetElementId}" not found`);
        }

        this.lastContent = '';
        this.previousContents = [];
        this.isPaused = false;
        this.isMuted = false;
        this.isPolling = false;
        this.pollTimer = null;

        this.initialize();
    }

    initialize() {
        this.createDisplayStructure();
        this.attachEventListeners();
        
        if (this.config.autoStart) {
            this.startPolling();
        }
    }

    createDisplayStructure() {
        const html = `
            <div class="richbot-display">
                ${this.config.showControls ? `
                    <div class="controls mb-3">
                        <button id="${this.getId('pause-button')}" class="btn btn-primary">Pause</button>
                        <button id="${this.getId('mute-button')}" class="btn btn-secondary">Mute</button>
                        <select id="${this.getId('content-dropdown')}" class="form-select" style="display: inline-block; width: auto;">
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                ` : ''}
                <div id="${this.getId('current-content')}" class="current-content">
                    <!-- Current content will be displayed here -->
                </div>
            </div>
        `;

        this.targetElement.innerHTML = html;
        this.addStyles();
    }

    addStyles() {
        const styleId = 'richbot-display-styles';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .richbot-display {
                    width: 100%;
                    height: 100%;
                }
                
                .richbot-display .controls {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 1rem;
                }
                
                .richbot-display .current-content {
                    width: 100%;
                    height: auto;
                    min-height: 200px;
                    overflow: auto;
                    padding: 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 0.25rem;
                }
            `;
            document.head.appendChild(style);
        }
    }

    getId(elementName) {
        return `richbot-display-${this.targetElement.id}-${elementName}`;
    }

    attachEventListeners() {
        const dropdown = document.getElementById(this.getId('content-dropdown'));
        if (dropdown) {
            dropdown.addEventListener('change', () => this.handleDropdownChange());
        }

        const pauseButton = document.getElementById(this.getId('pause-button'));
        if (pauseButton) {
            pauseButton.addEventListener('click', () => this.togglePause());
        }

        const muteButton = document.getElementById(this.getId('mute-button'));
        if (muteButton) {
            muteButton.addEventListener('click', () => this.toggleMute());
        }
    }

    async pollEndpoint() {
        if (this.isPaused || !this.isPolling) {
            return;
        }

        try {
            const response = await fetch(`/display/${this.config.displayName}`, {
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const newData = await response.json();
            const content = newData.content;
            const audioUrl = newData.audio_url;

            if (content !== this.lastContent) {
                if (!this.isMuted && audioUrl) {
                    this.playAudio(audioUrl);
                }

                this.updateContent(content);
                this.previousContents.unshift(content);
                this.updateDropdown();
                this.lastContent = content;
                this.emit('contentUpdated', { content, audioUrl });
            }
        } catch (error) {
            console.error(`Error polling /display/${this.config.displayName}:`, error);
            this.emit('error', error);
        } finally {
            if (this.isPolling) {
                this.pollTimer = setTimeout(() => this.pollEndpoint(), this.config.pollInterval);
            }
        }
    }

    updateContent(newContent) {
        const currentContentEl = document.getElementById(this.getId('current-content'));
        if (currentContentEl) {
            currentContentEl.innerHTML = newContent;
            this.scaleTextToFit(currentContentEl);
            this.emit('contentRendered', newContent);
        }
    }

    updateDropdown() {
        const dropdown = document.getElementById(this.getId('content-dropdown'));
        if (dropdown) {
            dropdown.innerHTML = '';
            this.previousContents.forEach((content, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.text = `Content ${index + 1}`;
                dropdown.add(option);
            });
        }
    }

    scaleTextToFit(element) {
        let fontSize = this.config.maxFontSize;
        element.style.fontSize = fontSize + 'px';
        element.style.whiteSpace = 'normal';

        while (
            (element.scrollWidth > element.clientWidth || 
             element.scrollHeight > element.clientHeight) &&
            fontSize > this.config.minFontSize
        ) {
            fontSize -= 1;
            element.style.fontSize = fontSize + 'px';
        }
    }

    handleDropdownChange() {
        const dropdown = document.getElementById(this.getId('content-dropdown'));
        const selectedIndex = dropdown.selectedIndex;
        const selectedContent = this.previousContents[selectedIndex];
        this.updateContent(selectedContent);
    }

    togglePause() {
        this.isPaused = !this.isPaused;
        const pauseButton = document.getElementById(this.getId('pause-button'));
        if (pauseButton) {
            pauseButton.textContent = this.isPaused ? 'Resume' : 'Pause';
        }
        this.emit('pauseStateChanged', this.isPaused);
    }

    toggleMute() {
        this.isMuted = !this.isMuted;
        const muteButton = document.getElementById(this.getId('mute-button'));
        if (muteButton) {
            muteButton.textContent = this.isMuted ? 'Unmute' : 'Mute';
        }
        this.emit('muteStateChanged', this.isMuted);
    }

    playAudio(url) {
        const audio = new Audio(url);
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
            this.emit('audioError', error);
        });
    }

    startPolling() {
        if (!this.isPolling) {
            this.isPolling = true;
            this.pollEndpoint();
            this.emit('pollingStarted');
        }
    }

    stopPolling() {
        this.isPolling = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
        this.emit('pollingStopped');
    }

    // Event handling
    on(event, callback) {
        if (!this.#eventListeners[event]) {
            this.#eventListeners[event] = [];
        }
        this.#eventListeners[event].push(callback);
    }

    emit(event, data) {
        if (this.#eventListeners[event]) {
            this.#eventListeners[event].forEach(callback => callback(data));
        }
    }
}

// Export for both ES modules and CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RichbotDisplay;
} else if (typeof define === 'function' && define.amd) {
    define([], function() { return RichbotDisplay; });
} else {
    window.RichbotDisplay = RichbotDisplay;
} 