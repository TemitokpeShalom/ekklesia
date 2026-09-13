<?php

// Amorce de l'equipe technique Oikonema (2026-09-13) : la toute premiere
// personne ne peut pas s'ajouter elle-meme depuis l'ecran /technique/equipe
// (il faudrait deja y avoir acces) - ces adresses, une fois deployees dans
// le .env du serveur, donnent l'acces sans passer par la base de donnees.
// Voir User::isTechnicalStaff(). Plusieurs adresses possibles, separees par
// des virgules.
return [
    'technical_staff_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TECHNICAL_STAFF_EMAILS', ''))
    ))),
];
