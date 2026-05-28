@extends('layouts.app')
@section('title', 'API Keys')

@section('content')
<div class="container py-4" style="max-width: 680px">

  <div class="d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-key fs-4 text-secondary"></i>
    <div>
      <h5 class="mb-0">API Keys</h5>
      <small class="text-muted">Manage your API keys for programmatic access to the store's APIs</small>
    </div>
  </div>

  <!-- Create API Key Card -->
  <div class="card shadow-sm border-0 mb-4 rounded-3 bg-white">
    <div class="card-body p-4">
      <h6 class="card-title fw-bold mb-3">Generate New API Key</h6>
      
      <div class="row g-3 align-items-center">
        <div class="col-auto flex-grow-1">
          <input type="text" id="new-key-name" class="form-control" placeholder="Key Name (e.g. Production)">
        </div>
        <div class="col-auto">
          <button type="button" id="btn-generate-key" class="btn btn-primary bg-indigo-600 hover:bg-indigo-700 text-white rounded-md px-4 border-0">
            Generate
          </button>
        </div>
      </div>
      <div id="generation-error" class="text-danger mt-2 small" style="display: none;"></div>
    </div>
  </div>

  <!-- Alert for Newly Generated Key -->
  <div id="new-key-alert" class="alert alert-warning border-warning shadow-sm mb-4" style="display: none;">
    <h6 class="alert-heading fw-bold d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill"></i> Save Your API Key
    </h6>
    <p class="mb-3 text-sm">Copy this API key now. For security, it will not be shown again.</p>
    <div class="input-group">
      <input type="text" id="generated-key-val" class="form-control font-monospace bg-light" readonly>
      <button class="btn btn-outline-secondary" type="button" id="btn-copy-key">
        <i class="bi bi-clipboard"></i> Copy
      </button>
    </div>
  </div>

  <!-- Table/List of API Keys -->
  <div class="card shadow-sm border-0 rounded-3 bg-white">
    <div class="card-body p-0">
      <div class="p-4 border-bottom">
        <h6 class="card-title fw-bold mb-0">Existing Keys</h6>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="api-keys-table" style="display: none;">
          <thead>
            <tr class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em">
              <th class="ps-4">Name</th>
              <th>Created</th>
              <th>Last Used</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loaded via AJAX -->
          </tbody>
        </table>
      </div>

      <div id="no-keys-message" class="text-center py-5" style="display: none;">
        <i class="bi bi-key-fill fs-1 text-muted"></i>
        <p class="fw-500 mt-3 mb-1">No API Keys</p>
        <p class="text-muted small">You haven't generated any API keys yet.</p>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const indexUrl = "{{ route('api-keys.index') }}";
        const storeUrl = "{{ route('api-keys.store') }}";

        // Set up CSRF token for jQuery AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Fetch and render keys
        function loadApiKeys() {
            $.get(indexUrl, function(response) {
                const tbody = $('#api-keys-table tbody');
                tbody.empty();

                if (response.api_keys.length === 0) {
                    $('#no-keys-message').show();
                    $('#api-keys-table').hide();
                    return;
                }

                $('#no-keys-message').hide();
                $('#api-keys-table').show();

                response.api_keys.forEach(function(key) {
                    const createdDate = new Date(key.created_at).toLocaleDateString();
                    const lastUsed = key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : "Never used";

                    tbody.append(`
                        <tr id="key-row-${key.id}">
                            <td class="ps-4 fw-semibold">${escapeHtml(key.name)}</td>
                            <td>${createdDate}</td>
                            <td>${lastUsed}</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-danger btn-revoke-key" data-id="${key.id}">
                                    <i class="bi bi-x-circle me-1"></i>Revoke
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }).fail(function() {
                console.error('Failed to load API keys.');
            });
        }

        // Escape HTML helper
        function escapeHtml(str) {
            return $('<div>').text(str).html();
        }

        // Load on init
        loadApiKeys();

        // Generate Key
        $('#btn-generate-key').on('click', function() {
            const nameInput = $('#new-key-name');
            const name = nameInput.val().trim();
            const errorDiv = $('#generation-error');

            errorDiv.hide().text('');

            if (!name) {
                errorDiv.text("Please provide a name for the API key.").show();
                return;
            }

            $.post(storeUrl, { name: name }, function(response) {
                nameInput.val('');
                // Show key warning
                $('#generated-key-val').val(response.plain_text_key);
                $('#new-key-alert').slideDown();

                // Reload table
                loadApiKeys();
            }).fail(function(xhr) {
                const errors = xhr.responseJSON?.errors;
                const msg = errors?.name ? errors.name[0] : "An error occurred.";
                errorDiv.text(msg).show();
            });
        });

        // Copy Key to Clipboard
        $('#btn-copy-key').on('click', function() {
            const keyInput = $('#generated-key-val');
            keyInput.select();
            document.execCommand('copy');
            
            const btn = $(this);
            const originalHtml = btn.html();
            btn.html('<i class="bi bi-check-lg"></i> Copied!').addClass('btn-success').removeClass('btn-outline-secondary');
            setTimeout(() => {
                btn.html(originalHtml).addClass('btn-outline-secondary').removeClass('btn-success');
            }, 2000);
        });

        // Revoke Key
        $(document).on('click', '.btn-revoke-key', function() {
            if (!confirm("Are you sure you want to revoke this API key? Any applications using it will lose access.")) {
                return;
            }

            const id = $(this).data('id');
            const row = $(`#key-row-${id}`);
            const deleteUrl = `{{ url('/api-keys') }}/${id}`;

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                success: function(response) {
                    row.fadeOut(function() {
                        $(this).remove();
                        loadApiKeys();
                    });
                },
                error: function() {
                    alert("Failed to revoke the API key.");
                }
            });
        });
    });
</script>
@endpush
