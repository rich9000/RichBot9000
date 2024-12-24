const pollingTasks = {};

// Listen for messages from the main thread
self.onmessage = function(event) {
    const { action, data } = event.data;

    switch(action) {
        case 'registerTask':
            registerPollingTask(data);
            break;
        case 'unregisterTask':
            unregisterPollingTask(data);
            break;
        default:
            self.postMessage({ action: 'error', message: `Unknown action: ${action}` });
    }
};

function registerPollingTask({ id, url, interval, token }) {
    if (pollingTasks[id]) {
        clearInterval(pollingTasks[id].intervalId);
    }

    // Start a new interval for the task
    pollingTasks[id] = {
        intervalId: setInterval(() => fetchData(id, url, token), interval),
        url: url,
        token: token
    };

    fetchData(id, url, token); // Immediate fetch
}

function unregisterPollingTask({ id }) {
    if (pollingTasks[id]) {
        clearInterval(pollingTasks[id].intervalId);
        delete pollingTasks[id];
    }
}

function fetchData(id, url, token) {
    fetch(url, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            self.postMessage({ action: 'dataFetched', id, data });
        })
        .catch(error => {
            self.postMessage({ action: 'error', id, message: `Failed to fetch data from ${url}.` });
        });
}
