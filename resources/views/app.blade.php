<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>Oikonema</title>

    {{--
        Icone dans l'onglet du navigateur + installation comme application
        sur telephone (retour du ministere, 2026-09-12) : favicon multi-
        taille pour les navigateurs de bureau, "apple-touch-icon" pour l'ecran
        d'accueil iOS, et le manifeste (icones/couleurs/nom) qui rend le site
        installable sur Android/Chrome - voir InstallPrompt.vue pour le
        bandeau qui propose l'installation, et public/sw.js pour le service
        worker minimal qu'elle necessite.
    --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#003080">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="OIKONEMA">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-slate-50 text-slate-900">
    @inertia
</body>
</html>
