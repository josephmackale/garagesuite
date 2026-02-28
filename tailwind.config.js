import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'grid-cols-6',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', 'ui-sans-serif', 'system-ui'],
            },
        },
    },

    plugins: [forms],
};
