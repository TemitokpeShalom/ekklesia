<?php

return [

    'default_currency' => 'XOF',

    'accounting_standard' => 'SYSCOHADA',

    // Comptes d'encaissement (recettes), regroupes par nature universelle.
    'income_accounts' => [
        'dime' => [
            ['code' => '70441', 'label' => 'Dîmes'],
        ],
        'offrande' => [
            ['code' => '70442', 'label' => 'Quêtes et offrandes'],
            ['code' => '70444', 'label' => 'Offrandes évangélisation'],
            ['code' => '70445', 'label' => 'Offrandes spéciales'],
            ['code' => '70446', 'label' => 'Quêtes impérées'],
        ],
        'action_de_grace' => [
            ['code' => '70443', 'label' => 'Action de grâce'],
        ],
        'don' => [
            ['code' => '70411', 'label' => 'Dons'],
            ['code' => '70412', 'label' => 'Legs'],
        ],
    ],

    // Comptes de decaissement (depenses), tous rattaches a la nature universelle "depense".
    'expense_accounts' => [
        ['code' => '60411', 'label' => 'Achats - fournitures'],
        ['code' => '60511', 'label' => 'Achats - eau'],
        ['code' => '60521', 'label' => 'Achats - électricité'],
        ['code' => '60561', 'label' => 'Achats - petit matériel'],
        ['code' => '61411', 'label' => 'Transport - fidèles'],
        ['code' => '61611', 'label' => 'Transport - plis'],
        ['code' => '61811', 'label' => 'Transport - matériels'],
        ['code' => '62211', 'label' => 'Services extérieurs - loyer'],
        ['code' => '62411', 'label' => 'Services extérieurs - entretien'],
        ['code' => '63311', 'label' => 'Autres services - formations'],
        ['code' => '63511', 'label' => 'Autres services - cotisations'],
        ['code' => '63611', 'label' => 'Autres services - recherche de fonds'],
        ['code' => '63811', 'label' => 'Autres services - missions'],
        ['code' => '63851', 'label' => 'Autres services - fêtes'],
        ['code' => '64111', 'label' => 'Impôts et taxes - directs/indirects'],
        ['code' => '64611', 'label' => "Impôts et taxes - droits d'enregistrement"],
        ['code' => '65210', 'label' => 'Autres charges - subventions accordées'],
        ['code' => '65215', 'label' => 'Reversement quote-part District'],
        ['code' => '65220', 'label' => 'Reversement quote-part Région'],
        ['code' => '65230', 'label' => 'Reversement quote-part Siège'],
        ['code' => '65411', 'label' => 'Autres charges - œuvres sociales'],
        ['code' => '66111', 'label' => 'Charges de personnel - salaires'],
        ['code' => '66311', 'label' => 'Charges de personnel - indemnités/primes'],
        ['code' => '66411', 'label' => 'Charges de personnel - charges sociales'],
        ['code' => '671', 'label' => 'Frais financiers'],
    ],

];
