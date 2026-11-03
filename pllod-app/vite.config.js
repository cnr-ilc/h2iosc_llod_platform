import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/filament/pllod/theme.css",
            ],
            // Immagini usate in Blade con Vite::asset()
            assets: ["resources/images/**"],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
