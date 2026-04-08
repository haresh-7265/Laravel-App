let browsingCustomers = {};
let totalJoinedToday = new Set();

const channel = window.Echo.join("store.browsing")

    // 🔹 Initial snapshot
    .here(function (users) {
        browsingCustomers = {};
        // console.log(browsingCustomers);

        $.each(users, function (_, user) {
            if (user.role != "customer") return true;
            browsingCustomers[user.id] = {
                ...user,
                joinedAt: Date.now(),
            };
        });

        Object.values(browsingCustomers).forEach(function (user) {
            totalJoinedToday.add(user['id'])
        });
        renderPanel(browsingCustomers);
    })

    // 🔹 User joined
    .joining(function (user) {
        if (user.role != "customer") return;
        browsingCustomers[user.id] = {
            ...user,
            joinedAt: Date.now(),
        };

        totalJoinedToday.add(user.id);
        renderPanel(browsingCustomers);
    })

    // 🔹 User left
    .leaving(function (user) {
        if (user.role != "customer") return;
        delete browsingCustomers[user.id];
        renderPanel(browsingCustomers);
    });

function renderPanel(browsingCustomers) {
    let customers = Object.values(browsingCustomers);
    // console.log("customers", customers);
    // 🔹 Metrics
    $("#online-count").text(customers.length);
    $("#joined-today").text(totalJoinedToday.size);

    // 🔹 Customer list
    let html = "";

    if (customers.length === 0) {
        html = '<p class="empty">No customers browsing right now.</p>';
    } else {
        $.each(customers, function (_, customer) {
            html += `
                <div class="customer-row d-flex align-items-center gap-3 p-2 border-bottom" data-id="${customer.id}">
    
    <span class="status-dot bg-success rounded-circle d-inline-block" style="width:8px;height:8px;"></span>

    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-semibold"
         style="width:38px;height:38px;font-size:14px;">
        ${initials(customer.name)}
    </div>

    <div class="info flex-grow-1 text-truncate">
        <span class="name fw-semibold d-block text-dark">
            ${customer.name}
        </span>
        <span class="page text-muted small text-truncate d-block">
            ${customer.page ?? "/"}
        </span>
    </div>

    <span class="duration text-muted small text-nowrap">
        ${sessionDuration(customer.joinedAt)}
    </span>

</div>
            `;
        });
    }

    $("#browsing-list").html(html);
}

function initials(name) {
    return name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .toUpperCase();
}

function sessionDuration(joinedAt) {
    let secs = Math.floor((Date.now() - joinedAt) / 1000);
    return secs < 60
        ? secs + "s"
        : Math.floor(secs / 60) + "m " + (secs % 60) + "s";
}

setInterval(function () {
    $.each(browsingCustomers, function (_, customer) {
        let $durEl = $(`[data-id="${customer.id}"] .duration`);

        if ($durEl.length) {
            $durEl.text(sessionDuration(customer.joinedAt));
        }
    });
}, 1000);
