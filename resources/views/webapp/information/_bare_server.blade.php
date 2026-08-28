@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0">BareWebsocketServer Documentation</h1>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> The live stack is the original <strong>BareWebsocketServer</strong> (<code>php artisan bare:server</code>) with the OpenAI relay <strong>bare:assistant-v2</strong>. Dashboard path: <code>wss://{domain}:9502/dashboard/{room}?token={api_token}</code>. Twilio streams use <code>/twilio-inbound/{room}/{callSid}</code>. Relays connect at <code>/openai/{room}/{assistant_id}</code>.
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">Quick Links</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <a href="#components" class="list-group-item list-group-item-action">Key Components</a>
                                    <a href="#connections" class="list-group-item list-group-item-action">Connection Types</a>
                                    <a href="#flow" class="list-group-item list-group-item-action">Message Flow</a>
                                    <a href="#security" class="list-group-item list-group-item-action">Security Features</a>
                                    <a href="#monitoring" class="list-group-item list-group-item-action">Monitoring</a>
                                    <a href="#configuration" class="list-group-item list-group-item-action">Configuration</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <section id="components" class="mb-5">
                                <h2 class="border-bottom pb-2">Key Components</h2>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">WebSocket Server</h5>
                                                <p class="card-text">Handles all WebSocket connections, manages connection lifecycle, and routes messages between clients.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">Client Table</h5>
                                                <p class="card-text">Tracks active connections, stores metadata, and manages connection state.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">Room Table</h5>
                                                <p class="card-text">Manages conversation rooms, tracks activity, and handles room lifecycle.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">Message Handlers</h5>
                                                <p class="card-text">Process different types of WebSocket messages, handle audio streaming, and manage function calls.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="connections" class="mb-5">
                                <h2 class="border-bottom pb-2">Connection Types</h2>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-header bg-info text-white">
                                                <h5 class="mb-0">Twilio Outbound</h5>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-check text-success"></i> Handles voice calls</li>
                                                    <li><i class="fas fa-check text-success"></i> Manages audio streaming</li>
                                                    <li><i class="fas fa-check text-success"></i> Processes Twilio messages</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-header bg-info text-white">
                                                <h5 class="mb-0">OpenAI Realtime</h5>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-check text-success"></i> Manages API connections</li>
                                                    <li><i class="fas fa-check text-success"></i> Handles function calls</li>
                                                    <li><i class="fas fa-check text-success"></i> Processes AI responses</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-header bg-info text-white">
                                                <h5 class="mb-0">Dashboard</h5>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-check text-success"></i> Monitoring interface</li>
                                                    <li><i class="fas fa-check text-success"></i> Call control</li>
                                                    <li><i class="fas fa-check text-success"></i> System status</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="flow" class="mb-5">
                                <h2 class="border-bottom pb-2">Message Flow</h2>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0">Audio Flow</h5>
                                            </div>
                                            <div class="card-body">
                                                <ol class="list-group list-group-numbered">
                                                    <li class="list-group-item">Twilio sends audio chunks</li>
                                                    <li class="list-group-item">Server forwards to OpenAI</li>
                                                    <li class="list-group-item">OpenAI processes audio</li>
                                                    <li class="list-group-item">Server routes responses</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0">Function Call Flow</h5>
                                            </div>
                                            <div class="card-body">
                                                <ol class="list-group list-group-numbered">
                                                    <li class="list-group-item">OpenAI initiates call</li>
                                                    <li class="list-group-item">Server receives arguments</li>
                                                    <li class="list-group-item">Function executes</li>
                                                    <li class="list-group-item">Results sent back</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="security" class="mb-5">
                                <h2 class="border-bottom pb-2">Security Features</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-lock fa-3x text-primary mb-2"></i>
                                                    <h5>SSL/TLS Encryption</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-user-shield fa-3x text-primary mb-2"></i>
                                                    <h5>Connection Validation</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-tachometer-alt fa-3x text-primary mb-2"></i>
                                                    <h5>Rate Limiting</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="monitoring" class="mb-5">
                                <h2 class="border-bottom pb-2">Monitoring</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-plug fa-2x text-info mb-2"></i>
                                                    <h6>Connection Status</h6>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-microphone fa-2x text-info mb-2"></i>
                                                    <h6>Audio Flow</h6>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-cogs fa-2x text-info mb-2"></i>
                                                    <h6>Function Calls</h6>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                                                    <h6>Performance</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="configuration" class="mb-5">
                                <h2 class="border-bottom pb-2">Configuration</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="bg-light p-3 rounded">
SSL Configuration:
- Certificate: config('app.ssl_cert_file')
- Key: config('app.ssl_key_file')
- Port: config('app.ws_port_alt') (default 9502)
- Host: 0.0.0.0
- Command: php artisan bare:server
- Relay: php artisan bare:assistant-v2 {room} {assistant_id}

Dashboard connect:
wss://{domain}:9502/dashboard/{room}?token={api_token}

Ask the server for state with:
{ "type": "request_server_data" }
                                        </pre>
                                    </div>
                                </div>
                            </section>

                            <section class="mb-5">
                                <h2 class="border-bottom pb-2">Usage Examples</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="bg-light p-3 rounded">
# Start the server
php artisan bare:server

# Start a V2 relay for a specific room
php artisan bare:assistant-v2 {room} {assistant_id} --debug
                                        </pre>
                                    </div>
                                </div>
                            </section>

                            <section class="mb-5">
                                <h2 class="border-bottom pb-2">Common Issues</h2>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card mb-3">
                                                    <div class="card-header bg-warning">
                                                        <h5 class="mb-0">Connection Drops</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <ul>
                                                            <li>Check SSL configuration</li>
                                                            <li>Verify network connectivity</li>
                                                            <li>Monitor connection limits</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card mb-3">
                                                    <div class="card-header bg-warning">
                                                        <h5 class="mb-0">Audio Issues</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <ul>
                                                            <li>Verify audio format settings</li>
                                                            <li>Check WebSocket connections</li>
                                                            <li>Monitor audio buffer</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card mb-3">
                                                    <div class="card-header bg-warning">
                                                        <h5 class="mb-0">Function Call Failures</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <ul>
                                                            <li>Check executor configuration</li>
                                                            <li>Verify permissions</li>
                                                            <li>Monitor rate limits</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .list-group-item {
        border-left: none;
        border-right: none;
    }
    .list-group-item:first-child {
        border-top: none;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    section {
        scroll-margin-top: 20px;
    }
</style>
@endpush 