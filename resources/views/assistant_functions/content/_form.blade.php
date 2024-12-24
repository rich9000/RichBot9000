
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Assistant Function Form</h5>
                </div>
                <div class="card-body bg-light">
                    <div class="alert alert-danger d-none" id="error-alert"></div>
                    <div class="alert alert-success d-none" id="success-alert"></div>

                    <form id="assistantFunctionForm" autocomplete="off">
                        @csrf <!-- CSRF Token for security -->

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $assistantFunction->name ?? '') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description">{{ old('description', $assistantFunction->description ?? '') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="parameters" class="form-label">Parameters (JSON)</label>
                            <textarea class="form-control" id="parameters" name="parameters">{{ old('parameters', $assistantFunction->parameters ?? '') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label">Code</label>
                            <textarea class="form-control" id="code" name="code">{{ old('code', $assistantFunction->code ?? '') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" {{ old('status', $assistantFunction->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $assistantFunction->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="deprecated" {{ old('status', $assistantFunction->status ?? '') === 'deprecated' ? 'selected' : '' }}>Deprecated</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="version" class="form-label">Version</label>
                            <input type="text" class="form-control" id="version" name="version" value="{{ old('version', $assistantFunction->version ?? '1.0') }}" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="saveButton">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#assistantFunctionForm').on('submit', function(e) {
            e.preventDefault();

            const formData = {
                name: $('#name').val(),
                description: $('#description').val(),
                parameters: $('#parameters').val(),
                code: $('#code').val(),
                status: $('#status').val(),
                version: $('#version').val()
            };

            let url = '{{ isset($assistantFunction) ? route("assistant_functions.update", $assistantFunction->id) : route("assistant_functions.store") }}';
            let method = '{{ isset($assistantFunction) ? "PUT" : "POST" }}';

            $.ajax({
                url: url,
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                //    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Include CSRF token for security
                },
                data: JSON.stringify(formData),
                success: function(response) {
                    $('#success-alert').text('Assistant Function saved successfully.').removeClass('d-none');
                    $('#error-alert').addClass('d-none');
                    setTimeout(() => {
                        window.location.href = '{{ route("assistant_functions.index") }}';
                    }, 2000);
                },
                error: function(xhr) {
                    $('#error-alert').text('Failed to save the Assistant Function: ' + xhr.responseJSON.message).removeClass('d-none');
                    $('#success-alert').addClass('d-none');
                }
            });
        });

        // Optional: Load function data from the database for editing
        @if(isset($assistantFunction))
        $.ajax({
            url: `/api/assistant_functions/${{ $assistantFunction->id }}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            },
            success: function(data) {
                $('#name').val(data.name);
                $('#description').val(data.description);
                $('#parameters').val(data.parameters);
                $('#code').val(data.code);
                $('#status').val(data.status);
                $('#version').val(data.version);
            }
        });
        @endif
    });
</script>
