<div class="video-container">
    <h2 class="text-center">Live Video Stream</h2>
    <video id="videoPlayer1" controls autoplay muted></video>
    <video id="videoPlayer2" style="display:none;" muted></video>
    <p id="currentFileName" class="text-center mt-3">
        <small>Currently Playing: <span id="fileName">Loading...</span></small>
    </p>
    <ul id="fileList" class="list-group"></ul>
</div>

<script>
    const userId = '1'; // Replace '1' with the actual user ID dynamically
    const video1 = document.getElementById('videoPlayer1');
    const video2 = document.getElementById('videoPlayer2');
    const fileNameDisplay = document.getElementById('fileName');
    const fileList = document.getElementById('fileList');
    const playlistUrl = `/storage/videos/${userId}/playlist.json`;

    let currentSegmentIndex = 0;
    let playlist = [];
    let isUsingVideo1 = true;

    // Fetch the playlist
    fetch(playlistUrl)
        .then(response => response.json())
        .then(data => {
            playlist = data;
            populateFileList(); // Populate the file list below the video
            playNextSegment();
        })
        .catch(error => {
            console.error('Error loading playlist:', error);
        });

    function populateFileList() {
        playlist.forEach((fileName, index) => {
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item';
            listItem.textContent = fileName;
            listItem.id = `file-${index}`;
            fileList.appendChild(listItem);
        });
    }

    function highlightCurrentFile() {
        // Remove highlight from the previous file
        if (currentSegmentIndex > 0) {
            const previousFile = document.getElementById(`file-${currentSegmentIndex - 1}`);
            previousFile.classList.remove('active');
        }

        // Highlight the current file
        const currentFile = document.getElementById(`file-${currentSegmentIndex}`);
        currentFile.classList.add('active');
    }

    function playNextSegment() {
        if (currentSegmentIndex < playlist.length) {
            const currentFileName = playlist[currentSegmentIndex];
            fileNameDisplay.textContent = currentFileName;
            highlightCurrentFile(); // Highlight the current file in the list

            const currentVideo = isUsingVideo1 ? video1 : video2;
            const nextVideo = isUsingVideo1 ? video2 : video1;

            currentVideo.src = `/storage/videos/${userId}/${currentFileName}`;
            currentVideo.play();

            currentVideo.onplay = () => {
                // Hide the current video and display the next one
                setTimeout(() => {
                    if (!isUsingVideo1) {
                        video1.style.display = 'none';
                        video2.style.display = 'block';
                    } else {
                        video1.style.display = 'block';
                        video2.style.display = 'none';
                    }
                    isUsingVideo1 = !isUsingVideo1; // Toggle between video elements
                }, 50); // Small delay to ensure smooth transition
            };

            currentVideo.onended = () => {
                currentSegmentIndex++;
                if (currentSegmentIndex < playlist.length) {
                    // Preload the next segment
                    const nextFileName = playlist[currentSegmentIndex];
                    nextVideo.src = `/storage/videos/${userId}/${nextFileName}`;
                    nextVideo.load(); // Preload the next video
                }
                playNextSegment(); // Play the next segment
            };
        } else {
            console.log('All segments played.');
        }
    }
</script>
