<!-- Conversations Table -->
<table id="conversationsTable" class="display table table-bordered table-striped">
    <thead>
    <tr>
        <th>Title</th>
        <th>Type</th>
        <th>Assistant/Pipeline</th>
        <th>Last Message</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- View Conversation Modal -->
<div class="modal fade" id="conversationModal" tabindex="-1" aria-labelledby="conversationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="conversationModalLabel">Conversation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="conversationMessages"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    // Initialize DataTable
    function loadConversationsDataTable() {
        const conversationsTable = document.querySelector('#conversationsTable');
        
        const dataTable = $(conversationsTable).DataTable({
            ajax: {
                url: '/api/conversations',
                headers: {
                    'Authorization': 'Bearer ' + appState.apiToken,
                    'Accept': 'application/json'
                },
                dataSrc: function(json) {
                    return json.conversations.map(conversation => ({
                        title: conversation.title || 'Untitled',
                        type: capitalizeFirstLetter(conversation.type || ''),
                        assistant: conversation.assistant_name || conversation.pipeline_name || 'N/A',
                        last_message: conversation.last_message ? formatMessage(conversation.last_message) : 'No messages',
                        status: `<span class="badge bg-${getStatusBadgeClass(conversation.status)}">${conversation.status}</span>`,
                        created_at: new Date(conversation.created_at).toLocaleString(),
                        actions: `
                            <button class="btn btn-info btn-sm view-conversation-btn" data-conversation-id="${conversation.id}">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-danger btn-sm delete-conversation-btn" data-conversation-id="${conversation.id}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        `
                    }));
                }
            },
            columns: [
                { data: 'title' },
                { data: 'type' },
                { data: 'assistant' },
                { data: 'last_message' },
                { data: 'status' },
                { data: 'created_at' },
                { data: 'actions', orderable: false }
            ],
            order: [[5, 'desc']], // Sort by created_at by default
            destroy: true,
            searching: true,
            ordering: true,
            paging: true
        });

        // Handle view conversation button click
        $('#conversationsTable').on('click', '.view-conversation-btn', function() {
            const conversationId = $(this).data('conversation-id');
            openConversationModal(conversationId);
        });

        // Handle delete conversation button click
        $('#conversationsTable').on('click', '.delete-conversation-btn', function() {
            const conversationId = $(this).data('conversation-id');
            if (confirm('Are you sure you want to delete this conversation?')) {
                deleteConversation(conversationId);
            }
        });
    }

    // Helper Functions
    function formatMessage(message) {
        const content = message.content.length > 50 
            ? `${message.content.substring(0, 50)}...` 
            : message.content;
        return `
            <div class="message-preview">
                <small class="text-muted">${message.role}:</small>
                <div>${content}</div>
                <small class="text-muted">${new Date(message.created_at).toLocaleString()}</small>
            </div>
        `;
    }

    function getStatusBadgeClass(status) {
        switch(status.toLowerCase()) {
            case 'active': return 'success';
            case 'completed': return 'primary';
            case 'error': return 'danger';
            default: return 'secondary';
        }
    }

    function openConversationModal(conversationId) {
        fetch(`/api/conversations/${conversationId}/messages`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const messagesHtml = data.messages.map(message => `
                <div class="message ${message.role}">
                    <div class="message-header">
                        <strong>${capitalizeFirstLetter(message.role)}</strong>
                        <small class="text-muted">${new Date(message.created_at).toLocaleString()}</small>
                    </div>
                    <div class="message-content">${message.content}</div>
                </div>
            `).join('');
            
            document.getElementById('conversationMessages').innerHTML = messagesHtml;
            new bootstrap.Modal(document.getElementById('conversationModal')).show();
        })
        .catch(err => {
            console.error(err);
            alert('Error loading conversation messages.');
        });
    }

    function deleteConversation(conversationId) {
        fetch(`/api/conversations/${conversationId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(() => {
            alert('Conversation deleted successfully.');
            loadConversationsDataTable();
        })
        .catch(err => {
            console.error(err);
            alert('Error deleting conversation.');
        });
    }

    // Initialize on page load
    loadConversationsDataTable();
</script>

<style>
.message {
    margin-bottom: 1rem;
    padding: 0.5rem;
    border-radius: 4px;
}

.message.user {
    background-color: #f8f9fa;
}

.message.assistant {
    background-color: #e9ecef;
}

.message.system {
    background-color: #fff3cd;
}

.message-header {
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
}

.message-content {
    white-space: pre-wrap;
}

.message-preview {
    font-size: 0.9rem;
}
</style> 