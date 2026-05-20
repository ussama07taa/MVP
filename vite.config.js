import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: { 
                transformAssetUrls: { 
                    base: null, 
                    includeAbsolute: false 
                } 
            }
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue': 'vue/dist/vue.esm-bundler.js'
        }
    },
    build: {
        cssCodeSplit: true,
        chunkSizeWarningLimit: 1000, // Increase warning limit to 1MB
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('pinia') || id.includes('inertia')) {
                            return 'vendor-core';
                        }
                        if (id.includes('axios') || id.includes('lucide-vue-next')) {
                            return 'vendor-utils';
                        }
                        return 'vendor-libs';
                    }
                }
            }
        }
    }
});
