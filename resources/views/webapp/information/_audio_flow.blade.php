<!-- Audio Processing Documentation -->
<div class="container py-4">
    <h1 class="mb-4">Audio Processing & WebSocket Architecture</h1>

    <!-- WebSocket Server Architecture -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">WebSocket Server Architecture</h2>
        </div>
        <div class="card-body">
            <div class="websocket-diagram">
                <!-- Server Core -->
                <div class="ws-node server">
                    <div class="node-title">OpenSwoole WebSocket Server</div>
                    <div class="node-port">Port 9501 (SSL)</div>
                </div>
                
                <!-- Connections -->
                <div class="ws-connections">
                    <div class="ws-client">
                        <div class="ws-node browser">Browser WebSocket</div>
                        <div class="ws-arrow">↑</div>
                    </div>
                    <div class="ws-client">
                        <div class="ws-node twilio">Twilio WebSocket</div>
                        <div class="ws-arrow">↑</div>
                    </div>
                    <div class="ws-client">
                        <div class="ws-node openai">OpenAI WebSocket</div>
                        <div class="ws-arrow">↑</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Processing Pipeline -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Audio Processing Pipeline</h2>
        </div>
        <div class="card-body">
            <div class="pipeline-diagram">
                <!-- Browser Audio Pipeline -->
                <div class="pipeline-section">
                    <div class="section-title">Browser Audio</div>
                    <div class="pipeline-flow">
                        <div class="pipeline-node">Microphone Input</div>
                        <div class="pipeline-arrow">→</div>
                        <div class="pipeline-node">AudioContext</div>
                        <div class="pipeline-arrow">→</div>
                        <div class="pipeline-node">WebSocket Stream</div>
                    </div>
                </div>

                <!-- Phone Audio Pipeline -->
                <div class="pipeline-section">
                    <div class="section-title">Phone Audio</div>
                    <div class="pipeline-flow">
                        <div class="pipeline-node">Twilio Input</div>
                        <div class="pipeline-arrow">→</div>
                        <div class="pipeline-node">μ-law Decode</div>
                        <div class="pipeline-arrow">→</div>
                        <div class="pipeline-node">Sample Rate Convert</div>
                    </div>
                </div>

                <!-- Server Processing -->
                <div class="pipeline-section">
                    <div class="section-title">Server Processing</div>
                    <div class="pipeline-flow">
                        <div class="pipeline-node">Audio Buffer</div>
                        <div class="pipeline-arrow">→</div>
                        <div class="pipeline-node">AI Processing</div>
                        <div class="pipeline-arrow">→</div>
                        <div class="pipeline-node">Response Stream</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Details -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Technical Implementation Details</h2>
        </div>
        <div class="card-body">
            <h3 class="h6">Browser Audio Implementation</h3>
            <ul>
                <li><strong>Audio Capture:</strong>
                    <ul>
                        <li>Uses Web Audio API with AudioContext</li>
                        <li>Custom AudioWorklet for real-time processing</li>
                        <li>48kHz sample rate with downsampling</li>
                    </ul>
                </li>
                <li><strong>Processing Pipeline:</strong>
                    <ul>
                        <li>Noise suppression and echo cancellation</li>
                        <li>PCM16 encoding for transmission</li>
                        <li>Chunked streaming with sequence IDs</li>
                    </ul>
                </li>
            </ul>

            <h3 class="h6">Phone Audio Processing</h3>
            <ul>
                <li><strong>Twilio Integration:</strong>
                    <ul>
                        <li>8kHz μ-law audio format</li>
                        <li>Base64 encoded payloads</li>
                        <li>Bidirectional streaming</li>
                    </ul>
                </li>
                <li><strong>Format Conversion:</strong>
                    <ul>
                        <li>μ-law to PCM16 conversion</li>
                        <li>Sample rate conversion (8kHz to 24kHz)</li>
                        <li>Buffer management for continuous streaming</li>
                    </ul>
                </li>
            </ul>

            <h3 class="h6">WebSocket Server Features</h3>
            <ul>
                <li><strong>Connection Management:</strong>
                    <ul>
                        <li>SSL/TLS encryption</li>
                        <li>Connection type detection</li>
                        <li>Session management</li>
                    </ul>
                </li>
                <li><strong>Data Routing:</strong>
                    <ul>
                        <li>Dynamic routing tables</li>
                        <li>Client tracking</li>
                        <li>Forward path management</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <!-- Alternative Implementations -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Alternative Implementation Options</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Current Implementation</th>
                            <th>Alternatives</th>
                            <th>Trade-offs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>WebSocket Server</td>
                            <td>OpenSwoole</td>
                            <td>
                                <ul>
                                    <li>Socket.io</li>
                                    <li>Ratchet</li>
                                    <li>Workerman</li>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <li>OpenSwoole: Better performance, more complex</li>
                                    <li>Socket.io: Easier implementation, more overhead</li>
                                    <li>Ratchet: Pure PHP, lower performance</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Audio Processing</td>
                            <td>Custom AudioWorklet</td>
                            <td>
                                <ul>
                                    <li>ScriptProcessor (legacy)</li>
                                    <li>WebRTC</li>
                                    <li>Media Recorder API</li>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <li>AudioWorklet: Better performance, modern</li>
                                    <li>ScriptProcessor: Wider support, deprecated</li>
                                    <li>WebRTC: Full featured, complex</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>Phone Integration</td>
                            <td>Twilio</td>
                            <td>
                                <ul>
                                    <li>Vonage/Nexmo</li>
                                    <li>Plivo</li>
                                    <li>Amazon Connect</li>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <li>Twilio: Best documentation, higher cost</li>
                                    <li>Vonage: Lower cost, less features</li>
                                    <li>Amazon Connect: AWS integration, complex</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* WebSocket Architecture Diagram */
    .websocket-diagram {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2rem;
        padding: 2rem;
    }

    .ws-node {
        padding: 1rem;
        border-radius: 0.5rem;
        color: white;
        text-align: center;
        min-width: 200px;
    }

    .ws-node.server {
        background: #2d2d2d;
        padding: 1.5rem;
    }

    .node-title {
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .node-port {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .ws-connections {
        display: flex;
        gap: 2rem;
        justify-content: center;
    }

    .ws-client {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .ws-arrow {
        font-size: 1.5rem;
        color: #6c757d;
    }

    .ws-node.browser { background: #6c63ff; }
    .ws-node.twilio { background: #dc3545; }
    .ws-node.openai { background: #198754; }

    /* Audio Pipeline Diagram */
    .pipeline-diagram {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        padding: 1rem;
    }

    .pipeline-section {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
    }

    .section-title {
        font-weight: bold;
        margin-bottom: 1rem;
        color: #495057;
    }

    .pipeline-flow {
        display: flex;
        align-items: center;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .pipeline-node {
        background: #6c63ff;
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        text-align: center;
        min-width: 150px;
    }

    .pipeline-arrow {
        font-size: 1.5rem;
        color: #6c757d;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .ws-connections {
            flex-direction: column;
            gap: 1rem;
        }

        .pipeline-flow {
            flex-direction: column;
        }

        .pipeline-arrow {
            transform: rotate(90deg);
        }
    }
</style> 