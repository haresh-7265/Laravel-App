$(document).ready(function () {
    $(document).on("submit", "#cart-form", function (e) {
        e.preventDefault();
        let url = $(this).attr("action");
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop("disabled", true);

        $.ajax({
            url: url,
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                submitBtn.prop("disabled", false);
                if (res.status === "success") {
                    updateCartBadge(res.cart_count);
                }
                showAlert(res.message, res.status);
            },
            error: function (xhr) {
                submitBtn.prop("disabled", false);
                showAlert(
                    xhr.responseJSON?.message ?? "Something went wrong.",
                    "danger",
                );
            },
        });
    });
});


const CSRF = $('meta[name="csrf-token"]').attr("content") ?? "";

/* ── AJAX helper ── */
function request(url, method = "POST", body = {}) {
    return $.ajax({
        url,
        method,
        contentType: "application/json",
        dataType: "json",
        headers     : {
            "X-CSRF-TOKEN"     : CSRF,
            "X-Requested-With" : "XMLHttpRequest",
        },
        data: method !== "GET" ? JSON.stringify(body) : undefined,
    });
}

/* ── Apply server response to DOM ── */
function applyCartResponse(data) {
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
            showAlert(data.message, data.status)
        })
        .fail(() => {
            showAlert("Could not update cart", "danger")
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
            setTimeout(() => applyCartResponse(data), 350);
            showAlert(data.message, data.status);
            
        })
        .fail(() => {
            showAlert("Could not remove item", "danger");
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
            toast("Cart cleared");
            showAlert(data.message, data.status);
        })
        .fail(() => {
            showAlert("Could not clear cart", "danger");
            $btn.prop("disabled", false);
        });
});
