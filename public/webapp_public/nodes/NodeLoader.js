// NodeLoader.js - Handles dynamic loading of all node modules

const NODE_TYPES = {
    action: [
        'SayAction',
        'PlayAction',
        'AssistantAction',
        'PipelineAction',
        'PhoneTreeAction',
        'SurveyAction',
        'HangupAction',
        'VoiceMailAction',
        'TransferAction',
        'RouteAction',
        'ConversationPathAction',
        'ScriptAction',
        'WebsocketAction',
        'SMSAction',
        'EmailAction',
        'WaitAction',
        'MonitorCallAction',
        'AssistantToolAction'
    ],
    data: [
        'OutageCheckData',
        'CustomerLookupData',
        'CustomData',
        'ContextAssistantData',
        'APIData',
        'FileData',
        'AssistantTool'
    ],
    decision: [
        'UserDecision',
        'AssistantDecision',
        'ConditionalDecision',
        'AssistantToolDecision'
    ],
    entry: [
        'RootEntry',
        'ChatEntry',
        'TwilioInboundEntry',
        'TwilioOutboundEntry'
    ]
};

class NodeLoader {
    constructor() {
        this.loadedModules = new Map();
    }

    async loadAllNodes() {
        try {
            // Load base node first
            await this.loadModule('BaseNode');

            // Load all node types
            for (const [type, nodes] of Object.entries(NODE_TYPES)) {
                for (const node of nodes) {
                    await this.loadModule(`${node}Node`);
                }
            }

            // Load NodeFactory last
            await this.loadModule('NodeFactory');

            return true;
        } catch (error) {
            console.error('Error loading nodes:', error);
            return false;
        }
    }

    async loadModule(moduleName) {
        if (this.loadedModules.has(moduleName)) {
            return this.loadedModules.get(moduleName);
        }

        try {
            const module = await import(`./${moduleName}.js`);
            this.loadedModules.set(moduleName, module);
            return module;
        } catch (error) {
            console.error(`Error loading module ${moduleName}:`, error);
            throw error;
        }
    }

    getLoadedModule(moduleName) {
        return this.loadedModules.get(moduleName);
    }
}

export default new NodeLoader(); 