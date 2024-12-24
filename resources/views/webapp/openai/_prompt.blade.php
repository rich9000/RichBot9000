<h3 style="">Open AI API Prompt</h3>
<style>
    .hidden { display: none; }
    .user-message { background-color: #e1f5fe; padding: 10px; border-radius: 5px; margin: 5px;
        display: block;
        text-align: right;
        max-width: 60vw;
        unicode-bidi: embed;
        font-family: monospace;
        white-space: pre; }
    .assistant-message {
        max-width: 60vw;
        text-align: left;
            display: block;
            unicode-bidi: embed;
            font-family: monospace;
            white-space: pre;

        background-color: #f3e5f5; padding: 10px; border-radius: 5px; margin: 5px;

    }
    #chatBox { border: 1px solid #ddd; padding: 10px; max-height: 300px; overflow-y: auto; margin-bottom: 10px; }
    #messageSection { margin-top: 20px; }
</style>
<style>
    /* File Tree Styles */
    #file-tree-container ul {
        list-style-type: none;
        padding-left: 20px;
    }
    #file-tree-container li::before {
        content: '📂 ';
    }
    #file-tree-container li.file::before {
        content: '📄 ';
    }
    #file-tree-container .nested {
        display: none;
    }
    #file-tree-container .active {
        display: block;
    }
    #file-tree-container .caret {
        cursor: pointer;
        user-select: none;
    }
    #file-tree-container .caret::before {
        content: "▶ ";
        color: black;
        display: inline-block;
        margin-right: 6px;
    }
    #file-tree-container .caret-down::before {
        transform: rotate(90deg);
    }
    /* Style for checkboxes */
    #file-tree-container input[type="checkbox"] {
        margin-right: 5px;
    }
</style>



<div id="assistantSelectionModal" class=" card">
    <div class="card-body">
        <h2 style="display: inline-block;">Select an Assistant</h2>
        <select id="assistant-select" class="form-control">
            <option value="">Select an assistant</option>
        </select>

        <h3>Initial Instructions</h3>
        <textarea id="initialInstructions" class="form-control" rows="4">You are a helpful assistant. Help the user with their request.
        </textarea>

        <h3>File Tree Select</h3>
        <div id="file-tree-container">

        </div>

        <button id="confirmAssistantButton" class="btn btn-primary">Confirm</button>
    </div>
</div>
<div id="messageSection" class="hidden">
    <div id="displayAssistant"></div>

    <div id="chatBox"></div>
    <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." />
    <button id="sendMessageButton" class="btn btn-primary">Send Message</button>
    <button id="getMessageButton" class="btn btn-primary">Get Messages</button>
</div>

<div class="row">

    <div class="card col-6 p-0 m-2">
        <div class="card-header" >
           prompt_display
        </div>
        <div class="card-body" id="prompt_display">
        </div>
    </div>
    <div class="card col-4 p-0 m-2">
        <div class="card-header" >
            Thread Info - <button id="updateThreadInfoButton" class="btn btn-secondary">Update Thread Info</button>
        </div>

        <div id="threadInfo"></div>


        </div>
    </div>

</div>


<!-- Add this inside your _prompt.blade.php -->



<script>

    function displayThreadInfo(runs) {
        const threadInfoDiv = document.getElementById('threadInfo');
        threadInfoDiv.innerHTML = ''; // Clear previous content

        runs.forEach(run => {
            const runDiv = document.createElement('div');
            runDiv.innerHTML = `
            <strong>Run ID:</strong> ${run.id}<br>
            <strong>Status:</strong> ${run.status}<br>
            <strong>Required Action:</strong> ${run.required_action ? JSON.stringify(run.required_action) : 'None'}
            <br/><strong>Last Error:</strong> ${run.last_error ? run.last_error.message : 'None'}
            <hr>
        `;
            threadInfoDiv.appendChild(runDiv);
        });
    }

    document.getElementById('updateThreadInfoButton').addEventListener('click', () => {
        const threadId = appState.current_thread;
        if (threadId) {
            fetch('/api/openai/thread-info?thread_id=' + threadId, {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.runs) {
                        displayThreadInfo(data.runs);
                    } else {
                        alert('Failed to get thread info.');
                    }
                })
                .catch(err => {
                    console.error('Failed to fetch thread info:', err);
                    alert('An error occurred while fetching thread info.');
                });
        } else {
            alert('No current thread.');
        }
    });



    let lastFetchedContent = '';

    function fetchContent() {
        const fileName = '/richbotdisplay/displays/prompt_display.html';

        if (fileName) {
            fetch(fileName)
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(data => {
                    // Check if the fetched content is different from the last fetched content
                    if (data === lastFetchedContent) {
                        console.log('Content has not changed. No update needed.');
                        return; // Exit the function early since there's no change
                    }

                    // Update the last fetched content
                    lastFetchedContent = data;

                    console.log('Fetched new content:', data);

                    // Create a temporary container to hold the fetched HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;

                    // Get the target content area
                    const contentArea = document.getElementById('prompt_display');

                    if (!contentArea) {
                        console.error('Element with id "prompt_display" not found.');
                        return;
                    }

                    // Clear existing content by removing all child nodes
                    while (contentArea.firstChild) {
                        contentArea.removeChild(contentArea.firstChild);
                    }

                    // Append the fetched content to the contentArea
                    while (tempDiv.firstChild) {
                        contentArea.appendChild(tempDiv.firstChild);
                    }

                    // Execute any script tags found in the fetched content
                    const scripts = contentArea.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');

                        // Copy all attributes from the old script to the new one
                        Array.from(oldScript.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });

                        // Copy the script content
                        newScript.textContent = oldScript.textContent;

                        // Replace the old script with the new one to execute it
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                });
        }
    }


    function fetchContentnewOld() {
        const fileName = '/richbotdisplay/displays/prompt_display.html';

        if (fileName) {
            fetch(fileName)
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(data => {
                    console.log(data);

                    // Create a temporary container to hold the fetched HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;

                    // Append the fetched content to the contentArea
                    const contentArea = document.getElementById('prompt_display');
                    while (tempDiv.firstChild) {
                        contentArea.appendChild(tempDiv.firstChild);
                    }

                    // Execute any script tags found in the fetched content
                    const scripts = tempDiv.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        // Copy attributes like src, type, etc.
                        Array.from(oldScript.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        newScript.textContent = oldScript.textContent;
                        document.body.appendChild(newScript);
                        // Optionally remove the old script tag
                        oldScript.parentNode.removeChild(oldScript);
                    });
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                });
        }
    }

    function fetchContentOld() {

        const fileName = '/richbotdisplay/displays/prompt_display';

        if (fileName) {
            fetch(fileName)
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(data => {
                    console.log(data);
                    document.getElementById('contentArea').innerHTML = data;
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                });
        }
    }

    setInterval(fetchContent, 10000);
    setInterval(() => {
        document.getElementById('updateThreadInfoButton').click();
    }, 30000);

    fetchContent();



</script>















<script>


    loadAssistants();
    loadAssistantFiles();














    // If an assistant is selected and a thread exists, show the messaging UI
    if (appState.current_assistant && appState.current_thread) {
        document.getElementById('messageSection').classList.remove('hidden');
        document.getElementById('assistantSelectionModal').classList.add('hidden');
        document.getElementById('displayAssistant').innerHTML = `Selected Assistant: ${appState.current_assistant_name} (ID: ${appState.current_assistant}) (Thread ID: ${appState.current_thread})` + ' <button id="resetConversationButton" class="btn btn-danger">Reset Conversation</button>';
    }






    document.getElementById('confirmAssistantButton').addEventListener('click', () => {

        const files = Array.from(document.querySelectorAll('input[name="files[]"]:checked')).map(el => el.value);
        console.log(files);



        const selectElement = document.getElementById('assistant-select');

        const assistantId = document.getElementById('assistant-select').value;
        const assistantName = document.getElementById('assistant-select').options[selectElement.selectedIndex].text;

        const instructions = document.getElementById('initialInstructions').value;

       // const files = document.getElementById('files').value;

        appState.current_assistant = assistantId;
        appState.current_assistant_name = assistantName;
        appState.current_assistant_files = files;

        localStorage.setItem('app_state', JSON.stringify(appState));

        const requestData = {
            files: files, // You can populate this with file selections if needed
            assistant: assistantId,
            instructions: instructions,
        };

        createThread(requestData)
            .then(data => {
                console.log('Thread created successfully:', data);
            })
            .catch(error => {
                console.error('Error creating thread:', error);
            });

        document.getElementById('displayAssistant').innerHTML = `Selected Assistant: ${appState.current_assistant_name} (ID: ${appState.current_assistant}) (Thread ID: ${appState.current_thread})` + ' <button id="resetConversationButton" class="btn btn-danger">Reset Conversation</button>';

        //document.getElementById('displayAssistant').textContent = `Selected Assistant: ${appState.current_assistant_name} (ID: ${appState.current_assistant}) (Thread ID: ${appState.current_thread})`;
        document.getElementById('messageSection').classList.remove('hidden');
        document.getElementById('assistantSelectionModal').classList.add('hidden');

    });
    function createThread(requestData) {

        return fetch('/api/openai/create-thread', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
            .then(response => response.json())


            .then(data => {


                console.log('it got here');

                appState.current_thread = data.thread_id;
                appState.threads.push(data.thread_id);
                localStorage.setItem('app_state', JSON.stringify(appState));

                return data;
            })
            .catch(err => Promise.reject(err));
    }



    // Function to send a message in the current thread
    document.getElementById('sendMessageButton').addEventListener('click', () => {
        const message = document.getElementById('messageInput').value;

        if (message && appState.current_thread) {


            const requestData = {
                thread_id: appState.current_thread,
                prompt: message,
                assistant: appState.current_assistant,
            };

            sendMessage(requestData).then(response => {
                // Append the message to the chat UI
                appendMessageToChat('user', message);
                appendMessageToChat('assistant', response.message);
            }).catch(err => {
                console.error('Failed to send message:', err);
            });
        }
    });


    function sendMessage(requestData) {
        const sendRequestBtn = document.getElementById('sendMessageButton');
        sendRequestBtn.classList.add('btn-loading');
        sendRequestBtn.innerHTML = 'Processing...';

        fetch('/api/openai/send-message', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
            .then(response => response.json())
            .then(data => {
                sendRequestBtn.classList.remove('btn-loading');
                sendRequestBtn.innerHTML = 'Send Request';

                console.log(data);
                displayMessages(data.messages);

                return data;
            })
            .catch(() => {
                alert('Failed to send the message.');
            });
    }



    // Function to send a message in the current thread
    document.getElementById('getMessageButton').addEventListener('click', () => {


        if (appState.current_thread) {


                thread_id = appState.current_thread;



            getMessage(thread_id).then(response => {
                // Append the message to the chat UI
                //appendMessageToChat('user', message);
                //appendMessageToChat('assistant', response.message);
            }).catch(err => {
                console.error('Failed to send message:', err);
            });
        }
    });


    document.getElementById('getMessageButton').click();



    function getMessage(thread_id) {

        const getMessageBtn = document.getElementById('getMessageButton');
        getMessageBtn.classList.add('btn-loading');
        getMessageBtn.innerHTML = 'Loading...';

        fetch('/api/openai/get-updates/' + thread_id, {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },

        })
            .then(response => response.json())
            .then(data => {
                getMessageBtn.classList.remove('btn-loading');
                getMessageBtn.innerHTML = 'Get Messages';

                console.log(data);
                displayMessages(data.messages);

                return data;
            })
            .catch(() => {
                alert('Failed to send the message.');
            });
    }






    // Display the messages in the chat box
    function displayMessages(messages) {
        const chatBox = document.getElementById('chatBox');
        chatBox.innerHTML = ''; // Clear previous messages

        messages.forEach(message => {
            const sender = message.role === 'assistant' ? 'assistant' : 'user';
            const messageText = message.content[0].text.value; // Access message content

            appendMessageToChat(sender, messageText);
        });
    }

    function appendMessageToChat(sender, message) {
        const chatBox = document.getElementById('chatBox');
        const messageDiv = document.createElement('div');
        messageDiv.classList.add(sender === 'user' ? 'user-message' : 'assistant-message');
        messageDiv.innerHTML = message;
        chatBox.appendChild(messageDiv);
    }

    // New Functionality: Reset the current thread and assistant
    document.getElementById('resetConversationButton').addEventListener('click', () => {
        // Confirm the reset action
        if (confirm('Are you sure you want to reset the conversation? This will delete the current thread and assistant.')) {
            // Reset appState properties
            appState.current_thread = null;
            appState.current_assistant = null;
            appState.current_assistant_name = null;

            localStorage.setItem('app_state', JSON.stringify(appState));

            // Clear the chat box
            document.getElementById('chatBox').innerHTML = '';

            // Show the assistant selection modal
            document.getElementById('displayAssistant').innerHTML = '';

            //document.getElementById('displayAssistant').textContent = `Selected Assistant: ${appState.current_assistant_name} (ID: ${appState.current_assistant}) (Thread ID: ${appState.current_thread})`;
            document.getElementById('messageSection').classList.add('hidden');
            document.getElementById('assistantSelectionModal').classList.remove('hidden');

        }
    });

</script>
