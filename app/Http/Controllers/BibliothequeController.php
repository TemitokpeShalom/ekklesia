<?php

namespace App\Http\Controllers;

use App\Models\Culte;
use App\Models\OrgUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bibliotheque ministerielle (point 08) : archive des messages preches.
 *
 * Corrige le 2026-09-12 (retour du ministere, deuxieme passe) : "tant que
 * la personne appartient deja au ministere... il doit pouvoir acceder a la
 * bibliotheque, aux messages de tout le monde... peu importe le pays...
 * les messages ne respectent pas l'ordre hierarchique... mais a
 * l'interieur du ministere, ca ne doit pas sortir du ministere pour un
 * autre ministere." Contrairement a une premiere version (limitee au
 * sous-arbre de l'unite consultee, comme les Documents ou les rapports),
 * ce module est volontairement PLAT au sein d'un meme ministere : n'importe
 * quel niveau voit TOUS les messages du ministere entier, jamais seulement
 * ceux de sa propre branche. La seule frontiere qui compte est le
 * ministere lui-meme (deja garanti par la politique RLS sur `cultes` -
 * `ministry_id = current_setting('app.current_ministry_id')` - aucune
 * clause supplementaire necessaire ici pour empecher une fuite vers un
 * autre ministere).
 *
 * L'acces reste reserve a ceux qui prechent (Pasteur, a tout rang, y
 * compris cellule - "seulement pour ceux qui sont choisis pour jouer le
 * role du dirigeant... le leader principal d'une cellule ou d'un guide de
 * maison"), jamais au Secretaire/Tresorier/Comptable meme responsable de
 * tout le ministere. Un meme role "Pasteur" rattache a une Affectation sur
 * le noeud concerne (jamais un role distinct par niveau, voir Role.php)
 * couvre deja tous ces cas sans rien ajouter.
 *
 * Idee evoquee en parenthese par Martin (une future publication qui
 * sortirait volontairement du ministere pour etre vue ailleurs) : non
 * implementee, aucun bouton prevu pour ca aujourd'hui - voir la reponse
 * donnee au ministere a ce sujet.
 */
class BibliothequeController extends Controller
{
    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);
        abort_unless($request->user()->hasPreachingAffectation(), 403);

        $search = trim((string) $request->query('q', ''));

        // Pas de filtre par sous-arbre (ancien whereRaw sur org_units.path) :
        // voir le doc-comment de la classe - ce module est plat sur tout le
        // ministere, la RLS de `cultes` suffit deja a ne jamais depasser
        // cette frontiere.
        $query = Culte::where('status', 'termine');

        // Corrige le 2026-09-12 (retour du ministere) : "un peu comme
        // Google fonctionne... les themes qui sont synonymes... pas
        // forcement exactement ce qu'on a tape" - la simple recherche par
        // sous-chaine (ILIKE) exigeait une correspondance litterale exacte.
        // Remplacee par une recherche plein texte Postgres (dictionnaire
        // francais, "prier"/"priere"/"priant" se retrouvent par exemple)
        // combinee a une similarite par trigrammes (pg_trgm, tolerante aux
        // variantes/fautes de frappe et aux mots partiels) - le tout deja
        // fourni par Postgres, sans nouvelle dependance Composer. Ce n'est
        // pas un vrai dictionnaire de synonymes (« joie » ne retrouvera pas
        // « bonheur ») : voir la reponse donnee au ministere a ce sujet.
        if ($search !== '') {
            $searchable = "coalesce(title,'') || ' ' || coalesce(speaker,'') || ' ' || coalesce(key_verses,'') || ' ' || coalesce(notes,'')";

            $query->whereRaw(
                "(to_tsvector('french', {$searchable}) @@ websearch_to_tsquery('french', ?)
                    OR similarity(coalesce(title,''), ?) > 0.2
                    OR similarity(coalesce(key_verses,''), ?) > 0.2
                    OR similarity(coalesce(notes,''), ?) > 0.15
                    OR similarity(coalesce(speaker,''), ?) > 0.3)",
                [$search, $search, $search, $search, $search]
            )->selectRaw(
                "cultes.*, (
                    ts_rank(to_tsvector('french', {$searchable}), websearch_to_tsquery('french', ?))
                    + greatest(similarity(coalesce(title,''), ?), similarity(coalesce(key_verses,''), ?), similarity(coalesce(notes,''), ?), similarity(coalesce(speaker,''), ?))
                ) as relevance",
                [$search, $search, $search, $search, $search]
            )->orderByDesc('relevance');
        } else {
            $query->orderByDesc('service_date');
        }

        // Corrige le 2026-09-12 (retour du ministere) : les messages venant
        // desormais potentiellement de n'importe quelle branche du
        // ministere (pas seulement de celle consultee), le seul nom de
        // l'eglise locale peut preter a confusion si le meme nom existe
        // ailleurs dans le ministere - on charge aussi l'unite parente
        // (une seule requete supplementaire, jamais un N+1) pour donner un
        // minimum de repere ("Cellule Bethel · District Nord").
        $messages = $query
            ->with(['orgUnit:id,name,level_label,parent_id', 'orgUnit.parent:id,name,level_label'])
            ->limit(200)
            ->get();

        return Inertia::render('Bibliotheque/Index', [
            'orgUnit' => $orgUnit,
            'messages' => $messages,
            'search' => $search,
        ]);
    }
}
