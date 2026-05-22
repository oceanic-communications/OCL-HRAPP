// OCL_HR — Laravel + Vite (auth, email OTP, RBAC)

import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number.parseInt(env.VITE_PORT ?? '5173', 10);
    const publicHost = env.VITE_DEV_SERVER_PUBLIC_HOST ?? 'localhost';

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/guest.js',
                    'resources/js/rich-editor.js',
                    'resources/js/policy-document-builder.js',
                    'resources/js/portal-sidebar-nav-group.js',
                ],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            port,
            strictPort: true,
            origin: `http://${publicHost}:${port}`,
            hmr: {
                host: publicHost,
                port,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
