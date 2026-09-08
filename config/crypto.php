<?php

return [

    /*
     * Point 15 (passerelle de paiement, diaspora) : reglement direct
     * portefeuille-a-portefeuille, jamais de cle privee ni de phrase de
     * recuperation manipulee par l'application - seule l'adresse PUBLIQUE
     * de reception est necessaire, et elle vient du serveur (.env), jamais
     * ecrite en dur ici.
     */
    'wallet_address' => env('CRYPTO_WALLET_ADDRESS'),

    // Contrat du jeton USDT sur BNB Smart Chain (BEP-20) - adresse publique
    // du contrat, identique pour tout le monde, jamais une donnee propre au
    // ministere.
    'usdt_bep20_contract' => '0x55d398326f99059fF775485246999027B3197955',

    // Plusieurs noeuds publics BNB Smart Chain, essayes dans l'ordre : un
    // noeud public gratuit peut etre temporairement indisponible, jamais
    // bloquant si un autre repond.
    'rpc_endpoints' => [
        'https://bsc-dataseed.bnbchain.org',
        'https://bsc-dataseed.defibit.io',
        'https://bsc-dataseed.ninicoin.io',
    ],

];
