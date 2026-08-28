class ConversationPath {
    constructor(data = {}) {
        this.id = data.id || null;
        this.name = data.name || '';
        this.description = data.description || '';
        this.nodes = [];
        this.settings = data.settings || {
            maxTurns: 50,
            timeout: 1800,
            language: 'en',
            fallbackLanguage: 'en',
            timeZone: 'UTC',
            recordConversation: true,
            transcribeAudio: true,
            enableProfanityFilter: true,
            maxMessageLength: 1000,
            maxAttachmentSize: 5242880,
            allowedFileTypes: ['audio/*', 'image/*', 'application/pdf'],
            enableTypingIndicator: true,
            enableReadReceipts: true,
            enableUserFeedback: true,
            maxRetries: 3,
            retryDelay: 1000,
            queueTimeout: 300,
            maxQueueSize: 100,
            priorityLevels: ['low', 'medium', 'high', 'urgent'],
            defaultPriority: 'medium'
        };

        // Initialize nodes using NodeFactory
        if (Array.isArray(data.nodes)) {
            this.nodes = data.nodes.map(nodeData => this.createNode(nodeData)).filter(node => node !== null);
        }

        // Ensure there's always a root entry node
        if (!this.nodes.some(node => node.type === 'entry' && node.subtype === 'root')) {
            const rootNode = this.createNode({
                type: 'entry',
                subtype: 'root',
                options: {
                    chat: { enabled: false, welcomeMessage: '' },
                    twilioInbound: { enabled: false, phoneNumber: '' },
                    twilioOutbound: { enabled: false, phoneNumber: '', initialMessage: '' }
                }
            });
            if (rootNode) {
                this.nodes.unshift(rootNode);
            }
        }
    }

    createNode(nodeData) {
        if (!nodeData || !nodeData.type) return null;

        try {
            // Use NodeFactory to create the appropriate node instance
            const node = window.pathBuilder.nodeFactory.createNode(nodeData);
            if (!node) {
                console.error('Failed to create node:', nodeData);
                return null;
            }
            return node;
        } catch (error) {
            console.error('Error creating node:', error);
            return null;
        }
    }

    addNode(nodeData) {
        const node = this.createNode(nodeData);
        if (!node) return null;

        this.nodes.push(node);
        return node;
    }

    removeNode(nodeIndex) {
        if (nodeIndex >= 0 && nodeIndex < this.nodes.length) {
            // Don't allow removing the root entry node
            if (this.nodes[nodeIndex].type === 'entry' && this.nodes[nodeIndex].subtype === 'root') {
                return null;
            }
            return this.nodes.splice(nodeIndex, 1)[0];
        }
        return null;
    }

    moveNode(fromIndex, toIndex) {
        if (fromIndex >= 0 && fromIndex < this.nodes.length &&
            toIndex >= 0 && toIndex < this.nodes.length) {
            // Don't allow moving the root entry node
            if (this.nodes[fromIndex].type === 'entry' && this.nodes[fromIndex].subtype === 'root') {
                return false;
            }
            const node = this.nodes.splice(fromIndex, 1)[0];
            this.nodes.splice(toIndex, 0, node);
            return true;
        }
        return false;
    }

    validate() {
        // Check required fields
        if (!this.name) return false;

        // Check if we have a root entry node
        const hasRootEntry = this.nodes.some(node => 
            node.type === 'entry' && node.subtype === 'root'
        );
        if (!hasRootEntry) return false;

        // Validate each node
        return this.nodes.every(node => {
            // Skip null nodes
            if (!node) return false;

            // Ensure node has required base properties
            if (!node.type || !node.subtype) return false;

            // Validate the node using its own validation method
            return node.validate();
        });
    }

    validateNode(nodeIndex) {
        const node = this.nodes[nodeIndex];
        if (!node) return false;
        return node.validate();
    }

    updateNodeContent(nodeIndex, field, value) {
        const node = this.nodes[nodeIndex];
        if (!node) return false;
        
        try {
            node.updateContent(field, value);
            return true;
        } catch (error) {
            console.error('Error updating node content:', error);
            return false;
        }
    }

    toJSON() {
        return {
            id: this.id,
            name: this.name,
            description: this.description,
            settings: this.settings,
            nodes: this.nodes.map(node => node.toJSON())
        };
    }

    async save() {
        try {
            if (!this.validate()) {
                throw new Error('Invalid conversation path');
            }

            const method = this.id ? 'PUT' : 'POST';
            const url = this.id ? `/api/conversation-paths/${this.id}` : '/api/conversation-paths';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.toJSON())
            });

            if (!response.ok) {
                throw new Error('Failed to save path');
            }

            const savedPath = await response.json();
            this.id = savedPath.id;
            return savedPath;
        } catch (error) {
            console.error('Error saving path:', error);
            throw error;
        }
    }

    static async load(pathId) {
        try {
            const response = await fetch(`/api/conversation-paths/${pathId}`, {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load path');
            }

            const pathData = await response.json();
            return new ConversationPath(pathData);
        } catch (error) {
            console.error('Error loading path:', error);
            throw error;
        }
    }

    static async loadAll() {
        try {
            const response = await fetch('/api/conversation-paths', {
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load paths');
            }

            const pathsData = await response.json();
            return pathsData.map(pathData => new ConversationPath(pathData));
        } catch (error) {
            console.error('Error loading paths:', error);
            throw error;
        }
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConversationPath;
} 