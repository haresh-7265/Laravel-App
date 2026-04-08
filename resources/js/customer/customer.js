// resources/js/customer.js

// Pass the page the customer is currently on via a custom header
window.Echo.options.auth = {
    headers: { 'X-Current-Page': window.location.pathname }
};

// Join the presence channel (triggers auth → channels.php)
const browsingChannel = window.Echo.join('store.browsing');

// Optional: notify admin when page changes (e.g. SPA navigation)
function notifyPageChange(newPath) {
    window.Echo.options.auth.headers['X-Current-Page'] = newPath;
    window.dispatchEvent(new CustomEvent('page-changed', { detail: { page: newPath } }));
}