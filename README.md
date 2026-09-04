# Ekklesia — Module Fondations

Premier module de code de la plateforme (voir `architecture.html`, point 10
de la feuille de route). Couvre : `org_units` + `org_unit_history` (point
02/13), isolation stricte par Row-Level Security (point 04), comptes
individuels par invitation (point 11), code de rattachement (point 03),
transformations organisationnelles de base — promotion, rattachement,
renommage (point 13), et une couche de services separee des controleurs,
prete pour une future API mobile (point 10).

## Important : ce qui a ete verifie, et ce qui reste a faire chez vous

L'environnement de developpement utilise pour construire ce module n'a
pas d'acces reseau vers Packagist (composer) ni npm : impossible d'y
executer `composer install` / `npm install` pour lancer l'application
elle-meme. Deux choses ont neanmoins ete verifiees reellement, pas
seulement ecrites :

1. **Le schema de base de donnees** (les 9 tables, l'extension `ltree`,
   les contraintes, les index) a ete traduit en SQL brut
   (`database/sql_check/schema_check.sql`) et execute avec succes sur un
   PostgreSQL 16 local.
2. **L'isolation entre ministeres (RLS)** et **l'historisation temporelle**
   ont ete testees avec de vraies donnees, dans deux scripts SQL
   (`database/sql_check/rls_test.sql` et `history_test.sql`) : creation de
   deux ministeres, lecture croisee bloquee, ecriture croisee rejetee,
   promotion d'une cellule en eglise avec reconstitution correcte de
   l'etat "tel qu'il etait avant" — tout s'est comporte comme prevu.

Ce qui n'a **pas** pu etre verifie ici, faute de reseau : que
`composer install` telecharge bien Laravel et ses dependances, et que
l'application demarre reellement une fois `vendor/` present. Le squelette
Laravel complet est en revanche bien present dans ce zip (`artisan`,
`public/index.php`, `bootstrap/`, `config/`, `storage/`) - il ne manque
que les dependances telechargees par composer/npm. Chez vous (ou sur un
serveur avec acces internet), la mise en route est standard :

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Cree la base ekklesia et le role ekklesia_app (voir .env.example),
# comme dans database/sql_check/rls_test.sql
php artisan migrate
php artisan db:seed         # catalogue des roles (point 05)

npm run build                # ou `npm run dev` en developpement
php artisan serve
```

Si `php artisan migrate` echoue sur une etape precise, comparez-la a
`database/sql_check/schema_check.sql` : ce fichier a deja ete execute
avec succes et donne le SQL exact attendu pour chaque table.

## Organisation du code

```
app/Models/          Organisation (OrgUnit), Utilisateur (User),
                      Affectation, Role, Invitation, AttachmentCode,
                      OrgUnitHistory, Ministry — point 11.
app/Services/         Toute la logique metier (point 10) : rien dans les
                      controleurs. AttachmentCodeService (point 03),
                      InvitationService (point 11),
                      OrgUnitTransformationService (point 13).
app/Http/Controllers/ Minces : valident l'entree, appellent un service,
                      renvoient une page Inertia.
app/Policies/         Droits en cascade par sous-arbre (point 05).
database/migrations/  Les 9 migrations du module Fondations, dans
                      l'ordre (ministries -> users -> org_units ->
                      org_unit_history -> roles -> affectations ->
                      invitations -> attachment_codes -> RLS).
database/sql_check/   Scripts de verification manuelle (voir ci-dessus) —
                      ne sont pas executes par l'application.
resources/js/Pages/   Connexion, acceptation d'invitation, tableau de
                      bord vide par niveau (point 10).
```

## Prochain module

Une fois ce module en route chez vous : Membres, cultes et effectifs
(voir la feuille de route, point 10 de `architecture.html`).
