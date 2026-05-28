@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-shield-lock fs-4 text-secondary"></i>
        <div>
            <h5 class="mb-0">Roles & Permissions</h5>
            <small class="text-muted">Manage user roles and role permissions</small>
        </div>
    </div>

    {{-- Nav Tabs --}}
    <ul class="nav nav-tabs mb-4" id="rolesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2"
                    id="users-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#users-panel"
                    type="button"
                    role="tab">
                <i class="bi bi-people"></i>
                Assign User Roles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2"
                    id="permissions-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#permissions-panel"
                    type="button"
                    role="tab">
                <i class="bi bi-key"></i>
                Role Permissions
            </button>
        </li>
    </ul>

    <div class="tab-content" id="rolesTabContent">

        {{-- ── TAB 1: Assign User Roles ─────────────────────────────── --}}
        <div class="tab-pane fade show active" id="users-panel" role="tabpanel">

            {{-- Search --}}
            <div class="mb-3" style="max-width: 320px">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text"
                           class="form-control"
                           id="user-search"
                           placeholder="Search by name or email…" />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="users-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 35%">User</th>
                            <th style="width: 30%">Roles</th>
                            <th style="width: 35%" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr data-user-id="{{ $user->id }}"
                            data-user-name="{{ strtolower($user->name) }}"
                            data-user-email="{{ strtolower($user->email) }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center"
                                         style="width:34px;height:34px;flex-shrink:0">
                                        <span class="fw-500 text-secondary" style="font-size:13px">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-500" style="font-size:14px">{{ $user->name }}</p>
                                        <p class="mb-0 text-muted" style="font-size:12px">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 role-badges" id="badges-{{ $user->id }}">
                                    @forelse($user->getRoleNames() as $role)
                                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-normal" style="font-size:11px">
                                            {{ $role }}
                                        </span>
                                    @empty
                                        <span class="text-muted" style="font-size:12px">No roles assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <span class="verification-status-badge" id="verification-status-{{ $user->id }}">
                                        @if($user->email_verified_at)
                                            <span class="badge bg-success bg-opacity-10 text-success fw-normal" style="font-size:11px">
                                                <i class="bi bi-check-circle me-1"></i>Verified
                                            </span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning fw-normal" style="font-size:11px">
                                                <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                            </span>
                                        @endif
                                    </span>

                                    <button class="btn btn-sm {{ $user->email_verified_at ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-verification-btn"
                                            data-user-id="{{ $user->id }}"
                                            data-verified="{{ $user->email_verified_at ? '1' : '0' }}"
                                            id="verify-btn-{{ $user->id }}">
                                        @if($user->email_verified_at)
                                            <i class="bi bi-x-circle me-1"></i>Unverify
                                        @else
                                            <i class="bi bi-check-circle me-1"></i>Verify
                                        @endif
                                    </button>

                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#assignRoleModal"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-roles="{{ $user->getRoleNames()->implode(',') }}">
                                        <i class="bi bi-pencil me-1"></i>Roles
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $users->links() }}
            </div>

        </div>

        {{-- ── TAB 2: Role Permissions ──────────────────────────────── --}}
        <div class="tab-pane fade" id="permissions-panel" role="tabpanel">

            <div class="row g-4">
                @foreach($roles as $role)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border" style="border-radius: 10px">
                        <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield text-secondary"></i>
                                <span class="fw-500 text-capitalize">{{ $role->name }}</span>
                            </div>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:11px">
                                {{ $role->permissions->count() }} permissions
                            </span>
                        </div>

                        <div class="card-body p-3">
                            @foreach($allPermissions->groupBy('group') as $group => $perms)
                            <p class="text-uppercase text-muted mb-2"
                               style="font-size:10px;letter-spacing:.06em;font-weight:500">
                                {{ $group ?? 'General' }}
                            </p>

                            <div class="d-flex flex-column gap-1 mb-3">
                                @foreach($perms as $permission)
                                <div class="d-flex align-items-center justify-content-between
                                            px-2 py-1 rounded"
                                     style="background: var(--bs-light)">
                                    <span style="font-size:13px">{{ $permission->label ?? $permission->name }}</span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input permission-toggle"
                                               type="checkbox"
                                               role="switch"
                                               data-role-id="{{ $role->id }}"
                                               data-role-name="{{ $role->name }}"
                                               data-permission="{{ $permission->name }}"
                                               id="perm_{{ $role->id }}_{{ $permission->name }}"
                                               {{ $role->permissions->contains('name', $permission->name) ? 'checked' : '' }} />
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </div>

                        <div class="card-footer bg-transparent border-top py-2 px-3">
                            <button class="btn btn-sm btn-primary w-100 save-role-perms"
                                    data-role-id="{{ $role->id }}"
                                    data-role-name="{{ $role->name }}">
                                <i class="bi bi-check2 me-1"></i>Save permissions
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>

</div>

{{-- ── Assign Role Modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="assignRoleModal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title d-flex align-items-center gap-2" id="assignRoleModalLabel">
                    <i class="bi bi-person-badge text-secondary"></i>
                    Assign roles — <span id="modal-user-name" class="fw-normal text-muted"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Select one or more roles to assign to this user.</p>
                <div class="d-flex flex-column gap-2" id="modal-roles-list">
                    @foreach($roles as $role)
                    <label class="d-flex align-items-center justify-content-between px-3 py-2 border rounded-2 cursor-pointer role-option"
                           for="modal_role_{{ $role->name }}"
                           style="cursor:pointer">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield text-secondary" style="font-size:15px"></i>
                            <div>
                                <p class="mb-0 fw-500 text-capitalize" style="font-size:14px">{{ $role->name }}</p>
                                <p class="mb-0 text-muted" style="font-size:12px">
                                    {{ $role->permissions->count() }} permissions
                                </p>
                            </div>
                        </div>
                        <input class="form-check-input modal-role-check"
                               type="checkbox"
                               name="roles[]"
                               value="{{ $role->name }}"
                               id="modal_role_{{ $role->name }}" />
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="save-roles-btn">
                    <i class="bi bi-check2 me-1"></i>Save roles
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

// ── helpers ──────────────────────────────────────────────────────────
function ajax(url, data, method = 'POST') {
    return fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    }).then(r => r.json());
}

// ── user search filter ────────────────────────────────────────────────
document.getElementById('user-search').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#users-table tbody tr').forEach(row => {
        const name  = row.dataset.userName  || '';
        const email = row.dataset.userEmail || '';
        row.style.display = (!q || name.includes(q) || email.includes(q)) ? '' : 'none';
    });
});

// ── assign role modal: populate checkboxes ────────────────────────────
document.getElementById('assignRoleModal').addEventListener('show.bs.modal', function (e) {
    const btn        = e.relatedTarget;
    const userId     = btn.dataset.userId;
    const userName   = btn.dataset.userName;
    const userRoles  = (btn.dataset.userRoles || '').split(',').filter(Boolean);

    document.getElementById('modal-user-name').textContent = userName;
    document.getElementById('save-roles-btn').dataset.userId = userId;

    document.querySelectorAll('.modal-role-check').forEach(cb => {
        cb.checked = userRoles.includes(cb.value);
    });
});

// ── save roles (ajax) ─────────────────────────────────────────────────
document.getElementById('save-roles-btn').addEventListener('click', function () {
    const userId    = this.dataset.userId;
    const selected  = [...document.querySelectorAll('.modal-role-check:checked')].map(c => c.value);
    const btn       = this;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

    ajax(`/admin/roles/${userId}/assign`, { roles: selected })
        .then(res => {
            if (res.success) {
                // update badges in table row
                const badgesEl = document.getElementById(`badges-${userId}`);
                if (badgesEl) {
                    badgesEl.innerHTML = selected.length
                        ? selected.map(r =>
                            `<span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-normal" style="font-size:11px">${r}</span>`
                          ).join('')
                        : '<span class="text-muted" style="font-size:12px">No roles assigned</span>';
                }

                // update trigger button data
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (row) {
                    const editBtn = row.querySelector('[data-bs-target="#assignRoleModal"]');
                    if (editBtn) editBtn.dataset.userRoles = selected.join(',');
                }

                bootstrap.Modal.getInstance(document.getElementById('assignRoleModal')).hide();
                notify('success', 'Roles updated', res.message || `Roles saved successfully.`);
            } else {
                notify('error', 'Failed', res.message || 'Could not update roles.');
            }
        })
        .catch(() => notify('error', 'Error', 'Something went wrong. Please try again.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Save roles';
        });
});

// ── save role permissions (ajax) ──────────────────────────────────────
document.querySelectorAll('.save-role-perms').forEach(btn => {
    btn.addEventListener('click', function () {
        const roleId   = this.dataset.roleId;
        const roleName = this.dataset.roleName;
        const card     = this.closest('.card');
        const selected = [...card.querySelectorAll('.permission-toggle:checked')].map(c => c.dataset.permission);

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        const self = this;

        ajax(`/admin/roles/${roleId}/permissions`, { permissions: selected })
            .then(res => {
                if (res.success) {
                    // update permission count badge
                    const badge = card.querySelector('.badge');
                    if (badge) badge.textContent = `${selected.length} permissions`;

                    notify('success', 'Permissions saved', res.message || `${roleName} role updated.`);
                } else {
                    notify('error', 'Failed', res.message || 'Could not update permissions.');
                }
            })
            .catch(() => notify('error', 'Error', 'Something went wrong. Please try again.'))
            .finally(() => {
                self.disabled = false;
                self.innerHTML = '<i class="bi bi-check2 me-1"></i>Save permissions';
            });
    });
});

// ── toggle verification (ajax) ────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.toggle-verification-btn');
    if (!btn) return;

    const userId = btn.dataset.userId;
    const isVerified = btn.dataset.verified === '1';
    const action = isVerified ? 'unverify' : 'verify';
    const url = `/admin/roles/${userId}/${action}`;

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    ajax(url, {}, 'POST')
        .then(res => {
            if (res.success) {
                // update button state
                if (isVerified) {
                    // user is now unverified
                    btn.className = 'btn btn-sm btn-outline-success toggle-verification-btn';
                    btn.dataset.verified = '0';
                    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Verify';
                    
                    const badge = document.getElementById(`verification-status-${userId}`);
                    if (badge) {
                        badge.innerHTML = `<span class="badge bg-warning bg-opacity-10 text-warning fw-normal" style="font-size:11px"><i class="bi bi-exclamation-circle me-1"></i>Unverified</span>`;
                    }
                } else {
                    // user is now verified
                    btn.className = 'btn btn-sm btn-outline-danger toggle-verification-btn';
                    btn.dataset.verified = '1';
                    btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Unverify';
                    
                    const badge = document.getElementById(`verification-status-${userId}`);
                    if (badge) {
                        badge.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success fw-normal" style="font-size:11px"><i class="bi bi-check-circle me-1"></i>Verified</span>`;
                    }
                }
                notify('success', 'Status updated', res.message || 'Verification status changed successfully.');
            } else {
                btn.innerHTML = originalHtml;
                notify('error', 'Failed', res.message || 'Could not update verification status.');
            }
        })
        .catch(() => {
            btn.innerHTML = originalHtml;
            notify('error', 'Error', 'Something went wrong. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
        });
});
</script>
@endpush