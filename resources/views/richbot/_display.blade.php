<style>
    .controls {
        display: flex;
        align-items: center;
    }
    .controls > * {
        margin-right: 10px;
    }
    #current-content {
        margin-top: 20px;
        width: 100%;
        height: auto;
        overflow: auto;
    }

</style>
<div class="container">
    <div class="controls mb-3">
        <button id="pause-button" class="btn btn-primary">Pause</button>
        <button id="mute-button" class="btn btn-secondary">Mute</button>
        <select id="content-dropdown" class="form-select" style="display: inline-block; width: auto;">
            <!-- Options will be populated by JavaScript -->
        </select>
    </div>
    <div id="current-content" class="col">
        <!-- Current content will be displayed here -->
    </div>
</div>
<script>
    let lastContent = ''; // Store the last content received
    let previousContents = []; // Store previous contents
    const pollInterval = 10000; // Poll every 10 seconds
    let isPaused = false; // Flag to pause polling
    let isMuted = false; // Flag to mute audio

    async function pollEndpoint() {
        if (isPaused) {
            setTimeout(pollEndpoint, pollInterval);
            return;
        }

        try {
            const response = await fetch('/display/{{$display_name}}',
                {

                    headers: {
                        'Content-Type': 'application/json',
                    }
                }
            ); // Call the endpoint
            if (!response.ok) throw new Error('Network response was not ok');

            const newData = await response.json(); // Get the content as JSON

            console.log('newData',newData);
            const content = newData.content;
            const audioUrl = newData.audio_url;

            // Check if the content is different from the last one
            if (content !== lastContent) {
                if (!isMuted && audioUrl) {
                    playAudio(audioUrl); // Play audio if available and not muted
                }

                updateContent(content); // Update the content on the page
                previousContents.unshift(content); // Add to previous contents
                updateDropdown(); // Update the dropdown with new content
                lastContent = content; // Set lastContent to the new content
            }
        } catch (error) {
            console.error('Error polling /display/{{$display_name}}:', error);
        } finally {
            setTimeout(pollEndpoint, pollInterval); // Continue polling
        }
    }

    function updateContent(newContent) {
        const currentContentEl = document.getElementById('current-content');
        if (currentContentEl) {
            currentContentEl.innerHTML = newContent; // Update current content
            scaleTextToFit(currentContentEl);
        }
    }

    function updateDropdown() {
        const dropdown = document.getElementById('content-dropdown');
        if (dropdown) {
            // Clear existing options
            dropdown.innerHTML = '';

            // Add options from previousContents
            previousContents.forEach((content, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.text = `Content ${index + 1}`;
                dropdown.add(option);
            });
        }
    }
    function scaleTextToFit(element) {
        const maxFontSize = 72; // Maximum font size in pixels
        const minFontSize = 16; // Minimum font size in pixels
        const containerWidth = element.clientWidth;
        const containerHeight = element.clientHeight;
        let fontSize = maxFontSize;

        element.style.fontSize = fontSize + 'px';
        element.style.whiteSpace = 'normal'; // Allow wrapping of longer content

        // Decrease font size until the text fits within the container
        while (
            (element.scrollWidth > containerWidth || element.scrollHeight > containerHeight) &&
            fontSize > minFontSize
            ) {
            fontSize -= 1;
            element.style.fontSize = fontSize + 'px';
        }
    }

    function handleDropdownChange() {
        const dropdown = document.getElementById('content-dropdown');
        const selectedIndex = dropdown.selectedIndex;
        const selectedContent = previousContents[selectedIndex];
        updateContent(selectedContent);
    }

    function togglePause() {
        isPaused = !isPaused;
        const pauseButton = document.getElementById('pause-button');
        pauseButton.textContent = isPaused ? 'Resume' : 'Pause';
    }

    function toggleMute() {
        isMuted = !isMuted;
        const muteButton = document.getElementById('mute-button');
        muteButton.textContent = isMuted ? 'Unmute' : 'Mute';
    }

    function playAudio(url) {
        const audio = new Audio(url);
        audio.play();
    }

    // Attach event listeners
    document.addEventListener('DOMContentLoaded', () => {
        const dropdown = document.getElementById('content-dropdown');
        if (dropdown) {
            dropdown.addEventListener('change', handleDropdownChange);
        }

        const pauseButton = document.getElementById('pause-button');
        if (pauseButton) {
            pauseButton.addEventListener('click', togglePause);
        }

        const muteButton = document.getElementById('mute-button');
        if (muteButton) {
            muteButton.addEventListener('click', toggleMute);
        }

        // Start polling
        pollEndpoint();
        scaleTextToFit(document.getElementById('current-content'));
    });
</script>
