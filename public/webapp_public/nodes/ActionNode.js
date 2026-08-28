class ActionNode extends BaseNode {
    static nodeTypes = {
        say: {
            icon: 'fa-comment',
            name: 'Say',
            color: '#007bff',
            description: 'Speak a message'
        },
        play: {
            icon: 'fa-play',
            name: 'Play',
            color: '#28a745',
            description: 'Play an audio file'
        },
        assistant: {
            icon: 'fa-robot',
            name: 'Assistant',
            color: '#6f42c1',
            description: 'Use an AI assistant'
        },
        assistantTool: {
            icon: 'fa-tools',
            name: 'Assistant Tool',
            color: '#6f42c1',
            description: 'Execute an assistant tool'
        },
        sms: {
            icon: 'fa-sms',
            name: 'SMS',
            color: '#17a2b8',
            description: 'Send an SMS message'
        },
        email: {
            icon: 'fa-envelope',
            name: 'Email',
            color: '#17a2b8',
            description: 'Send an email'
        },
        pipeline: {
            icon: 'fa-project-diagram',
            name: 'Pipeline',
            color: '#20c997'
        },
        phoneTree: {
            icon: 'fa-sitemap',
            name: 'Phone Tree',
            color: '#20c997'
        },
        survey: {
            icon: 'fa-poll',
            name: 'Survey',
            color: '#20c997'
        },
        script: {
            icon: 'fa-code',
            name: 'Script',
            color: '#fd7e14',
            description: 'Execute a script'
        },
        hangup: {
            icon: 'fa-phone-slash',
            name: 'Hang Up',
            color: '#20c997'
        },
        voiceMail: {
            icon: 'fa-microphone',
            name: 'Voice Mail',
            color: '#20c997'
        },
        transfer: {
            icon: 'fa-exchange-alt',
            name: 'Phone Transfer',
            color: '#20c997'
        },
        route: {
            icon: 'fa-random',
            name: 'Route to Node',
            color: '#20c997'
        },
        conversationPath: {
            icon: 'fa-project-diagram',
            name: 'Route to Path',
            color: '#20c997'
        },
        websocket: {
            icon: 'fa-plug',
            name: 'WebSocket',
            color: '#20c997'
        },
        monitorCall: {
            icon: 'fa-phone',
            name: 'Monitor Call',
            color: '#20c997'
        }
    };

    constructor(data = {}) {
        super(data);
        this.type = 'action';
        this.icon = 'fa-play';
        this.name = 'Action';
        this.color = '#20c997';
        this.description = 'Perform an action in the conversation';
        this.subtype = data.subtype || '';
        // Ensure content is always an object
        if (!this.content || typeof this.content !== 'object' || Array.isArray(this.content)) {
            this.content = {};
        }

        // Set icon, name, and color based on subtype
        const nodeType = ActionNode.nodeTypes[this.subtype] || {};
        this.icon = nodeType.icon || 'fa-circle';
        this.name = nodeType.name || 'Action';
        this.color = nodeType.color || '#20c997';
    }

    getNodeInfo() {
        switch (this.subtype) {
            case 'say':
                return this.content.say_text ? 
                    `Text: "${this.content.say_text.substring(0, 30)}${this.content.say_text.length > 30 ? '...' : ''}"${this.content.voice ? `<br>Voice: ${this.content.voice}` : ''}` : 
                    'No text set';
            
            case 'play':
                if (this.content?.audioFileId) {
                    const audioFile = window.pathBuilder.audioFiles?.find(a => a.id === parseInt(this.content.audioFileId));
                    return audioFile ? 
                        `Audio: ${audioFile.name}${this.content.loopCount ? `<br>Loop: ${this.content.loopCount}` : ''}` : 
                        'Audio file not found';
                } else if (this.content?.audioUrl) {
                    return `URL: ${this.content.audioUrl}${this.content.loopCount ? `<br>Loop: ${this.content.loopCount}` : ''}`;
                }
                return 'No audio selected';
            
            case 'assistant':
                const assistantId = this.content.assistantId || this.content.assistant_id;
                if (!assistantId) return 'No assistant selected';
                const assistant = window.pathBuilder.assistants?.find(a => a.id === parseInt(assistantId));
                return assistant ? 
                    `Assistant: ${assistant.name}${this.content.prompt ? '<br>Has prompt' : ''}` : 
                    'No assistant selected';
            
            case 'pipeline':
                const pipeline = window.pathBuilder.pipelines?.find(p => p.id === parseInt(this.content?.pipelineId));
                return pipeline ? `Pipeline: ${pipeline.name}` : 'No pipeline selected';
            
            case 'phoneTree':
                const phoneTree = window.pathBuilder.phoneTrees?.find(p => p.id === this.content.phoneTreeId);
                return phoneTree ? `Phone Tree: ${phoneTree.name}` : 'No phone tree selected';
            
            case 'survey':
                const survey = window.pathBuilder.surveys?.find(s => s.id === this.content.surveyId);
                switch (this.content.surveyType) {
                    case 'phone_tree_survey':
                        const tree = window.pathBuilder.phoneTrees?.find(p => p.id === this.content.phoneTreeId);
                        return tree ? `Phone Tree Survey: ${tree.name}` : 'Select phone tree';
                    case 'ask_and_wait':
                        return this.content.question ? 
                            `Question: "${this.content.question.substring(0, 30)}${this.content.question.length > 30 ? '...' : ''}"` : 
                            'No question set';
                    case 'survey_assistant':
                        const surveyAssistant = window.pathBuilder.assistants?.find(a => a.id === this.content.assistantId);
                        return surveyAssistant ? `Survey Assistant: ${surveyAssistant.name}` : 'No assistant selected';
                    default:
                        return survey ? `Survey: ${survey.title}` : 'No survey selected';
                }
            
            case 'script':
                const script = window.pathBuilder.scripts?.find(s => s.id === this.content.scriptId);
                return script ? `Script: ${script.name}` : 'No script selected';
            
            case 'voiceMail':
                return this.content.phoneNumber ? 
                    `Voice Mail: ${this.content.phoneNumber}` : 
                    'No phone number set';
            
            case 'transfer':
                return this.content.phoneNumber ? 
                    `Transfer to: ${this.content.phoneNumber}` : 
                    'No phone number set';
            
            case 'route':
                if (this.content.targetNode !== null && this.content.targetNode !== undefined) {
                    const targetNode = window.pathBuilder.selectedPath.nodes[this.content.targetNode];
                    if (targetNode) {
                        return `Route to: ${window.pathBuilder.nodeTypes[targetNode.type][targetNode.subtype].name}`;
                    }
                }
                return 'No target node selected';
            
            case 'conversationPath':
                const pathsArg = arguments[0];
                const paths = Array.isArray(pathsArg) ? pathsArg : window.pathBuilder.paths;
                const targetPath = paths.find(p => p.id === parseInt(this.content.targetPathId));
                return targetPath ? `Path: ${targetPath.name}` : 'No target path selected';
            
            case 'websocket':
                return this.content.wsUrl ? 
                    `WebSocket: ${this.content.wsUrl}` : 
                    'No WebSocket URL set';
            
            case 'hangup':
                return this.content.reason ? 
                    `Reason: ${this.content.reason}` : 
                    'No reason set';
            
            case 'monitorCall':
                const monitorAssistantId = this.content.assistantId;
                if (!monitorAssistantId) return 'No assistant selected';
                const monitorAssistant = window.pathBuilder.assistants?.find(a => a.id === parseInt(monitorAssistantId));
                return monitorAssistant ? 
                    `Assistant: ${monitorAssistant.name}${this.content.startInteractive ? '<br>Interactive' : ''}${this.content.recordAudio ? '<br>Recording' : ''}${this.content.transcribeAudio ? '<br>Transcribing' : ''}` : 
                    'No assistant selected';
            
            default:
                return '';
        }
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        // If not overridden by a subclass, render a generic settings grid and Save button
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">No custom settings for this action.</div>
                </div>
            </div>
            ${super.getSettingsFormTemplate(nodeIndex, context)}
        `;
    }

    validate() {
        switch (this.subtype) {
            case 'say':
                return !!this.content.say_text;
            
            case 'play':
                return !!(this.content.audioFileId || this.content.audioUrl);
            
            case 'assistant':
                return !!(this.content.assistantId || this.content.assistant_id);
            
            case 'pipeline':
                return !!this.content.pipelineId;
            
            case 'phoneTree':
                return !!this.content.phoneTreeId;
            
            case 'survey':
                return !!this.content.surveyId;
            
            case 'script':
                return !!this.content.scriptId;
            
            case 'voiceMail':
            case 'transfer':
                return !!this.content.phoneNumber;
            
            case 'route':
                return this.content.targetNode !== null && this.content.targetNode !== undefined;
            
            case 'conversationPath':
                return !!this.content.targetPathId;
            
            case 'websocket':
                return !!this.content.wsUrl;
            
            case 'hangup':
                return true; // Hangup is always valid
            
            case 'monitorCall':
                return !!this.content.assistantId;
            
            default:
                return false;
        }
    }
}

window.ActionNode = ActionNode;

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ActionNode;
} 