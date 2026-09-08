<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

/**
 * Manuel d'utilisation integre (point 09) : un article par ecran deja
 * construit, redige a partir du code reellement ecrit (champs, boutons,
 * regles) pour rester exact. updateOrCreate() par slug : on peut relancer
 * ce seeder a chaque nouveau bloc livre, sans dupliquer ni ecraser
 * l'historique de creation des articles deja en place.
 *
 * L'ordre choisi ici determine a la fois l'ordre d'affichage dans le
 * manuel complet et le regroupement par module (les articles d'un meme
 * module se suivent toujours).
 */
class HelpArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'slug' => 'dashboard',
                'module' => 'Tableau de bord',
                'title' => 'Le tableau de bord',
                'order' => 1,
                'body' => "Le tableau de bord est l'écran d'accueil après connexion. Il correspond toujours à un nœud précis de l'arbre hiérarchique (une cellule, une église, un district...) : le nom affiché en haut de la page et l'étiquette du niveau (Église locale, District...) indiquent en permanence sur quel nœud vous travaillez.\n\nSi vous occupez plusieurs fonctions à des endroits différents (par exemple pasteur d'une église et aussi responsable régional), chacune de vos affectations actives apparaît dans la section \"Mes affectations actives\" : cliquer dessus bascule le contexte de travail vers ce nœud.\n\nLa liste \"Module\" donne accès aux fonctions rattachées à ce nœud (Membres, Cultes, Finances, Inventaire des biens, Annonces, Rapport d'activités...). La section \"Gouvernance des accès\" (visible seulement si vous êtes habilité à gérer des personnes) permet d'inviter un nouveau titulaire, d'émettre un code de rattachement pour créer une entité rattachée, ou de révoquer un accès existant. La section \"Structure organisationnelle\" (visible seulement si vous êtes habilité) permet de renommer, promouvoir ou rattacher cette entité. \"Entités directement rattachées\" liste les nœuds enfants immédiats : cliquer sur l'un d'eux ouvre son propre tableau de bord.\n\nLe lien \"Aide\" en haut à droite ouvre ce manuel à tout moment ; \"Se déconnecter\" ferme la session.",
            ],
            [
                'slug' => 'members',
                'module' => 'Membres & présence',
                'title' => 'Gérer les membres',
                'order' => 2,
                'body' => "La liste des membres affiche toutes les personnes enregistrées sur ce nœud et ses descendants (donnée consolidée, comme partout ailleurs sur la plateforme). \"Ajouter un membre\" ouvre un formulaire avec : prénom, nom, téléphone, email, genre (non précisé, homme ou femme), date de naissance, date d'adhésion, une photo facultative, et, si la personne est mariée, le nom et la photo du conjoint ou de la conjointe.\n\nUn membre appartient toujours au nœud où il a été saisi (donnée propre) : il n'est pas possible de le déplacer directement d'une église à une autre depuis cet écran, seulement de le modifier ou de le retirer. Les photos alimentent aussi le trombinoscope de ce nœud.\n\nPeuvent créer, modifier ou retirer un membre : le responsable du nœud (Pasteur ou équivalent) et le Secrétaire général.",
            ],
            [
                'slug' => 'trombinoscope',
                'module' => 'Membres & présence',
                'title' => 'Trombinoscope',
                'order' => 3,
                'body' => "Le trombinoscope affiche, sous forme de grille de photos, tous les membres actifs de ce nœud et de ses descendants (jusqu'à 500 personnes à la fois). Chaque vignette montre la photo si elle existe, ou les initiales de la personne sinon.\n\nCet écran est pensé pour être imprimé : le bouton d'impression du navigateur produit une page propre, sans menus ni boutons, idéale pour un trombinoscope papier affiché dans un couloir ou distribué en réunion. L'accès est en lecture seule : pour corriger une photo ou un nom, il faut passer par l'écran \"Membres\".",
            ],
            [
                'slug' => 'cultes',
                'module' => 'Membres & présence',
                'title' => 'Cultes et effectifs',
                'order' => 4,
                'body' => "Chaque culte (ou réunion, service) se déclare sur cet écran avec : le thème du message, l'orateur, la date et l'heure, l'effectif d'adultes et d'enfants présents (comptés séparément), les versets clés étudiés, et des notes libres.\n\nCette liste nourrit automatiquement deux choses : les effectifs affichés dans le rapport d'activités mensuel (calculés depuis les cultes du mois, jamais ressaisis), et la bibliothèque ministérielle lorsque le culte est enrichi d'un résumé. Seuls le responsable du nœud et le Secrétaire général peuvent créer, modifier ou supprimer un culte.",
            ],
            [
                'slug' => 'sacrements',
                'module' => 'Membres & présence',
                'title' => 'Sacrements : baptêmes et mariages',
                'order' => 5,
                'body' => "Cet écran garde la trace, membre par membre, des baptêmes et des mariages célébrés. Il est volontairement distinct du culte pendant lequel la cérémonie a eu lieu : un baptême collectif fait souvent l'objet d'un seul culte, mais chaque personne baptisée reçoit ici sa propre fiche.\n\nPour un baptême, il suffit de choisir le membre concerné. Pour un mariage, le premier conjoint est toujours un membre déjà enregistré ; le second peut l'être aussi (choisi dans la même liste), ou bien être une personne non enregistrée sur la plateforme, auquel cas son nom se saisit librement. Chaque sacrement porte en plus une date, un lieu, l'officiant, et des notes facultatives.\n\nLe nombre de baptêmes du mois continue d'apparaître, comme avant, dans le rapport d'activités : celui-ci reste un chiffre global, quand cet écran-ci conserve le détail nominatif. Seuls le responsable du nœud et le Secrétaire général peuvent créer, modifier ou retirer un sacrement.",
            ],
            [
                'slug' => 'finances',
                'module' => 'Finances',
                'title' => 'Enregistrer un mouvement financier',
                'order' => 6,
                'body' => "Chaque entrée ou sortie d'argent (dîme, offrande, action de grâce, don, dépense) se saisit ici avec : le type de mouvement, le compte comptable correspondant (tiré du plan de comptes SYSCOHADA configuré pour ce ministère), le montant, la date, la personne ou l'organisme concerné (facultatif), et une description.\n\nCette liste alimente directement le rapport financier mensuel : rien n'est ressaisi. Le Trésorier porte la responsabilité pleine des finances de son périmètre ; le Comptable, quand ce poste existe sur ce nœud, peut saisir les écritures courantes sans pouvoir les valider. Une correction reste libre tant que le rapport du mois n'a pas été validé ; après validation, toute correction doit passer par une écriture de régularisation datée, jamais par une modification silencieuse.",
            ],
            [
                'slug' => 'finances-rapport',
                'module' => 'Finances',
                'title' => 'Rapport financier mensuel',
                'order' => 7,
                'body' => "Le rapport financier compile, pour le mois choisi, tous les encaissements et décaissements de ce nœud et de ses descendants (vue consolidée), organisés par compte comptable. Il ne se ressaisit jamais : il se recompile à la demande depuis les mouvements déjà enregistrés (voir l'article \"Enregistrer un mouvement financier\").\n\nUne fois validé par le Trésorier, ce rapport devient la référence officielle du mois pour ce nœud et remonte à son tour dans la consolidation du niveau supérieur.",
            ],
            [
                'slug' => 'inventaire',
                'module' => 'Inventaire des biens',
                'title' => "Gérer l'inventaire des biens",
                'order' => 8,
                'body' => "Chaque bien du ministère (immobilier comme un terrain ou un bâtiment, ou mobilier comme du matériel ou des meubles) s'enregistre ici avec : sa catégorie, sa désignation, sa quantité, sa date d'acquisition, sa valeur, sa provenance, et son état (fonctionnel, à surveiller, ou hors service), avec une observation libre.\n\nSi le bien a été payé par une dépense déjà enregistrée dans les finances, le lier directement au mouvement financier évite de ressaisir le montant. Chaque bien reçoit automatiquement un code d'identification unique et permanent (par exemple BAT-000001 pour un bien immobilier, MOB-000001 pour un bien mobilier), jamais réutilisé même après suppression.",
            ],
            [
                'slug' => 'inventaire-rapport',
                'module' => 'Inventaire des biens',
                'title' => "Fiche d'inventaire consolidée",
                'order' => 9,
                'body' => "La fiche d'inventaire consolidée liste tous les biens de ce nœud et de ses descendants, avec leur état, exactement selon le même principe de consolidation que les finances et les effectifs : chaque niveau voit ses biens propres plus ceux de tout son sous-arbre.",
            ],
            [
                'slug' => 'bibliotheque',
                'module' => 'Communication & documents',
                'title' => 'Bibliothèque ministérielle',
                'order' => 10,
                'body' => "La bibliothèque ministérielle rassemble les prédications enregistrées lors de la saisie des cultes (thème, versets, résumé) dans un espace de consultation commun à tout le ministère, classé par thèmes. C'est un espace de lecture réservé : seules les personnes habilitées à précher ou à diriger (Pasteur, à n'importe quel rang, y compris en cellule) y ont accès. Le Secrétaire général, le Trésorier et le Comptable n'y accèdent pas : ce n'est pas un oubli, c'est le périmètre voulu pour cet espace.\n\nCet écran est disponible uniquement depuis le tableau de bord du Ministère (le nœud racine), puisque la bibliothèque appartient à l'ensemble du ministère et non à une seule église.",
            ],
            [
                'slug' => 'annonces',
                'module' => 'Communication & documents',
                'title' => 'Publier une annonce',
                'order' => 11,
                'body' => "Une annonce publiée depuis un nœud est visible par ce nœud et par tous ses descendants (diffusion du haut vers le bas, l'inverse de la remontée des données). Le formulaire demande un titre, un message facultatif, une pièce jointe facultative, et une date d'expiration facultative au-delà de laquelle l'annonce disparaît d'elle-même.\n\nLa liste des annonces indique, pour chacune, si elle a déjà été marquée comme lue. Modifier ou supprimer une annonce reste possible tant qu'elle est en ligne.",
            ],
            [
                'slug' => 'activites-rapport',
                'module' => 'Rapports mensuels',
                'title' => "Rapport d'activités mensuel",
                'order' => 12,
                'body' => "Le rapport d'activités est distinct du rapport financier : il couvre la vie de l'église plutôt que ses finances. Les effectifs du mois y apparaissent déjà calculés depuis les cultes saisis, sans ressaisie. S'y ajoutent le nombre de baptêmes et de nouveaux convertis, un résumé des activités du mois, des remarques ou suggestions libres, et un champ \"Situation du responsable\", volontairement confidentiel : il n'est visible que par la ligne hiérarchique pastorale directe, jamais par le Secrétaire général, le Trésorier ou le Comptable.",
            ],
            [
                'slug' => 'invitations',
                'module' => 'Gouvernance des accès',
                'title' => 'Inviter un titulaire',
                'order' => 13,
                'body' => "Il n'existe pas d'inscription libre sur la plateforme : un compte naît toujours d'une invitation. Depuis cet écran, un responsable habilité choisit la fonction à pourvoir (Pasteur, Secrétaire général, Trésorier...) sur ce nœud, puis génère un lien d'invitation à transmettre à la personne concernée. En ouvrant ce lien, elle crée son compte et se retrouve automatiquement affectée à la fonction choisie, sur ce nœud précis.\n\nPeut inviter : un responsable qui gère déjà ce nœud ou l'un de ses ancêtres, jamais un nœud situé ailleurs dans l'arbre.",
            ],
            [
                'slug' => 'attachment-codes',
                'module' => 'Gouvernance des accès',
                'title' => 'Émettre un code de rattachement',
                'order' => 14,
                'body' => "Un code de rattachement sert à créer une nouvelle entité (par exemple un nouveau district ou une nouvelle église) rattachée à ce nœud, sans jamais laisser la personne qui la crée choisir librement son parent. Depuis cet écran, un responsable habilité génère un code à usage unique et le transmet à la personne mandatée pour enregistrer la nouvelle entité.\n\nEn saisissant ce code au moment de la création, cette dernière hérite automatiquement du bon rattachement (parent, chemin hiérarchique, ministère) : la création du code est, en elle-même, l'acte de validation, il n'y a pas de file d'attente séparée à approuver.",
            ],
            [
                'slug' => 'affectations',
                'module' => 'Gouvernance des accès',
                'title' => 'Gérer les accès',
                'order' => 15,
                'body' => "Cet écran liste tous les titulaires actifs d'un poste sur ce nœud (Pasteur, Secrétaire général, Trésorier...). Le bouton \"Révoquer\", avec un motif facultatif, retire l'accès immédiatement ; l'historique produit par la personne avant son départ reste intact et reste signé de son nom d'origine.\n\nUn responsable peut révoquer un accès sur son propre nœud ou sur n'importe lequel de ses descendants, jamais au-delà. Après une révocation, un nouveau titulaire peut être affecté au même poste directement depuis le lien \"Inviter un titulaire\".",
            ],
            [
                'slug' => 'org-units-transform',
                'module' => 'Structure organisationnelle',
                'title' => 'Transformer une entité',
                'order' => 16,
                'body' => "Cet écran permet de faire évoluer une entité déjà créée, sans jamais perdre son identité ni son historique : la renommer, la faire passer au niveau immédiatement supérieur (par exemple d'Église locale à District, une seule marche à la fois), ou la rattacher à une autre entité du même ministère à un rang strictement supérieur. Un motif est obligatoire pour chaque transformation, et l'historique complet (qui, quand, pourquoi) reste affiché en bas de la page.\n\nLa fermeture, la scission et la fusion d'entités ne sont volontairement pas encore proposées ici : ces opérations touchent souvent plusieurs entités à la fois (membres, finances, affectations à répartir) et demandent une décision explicite avant de pouvoir être automatisées sereinement.",
            ],
        ];

        foreach ($articles as $article) {
            HelpArticle::updateOrCreate(['slug' => $article['slug']], $article);
        }
    }
}
