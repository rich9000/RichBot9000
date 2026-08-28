<!-- Tech Stack Documentation -->
<div class="container py-4">
    <h1 class="mb-4">RichBot9000 Technical Stack</h1>

    <!-- Architecture Overview -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Architecture Overview</h2>
        </div>
        <div class="card-body">
            <div class="architecture-diagram">
                <!-- Frontend Layer -->
                <div class="diagram-layer frontend-layer">
                    <div class="diagram-node">User Interface Layer</div>
                </div>
                
                <!-- Backend Layer -->
                <div class="diagram-layer backend-layer">
                    <div class="diagram-node">API Layer</div>
                    <div class="diagram-node">WebSocket Layer</div>
                </div>
                
                <!-- Data Layer -->
                <div class="diagram-layer data-layer">
                    <div class="diagram-node">Database Layer</div>
                </div>
                
                <!-- Integration Layer -->
                <div class="diagram-layer integration-layer">
                    <div class="diagram-node">AI Integration Layer</div>
                    <div class="diagram-node">Phone Integration Layer</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Frontend Stack -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Frontend Stack</h2>
        </div>
        <div class="card-body">
            <h3 class="h6">Core Technologies</h3>
            <ul>
                <li><strong>JavaScript:</strong> Plain JS for core functionality</li>
                <li><strong>jQuery:</strong> Used specifically for DataTables integration</li>
                <li><strong>Bootstrap 5:</strong> UI framework for responsive design</li>
                <li><strong>Font Awesome:</strong> Icon library</li>
            </ul>

            <h3 class="h6">State Management</h3>
            <ul>
                <li><strong>appState:</strong> Custom state management using localStorage</li>
                <li><strong>Bearer Token Authentication:</strong> Stored in appState.apiToken</li>
            </ul>

            <h3 class="h6">Dynamic Content Loading</h3>
            <ul>
                <li><strong>Blade Templates:</strong> Server-side templates with embedded JavaScript</li>
                <li><strong>Dynamic Imports:</strong> JavaScript modules loaded on demand</li>
                <li><strong>Content Loading:</strong> API-based content loading with script injection</li>
            </ul>
        </div>
    </div>

    <!-- Backend Stack -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Backend Stack</h2>
        </div>
        <div class="card-body">
            <h3 class="h6">Core Framework</h3>
            <ul>
                <li><strong>Laravel:</strong> PHP framework for API and routing</li>
                <li><strong>OpenSwoole:</strong> High-performance WebSocket server</li>
                <li><strong>MySQL/MariaDB:</strong> Primary database</li>
            </ul>

            <h3 class="h6">WebSocket Architecture</h3>
            <ul>
                <li><strong>Main WebSocket Server:</strong> Port 9501 with SSL</li>
                <li><strong>Connection Types:</strong>
                    <ul>
                        <li>Browser-to-Server: User interface connections</li>
                        <li>Twilio-to-Server: Phone call audio streams</li>
                        <li>Server-to-OpenAI: AI model connections</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <!-- Integration Layer -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Integration Layer</h2>
        </div>
        <div class="card-body">
            <h3 class="h6">Phone Integration</h3>
            <ul>
                <li><strong>Twilio:</strong> Phone call handling and audio streaming</li>
                <li><strong>Audio Processing:</strong>
                    <ul>
                        <li>μ-law encoding/decoding</li>
                        <li>Sample rate conversion (8kHz ↔ 24kHz)</li>
                        <li>Real-time audio streaming</li>
                    </ul>
                </li>
            </ul>

            <h3 class="h6">AI Integration</h3>
            <ul>
                <li><strong>OpenAI:</strong>
                    <ul>
                        <li>GPT-4 for text processing</li>
                        <li>Whisper for speech-to-text</li>
                        <li>Text-to-Speech for responses</li>
                    </ul>
                </li>
                <li><strong>Custom Tools:</strong> Extensible AI assistant tools system</li>
            </ul>
        </div>
    </div>

    <!-- Data Flow Diagram -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Data Flow</h2>
        </div>
        <div class="card-body">
            <div class="data-flow-diagram">
                <div class="flow-row">
                    <div class="flow-node client">User/Browser</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-node server">WebSocket Server</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-node ai">AI Service</div>
                </div>
                <div class="flow-row mt-4">
                    <div class="flow-node phone">Phone Service</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-node server">WebSocket Server</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Load Process -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Page Load Process</h2>
        </div>
        <div class="card-body">
            <!-- Process Timeline -->
            <div class="load-process">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3 class="h6">Initial Page Load</h3>
                        <div class="code-block">
                            <pre class="small bg-light p-2"><code>// Initial state setup
document.addEventListener('DOMContentLoaded', () => {
    appState = JSON.parse(localStorage.getItem('app_state') || '{}');
    setupMenuForRoles(appState.user?.roles || []);
});</code></pre>
                        </div>
                        <ul class="small">
                            <li>Load core HTML structure</li>
                            <li>Initialize appState from localStorage</li>
                            <li>Setup role-based menu visibility</li>
                        </ul>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3 class="h6">Authentication Check</h3>
                        <div class="code-block">
                            <pre class="small bg-light p-2"><code>// Auth verification
if (appState.apiToken) {
    updateUserUI();
    showSection('richbotSection');
} else {
    showSection('richbotLoginSection');
}</code></pre>
                        </div>
                        <ul class="small">
                            <li>Verify authentication token</li>
                            <li>Update UI based on auth status</li>
                            <li>Show appropriate section</li>
                        </ul>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3 class="h6">Service Initialization</h3>
                        <div class="code-block">
                            <pre class="small bg-light p-2"><code>// Initialize services
initializeWebSocket();
setupEventListeners();
populateServicesList();</code></pre>
                        </div>
                        <ul class="small">
                            <li>Connect to WebSocket server</li>
                            <li>Setup global event listeners</li>
                            <li>Initialize service status tracking</li>
                        </ul>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3 class="h6">Content Loading</h3>
                        <div class="code-block">
                            <pre class="small bg-light p-2"><code>// Dynamic content loading
function loadContent(view, section) {
    fetch(`/api/content/${view}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById(section).innerHTML = data.content;
            extractAndExecuteScripts(data.content);
        });
}</code></pre>
                        </div>
                        <ul class="small">
                            <li>Load section-specific content</li>
                            <li>Extract and execute embedded scripts</li>
                            <li>Initialize section components</li>
                        </ul>
                    </div>
                </div>

                <div class="process-step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h3 class="h6">Post-Load Operations</h3>
                        <div class="code-block">
                            <pre class="small bg-light p-2"><code>// Post-load tasks
updateOpenTabsMenu();
setupMenuInteractivity();
initializeNotifications();</code></pre>
                        </div>
                        <ul class="small">
                            <li>Update navigation state</li>
                            <li>Setup menu interactivity</li>
                            <li>Initialize notification system</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Current vs Alternative Process -->
            <div class="mt-4">
                <h3 class="h6">Alternative Load Processes</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Current Process</th>
                                <th>Alternative Approach</th>
                                <th>Benefits</th>
                                <th>Drawbacks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Sequential Loading</td>
                                <td>Parallel Loading with Service Workers</td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>Faster initial load</li>
                                        <li>Offline capabilities</li>
                                        <li>Background updates</li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>More complex caching</li>
                                        <li>Service worker overhead</li>
                                        <li>Browser compatibility</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>Direct Script Execution</td>
                                <td>Module-based Loading</td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>Better dependency management</li>
                                        <li>Code splitting</li>
                                        <li>Tree shaking</li>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>Build step required</li>
                                        <li>More complex setup</li>
                                        <li>Bundle size management</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alternative Implementations -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Alternative Implementations</h2>
        </div>
        <div class="card-body">
            <!-- Frontend Alternatives -->
            <div class="alternatives-section mb-4">
                <h3 class="h6">Frontend Architecture</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Current Approach</th>
                                <th>Alternative</th>
                                <th>Implementation Example</th>
                                <th>Trade-offs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Plain JavaScript + Dynamic Loading</td>
                                <td>React SPA</td>
                                <td>
                                    <pre class="small bg-light p-2 mb-0"><code>// React Component
const AudioChat = () => {
  const [stream, setStream] = useState(null);
  return (
    <WebSocketProvider>
      <AudioStream />
      <ChatInterface />
    </WebSocketProvider>
  );
};</code></pre>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>+ Better state management</li>
                                        <li>+ Component reusability</li>
                                        <li>- More complex build process</li>
                                        <li>- Larger initial bundle</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>Server-side Blade Templates</td>
                                <td>Vue.js with Laravel</td>
                                <td>
                                    <pre class="small bg-light p-2 mb-0"><code>// Vue Component
export default {
  setup() {
    const store = useStore();
    return {
      audio: useAudioStream(),
      chat: useChatState()
    };
  }
};</code></pre>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>+ Progressive enhancement</li>
                                        <li>+ Better reactivity</li>
                                        <li>- Learning curve</li>
                                        <li>- More boilerplate</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Backend Alternatives -->
            <div class="alternatives-section mb-4">
                <h3 class="h6">Backend Architecture</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Current Approach</th>
                                <th>Alternative</th>
                                <th>Implementation Example</th>
                                <th>Trade-offs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Monolithic Laravel + OpenSwoole</td>
                                <td>Microservices with Node.js</td>
                                <td>
                                    <pre class="small bg-light p-2 mb-0"><code>// Audio Service
const audioService = new FastifyWS();
audioService.ws('/stream', {
  handler: handleAudioStream,
  codec: audioCodec
});

// Chat Service
const chatService = new NestJS();
chatService.useWebSockets();</code></pre>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>+ Better scalability</li>
                                        <li>+ Service isolation</li>
                                        <li>- More complex deployment</li>
                                        <li>- Higher latency</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>WebSocket for Real-time</td>
                                <td>gRPC with HTTP/2</td>
                                <td>
                                    <pre class="small bg-light p-2 mb-0"><code>// Proto definition
service AudioChat {
  rpc StreamAudio (stream Audio)
    returns (stream Response);
  rpc ProcessText (TextRequest)
    returns (AIResponse);
}</code></pre>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>+ Better performance</li>
                                        <li>+ Type safety</li>
                                        <li>- Browser support issues</li>
                                        <li>- More complex setup</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- State Management Alternatives -->
            <div class="alternatives-section">
                <h3 class="h6">State Management</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Current Approach</th>
                                <th>Alternative</th>
                                <th>Implementation Example</th>
                                <th>Trade-offs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Custom appState + localStorage</td>
                                <td>Redux + Redux Persist</td>
                                <td>
                                    <pre class="small bg-light p-2 mb-0"><code>// Redux Store
const store = configureStore({
  reducer: {
    audio: audioReducer,
    chat: chatReducer,
    auth: authReducer
  },
  middleware: [persist]
});</code></pre>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>+ Better state tracking</li>
                                        <li>+ Time-travel debugging</li>
                                        <li>- More boilerplate</li>
                                        <li>- Steeper learning curve</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>Direct WebSocket State</td>
                                <td>RxJS Observables</td>
                                <td>
                                    <pre class="small bg-light p-2 mb-0"><code>// RxJS Streams
const audioStream$ = new Subject();
const chatState$ = audioStream$.pipe(
  map(processAudio),
  mergeMap(getAIResponse),
  shareReplay(1)
);</code></pre>
                                </td>
                                <td>
                                    <ul class="small mb-0">
                                        <li>+ Better stream handling</li>
                                        <li>+ Reactive programming</li>
                                        <li>- Complex operators</li>
                                        <li>- Memory management</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Architecture Diagram Styles */
    .architecture-diagram {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        align-items: center;
        padding: 2rem;
    }

    .diagram-layer {
        display: flex;
        gap: 1rem;
        width: 100%;
        justify-content: center;
    }

    .diagram-node {
        padding: 1rem;
        border-radius: 0.5rem;
        color: white;
        text-align: center;
        min-width: 200px;
    }

    .frontend-layer .diagram-node {
        background: #6c63ff;
    }

    .backend-layer .diagram-node {
        background: #2d2d2d;
    }

    .data-layer .diagram-node {
        background: #198754;
    }

    .integration-layer .diagram-node {
        background: #dc3545;
    }

    /* Data Flow Diagram Styles */
    .data-flow-diagram {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        padding: 2rem;
    }

    .flow-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        justify-content: center;
    }

    .flow-node {
        padding: 1rem;
        border-radius: 0.5rem;
        color: white;
        text-align: center;
        min-width: 150px;
    }

    .flow-arrow {
        font-size: 1.5rem;
        color: #6c757d;
    }

    .flow-node.client { background: #6c63ff; }
    .flow-node.server { background: #2d2d2d; }
    .flow-node.ai { background: #198754; }
    .flow-node.phone { background: #dc3545; }

    /* Alternative Implementations Styles */
    .alternatives-section pre {
        margin: 0;
        border-radius: 0.25rem;
    }

    .alternatives-section code {
        font-size: 0.8rem;
    }

    .alternatives-section ul {
        padding-left: 1.2rem;
    }

    .alternatives-section .table td {
        vertical-align: top;
    }

    .alternatives-section .table pre {
        max-width: 300px;
        overflow-x: auto;
    }

    @media (max-width: 768px) {
        .alternatives-section .table {
            display: block;
            overflow-x: auto;
        }
    }

    /* Page Load Process Styles */
    .load-process {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        padding: 1rem;
    }

    .process-step {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .step-number {
        background: #6c63ff;
        color: white;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }

    .step-content {
        flex-grow: 1;
    }

    .code-block {
        margin: 0.5rem 0;
    }

    .code-block pre {
        margin: 0;
        border-radius: 0.25rem;
    }

    @media (max-width: 768px) {
        .process-step {
            flex-direction: column;
        }

        .step-number {
            margin-bottom: 0.5rem;
        }
    }
</style>
