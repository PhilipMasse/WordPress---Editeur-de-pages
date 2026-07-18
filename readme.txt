=== Simple Page Builder ===
Contributors: Mairie de Berre-les-Alpes
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0

Un constructeur de page visuel par glisser-deposer, simple d'utilisation,
inspire de WPBakery Page Builder.

== Nouveautes 1.3.0 ==

* Le constructeur remplace desormais automatiquement l'editeur Gutenberg
  sur toute NOUVELLE page ou article : la case "Activer le constructeur
  visuel" est cochee par defaut et l'editeur de blocs ne se charge meme
  plus, pour une prise en main immediate sans manipulation.
* Important : ce changement ne s'applique qu'aux pages/articles crees
  APRES l'activation de cette version, ou a toute page existante pour
  laquelle vous cochez vous-meme la case. Aucune page existante n'est
  modifiee ou masquee automatiquement -- votre contenu actuel sur
  test3.berrelesalpes.fr reste intact et affiche normalement tant que
  vous n'activez pas volontairement le constructeur dessus.
* L'editeur de contenu classique se masque desormais automatiquement des
  que le constructeur est active sur une page (et reapparait si vous
  decochez la case), pour une interface plus claire.
* Un message d'information s'affiche une fois apres l'activation du
  plugin pour rappeler ce comportement.

== Nouveautes 1.2.3 ==

* Correction definitive (partie 2) du bug d'antislash perdu (retours a
  la ligne "\n" affiches "n", caracteres accentues affiches "u00e9" au
  lieu de "é"). La version 1.2.2 ne securisait que le trajet
  navigateur -> serveur (POST) ; le meme risque existait aussi au
  niveau du STOCKAGE en base de donnees. La mise en page est desormais
  egalement enregistree en base64 dans la meta '_spb_layout', ce qui
  elimine toute possibilite de corruption a n'importe quelle etape
  (WordPress, cache, plugin de securite...). Les pages deja enregistrees
  avec une version anterieure du plugin restent lisibles (compatibilite
  ascendante automatique).

== Nouveautes 1.2.2 ==

* Correction definitive du bug de retours a la ligne (bloc Liste et
  autres champs multi-lignes) : les donnees transitent desormais entre
  le navigateur et le serveur encodees en base64 au lieu de JSON brut.
  La tentative precedente (1.2.1) supposait un comportement particulier
  de $_POST cote WordPress qui ne s'est pas verifie dans tous les
  contextes ; l'encodage base64 supprime le probleme a la racine, car
  cet alphabet ne contient aucun caractere (guillemet, antislash) que
  WordPress puisse echapper ou desechapper par erreur.

== Nouveautes 1.2.1 ==

* Correction (bloc Liste) : les retours a la ligne saisis dans le bloc
  "Liste" (et plus generalement dans tout champ multi-lignes) etaient
  corrompus a l'enregistrement -- le texte se retrouvait fusionne avec
  la lettre "n" a la place du saut de ligne (ex. "test1ntest2"). Cause :
  WordPress desechape deja $_POST avant que le plugin ne l'enregistre ;
  le plugin le desechappait une seconde fois par erreur, ce qui
  supprimait a tort l'antislash des sequences d'echappement JSON. Le
  plugin detecte desormais automatiquement l'etat des donnees recues.

== Nouveautes 1.2 ==

* Correction : le selecteur d'icone (fenetre de choix) restait inutilisable
  tant que le panneau de reglages etait ouvert (probleme de superposition).
* Bloc Bouton : le lien peut desormais pointer soit vers une URL externe,
  soit vers une page ou un article existant du site (liste deroulante,
  le permalien reste toujours a jour meme si l'URL de la page change).
* Bloc Texte : bouton "Editer le code HTML" dans la barre d'outils pour
  passer en edition du code source, puis revenir a l'edition visuelle.
* Correction : les retours a la ligne saisis dans le bloc Liste (un
  element par ligne) sont maintenant systematiquement respectes.
* Correction technique : la validation des listes deroulantes comparait
  parfois incorrectement les valeurs numeriques (PHP convertit les cles
  de tableau numeriques en entiers), ce qui pouvait faire echouer
  silencieusement certains reglages.

== Nouveautes 1.1 ==

* Correction : les textes d'exemple ("Votre titre", "Saisissez votre texte
  ici...") sont maintenant de vrais indicatifs qui disparaissent des la
  saisie, au lieu d'un contenu a effacer manuellement.
* Bloc Texte : barre d'outils enrichie (formats de titre, gras, italique,
  souligne, barre, couleur de texte, surlignage, alignements, listes,
  liens, effacer la mise en forme).
* Bloc Titre : couleur de texte, et formes decoratives colorees (trait
  souligne, pastille, encadre), + icone optionnelle avant le texte.
* Bloc Bouton : tailles (petit/moyen/grand), formes d'angles (carre /
  arrondi / pilule), couleurs personnalisees, pleine largeur, icone
  optionnelle avant ou apres le texte.
* Bloc Separateur : epaisseur, largeur et alignement reglables.
* Bloc Liste : puces fleche/numerotee en plus de rond/coche, couleur des
  puces, espacement entre elements.
* Bloc icone : veritable selecteur visuel (apercu de l'icone choisie,
  bibliotheque elargie a plus de 70 icones, recherche), couleur et
  taille reglables.
* L'icone peut desormais aussi etre ajoutee directement en debut d'un
  bloc Titre ou Bouton, sans passer par un bloc dedie.


== Description ==

Ce plugin ajoute une boite "Constructeur de page" sur l'ecran d'edition
des pages et des articles. Une fois active pour une page donnee, il
remplace l'affichage du contenu par une mise en page construite avec :

* des LIGNES (avec choix de mise en page en colonnes : 1, 2, 3, 4 colonnes,
  ou colonnes inegales),
* des COLONNES a l'interieur de chaque ligne,
* des ELEMENTS a l'interieur de chaque colonne : titre, texte, image,
  bouton, separateur, espacement, video, bloc icone, citation, liste.

Chaque ligne et chaque element dispose d'un panneau de reglages simple
(formulaire) et peut etre reordonne par glisser-deposer.

== Utilisation ==

1. Activez le plugin.
2. Ouvrez une page ou un article a editer.
3. Cochez "Activer le constructeur visuel pour cette page" dans la boite
   "Constructeur de page (glisser-deposer)".
4. Cliquez sur "Ajouter une ligne", choisissez une mise en page.
5. Dans une colonne, cliquez sur "Ajouter un element" et choisissez son type.
6. Cliquez sur le crayon d'un element pour regler son contenu, cliquez sur
   "Appliquer" pour valider.
7. Reordonnez les lignes ou les elements par glisser-deposer (poignee
   avec l'icone de fleches).
8. Cliquez sur "Publier" / "Mettre a jour" comme d'habitude : la mise en
   page est enregistree et affichee a la place du contenu classique.

Decocher la case desactive le constructeur : le contenu classique de
l'editeur reprend l'affichage.

== Mises a jour automatiques ==

Le plugin integre la bibliotheque "Plugin Update Checker" qui permet de
proposer les mises a jour depuis un depot GitHub, comme pour une
extension officielle. Voir le fichier GITHUB-UPDATES.md pour la
procedure complete (creation du depot, configuration, publication des
nouvelles versions).

== Notes techniques ==

* Les donnees de mise en page sont stockees en JSON dans le champ meta
  `_spb_layout` de l'article, et l'activation dans `_spb_enabled`.
* Le JSON envoye par le navigateur est integralement revalide et
  reconstruit cote serveur (types de champs, echappement, wp_kses_post)
  avant d'etre enregistre : aucun HTML arbitraire n'est stocke tel quel.
* Le rendu public est genere a partir de cette structure sanitized par
  la classe SPB_Render (filtre `the_content`).
* Les types de contenus geres par defaut sont "page" et "article"
  (filtrable via `spb_allowed_post_types`).
