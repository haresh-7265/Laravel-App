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
    channel.listen(".OrderPlaced", (payload) => {
        console.log("📦 OrderPlaced received", payload);
        showOrderToast(payload);
    });
}
