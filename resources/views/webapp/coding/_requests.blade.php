<div id="requests">
    <h1>Pending Requests</h1>
    <ul id="request-list"></ul>
</div>

<script>
    const loadRequests = async (url, elementId) => {
        try {
            const response = await fetch(url);
            const requests = await response.json();

            const listElement = document.getElementById(elementId);
            listElement.innerHTML = '';

            requests.forEach(request => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <strong>${request.command || request.sql_query}</strong>
                    <p>Status: ${request.status}</p>
                    <button onclick="approve('${url}/approve/${request.id}')">Approve</button>
                    <button onclick="reject('${url}/reject/${request.id}')">Reject</button>
                `;
                listElement.appendChild(li);
            });
        } catch (error) {
            console.error('Error loading requests:', error);
        }
    };

    const approve = async (url) => {
        await fetch(url, { method: 'PATCH' });
        alert('Request approved!');
        window.location.reload();
    };

    const reject = async (url) => {
        await fetch(url, { method: 'PATCH' });
        alert('Request rejected!');
        window.location.reload();
    };


        loadRequests('/api/sql-requests', 'request-list');

</script>
