import "./bootstrap";
import "./products";
import "./notify.js";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

// Real-time Notification Listener
$(function () {
    const userId = $('meta[name="user-id"]').attr('content');
    const guard = $('meta[name="user-guard"]').attr('content');

    if (userId && guard && window.Echo) {
        const channelName = guard === 'admin' 
            ? `App.Models.Admin.${userId}` 
            : `App.Models.User.${userId}`;

        window.Echo.private(channelName)
            .notification((notification) => {
                // 1. Display Toast notification
                console.log(notification);
                if (window.notify) {
                    window.notify('info', 'New Notification', notification.message || 'You received a new update.');
                }

                // 2. Increment sidebar badge count
                const $sidebarBadge = $('#sidebar-unread');
                if ($sidebarBadge.length) {
                    let currentCount = parseInt($sidebarBadge.text()) || 0;
                    currentCount++;
                    $sidebarBadge.text(currentCount > 99 ? '99+' : currentCount).show();
                } else {
                    const $notifLink = $('aside#sidebar a[href$="/notifications"]');
                    if ($notifLink.length) {
                        $notifLink.append(`
                            <span class="ms-auto bg-indigo-500 text-white text-[10px] font-semibold min-w-[20px] h-5 flex items-center justify-center rounded-full px-1" id="sidebar-unread">
                                1
                            </span>
                        `);
                    }
                }

                // 3. Increment topbar bell icon badge
                const $bellBadge = $('#notification-badge');
                if ($bellBadge.length) {
                    let currentBell = parseInt($bellBadge.text()) || 0;
                    currentBell++;
                    $bellBadge.text(currentBell > 99 ? '99+' : currentBell).css('display', 'flex');
                }

                // 4. Refresh dropdown list if visible
                if (typeof window.fetchDropdownNotifications === 'function') {
                    window.fetchDropdownNotifications();
                }

                // 5. Refresh full panel if on notifications page
                if (typeof window.refreshFullNotifications === 'function') {
                    window.refreshFullNotifications();
                }
            });
    }
});

