@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-people-fill fs-4 text-secondary"></i>
        <div>
            <h5 class="mb-0">Manage Users</h5>
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
        @can('assign_roles')
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
        @endcan
        @role('admin')
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2"
                    id="trashed-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#trashed-panel"
                    type="button"
                    role="tab">
                <i class="bi bi-trash3"></i>
                Trashed Users
                @if($trashedUsers->isNotEmpty())
                    <span class="badge bg-danger bg-opacity-75 rounded-pill" style="font-size:10px">{{ $trashedUsers->count() }}</span>
                @endif
            </button>
        </li>
        @endrole
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
                            <th style="width: 30%">User</th>
                            <th style="width: 20%">Joined</th>
                            <th style="width: 25%">Roles</th>
                            <th style="width: 25%" class="text-end">Action</th>
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
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <p class="mb-0 fw-500 text-nowrap" style="font-size:14px">{{ $user->name }}</p>
                                            <span class="force-reset-status-badge" id="force-reset-status-{{ $user->id }}">
                                                @if($user->force_password_reset)
                                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-normal" style="font-size:10px; padding: 2px 6px">
                                                        Reset Pending
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="verification-status-badge" id="verification-status-{{ $user->id }}">
                                                @if($user->email_verified_at)
                                                    <span class="badge bg-success bg-opacity-10 text-success fw-normal" style="font-size:10px; padding: 2px 6px">
                                                        Verified
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-10 text-warning fw-normal" style="font-size:10px; padding: 2px 6px">
                                                        Unverified
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                        <p class="mb-0 text-muted" style="font-size:12px">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted text-nowrap" style="font-size:13px" title="{{ $user->created_at->toDayDateTimeString() }}">
                                    {{ $user->created_at->format('M d, Y') }}
                                </span>
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
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="actionsDropdown-{{ $user->id }}" style="text-decoration: none">
                                        <i class="bi bi-three-dots-vertical fs-5"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="actionsDropdown-{{ $user->id }}">
                                        {{-- ── Toggle Verification ── --}}
                                        <li>
                                            <button class="dropdown-item toggle-verification-btn d-flex align-items-center gap-2"
                                                    data-user-id="{{ $user->id }}"
                                                    data-verified="{{ $user->email_verified_at ? '1' : '0' }}"
                                                    id="verify-btn-{{ $user->id }}">
                                                @if($user->email_verified_at)
                                                    <i class="bi bi-x-circle text-danger"></i> <span class="action-text">Unverify</span>
                                                @else
                                                    <i class="bi bi-check-circle text-success"></i> <span class="action-text">Verify</span>
                                                @endif
                                            </button>
                                        </li>

                                        {{-- ── Force Password Reset ── --}}
                                        <li>
                                            <button class="dropdown-item force-reset-btn d-flex align-items-center gap-2"
                                                    data-user-id="{{ $user->id }}"
                                                    id="force-reset-btn-{{ $user->id }}"
                                                    {{ $user->force_password_reset ? 'disabled' : '' }}>
                                                <i class="bi bi-shield-slash text-warning"></i>
                                                <span class="action-text">{{ $user->force_password_reset ? 'Forced' : 'Force Reset' }}</span>
                                            </button>
                                        </li>

                                        {{-- ── Assign Roles (Visible if logged-in user has assign_roles permission) ── --}}
                                        @can('assign_roles')
                                        <li>
                                            <button class="dropdown-item d-flex align-items-center gap-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#assignRoleModal"
                                                    data-user-id="{{ $user->id }}"
                                                    data-user-name="{{ $user->name }}"
                                                    data-user-roles="{{ $user->getRoleNames()->implode(',') }}">
                                                <i class="bi bi-pencil text-primary"></i> <span class="action-text">Assign Roles</span>
                                            </button>
                                        </li>
                                        @endcan

                                        {{-- ── Delete User (soft-delete) ── --}}
                                        @can('manage_users')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item delete-user-btn d-flex align-items-center gap-2"
                                                    data-user-id="{{ $user->id }}"
                                                    data-user-name="{{ $user->name }}"
                                                    id="delete-btn-{{ $user->id }}">
                                                <i class="bi bi-trash3 text-danger"></i>
                                                <span class="action-text text-danger">Delete</span>
                                            </button>
                                        </li>
                                        @endcan

                                        {{-- ── Impersonate / Magic Link (Visible if logged-in user has impersonate-users permission) ── --}}
                                        @can('impersonate-users')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a href="{{ route('admin.impersonate', $user->id) }}" class="dropdown-item impersonate-btn d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-fill-exclamation text-info"></i> <span class="action-text">Impersonate</span>
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item magic-link-btn d-flex align-items-center gap-2"
                                                        data-user-id="{{ $user->id }}"
                                                        data-url="{{ route('admin.magic-link.generate', $user->id) }}"
                                                        id="magic-link-btn-{{ $user->id }}">
                                                    <i class="bi bi-link-45deg text-success"></i> <span class="action-text">Magic Link</span>
                                                </button>
                                            </li>
                                        @endcan
                                    </ul>
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
        @can('assign_roles')
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
        @endcan

        {{-- ── TAB 3: Trashed Users (admin role only) ────────────────── --}}
        @role('admin')
        <div class="tab-pane fade" id="trashed-panel" role="tabpanel">

            @if($trashedUsers->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-trash3 fs-1 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">No trashed users found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="trashed-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:35%">User</th>
                                <th style="width:20%">Joined</th>
                                <th style="width:20%">Deleted</th>
                                <th style="width:25%" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trashedUsers as $trashed)
                            <tr id="trashed-row-{{ $trashed->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center"
                                             style="width:34px;height:34px;flex-shrink:0">
                                            <span class="fw-500 text-danger" style="font-size:13px">
                                                {{ strtoupper(substr($trashed->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-500 text-nowrap text-muted" style="font-size:14px">{{ $trashed->name }}</p>
                                            <p class="mb-0 text-muted" style="font-size:12px">{{ $trashed->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted text-nowrap" style="font-size:13px">
                                        {{ $trashed->created_at->format('M d, Y') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-danger text-nowrap" style="font-size:13px"
                                          title="{{ $trashed->deleted_at->toDayDateTimeString() }}">
                                        {{ $trashed->deleted_at->format('M d, Y') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn btn-sm btn-outline-success restore-user-btn"
                                                data-user-id="{{ $trashed->id }}"
                                                data-user-name="{{ $trashed->name }}"
                                                id="restore-btn-{{ $trashed->id }}"
                                                title="Restore user">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                        </button>
                                        <button class="btn btn-sm btn-danger force-delete-user-btn"
                                                data-user-id="{{ $trashed->id }}"
                                                data-user-name="{{ $trashed->name }}"
                                                id="perm-delete-btn-{{ $trashed->id }}"
                                                title="Permanently delete — cannot be undone">
                                            <i class="bi bi-trash3-fill me-1"></i>Delete Permanently
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
        @endrole
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

{{-- ── Magic Link Modal ── --}}
<div class="modal fade" id="magicLinkModal" tabindex="-1" aria-labelledby="magicLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title d-flex align-items-center gap-2" id="magicLinkModalLabel">
                    <i class="bi bi-link-45deg text-secondary"></i>
                    Magic Login Link
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">This is a temporary signed URL valid for 15 minutes. Anyone with this link can login as this user.</p>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" id="magic-link-input" class="form-control" readonly style="background: var(--bs-light)">
                    <button class="btn btn-primary d-flex align-items-center gap-1" type="button" id="copy-magic-link-btn">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
                <div id="copy-success-msg" class="text-success small d-none">
                    <i class="bi bi-check-circle me-1"></i>Link copied to clipboard!
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
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
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        }
    };
    if (method !== 'GET' && method !== 'HEAD' && data) {
        options.body = JSON.stringify(data);
    }
    return fetch(url, options).then(r => r.json());
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
    const url = `/admin/user/${userId}/${action}`;

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> <span class="action-text">Saving…</span>';

    ajax(url, {}, 'POST')
        .then(res => {
            if (res.success) {
                // update button state
                if (isVerified) {
                    // user is now unverified
                    btn.className = 'dropdown-item toggle-verification-btn d-flex align-items-center gap-2';
                    btn.dataset.verified = '0';
                    btn.innerHTML = '<i class="bi bi-check-circle text-success"></i> <span class="action-text">Verify</span>';
                    
                    const badge = document.getElementById(`verification-status-${userId}`);
                    if (badge) {
                        badge.innerHTML = `<span class="badge bg-warning bg-opacity-10 text-warning fw-normal" style="font-size:10px; padding: 2px 6px">Unverified</span>`;
                    }
                } else {
                    // user is now verified
                    btn.className = 'dropdown-item toggle-verification-btn d-flex align-items-center gap-2';
                    btn.dataset.verified = '1';
                    btn.innerHTML = '<i class="bi bi-x-circle text-danger"></i> <span class="action-text">Unverify</span>';
                    
                    const badge = document.getElementById(`verification-status-${userId}`);
                    if (badge) {
                        badge.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success fw-normal" style="font-size:10px; padding: 2px 6px">Verified</span>`;
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

// ── force password reset (ajax) ───────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.force-reset-btn');
    if (!btn) return;

    if (!confirm('Are you sure you want to force a password reset for this user? They will be blocked from accessing the site until they complete the reset.')) {
        return;
    }

    const userId = btn.dataset.userId;
    const url = `/admin/user/${userId}/force-reset`;

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> <span class="action-text">Forcing…</span>';

    ajax(url, {}, 'POST')
        .then(res => {
            if (res.success) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-shield-slash text-warning"></i> <span class="action-text">Forced</span>';
                
                const statusContainer = document.getElementById(`force-reset-status-${userId}`);
                if (statusContainer) {
                    statusContainer.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger fw-normal" style="font-size:10px; padding: 2px 6px">Reset Pending</span>`;
                }
                notify('success', 'Forced Reset Triggered', res.message || 'User password reset forced successfully.');
            } else {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                notify('error', 'Failed', res.message || 'Could not force password reset.');
            }
        })
        .catch(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            notify('error', 'Error', 'Something went wrong. Please try again.');
        });
});

// ── impersonate loading spinner ───────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.impersonate-btn');
    if (!btn) return;

    // Show spinner in the link button
    const icon = btn.querySelector('i');
    if (icon) {
        icon.outerHTML = '<span class="spinner-border spinner-border-sm text-info me-1" role="status"></span>';
    }
    btn.classList.add('disabled');
});

// ── magic link generation & spinner (ajax) ────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.magic-link-btn');
    if (!btn) return;

    const url = btn.dataset.url;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm text-success me-1"></span> <span class="action-text">Generating…</span>';

    ajax(url, {}, 'GET')
        .then(res => {
            if (res.magic_link) {
                // Populate input and show modal
                const modalEl = document.getElementById('magicLinkModal');
                const input = document.getElementById('magic-link-input');
                input.value = res.magic_link;
                
                // Hide copy success message
                document.getElementById('copy-success-msg').classList.add('d-none');
                
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                notify('error', 'Failed', 'Could not generate magic link.');
            }
        })
        .catch(() => {
            notify('error', 'Error', 'Something went wrong. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
});

// ── copy magic link to clipboard ──────────────────────────────────────
document.getElementById('copy-magic-link-btn').addEventListener('click', function () {
    const input = document.getElementById('magic-link-input');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value)
        .then(() => {
            document.getElementById('copy-success-msg').classList.remove('d-none');
        })
        .catch(() => {
            notify('error', 'Error', 'Failed to copy to clipboard.');
        });
});

// ── soft-delete user ──────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-user-btn');
    if (!btn) return;

    const userId   = btn.dataset.userId;
    const userName = btn.dataset.userName;

    if (!confirm(`Delete "${userName}"? They will be moved to trash and can be restored later.`)) return;

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> <span class="action-text">Deleting…</span>';

    ajax(`/admin/user/${userId}/delete`, {}, 'DELETE')
        .then(res => {
            if (res.success) {
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (row) row.remove();

                const badge = document.querySelector('#trashed-tab .badge');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent || '0') + 1;
                } else {
                    const trashedTab = document.getElementById('trashed-tab');
                    if (trashedTab) trashedTab.insertAdjacentHTML('beforeend', '<span class="badge bg-danger bg-opacity-75 rounded-pill ms-1" style="font-size:10px">1</span>');
                }
                notify('success', 'User Deleted', res.message);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                notify('error', 'Failed', res.message || 'Could not delete user.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            notify('error', 'Error', 'Something went wrong. Please try again.');
        });
});

// ── restore trashed user ──────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.restore-user-btn');
    if (!btn) return;

    const userId   = btn.dataset.userId;
    const userName = btn.dataset.userName;

    if (!confirm(`Restore "${userName}"? They will be able to log in again.`)) return;

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Restoring…';

    ajax(`/admin/user/${userId}/restore`, {}, 'POST')
        .then(res => {
            if (res.success) {
                const row = document.getElementById(`trashed-row-${userId}`);
                if (row) row.remove();
                decrementTrashedBadge();
                notify('success', 'User Restored', res.message);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                notify('error', 'Failed', res.message || 'Could not restore user.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            notify('error', 'Error', 'Something went wrong. Please try again.');
        });
});

// ── permanently delete trashed user ──────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.force-delete-user-btn');
    if (!btn) return;

    const userId   = btn.dataset.userId;
    const userName = btn.dataset.userName;

    if (!confirm(`⚠️ Permanently delete "${userName}"?\n\nThis CANNOT be undone. All user data will be erased.`)) return;

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Deleting…';

    ajax(`/admin/user/${userId}/force-delete`, {}, 'DELETE')
        .then(res => {
            if (res.success) {
                const row = document.getElementById(`trashed-row-${userId}`);
                if (row) row.remove();
                decrementTrashedBadge();
                notify('success', 'Permanently Deleted', res.message);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                notify('error', 'Failed', res.message || 'Could not permanently delete user.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            notify('error', 'Error', 'Something went wrong. Please try again.');
        });
});

// ── helper: decrement trashed badge count ─────────────────────────────
function decrementTrashedBadge() {
    const badge = document.querySelector('#trashed-tab .badge');
    if (!badge) return;
    const current = parseInt(badge.textContent || '1') - 1;
    if (current <= 0) badge.remove();
    else badge.textContent = current;
}
</script>
@endpush