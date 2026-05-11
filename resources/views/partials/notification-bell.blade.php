{{-- resources/views/partials/notification-bell.blade.php --}}
{{-- Notification bell dropdown — included in both customer navbar and admin sidebar header --}}
@auth
<div class="notification-bell-wrapper" style="position:relative;display:inline-flex;">

    {{-- Bell trigger --}}
    <button id="notification-bell-toggle" type="button"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition"
            title="Notifications" aria-label="Notifications" aria-expanded="false" aria-controls="notification-dropdown">
        <i class="bi bi-bell text-xl leading-none"></i>
        {{-- Unread badge --}}
        <span id="notification-badge"
              class="absolute -top-1 -end-1 bg-red-500 text-white text-[9px] min-w-[16px] h-4 flex items-center justify-center rounded-full px-0.5"
              style="display:none;">
            0
        </span>
    </button>

    {{-- Dropdown panel --}}
    <div id="notification-dropdown"
         class="notification-dropdown"
         style="display:none; position:absolute; top:calc(100% + 8px); inset-inline-end:0; width:360px; max-height:460px;
                background:#1f2937; border:1px solid #374151; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,.45);
                z-index:9999; overflow:hidden;">

        {{-- Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px 10px; border-bottom:1px solid #374151;">
            <span style="font-weight:600; font-size:15px; color:#f9fafb;">
                <i class="bi bi-bell-fill" style="color:#60a5fa; margin-inline-end:6px;"></i>Notifications
            </span>
            <div style="display:flex; align-items:center; gap:10px;">
                <button id="notification-mark-all" type="button"
                        style="background:none; border:none; color:#60a5fa; font-size:12px; cursor:pointer; padding:0;"
                        title="Mark all as read">
                    Mark all read
                </button>
                <a href="{{ route('notifications.index') }}"
                   style="color:#9ca3af; font-size:12px; text-decoration:none;"
                   title="View all notifications">
                    View all
                </a>
            </div>
        </div>

        {{-- Notification list --}}
        <div id="notification-list" style="overflow-y:auto; max-height:380px; padding:4px 0;">
            <div style="text-align:center; padding:30px 16px; color:#6b7280; font-size:13px;">
                <i class="bi bi-bell-slash" style="font-size:24px; display:block; margin-bottom:8px; opacity:.5;"></i>
                Loading…
            </div>
        </div>
    </div>
</div>
@endauth

@auth
<style>
    .notification-dropdown::-webkit-scrollbar { width: 5px; }
    .notification-dropdown::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 10px; }
    .notification-item { display:flex; align-items:flex-start; gap:10px; padding:10px 16px; text-decoration:none; color:#e5e7eb; transition:background .15s; border-inline-start:3px solid transparent; }
    .notification-item:hover { background:#374151; }
    .notification-item.unread { border-inline-start-color:#60a5fa; background:rgba(96,165,250,.06); }
    .notification-item .notif-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:15px; }
    .notification-item .notif-body { flex:1; min-width:0; }
    .notification-item .notif-msg { font-size:13px; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .notification-item .notif-time { font-size:11px; color:#6b7280; margin-top:3px; }
    .notification-item .notif-dot { width:8px; height:8px; border-radius:50%; background:#60a5fa; flex-shrink:0; margin-top:5px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bell     = document.getElementById('notification-bell-toggle');
    const dropdown = document.getElementById('notification-dropdown');
    const badge    = document.getElementById('notification-badge');
    const list     = document.getElementById('notification-list');
    const markAll  = document.getElementById('notification-mark-all');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!bell) return;

    // Icon map
    const iconMap = {
        truck:   { icon: 'bi-truck',        bg: '#1e3a5f', color: '#60a5fa' },
        order:   { icon: 'bi-bag-check',    bg: '#1a3a2a', color: '#34d399' },
        alert:   { icon: 'bi-exclamation-triangle', bg: '#3a2a1a', color: '#fbbf24' },
        default: { icon: 'bi-bell',         bg: '#2d2d3d', color: '#a78bfa' },
    };

    // ── Toggle dropdown ─────────────────────────────
    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = dropdown.style.display === 'none';
        dropdown.style.display = open ? 'block' : 'none';
        bell.setAttribute('aria-expanded', open);
        if (open) fetchNotifications();
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.style.display = 'none';
            bell.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            bell.setAttribute('aria-expanded', 'false');
        }
    });

    // ── Fetch unread count (cached 60s server-side) ─
    function refreshBadge () {
        fetch('{{ route("notifications.unread") }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                const c = d.count || 0;
                badge.textContent = c > 99 ? '99+' : c;
                badge.style.display = c > 0 ? 'flex' : 'none';
            })
            .catch(() => {});
    }

    // ── Fetch latest 10 notifications ───────────────
    function fetchNotifications () {
        fetch('{{ route("notifications.index") }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => renderList(d.data || []))
            .catch(() => {
                list.innerHTML = '<div style="text-align:center;padding:30px 16px;color:#6b7280;font-size:13px;">Unable to load.</div>';
            });
    }

    function renderList (items) {
        if (!items.length) {
            list.innerHTML = '<div style="text-align:center;padding:30px 16px;color:#6b7280;font-size:13px;"><i class="bi bi-bell-slash" style="font-size:24px;display:block;margin-bottom:8px;opacity:.5;"></i>No notifications yet</div>';
            return;
        }

        list.innerHTML = items.slice(0, 10).map(n => {
            const data   = n.data || {};
            const msg    = data.message || 'New notification';
            const icon   = iconMap[data.icon] || iconMap.default;
            const unread = !n.read_at;
            const time   = timeAgo(n.created_at);
            const url    = `/notifications/${n.id}/read`;

            return `
                <a href="#" class="notification-item ${unread ? 'unread' : ''}" data-url="${url}" data-id="${n.id}">
                    <div class="notif-icon" style="background:${icon.bg};color:${icon.color};">
                        <i class="bi ${icon.icon}"></i>
                    </div>
                    <div class="notif-body">
                        <div class="notif-msg">${escapeHtml(msg)}</div>
                        <div class="notif-time"><i class="bi bi-clock" style="margin-inline-end:3px;"></i>${time}</div>
                    </div>
                    ${unread ? '<span class="notif-dot"></span>' : ''}
                </a>`;
        }).join('');

        // Click → PATCH mark-as-read → redirect
        list.querySelectorAll('.notification-item').forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.dataset.url;
                // Use a hidden form to PATCH
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="_method" value="PATCH">`;
                document.body.appendChild(form);
                form.submit();
            });
        });
    }

    // ── Mark all read ───────────────────────────────
    markAll?.addEventListener('click', function () {
        fetch('{{ route("notifications.markAllRead") }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(() => {
            badge.style.display = 'none';
            badge.textContent = '0';
            // Remove unread styling
            list.querySelectorAll('.notification-item.unread').forEach(el => {
                el.classList.remove('unread');
                el.querySelector('.notif-dot')?.remove();
            });
        })
        .catch(() => {});
    });

    // ── Helpers ─────────────────────────────────────
    function timeAgo (dateStr) {
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60)    return 'just now';
        if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return new Date(dateStr).toLocaleDateString();
    }

    function escapeHtml (s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // Initial badge load
    refreshBadge();
    // Refresh badge every 60s
    setInterval(refreshBadge, 60000);
});
</script>
@endauth
