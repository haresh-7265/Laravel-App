/**
 * resources/js/admin/orders-infinite.js
 * jQuery-based infinite scroll for orders table using cursor pagination.
 */

$(function () {
    const S = window._ordersState;

    const $tbody    = $('#orders-tbody');
    const $loading  = $('#loading-row');
    const $empty    = $('#empty-state');
    const $end      = $('#end-of-results');
    const $sentinel = $('#scroll-sentinel');
    const $table    = $('#orders-table');

    const statusColors = {
        pending:    { bg: 'bg-amber-100',  text: 'text-amber-700'  },
        processing: { bg: 'bg-blue-100',   text: 'text-blue-700'   },
        shipped:    { bg: 'bg-sky-100',     text: 'text-sky-700'    },
        delivered:  { bg: 'bg-green-100',  text: 'text-green-700'  },
        cancelled:  { bg: 'bg-red-100',    text: 'text-red-700'    },
    };

    const paymentColors = {
        paid:   { bg: 'bg-green-100', text: 'text-green-700' },
        unpaid: { bg: 'bg-red-100',   text: 'text-red-700'   },
    };

    // ─── Fetch ────────────────────────────────────────────────────────────────

    function fetchOrders() {
        if (S.loading || !S.hasMore) return;

        S.loading = true;
        $loading.removeClass('hidden').addClass('flex');
        $empty.addClass('hidden');

        const params = $.extend({}, S.filters, { cursor: S.cursor });

        // strip empty filter values
        Object.keys(params).forEach(k => {
            if (params[k] === '' || params[k] === null || params[k] === undefined) {
                delete params[k];
            }
        });

        $.ajax({
            url:      S.fetchUrl,
            method:   'GET',
            dataType: 'json',
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': S.csrfToken,
            },
            data: params,

            success(response) {
                const { data, next_cursor, has_more } = response;

                if (data.length === 0 && S.rowCount === 0) {
                    showEmpty();
                    return;
                }

                data.forEach(order => appendRow(order));

                S.cursor  = next_cursor;
                S.hasMore = has_more;

                if (!S.hasMore) {
                    $sentinel.remove();
                    $end.removeClass('hidden');
                }
            },

            error(xhr) {
                if (xhr.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                if (xhr.status === 419) {
                    // CSRF expired — reload
                    window.location.reload();
                    return;
                }
                console.error('Orders fetch failed', xhr.responseJSON ?? xhr.statusText);
            },

            complete() {
                S.loading = false;
                $loading.addClass('hidden').removeClass('flex');
            },
        });
    }

    // ─── Row builder ──────────────────────────────────────────────────────────

    function appendRow(order) {
        S.rowCount++;

        const $template = $('#row-template').prop('content');
        const $row      = $($template).find('tr').clone();

        // index
        $row.find('.row-index').text(S.rowCount);

        // order number
        $row.find('.row-order-number').text(order.order_number);

        // customer avatar + info
        const initial = (order.customer_name ?? 'U').charAt(0).toUpperCase();
        $row.find('.row-avatar').text(initial);
        $row.find('.row-customer-name').text(order.customer_name ?? '—');
        $row.find('.row-customer-email').text(order.customer_email ?? '');

        // items thumbnails
        const $items = $row.find('.row-items');
        (order.items ?? []).slice(0, 2).forEach(item => {
            if (item.image) {
                $items.append(
                    $('<img>')
                        .attr('src', item.image_url)
                        .attr('title', item.product_name)
                        .addClass('w-8 h-8 rounded border object-cover flex-shrink-0')
                );
            } else {
                $items.append(
                    $('<div>')
                        .addClass('w-8 h-8 rounded border bg-gray-100 flex items-center justify-center')
                        .html('<i class="bi bi-image text-gray-400 text-xs"></i>')
                );
            }
        });
        if ((order.items ?? []).length > 2) {
            $items.append(
                $('<span>').addClass('text-xs text-gray-400').text(`+${order.items.length - 2}`)
            );
        }

        // total
        $row.find('.row-total').text(order.total_formatted ?? `₹${order.total}`);

        // payment badge
        const pc = paymentColors[order.payment_status] ?? { bg: 'bg-gray-100', text: 'text-gray-600' };
        $row.find('.row-payment-badge').html(
            `<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full ${pc.bg} ${pc.text}">
                ${ucfirst(order.payment_status)}
            </span>`
        );
        $row.find('.row-payment-method').text((order.payment_method ?? '').toUpperCase());

        // order status badge
        const sc = statusColors[order.status] ?? { bg: 'bg-gray-100', text: 'text-gray-600' };
        $row.find('.row-status-badge').html(
            `<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full ${sc.bg} ${sc.text}">
                ${ucfirst(order.status)}
            </span>`
        );

        // date
        $row.find('.row-date').text(order.created_date ?? '');
        $row.find('.row-time').text(order.created_time ?? '');
        $row.find('.row-ago').text(order.created_ago ?? '');

        // view link
        $row.find('.row-view-link').attr(
            'href', S.showUrl.replace('__ID__', order.order_number)
        );

        $tbody.append($row);
    }

    // ─── Empty state ──────────────────────────────────────────────────────────

    function showEmpty() {
        $table.addClass('hidden');
        $end.addClass('hidden');
        $empty.removeClass('hidden');

        const hasFilters = Object.values(S.filters).some(v => v !== '' && v !== null);
        if (hasFilters) {
            $('#empty-message').text('No orders match your current filters.');
            $('#btn-clear-empty').removeClass('hidden');
        } else {
            $('#empty-message').text('No orders have been placed yet.');
            $('#btn-clear-empty').addClass('hidden');
        }
    }

    function resetList() {
        S.cursor   = null;
        S.hasMore  = true;
        S.rowCount = 0;

        $tbody.empty();
        $end.addClass('hidden');
        $empty.addClass('hidden');
        $table.removeClass('hidden');

        // re-add sentinel if removed
        if ($('#scroll-sentinel').length === 0) {
            $('<div id="scroll-sentinel" class="h-2">').appendTo('#orders-table').closest('.bg-white');
        }
    }

    // ─── Filters ──────────────────────────────────────────────────────────────

    function applyFilters() {
        S.filters.search         = $('#filter-search').val().trim();
        S.filters.status         = $('#filter-status').val();
        S.filters.payment_status = $('#filter-payment').val();
        S.filters.date           = $('#filter-date').val();

        resetList();
        fetchOrders();
    }

    function clearFilters() {
        $('#filter-search').val('');
        $('#filter-status').val('');
        $('#filter-payment').val('');
        $('#filter-date').val('');

        S.filters = { search: '', status: '', payment_status: '', date: '' };

        // clear active stat card
        $('.stat-card').removeClass('ring-2 ring-offset-1');

        resetList();
        fetchOrders();
    }

    $('#btn-filter').on('click', applyFilters);
    $('#btn-clear').on('click', clearFilters);
    $('#btn-clear-empty').on('click', clearFilters);

    // search on Enter key
    $('#filter-search').on('keydown', function (e) {
        if (e.key === 'Enter') applyFilters();
    });

    // stat cards — click to filter by status
    $(document).on('click', '.stat-card', function () {
        const status = $(this).data('stat-status');

        $('.stat-card').removeClass('ring-2 ring-offset-1');
        $(this).addClass('ring-2 ring-offset-1');

        $('#filter-status').val(status);
        S.filters.status = status;

        resetList();
        fetchOrders();
    });

    // ─── Intersection Observer (infinite scroll trigger) ──────────────────────

    const observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting && !S.loading && S.hasMore) {
            fetchOrders();
        }
    }, {
        rootMargin: '300px', // trigger 300px before sentinel hits viewport
        threshold:  0,
    });

    if ($sentinel.length) {
        observer.observe($sentinel[0]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // ─── Init ─────────────────────────────────────────────────────────────────

    fetchOrders(); // load first page on mount
});

// scroll to top button visibility
$(window).on('scroll', function () {
    if ($(this).scrollTop() > 300) {
        $('#scroll-top').removeClass('hidden').addClass('flex');
    } else {
        $('#scroll-top').addClass('hidden').removeClass('flex');
    }
});