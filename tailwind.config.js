import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Cinzel', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Play Snooker brand palette: dark snooker-baize green + gold accents.
                baize: {
                    50: '#e9f5ee',
                    100: '#c9e8d5',
                    200: '#93d0ab',
                    300: '#5cb480',
                    400: '#33935e',
                    500: '#1f7a48',
                    600: '#14603a',
                    700: '#104a2f',
                    800: '#0d3924',
                    900: '#0a1e2b',
                    950: '#061219',
                },
                gold: {
                    50: '#fdf8e8',
                    100: '#faedc1',
                    200: '#f4dc89',
                    300: '#edc850',
                    400: '#e3b02b',
                    500: '#c99418',
                    600: '#a37412',
                    700: '#7d5810',
                    800: '#5c4110',
                    900: '#3c2b0c',
                },
                wood: {
                    50: '#f8f0e6',
                    100: '#ecd9bd',
                    200: '#d9b787',
                    300: '#c1935c',
                    400: '#a1713f',
                    500: '#815630',
                    600: '#654226',
                    700: '#4a301d',
                    800: '#332114',
                    900: '#1f140c',
                },
            },
            boxShadow: {
                glow: '0 0 25px rgba(227, 176, 43, 0.25)',
            },
            backgroundImage: {
                'baize-felt': 'radial-gradient(ellipse at top, rgba(51,147,94,0.35), transparent 60%), linear-gradient(160deg, #0a1e2b 0%, #0d3924 45%, #0a1e2b 100%)',
            },
        },
    },

    plugins: [forms, typography],
};
