# Mettre le plugin sur GitHub pour des mises a jour automatiques

Ce plugin inclut deja la bibliotheque "Plugin Update Checker" (dans
`lib/plugin-update-checker/`), qui permet a WordPress de vérifier et
proposer les mises a jour depuis un depot GitHub, exactement comme pour
une extension du repertoire officiel (menu Extensions > Extensions
installees).

## Etape 1 : creer le depot GitHub

1. Va sur https://github.com/new
2. Nom du depot, par exemple : `simple-page-builder`
3. Visibilite : **Public** (le plus simple) ou **Prive** (necessite un
   jeton d'acces, voir plus bas)
4. Ne coche aucune case d'initialisation (pas de README, pas de licence)
   -- on va pousser le code existant.

## Etape 2 : pousser le code du plugin

Depuis le dossier du plugin (celui qui contient `simple-page-builder.php`
directement a la racine -- important, ne pas mettre de sous-dossier en
plus) :

```bash
cd simple-page-builder
git init
git add .
git commit -m "Version initiale"
git branch -M main
git remote add origin https://github.com/PhilipMasse/WordPress---Editeur-de-pages.git
git push -u origin main
```

## Etape 3 : URL du depot (deja renseignee)

L'URL `https://github.com/PhilipMasse/WordPress---Editeur-de-pages/` est
deja renseignee dans `simple-page-builder.php` (en-tete `Update URI:` et
configuration du `PucFactory::buildUpdateChecker(...)`). Si jamais tu
changes de depot ou de compte a l'avenir, il faudra mettre a jour ces
deux endroits.

## Etape 4 : creer une Release a chaque nouvelle version

C'est CETTE etape qui declenche la proposition de mise a jour dans
WordPress (un simple `git push` sur `main` ne suffit pas -- il faut une
vraie Release, pour eviter qu'un push de travail en cours ne casse un
site en production) :

1. Modifie le numero de version dans `simple-page-builder.php`
   (les DEUX endroits : `Version:` dans l'en-tete, et la constante
   `SPB_VERSION`).
2. Commit et push sur `main`.
3. Sur GitHub, va dans l'onglet **Releases** du depot > **Draft a new
   release**.
4. Cree un tag au format `vX.Y.Z` (ex. `v1.4.0` -- doit correspondre au
   numero de version du plugin).
5. Publie la release.

Dans les 12 heures (ou immediatement si tu cliques sur "Rechercher des
mises a jour" dans Extensions), WordPress detecte la nouvelle version et
affiche le bouton de mise a jour habituel.

## Depot prive (optionnel)

Si le depot GitHub est prive :

1. Cree un jeton d'acces personnel sur GitHub : Settings (menu du
   compte) > Developer settings > Personal access tokens > Tokens
   (classic) > Generate new token. Coche uniquement le droit `repo`
   (lecture du code).
2. Dans `simple-page-builder.php`, decommente la ligne :
   ```php
   $spb_update_checker->setAuthentication( 'ghp_votre_jeton_ici' );
   ```
   et colle le jeton genere.

⚠️ Ce jeton donne acces en lecture au depot : ne le partage pas et ne le
publie jamais sur un depot public.

## Verifier que ca fonctionne

Sur le site WordPress (avec une version DEJA installee, inferieure a la
version taguee sur GitHub) : Extensions > Extensions installees >
"Rechercher des mises a jour". Simple Page Builder doit apparaitre dans
la liste des mises a jour disponibles, avec le changelog si tu en as mis
un dans le corps de la Release GitHub.
