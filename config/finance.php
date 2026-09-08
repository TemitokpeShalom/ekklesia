<?php

return [

    'default_currency' => 'XOF',

    /*
     * Point 18 (Finances multi-normes) : la nature d'un mouvement (dime,
     * offrande, action_de_grace, don, depense) est universelle et
     * s'enregistre partout, avec ou sans norme documentee - c'est la
     * couche portee par financial_transactions.type/nature (voir
     * FinancialTransaction). Son rattachement a un compte code, lui,
     * depend de la norme comptable du pays de l'entite qui l'enregistre :
     * c'est cette seconde couche, distincte, qui change de pays en pays
     * (voir App\Services\AccountingStandardResolver).
     *
     * Une norme n'est ecrite ici qu'une fois des documents reels fournis
     * pour l'implementer fidelement, jamais par anticipation. Seule
     * SYSCOHADA est documentee a ce jour : plan repris tel quel du
     * "Rapport Financier Mensuel" reel fourni par le ministere pilote
     * (03/09/2026). Les sept autres zones ou le ministere pilote confirme
     * une presence (France, Etats-Unis, Suisse, Inde, Nigeria, Turquie,
     * Republique tcheque) restent en couche universelle seule (pas de
     * compte code) jusqu'a ce que leurs propres documents soient fournis
     * a leur tour - jamais bloquant pour l'usage courant en attendant.
     */
    'standards' => [
        'SYSCOHADA' => [
            'label' => 'SYSCOHADA (espace OHADA)',

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
        ],
    ],

    /*
     * Association pays -> code de norme documentee ci-dessus, uniquement
     * pour les pays qui en ont une. Cle = nom du pays tel que saisi sur
     * le noeud "Pays" (org_units.name), normalise par
     * AccountingStandardResolver::normalizeCountryName() (minuscules,
     * sans accents ni ponctuation) pour absorber les variantes d'ecriture
     * courantes (« Côte d'Ivoire », « Cote d Ivoire »...). Un pays absent
     * de cette liste reste utilisable normalement : seule la couche
     * universelle s'applique, sans compte code, jusqu'a l'ajout de sa
     * norme a la demande.
     */
    'country_standards' => [
        'benin' => 'SYSCOHADA',
        'togo' => 'SYSCOHADA',
        'cote d ivoire' => 'SYSCOHADA',
        'tchad' => 'SYSCOHADA',
        'gabon' => 'SYSCOHADA',
        'centrafrique' => 'SYSCOHADA',
        'republique centrafricaine' => 'SYSCOHADA',
    ],

    /*
     * Devise par defaut d'un pays documente ci-dessus - un mouvement peut
     * toujours preciser une autre devise explicitement (voir
     * FinancialTransaction::currency). Un pays absent de cette liste
     * retombe sur 'default_currency' plus haut.
     */
    'country_currencies' => [
        'benin' => 'XOF',
        'togo' => 'XOF',
        'cote d ivoire' => 'XOF',
        'tchad' => 'XOF',
        'gabon' => 'XOF',
        'centrafrique' => 'XOF',
        'republique centrafricaine' => 'XOF',
    ],

];
