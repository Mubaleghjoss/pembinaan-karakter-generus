import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/public-scanner.js',
                'resources/js/qr-print.js',
                'resources/js/rpg-beta-3d.js',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('html5-qrcode')) {
                        return 'qr-scanner';
                    }

                    if (id.includes('swiper')) {
                        return 'news-slider';
                    }

                    if (id.includes('@fullcalendar')) {
                        return 'calendar-vendor';
                    }

                    if (id.includes('\\three\\') || id.includes('/three/')) {
                        return 'three-vendor';
                    }

                    if (id.includes('lucide-react')) {
                        return 'icon-pack';
                    }

                    if (id.includes('react-dom') || id.includes('\\react\\') || id.includes('/react/')) {
                        return 'react-vendor';
                    }
                },
            },
        },
    },
});
