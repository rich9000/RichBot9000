<h1>SMS Messages</h1>

<div id="messages-container">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>From</th>
            <th>To</th>
            <th>Body</th>
            <th>Direction</th>
            <th>Status</th>
            <th>Timestamp</th>
        </tr>
        </thead>
        <tbody id="messages-table-body">
        <!-- SMS messages will be loaded here via AJAX -->
        </tbody>
    </table>
</div>

    <script>

        function fetchSmsMessages() {
            const url = "{{ route('sms.index') }}"; // Replace with actual URL

            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const tbody = document.getElementById('messages-table-body');
                    tbody.innerHTML = ''; // Clear existing data

                    data.forEach(message => {
                        const userName = message.user ? message.user.name : 'N/A';
                        const status = message.status || '-';
                        const row = `
                <tr>
                    <td>${message.id}</td>
                    <td>${userName}</td>
                    <td>${message.from_number}</td>
                    <td>${message.to_number}</td>
                    <td>${message.body}</td>
                    <td>${message.direction}</td>
                    <td>${status}</td>
                    <td>${new Date(message.created_at).toLocaleString()}</td>
                </tr>
            `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Failed to fetch SMS messages:', error);
                });
        }

        // Fetch SMS messages on page load
        fetchSmsMessages();

            // Optionally, set up polling to refresh messages periodically
            // setInterval(fetchSmsMessages, 60000); // Refresh every 60 seconds

    </script>
