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
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', 'Georgia', 'serif'],
            },
            colors: {
                // Paleta de Clínica Santa Aurora
                fondo: '#f6f4ef',
                tinta: '#16202e',
                suave: '#5b6676',
                linea: '#e3dfd6',
                primary: '#0b6b74',
                deep: '#083f45',
                crema: '#f6f4ef',
                ambar: '#e8963e',
                'ambar-claro': '#f4d9b5',
                ok: '#1f6b43',
                // Los "teal" que ya usan las pantallas del sistema pasan a la paleta de la clínica
                teal: {
                    50: '#eef7f8',
                    100: '#e3f1f2',
                    200: '#c4e1e4',
                    300: '#8fc3c8',
                    400: '#4f9ba3',
                    500: '#1f7f88',
                    600: '#0b6b74',
                    700: '#0a5a62',
                    800: '#083f45',
                    900: '#062f34',
                },
            },
        },
    },

    plugins: [forms],
};
