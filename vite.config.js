import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    // 🔥 CONFIGURACIÓN PARA PRODUCCIÓN
    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            input: ['resources/js/app.tsx'],
        },
    },
});