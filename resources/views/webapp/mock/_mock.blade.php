<div id="mock-app">
    <div id="mock-app-content">
        <div id="mock-app-content-header">
            <h1>Internet Service Support Conversation Path</h1>
        </div>
        <div id="conversation-path-container">
            <div class="section">
                <h2>Conversation Flow</h2>
                <div class="flow-container">
                    <ol class="flow-list">
                        <li>
                            <strong>Initial Greeting & Issue Identification</strong>
                            <ul>
                                <li>Greet customer</li>
                                <li>Ask about the nature of the call</li>
                                <li>If internet issue, proceed to outage check</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Outage Check</strong>
                            <ul>
                                <li>Check system for active outages</li>
                                <li>If outage exists:
                                    <ul>
                                        <li>Inform customer about outage</li>
                                        <li>Add to outage notification list</li>
                                        <li>End call</li>
                                    </ul>
                                </li>
                                <li>If no outage, proceed to verification</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Customer Verification</strong>
                            <ul>
                                <li>Check if calling from primary phone
                                    <ul>
                                        <li>If yes: Ask about service at address</li>
                                        <li>If no: Ask for customer name
                                            <ul>
                                                <li>Search accounts by name</li>
                                                <li>Ask about service address</li>
                                                <li>Verify address matches</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <strong>Service Information Collection</strong>
                            <ul>
                                <li>Get account number</li>
                                <li>Get service type</li>
                                <li>Get modem/router information</li>
                                <li>Get error messages or symptoms</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Problem Analysis</strong>
                            <ul>
                                <li>AI analyzes collected information</li>
                                <li>Check for common issues:
                                    <ul>
                                        <li>Modem/router issues</li>
                                        <li>Line issues</li>
                                        <li>Account issues</li>
                                        <li>Billing issues</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <strong>Resolution Path</strong>
                            <ul>
                                <li>If AI can identify problem:
                                    <ul>
                                        <li>Provide solution</li>
                                        <li>Schedule technician if needed</li>
                                        <li>Create service ticket</li>
                                    </ul>
                                </li>
                                <li>If broader issue detected:
                                    <ul>
                                        <li>Create outage record</li>
                                        <li>Add customer to notification list</li>
                                        <li>Schedule callback</li>
                                        <li>End call</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <strong>Follow-up</strong>
                            <ul>
                                <li>Schedule callback if needed</li>
                                <li>Send confirmation email/SMS</li>
                                <li>Create service ticket</li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="section">
                <h2>Flowchart</h2>
                <div class="flowchart-container">
                    <pre class="flowchart">
[Start]
   ↓
[Greet Customer]
   ↓
[Check for Outages] → [Outage Exists] → [Add to Notification List] → [End Call]
   ↓
[No Outage]
   ↓
[Verify Customer]
   ↓
[Check Primary Phone] → [Yes] → [Verify Address]
   ↓
[No] → [Get Customer Name] → [Search Accounts] → [Verify Address]
   ↓
[Collect Service Info]
   ↓
[AI Analysis]
   ↓
[Can AI Solve?] → [Yes] → [Provide Solution] → [Create Ticket]
   ↓
[No] → [Broader Issue?] → [Yes] → [Create Outage] → [Add to Notification]
   ↓
[No] → [Schedule Technician]
   ↓
[End Call]
                    </pre>
                </div>
            </div>

            <div class="section">
                <h2>Database Structure</h2>
                <div class="database-container">
                    <div class="table">
                        <h3>conversation_paths</h3>
                        <ul>
                            <li>id</li>
                            <li>title</li>
                            <li>description</li>
                            <li>is_active</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>conversation_steps</h3>
                        <ul>
                            <li>id</li>
                            <li>path_id</li>
                            <li>step_order</li>
                            <li>question</li>
                            <li>type (multiple_choice, text, number, etc.)</li>
                            <li>validation_rules</li>
                            <li>next_step_id</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>conversation_answers</h3>
                        <ul>
                            <li>id</li>
                            <li>step_id</li>
                            <li>answer</li>
                            <li>validation_result</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>conversations</h3>
                        <ul>
                            <li>id</li>
                            <li>path_id</li>
                            <li>customer_id</li>
                            <li>status</li>
                            <li>current_step_id</li>
                            <li>started_at</li>
                            <li>completed_at</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>outages</h3>
                        <ul>
                            <li>id</li>
                            <li>title</li>
                            <li>description</li>
                            <li>affected_area</li>
                            <li>start_time</li>
                            <li>end_time</li>
                            <li>status</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>outage_notifications</h3>
                        <ul>
                            <li>id</li>
                            <li>outage_id</li>
                            <li>customer_id</li>
                            <li>notification_type</li>
                            <li>status</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>service_tickets</h3>
                        <ul>
                            <li>id</li>
                            <li>customer_id</li>
                            <li>conversation_id</li>
                            <li>issue_type</li>
                            <li>description</li>
                            <li>status</li>
                            <li>priority</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>customer_verification_logs</h3>
                        <ul>
                            <li>id</li>
                            <li>conversation_id</li>
                            <li>verification_method</li>
                            <li>verification_result</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>ai_analysis_results</h3>
                        <ul>
                            <li>id</li>
                            <li>conversation_id</li>
                            <li>analysis_type</li>
                            <li>result</li>
                            <li>confidence_score</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>

                    <div class="table">
                        <h3>callback_schedules</h3>
                        <ul>
                            <li>id</li>
                            <li>conversation_id</li>
                            <li>customer_id</li>
                            <li>scheduled_time</li>
                            <li>status</li>
                            <li>created_at</li>
                            <li>updated_at</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Pipeline System Integration</h2>
                <div class="pipeline-container">
                    <h3>How Pipelines Work</h3>
                    <p>Pipelines are a powerful automation system that can be used to manage complex conversation flows. Here's how they work:</p>
                    
                    <div class="pipeline-structure">
                        <h4>Pipeline Structure</h4>
                        <ul>
                            <li><strong>Pipeline</strong>: A container for a sequence of stages
                                <ul>
                                    <li>Name and description</li>
                                    <li>Ordered collection of stages</li>
                                    <li>Can be associated with conversations</li>
                                </ul>
                            </li>
                            <li><strong>Stages</strong>: Individual steps in the pipeline
                                <ul>
                                    <li>Can be of different types (assistant, transform, context)</li>
                                    <li>Have an order in the pipeline</li>
                                    <li>Can have associated tools and assistants</li>
                                    <li>Can have success/failure paths</li>
                                </ul>
                            </li>
                            <li><strong>Tools</strong>: Actions that can be performed at each stage
                                <ul>
                                    <li>Can be associated with stages</li>
                                    <li>Can determine next steps</li>
                                    <li>Can modify conversation flow</li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div class="pipeline-conversation">
                        <h4>Using Pipelines for Conversation Paths</h4>
                        <p>Pipelines can be used to implement the conversation path system in several ways:</p>
                        <ul>
                            <li><strong>Stage-Based Flow</strong>
                                <ul>
                                    <li>Each stage represents a step in the conversation</li>
                                    <li>Stages can have different types of interactions</li>
                                    <li>Tools can be used to validate responses</li>
                                </ul>
                            </li>
                            <li><strong>Dynamic Path Selection</strong>
                                <ul>
                                    <li>Tools can analyze responses and determine next steps</li>
                                    <li>Success/failure paths can lead to different stages</li>
                                    <li>Can handle complex branching logic</li>
                                </ul>
                            </li>
                            <li><strong>Integration with AI</strong>
                                <ul>
                                    <li>Assistants can be assigned to stages</li>
                                    <li>AI can analyze responses and determine next steps</li>
                                    <li>Can handle natural language processing</li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div class="pipeline-implementation">
                        <h4>Implementation Example</h4>
                        <pre class="code-example">
// Example Pipeline Structure for Internet Support
Pipeline: Internet Support Flow
├── Stage 1: Initial Greeting
│   ├── Type: Assistant
│   └── Tools: [Greeting, Issue Identification]
├── Stage 2: Outage Check
│   ├── Type: Transform
│   └── Tools: [Outage Check, Notification]
├── Stage 3: Customer Verification
│   ├── Type: Assistant
│   └── Tools: [Phone Verification, Address Verification]
├── Stage 4: Service Information
│   ├── Type: Assistant
│   └── Tools: [Account Lookup, Service Details]
└── Stage 5: Problem Resolution
    ├── Type: Assistant
    └── Tools: [Issue Analysis, Solution Provider]</pre>
                    </div>

                    <div class="pipeline-benefits">
                        <h4>Benefits of Using Pipelines</h4>
                        <ul>
                            <li><strong>Flexibility</strong>: Easy to modify and extend conversation flows</li>
                            <li><strong>Reusability</strong>: Pipeline components can be reused across different flows</li>
                            <li><strong>Maintainability</strong>: Clear structure makes it easy to update and debug</li>
                            <li><strong>Scalability</strong>: Can handle complex conversation paths with multiple branches</li>
                            <li><strong>Integration</strong>: Can work with existing AI and tool systems</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Conversation Path Builder</h2>
                <div id="conversation-path-builder"></div>
            </div>

            <div class="section">
                <h2>Phone Tree Builder</h2>
                <div id="phone-tree-builder"></div>
            </div>

            <div class="section">
                <h2>Pipeline Builder</h2>
                <div id="pipeline-builder"></div>
            </div>

            <script>
                // Initialize the conversation path builder
                const pathBuilder = new ConversationPathBuilder('conversation-path-builder');
                // Initialize the phone tree builder
                const phoneTreeBuilder = new PhoneTreePath('phone-tree-builder');
                // Initialize the pipeline builder
                const pipelineBuilder = new PipelineBuilder('pipeline-builder');
            </script>
        </div>
    </div>
</div>

<style>
    .section {
        margin-bottom: 2rem;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .flow-container {
        margin: 1rem 0;
    }

    .flow-list {
        list-style-type: decimal;
        padding-left: 2rem;
    }

    .flow-list li {
        margin-bottom: 1rem;
    }

    .flow-list ul {
        list-style-type: disc;
        padding-left: 2rem;
        margin-top: 0.5rem;
    }

    .flowchart-container {
        margin: 1rem 0;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 4px;
        overflow-x: auto;
    }

    .flowchart {
        font-family: monospace;
        white-space: pre;
        margin: 0;
    }

    .database-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .table {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }

    .table h3 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.5rem;
    }

    .table ul {
        list-style-type: none;
        padding-left: 0;
        margin: 0;
    }

    .table li {
        padding: 0.25rem 0;
        border-bottom: 1px solid #dee2e6;
    }

    .table li:last-child {
        border-bottom: none;
    }

    .pipeline-container {
        padding: 1rem;
    }

    .pipeline-structure,
    .pipeline-conversation,
    .pipeline-implementation,
    .pipeline-benefits {
        margin-bottom: 2rem;
    }

    .code-example {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 4px;
        font-family: monospace;
        white-space: pre;
        overflow-x: auto;
    }

    .pipeline-container h3 {
        color: #333;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .pipeline-container h4 {
        color: #444;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .pipeline-container ul {
        list-style-type: disc;
        padding-left: 2rem;
    }

    .pipeline-container ul ul {
        list-style-type: circle;
    }
</style>



