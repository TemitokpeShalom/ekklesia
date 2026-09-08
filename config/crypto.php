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

    // Point 15 (conversion XOF -> USDT affichee a l'ecran, et verification
    // du montant recu - voir ExchangeRateService). API publiques, sans cle :
    // le franc CFA etant arrime a l'euro a taux fixe (655,957 XOF = 1 EUR),
    // seul le taux EUR/USD a besoin d'etre interroge en direct.
    'eur_usd_endpoint' => 'https://api.frankfurter.dev/v1/latest?from=EUR&to=USD',

    // BNB/USD : Binance en premier (cours de reference du marche), CoinGecko
    // en repli si Binance est injoignable depuis ce serveur.
    'bnb_usd_endpoints' => [
        ['url' => 'https://api.binance.com/api/v3/ticker/price?symbol=BNBUSDT', 'path' => 'price'],
        ['url' => 'https://api.coingecko.com/api/v3/simple/price?ids=binancecoin&vs_currencies=usd', 'path' => 'binancecoin.usd'],
    ],

];
