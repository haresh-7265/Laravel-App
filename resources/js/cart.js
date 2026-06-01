$(document).ready(function () {
    $(document).on(
        "submit",
        "#cart-form, .ajax-add-to-cart-form",
        function (e) {
            e.preventDefault();
            let $form = $(this);
            let url = $form.attr("action");
            let submitBtn = $form.find('button[type="submit"]');
            let originalLabel = submitBtn.html();
            submitBtn.prop("disabled", true);

            $.ajax({
                url: url,
                method: "POST",
                data: $form.serialize(),
                dataType: "json",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                beforeSend: function () {
                    submitBtn.text("Adding...");
                },
                success: function (res) {
                    submitBtn.prop("disabled", false).html(originalLabel);
                    if (res.status === "success") {
                        updateCartBadge(res.cart_count ?? res.count ?? 0);
                    }
                    window.notify(res.status, res.message);
                },
                error: function (xhr) {
                    submitBtn.prop("disabled", false).html(originalLabel);
                    window.notify(
                        "error",
                        xhr.responseJSON?.message ??
                            xhr.responseJSON?.error ??
                            "Something went wrong.",
                    );
                },
            });
        },
    );

    let productId = $("#productId").val();

    if (productId && window.Echo) {
        window.Echo.channel(`product.${productId}`).listen(
            ".ProductStockChanged",
            function (e) {
                updateStockUI(e);
            },
        );
    }
});

const CSRF = $('meta[name="csrf-token"]').attr("content") ?? "";

/* ── AJAX helper ── */
function request(url, method = "POST", body = {}) {
    return $.ajax({
        url,
        method,
        contentType: "application/json",
        dataType: "json",
        headers: {
            "X-CSRF-TOKEN": CSRF,
            "X-Requested-With": "XMLHttpRequest",
        },
        data: method !== "GET" ? JSON.stringify(body) : undefined,
    });
}

/* ── Apply server response to DOM ── */
function applyCartResponse(data) {
    window.notify(data.status, data.message);
    if (data.empty) {
        updateCartBadge(data.count);
        showEmptyState();
        return;
    }
    if (data.items_html) $("#cart-items-list").html(data.items_html);
    if (data.summary_html) $("#order-summary").html(data.summary_html);
    if (data.shipping_html) $("#shipping-bar").html(data.shipping_html);
    if (data.count != null) updateCountBadge(data.count);
}

function showEmptyState() {
    $("#cart-layout").addClass("d-none");
    $("#empty-cart").removeClass("d-none");
    $("#cart-count-badge").hide();
}

function updateCountBadge(count) {
    updateCartBadge(count);
    $("#cart-count-text").text(count);
    $("#cart-count-badge").toggle(count > 0);
}

/* ── Event delegation — qty & remove ── */
$("#cart-items-list").on("click", '[data-action="qty"]', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const $row = $btn.closest(".cart-item");

    $row.addClass("item-loading");

    request($btn.data("url"), "PATCH", {
        quantity: parseInt($btn.data("qty"), 10),
    })
        .done((data) => {
            applyCartResponse(data);
        })
        .fail(() => {
            window.notify("error", "Could not update cart");
            $row.removeClass("item-loading");
        });
});

$("#cart-items-list").on("click", '[data-action="remove"]', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const name = $btn.data("name");

    if (!confirm(`Remove ${name} from cart?`)) return;

    const $row = $btn.closest(".cart-item");
    $row.addClass("removing");

    request($btn.data("url"), "DELETE")
        .done((data) => {
            applyCartResponse(data);
        })
        .fail(() => {
            window.notify("error", "Could not remove item");
            $row.removeClass("removing");
        });
});

/* ── Clear Cart ── */
$("#clear-cart-btn").on("click", function () {
    if (!confirm("Clear all items from cart?")) return;
    const $btn = $(this).prop("disabled", true);

    request($btn.data("url"), "DELETE")
        .done((data) => {
            applyCartResponse(data);
        })
        .fail(() => {
            window.notify("error", "Could not clear cart");
            $btn.prop("disabled", false);
        });
});

/* ═══════════════════════════════════════════════════════════
   COUPON — Apply & Remove
   ═══════════════════════════════════════════════════════════ */

/* ── Apply Coupon ── */
$(document).on("click", "#apply-coupon-btn", function () {
    const $btn = $(this);
    const $input = $("#coupon-code-input");
    const $feedback = $("#coupon-feedback");
    const code = $input.val().trim();

    if (!code) {
        showCouponFeedback($feedback, "Please enter a coupon code.", "danger");
        $input.focus();
        return;
    }

    // Loading state
    $btn.prop("disabled", true).html(
        '<span class="spinner-border spinner-border-sm me-1"></span>Applying...',
    );
    $input.prop("disabled", true);
    $feedback.hide();

    request($btn.data("url"), "POST", { coupon_code: code })
        .done((data) => {
            applyCartResponse(data);
        })
        .fail((xhr) => {
            const msg = xhr.responseJSON?.message ?? "Could not apply coupon.";
            showCouponFeedback($feedback, msg, "danger");
            window.notify("error", msg);
        })
        .always(() => {
            $btn.prop("disabled", false).html("Apply");
            $input.prop("disabled", false);
        });
});

/* ── Apply on Enter key ── */
$(document).on("keydown", "#coupon-code-input", function (e) {
    if (e.key === "Enter") {
        e.preventDefault();
        $("#apply-coupon-btn").trigger("click");
    }
});

/* ── Remove Coupon ── */
$(document).on("click", "#remove-coupon-btn", function () {
    const $btn = $(this);
    $btn.prop("disabled", true);

    request($btn.data("url"), "DELETE")
        .done((data) => {
            applyCartResponse(data);
        })
        .fail(() => {
            window.notify("error", "Could not remove coupon");
            $btn.prop("disabled", false);
        });
});

/* ── Coupon feedback helper ── */
function showCouponFeedback($el, message, type) {
    $el.html(message)
        .removeClass("text-success text-danger")
        .addClass(type === "success" ? "text-success" : "text-danger")
        .slideDown(200);
}

function updateStockUI(data) {
    let stock = data.stock;

    // 🔹 Update stock badge
    $("#stockCount")
        .text(stock > 0 ? stock + " units" : "Out of Stock")
        .removeClass("text-primary text-danger")
        .addClass(stock > 0 ? "text-primary" : "text-danger");

    // 🔹 Disable/Enable button
    if (stock <= 0) {
        $("#addToCartBtn").prop("disabled", true);
        $("#cartWrapper").hide();
        $("#outOfStockBtn").show();
    } else {
        $("#addToCartBtn").prop("disabled", false);
        $("#cartWrapper").show();
        $("#outOfStockBtn").hide();
    }
}

/* ═══════════════════════════════════════════════════════════
   WAITLIST — Notify Me / Remove Notify (toggle)
   ═══════════════════════════════════════════════════════════ */

/* ── Add to waitlist ── */
$(document).on("click", ".waitlist-btn", function () {
    const $btn = $(this);
    const url = $btn.data("store-url");
    const originalLabel = $btn.html();

    $btn.prop("disabled", true).html("Adding...");

    $.ajax({
        url: url,
        method: "POST",
        dataType: "json",
        headers: {
            "X-CSRF-TOKEN": CSRF,
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
        success: function (res) {
            window.notify(res.status, res.message);
            // Swap to "Remove Notify" state
            $btn.removeClass("waitlist-btn bg-violet-600 text-white")
                .addClass(
                    "waitlist-remove-btn bg-violet-100 text-violet-700 border border-violet-200",
                )
                .html("<i class='bi bi-bell-slash me-1'> Remove Notify")
                .prop("disabled", false);
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message ?? "Something went wrong.";
            window.notify(xhr.responseJSON?.status ?? "error", msg);

            if (xhr.status === 409) {
                // Already on waitlist — swap to remove state
                $btn.removeClass("waitlist-btn bg-violet-600 text-white")
                    .addClass(
                        "waitlist-remove-btn bg-violet-100 text-violet-700 border border-violet-200",
                    )
                    .html("<i class='bi bi-bell-slash me-1'> Remove Notify")
                    .prop("disabled", false);
            } else {
                $btn.prop("disabled", false).html(originalLabel);
            }
        },
    });
});

/* ── Remove from waitlist ── */
$(document).on("click", ".waitlist-remove-btn", function () {
    const $btn = $(this);
    const url = $btn.data("destroy-url");
    const originalLabel = $btn.html();

    $btn.prop("disabled", true).html("Removing...");

    $.ajax({
        url: url,
        method: "DELETE",
        dataType: "json",
        headers: {
            "X-CSRF-TOKEN": CSRF,
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
        success: function (res) {
            window.notify(res.status, res.message);
            // Swap to "Notify Me" state
            $btn.removeClass(
                "waitlist-remove-btn bg-violet-100 text-violet-700 border border-violet-200",
            )
                .addClass("waitlist-btn bg-violet-600 text-white")
                .html("<i class='bi bi-bell me-1'> Notify Me")
                .prop("disabled", false);
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message ?? "Something went wrong.";
            window.notify(xhr.responseJSON?.status ?? "error", msg);
            $btn.prop("disabled", false).html(originalLabel);
        },
    });
});
