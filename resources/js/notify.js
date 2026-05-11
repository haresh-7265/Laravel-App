(function () {
    const icons = { 
        success: '<i class="bi bi-check-circle-fill text-green-400"></i>', 
        error: '<i class="bi bi-x-circle-fill text-red-400"></i>', 
        warning: '<i class="bi bi-exclamation-triangle-fill text-yellow-400"></i>', 
        info: '<i class="bi bi-info-circle-fill text-blue-400"></i>' 
    };

    window.notify = function (type, title, message = "", duration = 4000) {
        const stack = document.getElementById("toast-stack");
        if (!stack) return;

        const el = document.createElement("div");
        // Rename class from "toast" to "custom-toast" to avoid Bootstrap's opacity:0 conflict.
        // Added Tailwind utility classes for modern styling.
        el.className = `custom-toast flex items-start gap-3 p-4 bg-gray-800 text-white rounded-xl shadow-lg border border-gray-700 transition-all duration-300 transform translate-x-full opacity-0 relative overflow-hidden`;
        el.setAttribute("role", "alert");
        
        let colorClass = 'bg-blue-500';
        if (type === 'success') colorClass = 'bg-green-500';
        if (type === 'error') colorClass = 'bg-red-500';
        if (type === 'warning') colorClass = 'bg-yellow-500';

        el.innerHTML = `
            <div class="flex-shrink-0 text-xl mt-0.5">
                ${icons[type] || icons.info}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-100">${_esc(title)}</p>
                ${message ? `<p class="text-xs text-gray-400 mt-1">${_esc(message)}</p>` : ""}
            </div>
            <button class="flex-shrink-0 text-gray-400 hover:text-white transition focus:outline-none" onclick="window._dismissToast(this.closest('.custom-toast'))">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
            <div class="absolute bottom-0 left-0 h-1 ${colorClass}" style="width: 100%; animation: shrinkToastBar ${duration}ms linear forwards;"></div>
        `;

        stack.prepend(el);
        
        // Trigger slide-in animation
        requestAnimationFrame(() => {
            el.classList.remove('translate-x-full', 'opacity-0');
        });

        setTimeout(() => window._dismissToast(el), duration);
        if (stack.children.length > 5)
            window._dismissToast(stack.lastElementChild);
    };

    window._dismissToast = function (el) {
        if (!el || el.classList.contains("removing")) return;
        el.classList.add("removing");
        // Trigger slide-out animation
        el.classList.add('translate-x-full', 'opacity-0');
        el.addEventListener("transitionend", () => el.remove(), { once: true });
    };

    function _esc(s) {
        const d = document.createElement("div");
        d.textContent = s;
        return d.innerHTML;
    }

    // Inject CSS animation for the progress bar
    if (!document.getElementById('toast-keyframes')) {
        const style = document.createElement('style');
        style.id = 'toast-keyframes';
        style.innerHTML = `
            @keyframes shrinkToastBar {
                from { width: 100%; }
                to { width: 0%; }
            }
        `;
        document.head.appendChild(style);
    }

    function initNotificationListener(userId) {
        if (!window.Echo || !userId) return;

        window.Echo.private(`App.Models.User.${userId}`)

            // ── listen for specific broadcastType() name ──
            .notification((notification) => {
                console.log(notification);
                const type = notification.type || "info";
                const title = notification.title || "New notification";
                const message = notification.message || "";

                // show toast
                window.notify(type, title, message, 6000);

                // bump bell badge
                bumpBadge();
            });

    }

    function bumpBadge() {
        const badge = document.getElementById("notification-badge");
        if (!badge) return;
        const count = parseInt(badge.textContent) || 0;
        const next = count + 1;
        badge.textContent = next > 99 ? "99+" : next;
        badge.style.display = "flex";
    }

    window.initNotificationListener = initNotificationListener;
})();
