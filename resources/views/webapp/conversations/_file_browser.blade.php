<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Conversation Files</h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="refreshFileBrowser()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <div class="card-body">
        <div class="file-browser-container" style="max-height: 600px; overflow-y: auto;">
            <div id="conversation-list" class="list-group mb-3">
                <!-- Conversations will be loaded here -->
            </div>
            <div id="file-list" class="list-group">
                <!-- Files will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.file-browser-container {
    font-size: 0.9rem;
}
.file-item, .conversation-item {
    cursor: pointer;
    padding: 0.5rem;
    border-bottom: 1px solid #eee;
}
.file-item:hover, .conversation-item:hover {
    background-color: #f8f9fa;
}
.file-item.selected, .conversation-item.selected {
    background-color: #e9ecef;
}
.file-icon {
    margin-right: 0.5rem;
    width: 20px;
    text-align: center;
}
.file-size {
    color: #6c757d;
    font-size: 0.8rem;
}
.file-date {
    color: #6c757d;
    font-size: 0.8rem;
}
</style>

<script>
let currentConversationId = null;

async function loadConversations() {
    try {
        const response = await fetch('/api/conversations', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load conversations');
        
        const conversations = await response.json();
        const container = document.getElementById('conversation-list');
        container.innerHTML = '';
        
        conversations.forEach(conv => {
            const date = new Date(conv.start_time * 1000).toLocaleString();
            const item = document.createElement('div');
            item.className = 'conversation-item';
            item.setAttribute('data-conversation-id', conv.id);
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-folder file-icon"></i>
                        ${conv.title || conv.id}
                    </div>
                    <small class="text-muted">${date}</small>
                </div>
            `;
            item.onclick = () => loadConversationFiles(conv.id);
            container.appendChild(item);
        });
    } catch (error) {
        console.error('Error loading conversations:', error);
    }
}

async function loadConversationFiles(conversationId) {
    try {
        currentConversationId = conversationId;
        const response = await fetch(`/api/conversations/${conversationId}/files`, {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load files');
        
        const data = await response.json();
        const container = document.getElementById('file-list');
        container.innerHTML = '';
        
        // Add directories
        data.directories.forEach(dir => {
            const item = document.createElement('div');
            item.className = 'file-item';
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-folder file-icon"></i>
                    ${dir.name}
                </div>
            `;
            container.appendChild(item);
        });
        
        // Add files
        data.files.forEach(file => {
            const size = formatFileSize(file.size);
            const date = new Date(file.last_modified * 1000).toLocaleString();
            const item = document.createElement('div');
            item.className = 'file-item';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-file file-icon"></i>
                        ${file.name}
                    </div>
                    <div>
                        <span class="file-size me-3">${size}</span>
                        <span class="file-date">${date}</span>
                    </div>
                </div>
            `;
            container.appendChild(item);
        });
        
        // Update selected state
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('selected');
            if (item.getAttribute('data-conversation-id') === conversationId) {
                item.classList.add('selected');
            }
        });
    } catch (error) {
        console.error('Error loading files:', error);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function refreshFileBrowser() {
    if (currentConversationId) {
        loadConversationFiles(currentConversationId);
    } else {
        loadConversations();
    }
}

// Initial load
loadConversations();
</script> 