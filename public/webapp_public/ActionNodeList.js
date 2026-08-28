console.log('ActionNodeList.js loading', window.ActionNode);

class ActionNodeList {
    constructor(nodes = []) {
        // Each item should be an ActionNode instance or plain object to be normalized
        this.nodes = nodes.map(node => this._normalizeNode(node));
    }

    _normalizeNode(node) {
        // Use NodeFactory to always get the correct subclass (e.g., SayActionNode)
        if (node && node.constructor && node.constructor.name.endsWith('ActionNode')) return node;
        if (typeof window.NodeFactory?.createNode === 'function') {
            return window.NodeFactory.createNode(node);
        }
        // Fallback: just return the object
        return node;
    }

    add(node) {
        this.nodes.push(this._normalizeNode(node));
    }

    remove(index) {
        if (index >= 0 && index < this.nodes.length) {
            this.nodes.splice(index, 1);
        }
    }

    move(fromIndex, toIndex) {
        if (
            fromIndex >= 0 && fromIndex < this.nodes.length &&
            toIndex >= 0 && toIndex < this.nodes.length &&
            fromIndex !== toIndex
        ) {
            const [moved] = this.nodes.splice(fromIndex, 1);
            this.nodes.splice(toIndex, 0, moved);
        }
    }

    get length() {
        return this.nodes.length;
    }

    get(index) {
        return this.nodes[index];
    }

    toJSON() {
        return this.nodes.map(node => (typeof node.toJSON === 'function' ? node.toJSON() : node));
    }

    static fromJSON(json) {
        return new ActionNodeList(json);
    }

    // Render the action nodes (for use in DecisionNode rendering)
    render(context, parentNodeIndex) {
        // context: builder context (pipelines, audioFiles, etc.)
        // parentNodeIndex: index of the parent decision node in the main flow
        return this.nodes.map((node, idx) => {
            return node.actionNodeListRowTemplate(idx, context);
        }).join('');
    }
}



console.error('ActionNodeList.js loading');
// Attach to window for global access if needed
window.ActionNodeList = ActionNodeList; 

console.log('ActionNodeList.js loaded', window.ActionNodeList);

// Add styles for bigger number and smaller arrows
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .decision-action-item .badge {
            font-size: 1.5rem;
            padding: 0.5em 1em;
        }
        .decision-action-item .move-up,
        .decision-action-item .move-down {
            font-size: 0.7rem;
            padding: 2px 4px;
            width: 22px;
            height: 22px;
            min-width: 22px;
            min-height: 22px;
            line-height: 1;
        }
        .decision-action-item .move-up i,
        .decision-action-item .move-down i {
            font-size: 0.8rem;
        }
    `;
    document.head.appendChild(style);
})();

// Helper to attach edit event handler to all .edit-toggle buttons
window.attachDecisionNodeEditHandlers = function() {
    document.querySelectorAll('.edit-toggle').forEach(btn => {
        btn.onclick = function(e) {
            e.stopPropagation();
            const card = btn.closest('.conversation-node');
            if (!card) return;
            const info = card.querySelector('.node-detailed-info');
            const form = card.querySelector('.node-edit-form');
            if (info && form) {
                info.style.display = 'none';
                form.style.display = 'block';
            }
        };
    });
};
