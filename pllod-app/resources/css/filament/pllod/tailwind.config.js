import preset from "../../../../vendor/filament/filament/tailwind.config.preset";
import defaultTheme from "tailwindcss/defaultTheme.js";

export default {
    presets: [preset],
    content: [
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./resources/views/forms/components/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ["Helvetica Neue", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                basics: {
                    white: "#FFFFFF",
                    black: "#191918",
                },
                brand: {
                    accentIndigo: "#07426e",
                    50: "#e7f0f6",
                    100: "#cddde8",
                    200: "#b3c7d6",
                    300: "#91adc3",
                    400: "#59819e",
                    500: "#306186",
                    600: "#07426e",
                    700: "#023356",
                    800: "#032137",
                    900: "#021624",
                },
                grey: {
                    50: "#f9fafb",
                    100: "#f3f4f6",
                    200: "#e5e9f0",
                    300: "#d1d5db",
                    400: "#9ca3af",
                    500: "#6b7280",
                    600: "#4b5563",
                    700: "#374151",
                    800: "#1f2937",
                    900: "#111827",
                },
                primary: {
                    50: "#e8e8f4",
                    100: "#e0e0ee",
                    200: "#cbcce4",
                    300: "#a2a4cc",
                    400: "#8386bb",
                    500: "#6468aa",
                    600: "#454a99",
                    700: "#35397a",
                    800: "#212449",
                    900: "#14152e",
                },
                success: {
                    50: "#f2fdf5 ",
                    100: "#d3f3df",
                    200: "#a7e8bf",
                    300: "#7adc9e",
                    400: "#4ed17e",
                    500: "#22c55e",
                    600: "#16a34a",
                    700: "#147638",
                    800: "#0e4f26",
                    900: "#072713",
                },
                warning: {
                    50: "#fffbeb",
                    100: "#fdecce",
                    200: "#fbd89d",
                    300: "#f9c56d",
                    400: "#f7b13c",
                    500: "#f59e0b",
                    600: "#d97706",
                    700: "#a96e09",
                    800: "#623f04",
                    900: "#312002",
                },
                danger: {
                    50: "#ffeaea",
                    100: "#f5cece",
                    200: "#f6b1b1",
                    300: "#ef7f7f",
                    400: "#ea5555",
                    500: "#e52a2a",
                    600: "#dc0000",
                    700: "#a10303",
                    800: "#5b0000",
                    900: "#370000",
                },
            },

            // Gradients
            backgroundImage: {
                "button-gradient":
                    "linear-gradient(180deg, #575CAC 0%, #454A99 100%)",
                "button-gradient-hover":
                    "linear-gradient(180deg, #454A99 0%, #35397A 100%)",
            },
        },
    },
};
