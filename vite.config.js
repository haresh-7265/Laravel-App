import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/welcome.css",
                "resources/js/welcome.js",
                "resources/js/csvExport.js",
                "resources/css/csvExport.css",
                "resources/css/cart.css",
                "resources/js/cart.js",
                "resources/js/helpers.js",
            ],
            refresh: true,
        }),
    ],
});
