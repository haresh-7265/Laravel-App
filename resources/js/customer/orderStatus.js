const STEPS = ["pending", "processing", "shipped", "delivered"];

function canCancel(status) {
    return ["pending", "processing"].includes(status);
}

function updateBadge(newStatus) {
    const badgeMap = {
        pending: "warning",
        processing: "primary",
        shipped: "info",
        delivered: "success",
        cancelled: "danger",
    };

    const badge = badgeMap[newStatus] ?? "secondary";

    $("#orderStatusBadge")
        .removeClass(
            "bg-warning bg-primary bg-info bg-success bg-danger bg-secondary",
        )
        .addClass("bg-" + badge)
        .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
}

function updateProgressUI(newStatus) {
    const $card = $("#orderProgressCard");
    const $cancelButton = $("#cancelBtn");
    if (!$card.length) return;

    // Hide entire card if cancelled
    if (newStatus === "cancelled") {
        $card.hide();
        $cancelButton.empty();
        return;
    }

    const currentIndex = STEPS.indexOf(newStatus);

    $.each(STEPS, function (index) {
        const isActive = currentIndex >= index;

        const $circle = $("#step-circle-" + index);
        const $label = $("#step-label-" + index);
        const $connector = $("#step-connector-" + index);

        // Update circle
        $circle
            .toggleClass("bg-primary text-white", isActive)
            .toggleClass("bg-light text-muted", !isActive);

        // Update label
        $label
            .toggleClass("text-primary fw-semibold", isActive)
            .toggleClass("text-muted", !isActive);

        // Update connector
        if ($connector.length) {
            $connector.css(
                "background",
                currentIndex > index ? "#0d6efd" : "#dee2e6",
            );
        }
    });

    // Update cancel notice
    const $cancelWrapper = $("#cancelOrderWrapper");
    if (canCancel(newStatus)) {
        $cancelWrapper.html(
            '<p class="text-muted small text-center mb-0 mt-3">' +
                '<i class="bi bi-info-circle me-1"></i>' +
                "You can cancel this order until it has been shipped." +
                "</p>",
        );
    } else {
        $cancelWrapper.empty();
        $cancelButton.empty();
    }
}

function getOrderId() {
    return $("#orderId").val();
}

window.Echo.private(`order.${getOrderId()}`).listen(
    ".OrderStatusUpdated",
    function (payload) {
        updateProgressUI(payload.status);
        updateBadge(payload.status);
    },
);
