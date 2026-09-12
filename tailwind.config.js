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
            // que sur du papier clair : "night" est la nouvelle toile de fond,
            // le vin et l'or y gagnent en profondeur et en lumiere, a la maniere
            // d'un vitrail dans une nef sombre. Les anciens tokens (parchment,
            // ink...) restent utilises tels quels par les pages pas encore
            // migrees : rien ne casse, la migration se fait ecran par ecran.
            //
            // Ajustement (2026-09-09) : retour du ministere apres publication --
            // la v3 etait jugee trop sombre ("beaucoup de noir"). "night" est
            // donc eclairci sensiblement (environ deux fois plus lumineux) et
            // vire du gris-violet vers un gris-vert discret, pour a la fois
            // apporter plus de legerete ET introduire un peu de vert dans
            // l'identite, sans quitter le registre "nuit". Meme principe que
            // pour la refonte initiale : on garde l'identite, on affine son
            // execution.
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
                // Corrige le 2026-09-12 (retour du ministere : "je veux que
                // les boutons de commande soient en bleu ou en noir") --
                // bleu marine echantillonne directement sur le vrai logo
                // Oikonema (public/images/oikonema-logo.png, dominante
                // #003080 environ) plutot qu'invente : les boutons de
                // commande des ecrans publics (accueil, connexion) portent
                // desormais cette couleur, jamais plus l'or. Distinct de
                // "slateblue" (deja utilise comme couleur de badge de
                // module, plus grise/discrete) pour que les boutons restent
                // visuellement plus affirmes que ces badges.
                azure: {
                    light: '#0a4aa0',
                    DEFAULT: '#003080',
                    dark: '#001c50',
                },
                // v3 : toile de fond sombre chaleureuse, teintee de vert plutot
                // que grise ou violette (jamais un noir pur/froid).
                night: {
                    DEFAULT: '#1a201c',
                    soft: '#232a24',
                    card: '#2b332c',
                    cardLight: '#343d34',
                    border: '#48534a',
                },
                // v4 "Constellation" (2026-09-10) : demande du ministere de
                // revenir a un fond clair, mais SANS reprendre le parchemin
                // v1 (juge "trop classique") -- ici un blanc net et un gris
                // neutre tres pale, dans l'esprit d'un portail administratif
                // (gouv.fr, service public...), plutot qu'un ton chaud
                // "papier ancien". Le vin et l'or restent les seuls accents
                // de marque, employes avec parcimonie sur ce fond clair --
                // rien n'est jete de l'identite, elle change seulement de
                // support (voir commentaires plus haut : v1 clair -> v3
                // sombre -> v4 clair a nouveau, mais neutre cette fois).
                paper: {
                    DEFAULT: '#ffffff',
                    soft: '#f6f7f9',
                    muted: '#eceef2',
                },
                // Texte : gris-anthracite neutre (jamais le brun chaud
                // d'"ink", reserve aux ecrans pas encore migres) pour un
                // rendu sobre et tres lisible sur fond blanc.
                graphite: {
                    DEFAULT: '#20242c',
                    soft: '#4c525c',
                    faint: '#8a909b',
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
                'glow-azure': '0 0 50px -12px rgba(0,48,128,0.55)',
                'inner-glow': 'inset 0 1px 0 0 rgba(255,255,255,0.06)',
                // v4 : ombre neutre discrete pour les cartes sur fond clair
                // (remplace l'effet "verre depoli sur fond sombre" -- les
                // glow-* colores restent disponibles et fonctionnent aussi
                // bien en survol sur fond blanc, cf. Dashboard/Index.vue).
                card: '0 1px 2px rgba(32,36,44,0.04), 0 2px 8px rgba(32,36,44,0.06)',
                'card-hover': '0 2px 4px rgba(32,36,44,0.05), 0 8px 20px rgba(32,36,44,0.09)',
            },
            backdropBlur: {
                xs: '2px',
            },
        },
    },
    plugins: [],
};
