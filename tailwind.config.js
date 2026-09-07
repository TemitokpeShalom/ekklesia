/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            // Identite visuelle (2026-09-07) : palette chaleureuse fusionnee plutot
            // qu'une liste de couleurs isolees. Le bordeaux (vin) porte la marque,
            // l'or reste le seul accent recurrent, le brun structure le texte et
            // les surfaces neutres ; le bleu et le vert n'apparaissent que par
            // petites touches, pour distinguer un module d'un autre a l'interieur
            // de cette meme famille de couleurs.
            colors: {
                sanctuary: {
                    light: '#8f3049',
                    DEFAULT: '#6e1f35',
                    dark: '#4a1424',
                },
                coffee: {
                    light: '#7a6152',
                    DEFAULT: '#4a3527',
                    dark: '#2e2018',
                },
                gold: {
                    soft: '#f1e4c8',
                    DEFAULT: '#b98a3e',
                    dark: '#8f6a2c',
                },
                parchment: '#faf6ee',
                ink: '#241a16',
                slateblue: '#2e4c6d',
                forest: '#2f6b4f',
            },
            fontFamily: {
                serif: ['Fraunces', 'ui-serif', 'Georgia', 'serif'],
                sans: ['"Source Sans 3"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
