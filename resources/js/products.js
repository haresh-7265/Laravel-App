document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".custom-alert").forEach((alert) => {
        const progressBar = alert.querySelector(".progress-bar");
        const duration = parseInt(alert.dataset.duration) || 3000;

        progressBar.style.width = "100%";

        progressBar.offsetHeight;

        progressBar.style.transition = `width ${duration}ms linear`;
        progressBar.style.width = "0%";

        const bsAlert = new bootstrap.Alert(alert);

        const timeout = setTimeout(() => {
            bsAlert.close();
        }, duration);

        alert.querySelector(".btn-close").addEventListener("click", () => {
            clearTimeout(timeout);
        });
    });
});

document.getElementById('name').addEventListener('keyup', function() {
    let slug = this.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');

    document.getElementById('slug').value = slug;
});