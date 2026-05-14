// console.log('helpers.js loaded ✅');

window.updateCartBadge = function (count) {
    const badge = $("#cart-badge");

    badge.text(count > 99 ? "99+" : count);
};
