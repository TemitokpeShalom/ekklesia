<?php

namespace App\Support;

/**
 * Noms de mois/jours en francais, partages par tout ce qui genere un
 * document rythme par le calendrier (rapports, calendrier annuel du
 * ministere) - une seule source, pour ne jamais desynchroniser deux listes
 * copiees-collees (chantier "module Documents", 2026-09-12).
 */
final class FrenchCalendar
{
    public const MOIS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    // Lundi -> Dimanche (ISO-8601, comme Carbon::dayOfWeekIso), pas
    // Dimanche -> Samedi : convention deja retenue dans le reste de
    // l'application (semaine "de travail" du ministere).
    public const JOURS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
}
