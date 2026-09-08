<?php

return [

    /*
     * Point 15 (passerelle de paiement, Afrique de l'Ouest/Centrale) :
     * jamais de valeur en dur ici, tout vient des variables d'environnement
     * du serveur (.env, exclu du depot). "sandbox" ou "live" - toujours
     * tester en sandbox avant de brancher les vraies cles.
     */
    'environment' => env('FEDAPAY_ENVIRONMENT', 'sandbox'),

    'public_key' => env('FEDAPAY_PUBLIC_KEY'),
    'secret_key' => env('FEDAPAY_SECRET_KEY'),

    // Cle propre au point de terminaison webhook (Workbench FedaPay ->
    // onglet Webhooks), distincte de la cle secrete API - sert uniquement
    // a verifier la signature des notifications recues, jamais a appeler
    // l'API.
    'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),

    'base_url' => [
        'sandbox' => 'https://sandbox-api.fedapay.com/v1',
        'live' => 'https://api.fedapay.com/v1',
    ],

];
