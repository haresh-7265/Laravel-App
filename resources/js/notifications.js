/**
 * notifications.js
 * Frontend controller for the notifications system index page.
 * Uses Flat UI, handles dynamic grouping, live stats updating, and tab filtering.
 */
(function ($) {
    'use strict';

    const S = window._notifState;
    const CSRF = S.csrfToken;
    
    let currentPage = 1;
    let activeFilter = ''; // '', 'unread', 'read'

    $(function () {
        if ($('#notif-groups-container').length === 0) return;

        initPage();
        bindEvents();
        
        // Expose a hook so that when the bell dropdown changes unread notifications,
        // we can refresh the main panel's contents in real-time.
        window.refreshFullNotifications = function() {
            loadPage(currentPage);
        };
    });

    function initPage() {
        // Activate correct filter tab visual style
        updateTabStyles();
        loadPage(1);
    }

    function updateTabStyles() {
        $('.filter-tab').removeClass('border-blue-600 text-blue-600 text-gray-900').addClass('border-transparent text-gray-500 hover:text-gray-900');
        
        const $active = $('.filter-tab').filter(function() {
            return $(this).data('status') === activeFilter;
        });
        
        $active.addClass('border-blue-600 text-blue-600 font-bold').removeClass('text-gray-500 border-transparent');
    }

    function loadPage(page) {
        currentPage = page;
        showLoading(true);

        $.ajax({
            url: S.fetchUrl,
            data: { page: currentPage, status: activeFilter },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                showLoading(false);
                renderGroupedNotifications(res.data || []);
                renderPagination(res.current_page, res.last_page, res.total);
                updateStatsAndTabs(res.stats);
                
                // Keep the topbar bell badge synchronized
                if (res.stats && typeof window.updateBellBadge === 'function') {
                    window.updateBellBadge(res.stats.unread);
                }
            },
            error: function () {
                showLoading(false);
                showEmptyState('Could not load notifications. Please try again.');
            }
        });
    }

    function renderGroupedNotifications(items) {
        const $container = $('#notif-groups-container').empty();
        $('#empty-state').addClass('hidden');

        if (!items.length) {
            showEmptyState(activeFilter === 'unread' ? 'You have no unread notifications.' : 
                           activeFilter === 'read' ? 'You have no read notifications.' : 
                           'You have no notifications yet.');
            return;
        }

        // Group items by "Today", "Yesterday", "Earlier"
        const groups = {
            'Today': [],
            'Yesterday': [],
            'Earlier': []
        };

        items.forEach(item => {
            if (groups[item.group] !== undefined) {
                groups[item.group].push(item);
            } else {
                groups['Earlier'].push(item);
            }
        });

        // Render each group
        Object.keys(groups).forEach(groupName => {
            const list = groups[groupName];
            if (list.length > 0) {
                // Group Header
                const $section = $('<div class="notif-group-section mb-6"></div>');
                $section.append(`<h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">${groupName}</h3>`);
                
                const $listContainer = $('<div class="space-y-3"></div>');
                
                list.forEach(n => {
                    $listContainer.append(buildNotificationRow(n));
                });
                
                $section.append($listContainer);
                $container.append($section);
            }
        });
    }

    function buildNotificationRow(n) {
        const tpl = document.getElementById('row-template');
        const node = tpl.content.cloneNode(true);
        const $row = $(node.querySelector('.notif-row'));

        $row.attr('data-id', n.id);

        // Styling based on Read / Unread state
        if (n.is_unread) {
            // Unread = light blue background + blue left dot
            $row.addClass('bg-blue-50/70 border-blue-200');
            $row.find('.row-dot').show();
            
            // Unread status pill
            $row.find('.row-status-pill')
                .addClass('bg-blue-100 text-blue-800')
                .html('<span class="w-1.5 h-1.5 rounded-full bg-blue-600 block"></span> Unread');
        } else {
            // Read = white background + no dot
            $row.addClass('bg-white border-gray-200');
            $row.find('.row-dot').hide();
            
            // Read status pill
            $row.find('.row-status-pill')
                .addClass('bg-gray-100 text-gray-600')
                .html('<i class="bi bi-check2 text-xs"></i> Read');
        }

        // Avatar styling
        $row.find('.row-avatar')
            .addClass(`${n.avatar_bg} ${n.avatar_text}`)
            .find('i').addClass(n.icon);

        // Message & Description
        $row.find('.row-message').html(n.message);
        $row.find('.row-description').text(n.description);

        // Category Tag
        $row.find('.row-category')
            .addClass(n.category_tag_class)
            .text(n.category);

        // Time ago text
        $row.find('.row-time-text').text(n.time);

        // Click interaction: Mark read & update counts
        $row.on('click', function (e) {
            e.preventDefault();
            handleRowClick(n.id, $row);
        });

        // Delete interaction
        $row.find('.row-delete-btn').on('click', function (e) {
            e.stopPropagation();
            handleRowDelete(n.id, $row);
        });

        return $row;
    }

    function handleRowClick(id, $row) {
        // If already read, only navigate if needed
        const isUnread = $row.find('.row-dot').is(':visible');
        
        $row.css('opacity', '0.6');

        $.ajax({
            url: S.readUrl.replace('__ID__', id),
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (res) {
                $row.css('opacity', '1');
                
                // Update row styling to read
                $row.removeClass('bg-blue-50/70 border-blue-200').addClass('bg-white border-gray-200');
                $row.find('.row-dot').hide();
                $row.find('.row-status-pill')
                    .removeClass('bg-blue-100 text-blue-800')
                    .addClass('bg-gray-100 text-gray-600')
                    .html('<i class="bi bi-check2 text-xs"></i> Read');
                
                // Update stats and tabs
                if (res.stats) {
                    updateStatsAndTabs(res.stats);
                    if (typeof window.updateBellBadge === 'function') {
                        window.updateBellBadge(res.stats.unread);
                    }
                }

                // If dropdown list is visible, update it too
                if (typeof window.fetchDropdownNotifications === 'function') {
                    window.fetchDropdownNotifications();
                }

                // If there's a redirect URL, redirect
                if (res.redirect_url) {
                    window.location.href = res.redirect_url;
                }
            },
            error: function () {
                $row.css('opacity', '1');
                if (window.notify) window.notify('error', 'Error', 'Failed to mark notification as read.');
            }
        });
    }

    function handleRowDelete(id, $row) {
        if (!confirm('Are you sure you want to delete this notification?')) {
            return;
        }

        $row.css('opacity', '0.5');

        $.ajax({
            url: S.deleteUrl.replace('__ID__', id),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (res) {
                $row.fadeOut(300, function () {
                    loadPage(currentPage);
                });
                
                if (window.notify) {
                    window.notify('success', 'Deleted', 'Notification deleted successfully.');
                }
            },
            error: function () {
                $row.css('opacity', '1');
                if (window.notify) {
                    window.notify('error', 'Error', 'Failed to delete notification.');
                }
            }
        });
    }

    function updateStatsAndTabs(stats) {
        if (!stats) return;

        // Update Stats Cards
        $('#stat-total').text(stats.total);
        $('#stat-unread').text(stats.unread);
        $('#stat-read').text(stats.read);

        // Update Tab Pills
        $('.tab-count-all').text(stats.total);
        $('.tab-count-unread').text(stats.unread);
        $('.tab-count-read').text(stats.read);
    }

    function showLoading(show) {
        if (show) {
            $('#loading-spinner').removeClass('hidden');
            $('#notif-groups-container').addClass('opacity-50 pointer-events-none');
            $('#empty-state').addClass('hidden');
        } else {
            $('#loading-spinner').addClass('hidden');
            $('#notif-groups-container').removeClass('opacity-50 pointer-events-none');
        }
    }

    function showEmptyState(msg) {
        $('#empty-message').text(msg);
        $('#empty-state').removeClass('hidden');
        $('#notif-pagination').addClass('hidden');
    }

    function bindEvents() {
        // Tab click
        $('.filter-tab').on('click', function () {
            activeFilter = $(this).data('status');
            updateTabStyles();
            loadPage(1);
        });

        // Mark all read button
        $('#mark-all-btn').on('click', function () {
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: S.markAllUrl,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (res) {
                    $btn.prop('disabled', false);
                    loadPage(currentPage);
                    if (window.notify) window.notify('success', 'Success', 'All notifications marked as read.');
                },
                error: function () {
                    $btn.prop('disabled', false);
                    if (window.notify) window.notify('error', 'Error', 'Failed to mark notifications read.');
                }
            });
        });

        // Pagination buttons
        $(document).on('click', '.pagination-btn', function () {
            const page = parseInt($(this).data('page'));
            if (!isNaN(page)) {
                loadPage(page);
            }
        });
    }

    function renderPagination(current, last, total) {
        const $wrap = $('#notif-pagination');
        if (last <= 1) { 
            $wrap.addClass('hidden'); 
            return; 
        }

        $wrap.removeClass('hidden');
        $('#pagination-info').text(`Page ${current} of ${last} · ${total} total`);

        let btns = '';
        btns += `<button class="pagination-btn px-2.5 py-1.5 text-xs border border-gray-200 bg-white rounded-lg text-gray-500 hover:bg-gray-50 disabled:opacity-30 transition-colors"
                          data-page="${current - 1}" ${current === 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i>
                 </button>`;

        for (let p = 1; p <= last; p++) {
            const active = p === current
                ? 'bg-gray-900 text-white border-gray-900 font-bold'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50';
            btns += `<button class="pagination-btn px-2.5 py-1.5 text-xs border rounded-lg transition-colors ${active}"
                              data-page="${p}">${p}</button>`;
        }

        btns += `<button class="pagination-btn px-2.5 py-1.5 text-xs border border-gray-200 bg-white rounded-lg text-gray-500 hover:bg-gray-50 disabled:opacity-30 transition-colors"
                          data-page="${current + 1}" ${current === last ? 'disabled' : ''}>
                    <i class="bi bi-chevron-right"></i>
                 </button>`;

        $('#pagination-buttons').html(btns);
    }

}(jQuery));
