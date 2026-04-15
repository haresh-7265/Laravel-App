export function showOrderToast(data) {

    let html = `
        <strong>${data.message}</strong><br>
        Order: <b>${data.order_number}</b><br>
        Customer: ${data.customer_name}<br>
        Items: ${data.items_count}<br>
        Total: ₹${data.order_total}<br>
        <small>${data.time}</small>
    `;

    $('#toastBody').html(html);

    // Clickable notification
    $('#toastBody').off('click').on('click', function () {
        window.location.href = `/admin/orders/${data.order_number}`;
    });

    let toast = new bootstrap.Toast($('#orderToast')[0], {
        delay: 5000
    });

    toast.show();
}