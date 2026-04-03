// console.log('helpers.js loaded ✅');
window.showAlert = function (message, type = "success", duration = 2000) {
    const id = "alert-" + Date.now(); // unique id for each alert

    const html = `
        <div id="${id}"
            class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            role="alert"
            style="z-index: 9999; min-width: 800px; max-width: 1000px;">

            ${message}

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

            <div class="progress position-absolute bottom-0 start-0 w-100" style="height: 4px;">
                <div id="bar-${id}" class="progress-bar bg-${type}" style="width: 100%; transition: width ${duration}ms linear;"></div>
            </div>
        </div>`;

    $("body").prepend(html);

    const alertEl = $("#" + id);
    const barEl = $("#bar-" + id);

    // start progress bar — must be after element is in DOM
    // requestAnimationFrame ensures browser has painted it first
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            barEl.css("width", "0%");
        });
    });

    // auto remove after duration
    setTimeout(function () {
        alertEl.removeClass("show"); // trigger Bootstrap fade out
        setTimeout(function () {
            alertEl.remove();
        }, 300); // remove after fade completes
    }, duration);

    // manual close — clicking X removes immediately
    alertEl.on("closed.bs.alert", function () {
        alertEl.remove();
    });
};

window.updateCartBadge = function (count) {
    const badge = $("#cart-badge");

    badge.text(count > 99 ? "99+" : count);
};
