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
            //
            // Identite visuelle v3 "Vitrail" (2026-09-08) : la remarque du
            // ministere etait que la version parchemin/claire restait "trop
            // classique". On garde la meme famille de couleurs (vin, or, brun,
            // vert, bleu ardoise -- rien de ce qui a ete valide n'est jete) mais
            // on la fait vivre sur des surfaces sombres et translucides plutot
            // que sur du papier clair : "night" est la nouvelle toile de fond
            // (proche du noir suggere), le vin et l'or y gagnent en profondeur et
            // en lumiere, a la maniere d'un vitrail dans une nef sombre. Les
            // anciens tokens (parchment, ink...) restent utilises tels quels par
            // les pages pas encore migrees : rien ne casse, la migration se fait
            // ecran par ecran.
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
                // v3 : toile de fond sombre chaleureuse (jamais un noir pur/froid).
                night: {
                    DEFAULT: '#0e0b11',
                    soft: '#171219',
                    card: '#1d1721',
                    cardLight: '#251e2b',
                    border: '#332a3a',
                },
            },
            fontFamily: {
                serif: ['Fraunces', 'ui-serif', 'Georgia', 'serif'],
                sans: ['"Source Sans 3"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                'glow-gold': '0 0 50px -12px rgba(185,138,62,0.55)',
                'glow-sanctuary': '0 0 50px -12px rgba(143,48,73,0.55)',
                'glow-forest': '0 0 50px -14px rgba(47,107,79,0.5)',
                'glow-slateblue': '0 0 50px -14px rgba(46,76,109,0.5)',
                'inner-glow': 'inset 0 1px 0 0 rgba(255,255,255,0.06)',
            },
            backdropBlur: {
                xs: '2px',
            },
        },
    },
    plugins: [],
};
