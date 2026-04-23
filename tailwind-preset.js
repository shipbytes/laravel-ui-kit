const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                boldtext: ['Poppins', ...defaultTheme.fontFamily.sans],
                booktext: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
    safelist: [
        // Status-color variants used by dynamic badges.
        { pattern: /(bg|text|ring|border)-(emerald|amber|orange|red|indigo|violet|blue|zinc)-(50|100|200|300|400|500|600|700|800|900|950)/ },
    ],
};
