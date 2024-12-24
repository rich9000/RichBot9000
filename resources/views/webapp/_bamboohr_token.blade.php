<!-- resources/views/webapp/_bamboohr_token.blade.php -->
<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark">
        <h5><i class="fas fa-leaf"></i> BambooHR Token Upload</h5>
    </div>
    <div class="card-body">
        <form id="bambooTokenForm">
            <div class="mb-3">
                <label for="bambooToken" class="form-label">API Token</label>
                <input
                    type="text"
                    class="form-control"
                    id="bambooToken"
                    placeholder="Paste your BambooHR API token here"
                    required
                >
            </div>
            <button type="submit" class="btn btn-warning w-100">
                <i class="fas fa-upload"></i> Upload Token
            </button>
        </form>
    </div>
</div>
