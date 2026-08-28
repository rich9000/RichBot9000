    <style>
        #output {
            background: #1e1e1e;
            color: #fff;
            font-family: monospace;
            padding: 20px;
            white-space: pre-wrap;
            height: 80vh;
            overflow-y: auto;
        }
        .line {
            margin: 0;
            padding: 2px 0;
        }
        .timestamp {
            color: #888;
            margin-right: 10px;
        }
    </style>

    <div id="output"></div>

    <script>
        // Initialize screen output when the page loads
       
            const output = document.getElementById('output');
            
            // Initial fetch of screen output
            fetch('/api/screen/output', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    output.innerHTML = data.output.map(line => 
                        `<div class="line">
                            <span class="timestamp">${line.timestamp}</span>
                            <span class="content">${line.content}</span>
                        </div>`
                    ).join('');
                    output.scrollTop = output.scrollHeight;
                }
            })
            .catch(error => console.error('Error fetching initial screen output:', error));

            // Set up SSE for real-time updates
            const evtSource = new EventSource('/api/screen/stream', {
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                }
            });

            evtSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                if (data.success) {
                    output.innerHTML = data.output.map(line => 
                        `<div class="line">
                            <span class="timestamp">${line.timestamp}</span>
                            <span class="content">${line.content}</span>
                        </div>`
                    ).join('');
                    output.scrollTop = output.scrollHeight;
                }
            };

            evtSource.onerror = function(err) {
                console.error("EventSource failed:", err);
                evtSource.close();
            };

    </script>
