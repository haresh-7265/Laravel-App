import { showOrderToast } from "./toast.js";

export function listenForOrders() {
    const channel = window.Echo.private("admin.orders");

    // Check subscription success
    channel.subscribed(() => {
        console.log("✅ Subscribed to admin.orders channel");
    });

    // Check subscription error
    channel.error((err) => {
        console.error("❌ Channel subscription failed", err);
        // common reason: auth returned 403 (user is not admin)
    });

    // Listen for the event
    channel
        .listen(".order.placed", (payload) => {
            showOrderToast(payload);
        })
        .listen(".order.shipped", (payload) => {
            showOrderToast(payload);
        })
        .listen(".order.paid", (payload) => {
            showOrderToast(payload);
        })
        .listen(".order.delivered", (payload) => {
            showOrderToast(payload);
        });
}
