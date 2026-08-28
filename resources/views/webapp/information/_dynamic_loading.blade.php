<!-- Dynamic Content Loading Documentation -->
<div class="container py-4">
    <h1 class="mb-4">Dynamic Content Loading System</h1>

    <!-- Content Loading Flow -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Content Loading Flow</h2>
        </div>
        <div class="card-body">
            <div class="loading-flow-diagram">
                <!-- Frontend Flow -->
                <div class="flow-section">
                    <div class="section-title">Frontend</div>
                    <div class="flow-steps">
                        <div class="flow-node">Navigation Click</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">AJAX Request</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">Content Container</div>
                    </div>
                </div>

                <!-- Backend Flow -->
                <div class="flow-section">
                    <div class="section-title">Backend</div>
                    <div class="flow-steps">
                        <div class="flow-node">Laravel Route</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">Controller</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">Blade View</div>
                    </div>
                </div>

                <!-- Script Handling -->
                <div class="flow-section">
                    <div class="section-title">Script Handling</div>
                    <div class="flow-steps">
                        <div class="flow-node">Extract Scripts</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">Execute Scripts</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-node">Update State</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Implementation Details -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Implementation Details</h2>
        </div>
        <div class="card-body">
            <h3 class="h6">Frontend Implementation</h3>
            <pre class="bg-light p-3 rounded"><code>// Content loading function
function loadContent(url, targetSection) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById(targetSection).innerHTML = data.content;
            executeScripts(targetSection);
        });
}</code></pre>

            <h3 class="h6 mt-4">Backend Implementation</h3>
            <pre class="bg-light p-3 rounded"><code>// Laravel route
Route::get('/load-view/{view}', function($view) {
    return response()->json([
        'content' => view($view)->render()
    ]);
});</code></pre>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Best Practices</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Practice</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Script Isolation</td>
                            <td>Execute scripts in isolated scope to prevent global namespace pollution</td>
                        </tr>
                        <tr>
                            <td>Error Handling</td>
                            <td>Implement robust error handling for failed content loads</td>
                        </tr>
                        <tr>
                            <td>State Management</td>
                            <td>Use appState for consistent state management across dynamic content</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Loading Flow Diagram */
    .loading-flow-diagram {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        padding: 1rem;
    }

    .flow-section {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
    }

    .section-title {
        font-weight: bold;
        margin-bottom: 1rem;
        color: #495057;
    }

    .flow-steps {
        display: flex;
        align-items: center;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .flow-node {
        background: #6c63ff;
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        text-align: center;
        min-width: 150px;
    }

    .flow-arrow {
        font-size: 1.5rem;
        color: #6c757d;
    }

    /* Code Blocks */
    pre {
        margin: 0;
        overflow-x: auto;
    }

    code {
        font-size: 0.9rem;
    }

    /* Table Styles */
    .table th {
        background: #f8f9fa;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .flow-steps {
            flex-direction: column;
        }

        .flow-arrow {
            transform: rotate(90deg);
        }
    }
</style> 