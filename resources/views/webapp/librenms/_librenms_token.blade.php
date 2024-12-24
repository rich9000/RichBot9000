<!-- resources/views/webapp/_librenms_token.blade.php -->
<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark">
        <h5><i class="fas fa-leaf"></i> Rainbow LibreNMS Token Upload</h5>
    </div>
    <div class="card-body">
        <form id="libreNMSTokenForm">
            <div class="mb-3">
                <label for="librenNMSToken" class="form-label">API Token</label>
                <input
                    type="text"
                    class="form-control"
                    id="libreNMSToken"
                    placeholder="Paste your LibreNMS API token here"
                    required
                >
            </div>
            <button type="submit" class="btn btn-warning w-100">
                <i class="fas fa-upload"></i> Upload Token
            </button>
        </form>
    </div>
</div>
