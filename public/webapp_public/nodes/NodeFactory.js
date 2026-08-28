// NodeFactory.js
// Factory for creating node instances by type and subtype

if (typeof window.NodeFactory === 'undefined') {
    window.NodeFactory = class NodeFactory {
        static createNode(data) {
            console.log('NodeFactory.createNode called with:', data);
            if (!data || !data.type) return null;

            try {
                let NodeClass;
                const prefix = data.subtype.charAt(0).toUpperCase() + data.subtype.slice(1);
                
                if (data.type === 'action') {
                    NodeClass = window[`${prefix}ActionNode`];
                } else if (data.type === 'data') {
                    NodeClass = window[`${prefix}DataNode`];
                } else if (data.type === 'decision') {
                    NodeClass = window[`${prefix}DecisionNode`];
                } else if (data.type === 'entry') {
                    NodeClass = window[`${prefix}EntryNode`];
                }

                console.log(`Looking for class: ${prefix}${data.type.charAt(0).toUpperCase() + data.type.slice(1)}Node`);
                console.log('Found class:', NodeClass);

                if (NodeClass) {
                    return new NodeClass(data);
                } else {
                    console.error(`Node class not found for type ${data.type} and subtype ${data.subtype}`);
                }
            } catch (error) {
                console.error(`Error creating node of type ${data.type} and subtype ${data.subtype}:`, error);
            }

            return null;
        }
    };
    console.log('NodeFactory class registered');
} 