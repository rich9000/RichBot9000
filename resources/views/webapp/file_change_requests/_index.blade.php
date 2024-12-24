
    <div class="container">
        <h2>Pending File Change Requests</h2>
        <div id="fileChangeRequests"></div>
    </div>

    <script>



            // Function to fetch pending requests
            function fetchRequests() {
                fetch('/api/file-change-requests', {
                    headers: apiHeaders(),
                })
                    .then(response => response.json())
                    .then(data => renderFileChangeRequests(data));
            }

            // Function to render requests
            function renderFileChangeRequests(requests) {
                const container = document.getElementById('fileChangeRequests');
                container.innerHTML = '';

                requests.forEach(request => {
                    const requestElement = document.createElement('div');
                    requestElement.classList.add('card', 'mb-3');
                    requestElement.innerHTML = `
                <div class="card-body">
                    <h5 class="card-title">File Path: ${request.file_path}</h5>
                    <p><strong>User ID:</strong> ${request.user_id} | <strong>Conversation ID:</strong> ${request.conversation_id}</p>
                    <pre><strong>Original Content:</strong>\n${request.original_content}</pre>
                    <pre><strong>New Content:</strong>\n${request.new_content}</pre>
                    <button class="btn btn-success" onclick="approveRequest(${request.id})">Approve</button>
                    <button class="btn btn-danger" onclick="rejectRequest(${request.id})">Reject</button>
                </div>
            `;
                    container.appendChild(requestElement);
                });
            }

            // Functions to handle approval/rejection
            function approveRequest(id) {
                fetch(`/api/file-change-requests/${id}/approve`, {
                    method: 'POST',
                    headers: { 'Authorization': bearerToken }
                }).then(() => fetchRequests());
            }

            function rejectRequest(id) {
                fetch(`/api/file-change-requests/${id}/reject`, {
                    method: 'POST',
                    headers: { 'Authorization': bearerToken }
                }).then(() => fetchRequests());
            }

            // Fetch requests every 10 seconds
            fetchRequests();
            setInterval(fetchRequests, 10000);

    </script>

