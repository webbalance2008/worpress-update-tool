import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                wb: {
                    bg: '#0e1822',
                    card: '#131f2c',
                    teal: '#00d4e8',
                    border: 'rgba(255,255,255,0.08)',
                    muted: '#717182',
                    destructive: '#d4183d',
                },
            },
            fontFamily: {
                mono: ['DM Mono', 'monospace'],
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
                body: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
