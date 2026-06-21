# Explication orientee objet de Velt UI

Ce document explique la partie orientee objet de Velt UI fichier par fichier. Il suit l'ordre du code source dans src/ et decrit ce que fait chaque bloc de code, pourquoi il existe, et comment les classes collaborent entre elles.

Portee de ce document :

- classes de domaine et composants de src/Components
- contrats d'abstraction dans src/Contracts
- page racine src/Page.php
- renderers HTML et JSON
- utilitaire d'echappement HTML
- chargement des vues declaratives

Si tu veux aussi un document ligne par ligne pour les tests, il pourra etre ajoute ensuite. Ici, l'objectif est de couvrir tout le code applicatif oriente objet du module.

## Vue d'ensemble OO

Velt UI utilise plusieurs concepts objet classiques :

- Encapsulation : les donnees internes sont stockees dans des proprietes protegees ou privees.
- Abstraction : les interfaces decrivent les contrats utilises par les renderers et le kernel.
- Heritage : tous les composants reutilisent une classe de base commune.
- Factory method : make() cree les instances avec une syntaxe declarative.
- Composition : une page contient des composants, et un composant peut contenir des enfants.
- Polymorphisme : les renderers traitent des objets differents via un contrat commun et une structure serialisee stable.
- Fluent interface : la majorite des methodes retournent l'objet courant pour permettre le chainage.

## src/Page.php

La classe Page represente une page complete. Elle implemente ViewInterface, donc elle peut etre chargee par une factory, lue par un renderer, puis serialisee sans connaitre la classe concrete.

- Lignes 1 a 4 : ouverture du fichier, declaration stricte des types, declaration du namespace, import du contrat de vue et du renderer JSON.
- Lignes 6 a 27 : commentaire de classe. Il decrit le role de Page comme point d'entree principal d'une interface UI declarative.
- Lignes 29 a 31 : declaration de la classe et implementation de ViewInterface. Cela force Page a exposer les methodes attendues par les autres couches.
- Lignes 33 a 55 : proprietes de la page. title contient le titre principal, layout garde le nom du layout, meta conserve les metadonnees, et children contient les composants enfants.
- Lignes 57 a 65 : constructeur prive. Il empeche l'instanciation directe et pousse a utiliser la methode factory make().
- Lignes 67 a 74 : methode statique make(). Elle cree une nouvelle page avec une syntaxe declarative fluide.
- Lignes 76 a 84 : methode layout(). Elle enregistre le nom du layout et retourne la meme instance pour continuer le chainage.
- Lignes 86 a 101 : methode meta(). Elle stocke les metadonnees de la page, par exemple le titre SEO ou la description.
- Lignes 103 a 112 : methode add(). Elle ajoute un composant enfant dans la page, ce qui realise la composition objet.
- Lignes 114 a 118 : methode children(). Elle retourne la liste brute des enfants.
- Lignes 120 a 124 : methode title(). Elle expose le titre de la page.
- Lignes 126 a 130 : methode getLayout(). Elle expose le layout courant.
- Lignes 132 a 136 : methode getMeta(). Elle expose les metadonnees de la page.
- Lignes 138 a 171 : methode toArray(). Elle transforme l'objet Page en structure serialisable. Chaque enfant est converti via toArray() si la methode existe, ce qui permet de traverser l'arbre objet sans lier Page a une classe particuliere.
- Lignes 173 a 183 : methode toJson(). Elle delegue le rendu JSON a JsonRenderer, ce qui garde la responsabilite de serialisation hors de Page.

## src/Contracts/ViewInterface.php

Ce contrat decrit ce qu'une vue chargeable doit fournir. Il permet au reste du systeme de manipuler une page sans dependre de l'implementation concrete.

- Lignes 1 a 4 : declaration du namespace et du commentaire qui explique le role du contrat.
- Lignes 6 a 17 : interface ViewInterface. Elle impose cinq methodes : title(), getLayout(), getMeta(), children() et toArray().
- Interet OO : le kernel et les renderers peuvent travailler avec ce contrat au lieu de connaitre Page directement.

## src/Contracts/ComponentInterface.php

Ce contrat decrit tout composant UI declaratif. Il sert de langage commun entre les composants concrets et les renderers.

- Lignes 1 a 4 : declaration du namespace et documentation du contrat.
- Lignes 6 a 28 : interface ComponentInterface. Elle impose getType(), getProps(), getChildren(), getContent() et toArray().
- Interet OO : les renderers peuvent inspecter un composant sans connaitre sa classe concrete, ce qui facilite l'ajout de nouveaux composants.

## src/Contracts/RendererInterface.php

Ce contrat formalise une strategie de rendu. Un renderer recoit une page et retourne une chaine.

- Lignes 1 a 4 : namespace et commentaire de contexte.
- Lignes 6 a 16 : interface RendererInterface avec la methode render(Page $page, array $options = []): string.
- Interet OO : on peut echanger un renderer HTML, JSON ou futur sans changer la facon d'appeler le contrat.

## src/Components/Component.php

Component est la classe de base de tous les composants UI. Elle concentre la logique commune : type, props, enfants, contenu et conversion en tableau.

- Lignes 1 a 4 : ouverture du fichier, types stricts, namespace et import du contrat ComponentInterface.
- Lignes 6 a 24 : commentaire de classe. Il annonce que cette classe abstraite gere le comportement partage par tous les composants.
- Lignes 26 a 28 : declaration abstraite de la classe et implementation de ComponentInterface.
- Lignes 30 a 41 : proprietes communes. type identifie le composant, props stocke les options declaratives, children stocke les descendants et content garde le texte simple.
- Lignes 43 a 49 : constructeur protege et final. Il reserve l'instanciation aux sous-classes via leurs factories make().
- Lignes 51 a 66 : methode protegee prop(). Elle centralise l'ajout d'une prop, ce qui evite de repeter la meme logique dans chaque sous-classe.
- Lignes 68 a 74 : methode class(). Elle repose sur prop() et stocke la classe CSS declarative.
- Lignes 76 a 83 : methode showIf(). Elle conserve une condition logique pour les renderers ou clients qui savent l'interpreter.
- Lignes 85 a 94 : methode add(). Elle ajoute un enfant au composant et permet d'assembler un arbre UI.
- Lignes 96 a 109 : methode children(). Elle remplace la liste des enfants si besoin.
- Lignes 111 a 115 : getChildren(). Elle expose les enfants.
- Lignes 117 a 121 : getProps(). Elle expose les props.
- Lignes 123 a 127 : getType(). Elle expose le type interne.
- Lignes 129 a 133 : getContent(). Elle expose le contenu textuel s'il existe.
- Lignes 135 a 164 : toArray(). Elle construit une representation stable du composant. Le tableau contient toujours type et props, ajoute content si present, puis serialize les enfants si la liste n'est pas vide.

## src/Components/Card.php

Card est un composant simple qui herite de Component sans ajouter de logique metier.

- Lignes 1 a 4 : namespace et commentaire qui decrit la carte comme groupe de contenu.
- Lignes 6 a 8 : la classe herite de Component.
- Ligne 10 : type vaut card, ce qui permet aux renderers de l'identifier.
- Lignes 13 a 17 : factory make(). Elle retourne une instance de Card et conserve la syntaxe declarative du module.

## src/Components/Button.php

Button stocke le texte du bouton et les intentions associees comme le type HTML, la variante et l'etat desactive.

- Lignes 1 a 9 : namespace, commentaire de classe et exemple d'utilisation.
- Lignes 11 a 13 : la classe herite de Component et fixe le type interne a button.
- Lignes 15 a 24 : factory make(string $label). Elle cree le composant et place le libelle dans content.
- Lignes 26 a 36 : methode type(). Elle stocke le type HTML du bouton comme prop.
- Lignes 38 a 48 : methode variant(). Elle stocke une intention de style ou de theme.
- Lignes 50 a 55 : methode disabled(). Elle marque le bouton comme desactive via une prop booleenne.

## src/Components/Alert.php

Alert represente un message d'information, d'erreur ou de statut.

- Lignes 1 a 9 : commentaire et exemple d'utilisation.
- Lignes 11 a 13 : classe fille de Component avec type interne alert.
- Lignes 15 a 24 : factory make(string $message). Elle stocke le message dans content.
- Lignes 26 a 36 : methode alertType(). Elle conserve le type logique de l'alerte.
- Lignes 38 a 45 : methode type(). C'est un alias de alertType(), utile pour garder une API fluide et plus naturelle.

## src/Components/Form.php

Form regroupe des champs et conserve les intentions liees a la soumission du formulaire.

- Lignes 1 a 9 : commentaire explicatif et exemple complet.
- Lignes 11 a 13 : la classe herite de Component et fixe le type form.
- Lignes 15 a 22 : factory make(). Elle retourne une nouvelle instance de formulaire.
- Lignes 24 a 35 : methode method(). Elle normalise la methode HTTP en majuscules avant de la stocker.
- Lignes 37 a 45 : methode action(). Elle conserve l'URL cible du formulaire.
- Lignes 47 a 55 : methode csrf(). Elle marque seulement l'intention de protection CSRF, sans fabriquer un faux jeton.

## src/Components/Input.php

Input represente un champ de saisie avec son nom logique, son label et ses props de rendu.

- Lignes 1 a 9 : commentaire de classe et exemple de rendu.
- Lignes 11 a 13 : la classe herite de Component et fixe le type input.
- Lignes 15 a 17 : proprietes specifiques name et label.
- Lignes 19 a 29 : factory make(string $name, string $label). Elle cree le champ et stocke ces deux informations essentielles.
- Lignes 31 a 41 : methode type(). Elle conserve le type de champ dans la prop inputType afin d'eviter un conflit avec le type du composant lui-meme.
- Lignes 43 a 48 : methode required(). Elle marque le champ comme obligatoire.
- Lignes 50 a 59 : methode placeholder(). Elle conserve le texte d'aide du champ.
- Lignes 61 a 70 : methode value(). Elle conserve une valeur initiale.
- Lignes 72 a 81 : toArray(). Elle commence par la conversion de base du parent, puis ajoute name et label au tableau pour les renderers et le schema Preview.

## src/Components/Link.php

Link represente un lien hypertexte declaratif.

- Lignes 1 a 9 : commentaire de classe et exemple HTML attendu.
- Lignes 11 a 13 : classe fille de Component avec type link.
- Lignes 15 a 17 : propriete specifique url.
- Lignes 19 a 29 : factory make(string $label, string $href). Elle stocke le texte du lien dans content et sa cible dans url.
- Lignes 31 a 39 : toArray(). Elle part de la conversion du parent puis ajoute href pour les renderers.

## src/Components/Text.php

Text sert a afficher du contenu textuel simple ou un titre.

- Lignes 1 a 9 : commentaire et exemple d'utilisation.
- Lignes 11 a 13 : la classe herite de Component et fixe le type text.
- Lignes 15 a 25 : factory make(string $content). Elle place le texte dans content.
- Lignes 27 a 37 : methode as(). Elle conserve le tag logique souhaite pour le rendu HTML, par exemple h1 ou p.

## src/Renderers/WebRenderer.php

WebRenderer traduit une page declarative en HTML navigateur. Il ne modifie pas l'arbre, il le lit et le transforme.

- Lignes 1 a 8 : namespace, import du contrat de renderer, de la classe Page et de l'outil Html.
- Lignes 10 a 16 : commentaire de classe. Il precise que le renderer mappe les composants vers du HTML et delegue le CSRF a un resolver optionnel.
- Lignes 17 a 19 : declaration finale de la classe et implementation de RendererInterface.
- Lignes 21 a 31 : constructeur avec resolver CSRF optionnel. Cela permet d'integrer la couche HTTP plus tard sans coupler le renderer a une implementation concrete.
- Lignes 33 a 58 : methode publique render(). Elle choisit entre document complet et fragment, puis construit la structure HTML complete si besoin.
- Lignes 60 a 73 : renderMetaTags(). Elle transforme les metadonnees scalaires en balises meta sauf title, charset et viewport, deja traites a part.
- Lignes 75 a 79 : renderChildren(). Elle rend chaque enfant et concatene le resultat avec des sauts de ligne.
- Lignes 81 a 90 : renderComponent(). Elle dispatch vers la methode specialisee selon le type du composant.
- Lignes 92 a 97 : renderCard(). Elle produit une balise section contenant les enfants.
- Lignes 99 a 106 : renderText(). Elle choisit un tag texte autorise, echappe le contenu, puis rend l'element.
- Lignes 108 a 118 : renderAlert(). Elle ajoute role=alert, conserve le type logique dans data-alert-type, et echappe le contenu.
- Lignes 120 a 138 : renderForm(). Elle construit les attributs du formulaire, ajoute eventuellement le champ CSRF issu du resolver, puis rend les enfants.
- Lignes 140 a 156 : renderInput(). Elle genere un label et un input, avec les props attendues, et respecte les attributs booleens ou facultatifs.
- Lignes 158 a 172 : renderButton(). Elle rend un bouton, ajoute l'etat disabled si necessaire et conserve la variante dans data-variant.
- Lignes 174 a 181 : renderLink(). Elle rend un ancre HTML avec href et class.
- Lignes 183 a 193 : wrapChildren(). Elle gere les sauts de ligne autour des enfants si le composant en contient.
- Lignes 195 a 197 : classAttribute(). Elle isole la lecture de la classe CSS declarative.
- Lignes 199 a 208 : textTag(). Elle normalise le tag texte et refuse les tags arbitraires. Si le tag n'est pas autorise, elle revient a p pour la securite.

## src/Renderers/JsonRenderer.php

JsonRenderer transforme la page en schema Preview stable, independant du HTML.

- Lignes 1 a 8 : namespace, import du contrat de renderer et de Page.
- Lignes 10 a 16 : commentaire de classe. Il explique que ce renderer produit uniquement des donnees declaratives et versionnees.
- Lignes 17 a 19 : classe finale qui implemente RendererInterface.
- Lignes 21 a 22 : constante SCHEMA_VERSION. Elle versionne explicitement le contrat JSON.
- Lignes 24 a 30 : methode render(). Elle encode en JSON la structure fournie par toPreviewArray() avec un format lisible et un encodage strict.
- Lignes 32 a 47 : toPreviewArray(). Elle lit l'arbre de la page et le convertit vers le schema Preview avec schemaVersion, screen, layout, meta et components.
- Lignes 49 a 74 : componentToPreview(). Elle convertit recursivement chaque composant, conserve les props utiles, ajoute content, name, label et href quand ils existent, puis descend dans les enfants.
- Lignes 76 a 88 : propsForPreview(). Elle adapte les props pour Preview. Le cas principal est input, ou inputType est renomme en type pour le schema public.
- Lignes 90 a 104 : previewType(). Elle mappe les types internes vers des noms publics en PascalCase. Les types custom sont normalises sans registre global.

## src/Support/Html.php

Cette classe utilitaire regroupe l'echappement HTML partage par les renderers.

- Lignes 1 a 8 : namespace et commentaire indiquant qu'il s'agit d'un helper d'echappement.
- Lignes 9 a 11 : classe finale, car elle n'est pas pensee pour etre etendue.
- Lignes 13 a 18 : methode escape(). Elle convertit une valeur en texte HTML securise avec htmlspecialchars.
- Lignes 20 a 39 : methode attributes(). Elle parcourt un tableau d'attributs, ignore les valeurs nulles ou fausses, rend les booleens vrais sans valeur, et echappe les noms et valeurs avant concatenation.

## src/View/ViewFactory.php

ViewFactory charge les fichiers de vue declaratifs depuis un dossier racine et retourne une instance de Page.

- Lignes 1 a 8 : namespace, imports utilitaires et import de Page.
- Lignes 10 a 15 : commentaire de classe. Il precise que la factory ne lance pas de controleur et n'injecte pas d'etat HTTP.
- Lignes 16 a 20 : classe finale avec un constructeur injectant la racine des vues.
- Lignes 22 a 39 : methode make(). Elle resout le chemin, verifie l'existence du fichier, l'inclut avec require, puis verifie que la vue retourne bien une Page.
- Lignes 41 a 51 : methode pathFor(). Elle valide le nom logique avec une expression reguliere, refuse les noms dangereux, puis convertit la notation par points en chemin de fichier .velt.php.

## src/View/ViewNotFoundException.php

Cette exception specialisee signale proprement qu'une vue demandee n'existe pas.

- Lignes 1 a 4 : namespace et import de RuntimeException.
- Lignes 6 a 7 : classe finale qui etend RuntimeException.
- Lignes 9 a 12 : methode statique forName(). Elle fabrique un message d'erreur explicite avec le nom logique et le chemin resolu.

## Ce que l'architecture objet apporte ici

Le module est construit autour d'un arbre d'objets declaratifs, pas autour de strings HTML dispersees.

- Page est la racine de l'arbre.
- Component est la base commune des noeuds UI.
- Card, Button, Alert, Form, Input, Link et Text specialise la base sans casser le contrat.
- ViewFactory charge des objets Page depuis des fichiers declaratifs.
- WebRenderer et JsonRenderer implementent deux strategies de sortie differentes sur la meme donnee source.
- Html centralise l'echappement pour eviter les duplications et les oublis.

## Resume de lecture

Si tu dois comprendre le module rapidement, commence par cet ordre :

1. src/Page.php
2. src/Components/Component.php
3. src/Components/Text.php
4. src/Renderers/WebRenderer.php
5. src/Renderers/JsonRenderer.php
6. src/View/ViewFactory.php

Ce sont les pieces qui montrent le mieux la logique declarative, l'heritage, la composition et la serialization.