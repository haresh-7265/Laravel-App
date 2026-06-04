{{-- resources/views/partials/notification-bell.blade.php --}}
@anyauth
<div class="relative inline-flex" id="bell-dropdown-wrapper">

    {{-- Bell trigger --}}
    <button id="notification-bell-toggle" type="button"
            class="relative flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900 transition-colors focus:outline-none"
            title="Notifications" aria-label="Notifications" aria-expanded="false">
        <i class="bi bi-bell text-lg"></i>
        {{-- Unread badge --}}
        <span id="notification-badge"
              class="absolute top-1.5 end-1.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold flex items-center justify-center rounded-full leading-none"
              style="display:none;">
            0
        </span>
    </button>

    {{-- Dropdown panel (Flat UI, no gradients or shadows) --}}
    <div id="notification-dropdown"
         class="hidden absolute end-0 mt-11 w-80 bg-white border border-gray-200 rounded-lg z-50 overflow-hidden"
         style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
            <span class="text-xs font-semibold text-gray-900">Notifications</span>
            <button id="notification-mark-all" type="button"
                    class="text-xs font-semibold text-blue-600 hover:underline bg-transparent border-none p-0 focus:outline-none cursor-pointer">
                Mark all read
            </button>
        </div>

        {{-- Notification list --}}
        <div id="notification-list" class="overflow-y-auto max-h-[320px] divide-y divide-gray-100">
            <div class="text-center py-8 text-gray-400 text-xs">
                <i class="bi bi-bell-slash text-xl block mb-2 opacity-50"></i>
                Loading notifications...
            </div>
        </div>

        {{-- Footer --}}
        <a href="{{ route('notifications.index') }}" id="go-to-full-panel"
           class="block text-center py-2.5 bg-gray-50 border-t border-gray-100 text-xs font-semibold text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors">
            View all notifications
        </a>
    </div>
</div>
@endanyauth

@anyauth
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bell      = document.getElementById('notification-bell-toggle');
    const dropdown  = document.getElementById('notification-dropdown');
    const badge     = document.getElementById('notification-badge');
    const list      = document.getElementById('notification-list');
    const markAll   = document.getElementById('notification-mark-all');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const isFullPage = !!document.getElementById('notif-tbody');

    if (!bell || !dropdown) return;

    // Toggle Dropdown
    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.style.display !== 'none' && !dropdown.classList.contains('hidden');
        if (isOpen) {
            dropdown.style.display = 'none';
            dropdown.classList.add('hidden');
        } else {
            dropdown.style.display = 'block';
            dropdown.classList.remove('hidden');
            fetchDropdownNotifications();
        }
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.style.display = 'none';
            dropdown.classList.add('hidden');
        }
    });

    // Handle dropdown mark all read
    markAll?.addEventListener('click', function (e) {
        e.stopPropagation();
        fetch('{{ route("notifications.read-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                updateBadge(0);
                fetchDropdownNotifications();
                // If we are on the main notifications index page, refresh its list too
                if (typeof window.refreshFullNotifications === 'function') {
                    window.refreshFullNotifications();
                }
            }
        })
        .catch(() => {});
    });

    // Fetch list for dropdown
    function fetchDropdownNotifications() {
        fetch('{{ route("notifications.index") }}?limit=10', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            renderDropdown(res.data || []);
            if (res.stats) {
                updateBadge(res.stats.unread);
            }
        })
        .catch(() => {
            list.innerHTML = `<div class="text-center py-6 text-xs text-red-500">Failed to load.</div>`;
        });
    }

    function renderDropdown(items) {
        if (!items.length) {
            list.innerHTML = `
                <div class="text-center py-8 text-gray-400 text-xs">
                    <i class="bi bi-bell-slash text-xl block mb-2 opacity-50"></i>
                    No notifications yet
                </div>`;
            return;
        }

        list.innerHTML = items.map(n => {
            // Design rules:
            // Unread = light blue background + blue left dot
            // Read = white background + no dot
            const bgClass = n.is_unread ? 'bg-blue-50 border-l-[3px] border-blue-500' : 'bg-white border-l-[3px] border-transparent';
            const dot = n.is_unread ? '<span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></span>' : '';
            
            return `
                <a href="/notifications/${n.id}/read" class="dropdown-item-link flex items-start gap-2.5 p-3 hover:bg-gray-50 transition-colors ${bgClass}" data-id="${n.id}">
                    <div class="w-8 h-8 rounded-full ${n.avatar_bg} ${n.avatar_text} flex items-center justify-center text-sm flex-shrink-0">
                        <i class="bi ${n.icon}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-900 leading-snug">${n.message}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="inline-block px-1.5 py-0.5 text-[9px] font-semibold rounded ${n.category_tag_class}">${n.category}</span>
                            <span class="text-[10px] text-gray-400">${n.time}</span>
                        </div>
                    </div>
                    ${dot}
                </a>`;
        }).join('');

        // Intercept dropdown clicks to mark as read and redirect via AJAX so counter updates
        list.querySelectorAll('.dropdown-item-link').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                fetch(`/notifications/${id}/read`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    // Update badge count
                    if (res.stats) updateBadge(res.stats.unread);
                    if (typeof window.refreshFullNotifications === 'function') {
                        window.refreshFullNotifications();
                    }
                    if (res.redirect_url) {
                        window.location.href = res.redirect_url;
                    }
                });
            });
        });
    }

    function updateBadge(c) {
        badge.textContent = c > 99 ? '99+' : c;
        badge.style.display = c > 0 ? 'flex' : 'none';
        
        // Update sidebar count if visible
        const sidebarUnread = document.getElementById('sidebar-unread');
        if (sidebarUnread) {
            sidebarUnread.textContent = c > 99 ? '99+' : c;
            sidebarUnread.style.display = c > 0 ? 'flex' : 'none';
        }
    }

    // Refresh unread count badge
    function fetchUnreadBadge() {
        fetch('{{ route("notifications.unread") }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => updateBadge(d.count || 0))
            .catch(() => {});
    }

    // fetchUnreadBadge();
    // setInterval(fetchUnreadBadge, 30000);
    
    // Bind to window to allow full page JS to trigger badge updates
    window.updateBellBadge = updateBadge;
    window.fetchDropdownNotifications = fetchDropdownNotifications;
});
</script>
@endanyauth
