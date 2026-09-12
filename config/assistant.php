<?php

return [

    /*
     * Assistant IA integre a Oikonema (chantier du 2026-09-10). Comme pour
     * FedaPay, jamais de valeur en dur ici - tout vient du .env du serveur.
     *
     * ANTHROPIC_API_KEY : cle secrete a creer sur https://console.anthropic.com
     * (compte Martin) - jamais commitee, jamais affichee cote navigateur,
     * utilisee UNIQUEMENT par AssistantService cote serveur.
     */
    'api_key' => env('ANTHROPIC_API_KEY'),

    // A verifier/ajuster sur https://docs.claude.com/en/docs/about-claude/models
    // avant mise en production : les identifiants de modele changent avec
    // le temps. Un modele "Sonnet" recent est le bon compromis qualite/cout
    // pour un assistant conversationnel + vision (captures d'ecran).
    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5-20250929'),

    'api_version' => '2023-06-01',

    'max_tokens' => (int) env('ASSISTANT_MAX_TOKENS', 1024),

    // Identite affichee cote utilisateur (point 3 de la demande) - un seul
    // reglage a changer pour renommer l'assistant, jamais en dur dans le
    // Vue (voir AssistantWidget.vue).
    'name' => env('ASSISTANT_NAME', 'Frère David'),
    'tagline' => env('ASSISTANT_TAGLINE', 'votre assistant Oikonema'),

    // Combien de messages (tours utilisateur) une conversation peut
    // accumuler avant que l'historique envoye a l'API ne soit tronque aux
    // plus recents - limite le cout d'un tour a l'autre (l'API est sans
    // etat : tout l'historique garde est renvoye a chaque appel).
    'history_window' => (int) env('ASSISTANT_HISTORY_WINDOW', 16),

    // Corrige le 2026-09-12 (retour du ministere, "il n'arrive pas a
    // reconnaitre qu'on a change de sujet") : sans ce garde-fou, la
    // conversation "active" d'un utilisateur pouvait rester la meme
    // indefiniment (des heures, des jours) tant qu'il ne cliquait pas
    // lui-meme sur "Nouvelle discussion" - tout l'historique d'un ancien
    // sujet restait alors renvoye a l'IA a chaque nouveau message, meme
    // sans aucun rapport avec ce qui est demande maintenant. Au-dela de ce
    // delai d'inactivite (en heures) depuis le dernier message, une
    // nouvelle conversation demarre automatiquement au prochain message -
    // sans que l'utilisateur ait a y penser (voir
    // AssistantService::activeConversation).
    'conversation_idle_hours' => (int) env('ASSISTANT_CONVERSATION_IDLE_HOURS', 3),

    // Garde-fou de cout (point 5 de la demande) : plafond de messages
    // utilisateur par JOUR et par MINISTERE, tous utilisateurs confondus -
    // la cle API est celle de Martin, partagee par tous les ministeres de
    // la plateforme. Passe a 0 pour desactiver (deconseille en production).
    'daily_message_limit_per_ministry' => (int) env('ASSISTANT_DAILY_MESSAGE_LIMIT', 200),

    // Pieces jointes (point 4 de la demande) : images et documents PDF
    // uniquement pour l'instant - la voix/audio n'est pas encore
    // implementee (voir le document de suivi du chantier).
    'max_attachment_kb' => (int) env('ASSISTANT_MAX_ATTACHMENT_KB', 10240),
    'allowed_attachment_mimes' => [
        'image/png', 'image/jpeg', 'image/webp', 'application/pdf',
    ],

];
