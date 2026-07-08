import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/css/admin/dashboard.css',
                'resources/css/admin/clientes.css',
                'resources/css/admin/emprestimos.css',
                'resources/css/admin/financeiro.css',
                'resources/js/app.js',
                'resources/js/admin/cliente.js',
                'resources/js/admin/emprestimo.js',
                'resources/js/admin/financeiro.js',
                'resources/js/admin/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
