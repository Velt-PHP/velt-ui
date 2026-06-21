# Architecture developpeur de Velt UI

Ce document explique `velt-ui` pour un developpeur qui doit utiliser ou faire evoluer cette partie du framework Velt.

Il decrit le role du module, son lien avec le kernel, les dossiers, les classes, les methodes importantes, le flux de donnees, les renderers, la securite HTML, les vues `.velt.php` et les tests.

## Role du module

`velt-ui` est le module qui permet d'ecrire une interface en PHP declaratif.

Au lieu d'ecrire directement du HTML dans une vue, le developpeur construit une `Page` composee de composants :

```php
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Text;
use Velt\Ui\Page;

$page = Page::make('Accueil')
    ->layout('guest')
    ->meta(['title' => 'Accueil'])
    ->add(
        Card::make()
            ->class('p-8')
            ->add(Text::make('Bienvenue')->as('h1'))
            ->add(Button::make('Continuer')->type('button'))
    );
```

Cette page est ensuite transformee par un renderer :

- `WebRenderer` produit du HTML pour un navigateur.
- `JsonRenderer` produit du JSON stable pour Preview.
- `ViewFactory` charge une page depuis un fichier `.velt.php`.

Le module ne gere pas le cycle HTTP complet. Il ne connait pas les routes, les controleurs, la session, les middlewares ou la reponse HTTP. Ces responsabilites restent cote kernel.

## Importance dans le projet

Dans Velt, ce module est important parce qu'il definit la facon officielle de decrire une interface.

Il sert de couche commune entre plusieurs parties du framework :

- le kernel charge les vues avec `ViewFactory` ;
- le module HTTP transforme le resultat en reponse ;
- le navigateur recoit le HTML produit par `WebRenderer` ;
- l'application Preview peut lire le JSON produit par `JsonRenderer` ;
- les futurs modules peuvent reutiliser le meme arbre declaratif sans lire du HTML.

La decision principale est la separation entre l'arbre UI et la sortie finale.

Une page Velt n'est pas du HTML. C'est une structure PHP serialisable. Le HTML et le JSON sont seulement deux representations possibles de cette structure.

## Organisation des dossiers

```text
velt-ui/
+-- composer.json
+-- src/
|   +-- Page.php
|   +-- Components/
|   |   +-- Component.php
|   |   +-- Alert.php
|   |   +-- Button.php
|   |   +-- Card.php
|   |   +-- Form.php
|   |   +-- Input.php
|   |   +-- Link.php
|   |   +-- Text.php
|   +-- Contracts/
|   |   +-- ComponentInterface.php
|   |   +-- RendererInterface.php
|   |   +-- ViewInterface.php
|   +-- Renderers/
|   |   +-- JsonRenderer.php
|   |   +-- WebRenderer.php
|   +-- Support/
|   |   +-- Html.php
|   +-- View/
|       +-- ViewFactory.php
|       +-- ViewNotFoundException.php
+-- tests/
+-- docs/
```

### `src/Page.php`

`Page` est la racine d'un ecran.

Elle contient :

- un titre ;
- un layout optionnel ;
- des meta donnees ;
- une liste de composants enfants.

Elle implemente `ViewInterface`, donc le reste du framework peut la traiter comme une vue Velt chargeable.

### `src/Components`

Ce dossier contient les briques UI.

`Component` est la classe abstraite commune. Tous les composants concrets heritent d'elle :

- `Text`
- `Button`
- `Card`
- `Form`
- `Input`
- `Link`
- `Alert`

Chaque composant garde ses donnees dans une structure simple :

- `type` : nom interne du composant ;
- `props` : options declaratives ;
- `content` : texte optionnel ;
- `children` : composants enfants.

### `src/Contracts`

Ce dossier definit les contrats publics.

Ces interfaces permettent aux autres modules de dependre d'une API stable plutot que des details internes des classes concretes.

### `src/Renderers`

Ce dossier transforme une `Page` en sortie texte.

`WebRenderer` transforme l'arbre en HTML.

`JsonRenderer` transforme l'arbre en schema JSON Preview.

### `src/Support`

`Html` contient les helpers partages pour echapper les textes et generer les attributs HTML.

### `src/View`

`ViewFactory` charge une page depuis un fichier `.velt.php`.

`ViewNotFoundException` signale qu'une vue demandee n'existe pas.

## Autoload Composer

Le package utilise Composer avec PSR-4 :

```json
{
    "autoload": {
        "psr-4": {
            "Velt\\Ui\\": "src/"
        }
    }
}
```

Cela veut dire que :

- `Velt\Ui\Page` pointe vers `src/Page.php` ;
- `Velt\Ui\Components\Button` pointe vers `src/Components/Button.php` ;
- `Velt\Ui\Renderers\WebRenderer` pointe vers `src/Renderers/WebRenderer.php`.

Apres un changement d'autoload, il faut regenerer Composer :

```powershell
composer dump-autoload
```

## Flux complet

Voici le flux typique dans un projet Velt complet :

```text
Route kernel
  -> controleur ou resolver de vue
  -> ViewFactory::make('auth.login')
  -> fichier resources/views/auth/login.velt.php
  -> retourne Page
  -> choix du renderer
      -> WebRenderer pour HTML
      -> JsonRenderer pour Preview
  -> Response HTTP creee par le kernel
```

`velt-ui` intervient seulement au milieu du flux :

```text
.velt.php -> Page -> composants -> renderer -> string HTML ou JSON
```

Le kernel garde la responsabilite de :

- recevoir la requete ;
- choisir la route ;
- instancier ou recuperer `ViewFactory` ;
- choisir le renderer ;
- fournir le vrai champ CSRF si necessaire ;
- definir les headers HTTP ;
- retourner la reponse.

## Communication avec le kernel

La dependance va dans ce sens :

```text
velt-ui -> kernel
```

`velt-ui` depend uniquement des abstractions minimales du kernel necessaires a son ServiceProvider.

Cette direction est volontaire. Elle permet de garder `velt-ui` :

- testable sans serveur HTTP ;
- reutilisable dans un contexte CLI ou Preview ;
- independant de la session ;
- independant du container du kernel ;
- independant des routes et controleurs.

Le kernel connecte le module avec trois services principaux :

```php
use Velt\Ui\Renderers\JsonRenderer;
use Velt\Ui\Renderers\WebRenderer;
use Velt\Ui\View\ViewFactory;

$views = new ViewFactory($projectRoot . '/resources/views');
$web = new WebRenderer($csrfResolver);
$json = new JsonRenderer();
```

Le kernel peut ensuite faire :

```php
$page = $views->make('auth.login');

$content = $request->expectsPreviewJson()
    ? $json->render($page)
    : $web->render($page);
```

## `Page` en detail

`Page` est le point d'entree d'une interface.

Creation :

```php
$page = Page::make('Connexion');
```

Le constructeur est prive. Le code force donc l'utilisation de `Page::make()`. Cette convention rend l'API plus declarative et coherente avec les composants.

### Proprietes internes

```php
protected string $title;
protected ?string $layout = null;
protected array $meta = [];
protected array $children = [];
```

`$title` contient le nom logique de l'ecran.

`$layout` garde une intention de layout, par exemple `auth` ou `dashboard`. Dans ce module, le layout n'est pas applique. Il est seulement conserve pour le kernel ou un futur renderer de layout.

`$meta` contient les informations de page comme `title`, `description`, `charset` ou `viewport`.

`$children` contient les composants ajoutes a la page.

### Methodes chainables

Les methodes retournent `self`, ce qui permet d'enchainer les appels :

```php
Page::make('Connexion')
    ->layout('auth')
    ->meta(['title' => 'Connexion'])
    ->add(Text::make('Bienvenue'));
```

`layout(string $layout)` stocke le layout.

`meta(array $meta)` remplace les meta donnees.

`add(object $component)` ajoute un composant enfant dans `$children`.

### Serialisation

`toArray()` transforme la page en tableau interne :

```php
[
    'type' => 'page',
    'title' => 'Connexion',
    'layout' => 'auth',
    'meta' => ['title' => 'Connexion'],
    'children' => [...]
]
```

La boucle importante est :

```php
array_map(
    function ($child) {
        if (method_exists($child, 'toArray')) {
            return $child->toArray();
        }

        return $child;
    },
    $this->children
)
```

Elle parcourt tous les enfants. Si l'enfant sait se serialiser avec `toArray()`, la page recupere sa forme tableau. Sinon, l'objet est retourne tel quel.

Dans l'usage normal, les enfants sont des composants Velt qui ont tous `toArray()`.

`toJson()` delegue au `JsonRenderer` :

```php
return (new JsonRenderer())->render($this);
```

Cette methode est pratique, mais dans le kernel il vaut mieux utiliser directement un renderer injecte par le container.

## `Component` en detail

`Component` est la base de tous les composants.

Elle implemente `ComponentInterface`, donc elle garantit les methodes necessaires aux renderers :

- `getType()`
- `getProps()`
- `getChildren()`
- `getContent()`
- `toArray()`

### Etat interne

```php
protected string $type;
protected array $props = [];
protected array $children = [];
protected ?string $content = null;
```

`$type` est le type interne utilise par les renderers.

Exemples :

- `text`
- `button`
- `card`
- `form`
- `input`
- `link`
- `alert`

`$props` contient les options declaratives.

Exemples :

- `class`
- `variant`
- `type`
- `inputType`
- `required`
- `placeholder`
- `csrf`
- `showIf`

`$children` contient les enfants du composant.

`$content` contient un texte simple, par exemple le label d'un bouton ou le contenu d'un texte.

### Constructeur protege final

```php
final protected function __construct()
{
}
```

Le constructeur est protege, donc on ne fait pas `new Button()`.

Il est aussi `final`, donc les composants ne redefinissent pas le constructeur. Ils utilisent tous une methode statique `make()`.

Exemple :

```php
Button::make('Envoyer');
Text::make('Bonjour');
Card::make();
```

Cette approche donne une API uniforme.

### `prop()`

```php
protected function prop(string $key, mixed $value): static
{
    $this->props[$key] = $value;

    return $this;
}
```

`prop()` est la methode centrale pour enregistrer une option.

Quand on appelle :

```php
Button::make('OK')->variant('primary');
```

`variant()` appelle :

```php
return $this->prop('variant', $variant);
```

Le tableau final contient :

```php
[
    'props' => [
        'variant' => 'primary',
    ],
]
```

### `class()`

```php
public function class(string $class): static
{
    return $this->prop('class', $class);
}
```

Cette methode est commune a tous les composants.

Elle ne valide pas les classes CSS et ne charge pas Tailwind. Elle conserve seulement l'intention de style.

`WebRenderer` la transforme en attribut HTML `class`.

`JsonRenderer` la conserve dans `props`.

### `showIf()`

`showIf()` conserve une condition logique :

```php
Text::make('Admin')->showIf('user.isAdmin');
```

Dans le module actuel, la condition n'est pas evaluee. Elle est gardee dans `props` pour un futur renderer ou un moteur d'etat.

### `add()` et `children()`

`add(object $child)` ajoute un enfant a la fin :

```php
Card::make()
    ->add(Text::make('Titre'))
    ->add(Button::make('Action'));
```

`children(array $children)` remplace toute la liste d'enfants :

```php
Card::make()->children([
    Text::make('Titre'),
    Button::make('Action'),
]);
```

### `toArray()`

`toArray()` produit la structure commune utilisee par les renderers :

```php
[
    'type' => 'button',
    'props' => [
        'type' => 'submit',
        'variant' => 'primary',
    ],
    'content' => 'Se connecter'
]
```

Si le composant contient des enfants, la methode ajoute `children`.

La boucle `array_map()` fait la meme chose que dans `Page` : chaque enfant qui possede `toArray()` est serialise.

## Composants concrets

### `Text`

`Text` sert aux textes et titres.

```php
Text::make('Bonjour')->as('h1')->class('text-xl');
```

Internement :

- `type` vaut `text` ;
- `content` vaut `Bonjour` ;
- `as('h1')` ajoute `props['as'] = 'h1'` ;
- `class('text-xl')` ajoute `props['class'] = 'text-xl'`.

En HTML, `WebRenderer` rend le tag indique par `as()`, mais seulement si le tag est autorise.

Tags autorises :

```text
p, span, strong, em, small, h1, h2, h3, h4, h5, h6
```

Si un tag dangereux ou inconnu est donne, le renderer revient a `p`.

### `Button`

`Button` represente une action utilisateur.

```php
Button::make('Se connecter')
    ->type('submit')
    ->variant('primary')
    ->disabled();
```

Internement :

- `type` du composant : `button` ;
- `content` : texte affiche ;
- `props['type']` : type HTML du bouton ;
- `props['variant']` : intention visuelle ;
- `props['disabled']` : attribut booleen.

En HTML :

```html
<button type="submit" data-variant="primary" disabled>Se connecter</button>
```

### `Card`

`Card` groupe du contenu.

```php
Card::make()
    ->class('p-8')
    ->add(Text::make('Titre'));
```

Le renderer HTML la transforme en `<section>`.

Elle n'ajoute pas de logique metier. C'est seulement un conteneur.

### `Form`

`Form` represente un formulaire declaratif.

```php
Form::make()
    ->method('POST')
    ->action('/login')
    ->csrf()
    ->add(Input::make('email', 'Email')->type('email')->required());
```

Internement :

- `method()` stocke la methode en majuscules ;
- `action()` stocke l'URL cible ;
- `csrf()` stocke `props['csrf'] = true` ;
- les champs et boutons sont ajoutes dans `children`.

Point important : `csrf()` ne genere pas de token.

Le vrai token depend de la session HTTP. Comme `velt-ui` ne connait pas la session, il marque seulement l'intention. Le kernel fournit le champ CSRF au `WebRenderer` via un resolver.

### `Input`

`Input` represente un champ de saisie avec label.

```php
Input::make('email', 'Email')
    ->type('email')
    ->required()
    ->placeholder('Votre email')
    ->value('demo@example.com');
```

`Input` ajoute deux proprietes propres :

```php
protected string $name;
protected string $label;
```

Sa methode `toArray()` appelle d'abord `parent::toArray()`, puis ajoute :

```php
$array['name'] = $this->name;
$array['label'] = $this->label;
```

Le type du champ est stocke dans `inputType`, pas dans `type`, pour eviter un conflit avec le type du composant.

Exemple interne :

```php
[
    'type' => 'input',
    'props' => [
        'inputType' => 'email',
        'required' => true,
    ],
    'name' => 'email',
    'label' => 'Email',
]
```

Dans le JSON Preview, `JsonRenderer` renomme `inputType` en `type` parce que le client Preview attend le type du champ dans les props.

### `Link`

`Link` represente une navigation.

```php
Link::make('Dashboard', '/dashboard')->class('underline');
```

Il garde :

- `content` pour le texte du lien ;
- `url` pour la cible ;
- `href` dans `toArray()`.

En HTML :

```html
<a href="/dashboard" class="underline">Dashboard</a>
```

### `Alert`

`Alert` represente un message.

```php
Alert::make('Erreur')
    ->alertType('error')
    ->class('text-red-700');
```

`type()` est un alias de `alertType()`.

Cela permet :

```php
Alert::make('OK')->type('success');
```

Attention : ici `type()` ne change pas le type interne du composant. Le composant reste `alert`. La methode change seulement `props['alertType']`.

## Contrats publics

### `ComponentInterface`

Ce contrat decrit ce qu'un renderer peut lire sur un composant :

```php
public function getType(): string;
public function getProps(): array;
public function getChildren(): array;
public function getContent(): ?string;
public function toArray(): array;
```

Les composants existants heritent de `Component`, qui implemente deja ce contrat.

### `ViewInterface`

Ce contrat decrit une vue Velt :

```php
public function title(): string;
public function getLayout(): ?string;
public function getMeta(): array;
public function children(): array;
public function toArray(): array;
```

Aujourd'hui, `Page` est l'implementation concrete.

### `RendererInterface`

Ce contrat est commun aux renderers :

```php
public function render(Page $page, array $options = []): string;
```

Il recoit une `Page` et retourne une chaine.

Le type de sortie depend du renderer :

- HTML pour `WebRenderer` ;
- JSON pour `JsonRenderer`.

## `WebRenderer` en detail

`WebRenderer` transforme une `Page` en HTML.

### Construction

```php
new WebRenderer();
```

ou avec CSRF :

```php
new WebRenderer(
    fn (array $form): string => '<input type="hidden" name="_token" value="...">'
);
```

Le parametre est un callable optionnel. Il recoit le formulaire serialise en tableau.

### Methode `render()`

```php
public function render(Page $page, array $options = []): string
```

La methode commence par savoir si elle doit rendre un document complet :

```php
$fullDocument = $options['document'] ?? true;
```

Puis elle recupere les enfants de la page :

```php
$body = $this->renderChildren($page->toArray()['children'] ?? []);
```

Si `document` vaut `false`, elle retourne seulement le fragment :

```php
$html = (new WebRenderer())->render($page, ['document' => false]);
```

Sinon elle construit :

- `<!doctype html>`
- `<html>`
- `<head>`
- meta charset ;
- meta viewport ;
- `<title>`
- autres metas scalaires ;
- `<body>`
- contenu des composants.

### Boucle de rendu des enfants

```php
private function renderChildren(array $children): string
{
    return implode("\n", array_map(
        fn (array $child): string => $this->renderComponent($child),
        $children
    ));
}
```

Cette boucle parcourt les composants deja serialises en tableau.

Chaque composant passe dans `renderComponent()`.

### Dispatch par `match`

```php
return match ($component['type'] ?? null) {
    'card' => $this->renderCard($component),
    'text' => $this->renderText($component),
    'alert' => $this->renderAlert($component),
    'form' => $this->renderForm($component),
    'input' => $this->renderInput($component),
    'button' => $this->renderButton($component),
    'link' => $this->renderLink($component),
    default => '',
};
```

Le renderer regarde `type` et appelle la methode privee adaptee.

Si le type est inconnu, il retourne une chaine vide.

### Mapping HTML

```text
card   -> section
text   -> p, h1, h2, span, etc.
alert  -> div role="alert"
form   -> form
input  -> label + input
button -> button
link   -> a
```

### Rendu des enfants imbriques

Les composants conteneurs comme `Card` et `Form` utilisent `wrapChildren()` :

```php
private function wrapChildren(array $component): string
{
    $children = $component['children'] ?? [];

    if ($children === []) {
        return '';
    }

    return "\n" . $this->renderChildren($children) . "\n";
}
```

Cette methode permet une recursion indirecte :

```text
renderCard()
  -> wrapChildren()
    -> renderChildren()
      -> renderComponent()
        -> renderText(), renderButton(), renderForm(), etc.
```

Ainsi une `Card` peut contenir un `Form`, qui peut contenir des `Input` et un `Button`.

### Echappement HTML

Le renderer utilise `Velt\Ui\Support\Html`.

Pour les textes :

```php
Html::escape($component['content'] ?? '')
```

Pour les attributs :

```php
Html::attributes([...])
```

Cela protege contre l'injection HTML simple dans les contenus et attributs.

Exemple :

```php
Text::make('<script>alert(1)</script>');
```

Le HTML rendu contient du texte echappe, pas un script executable.

### Attributs booleens

`Html::attributes()` traite les valeurs speciales :

- `null` est ignore ;
- `false` est ignore ;
- `true` devient un attribut booleen ;
- les autres valeurs deviennent `name="value"` apres escaping.

Exemple :

```php
['required' => true, 'placeholder' => null]
```

devient :

```html
 required
```

### CSRF dans `WebRenderer`

Dans `renderForm()`, le renderer verifie :

```php
if (($props['csrf'] ?? false) === true && is_callable($this->csrfFieldResolver)) {
    $csrfField = (string) call_user_func($this->csrfFieldResolver, $component);
}
```

Donc :

- si le formulaire n'a pas `csrf`, rien n'est ajoute ;
- si le formulaire a `csrf`, mais aucun resolver n'est fourni, rien n'est ajoute ;
- si le formulaire a `csrf` et un resolver est fourni, le champ retourne par le kernel est insere.

Ce choix evite de creer un faux token de securite dans `velt-ui`.

## `JsonRenderer` en detail

`JsonRenderer` transforme une `Page` en schema JSON Preview.

Il ne produit pas de HTML.

### Version du schema

```php
public const SCHEMA_VERSION = 1;
```

Le JSON contient toujours :

```json
"schemaVersion": 1
```

Cela permet aux clients Preview de savoir quelle version du contrat ils lisent.

### Methode `render()`

```php
return json_encode(
    $this->toPreviewArray($page),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
```

Options utilisees :

- `JSON_PRETTY_PRINT` rend le JSON lisible ;
- `JSON_UNESCAPED_UNICODE` garde les caracteres Unicode lisibles ;
- `JSON_THROW_ON_ERROR` leve une exception si l'encodage echoue.

### Methode `toPreviewArray()`

Cette methode construit le tableau Preview avant encodage :

```php
[
    'schemaVersion' => 1,
    'screen' => $tree['title'],
    'layout' => $tree['layout'],
    'meta' => $tree['meta'],
    'components' => [...]
]
```

Elle part de :

```php
$tree = $page->toArray();
```

Donc le renderer ne lit pas les proprietes internes de `Page`. Il utilise sa representation publique.

### Transformation des composants

Chaque composant passe dans `componentToPreview()`.

Cette methode :

- convertit le type interne en type Preview ;
- garde les `props` ;
- convertit les enfants recursivement ;
- garde `content` s'il existe ;
- garde `name`, `label` ou `href` s'ils existent.

### Types Preview

```text
card   -> Card
text   -> Text
alert  -> Alert
form   -> Form
input  -> Input
button -> Button
link   -> Link
```

Pour un composant custom inconnu, le renderer normalise le nom en PascalCase.

Exemple :

```text
custom-box -> CustomBox
```

### Cas special `Input`

En interne, `Input` utilise `inputType`.

Preview attend `type`.

Donc `propsForPreview()` fait :

```php
if (($component['type'] ?? null) === 'input' && isset($props['inputType'])) {
    $props['type'] = $props['inputType'];
    unset($props['inputType']);
}
```

Cela evite le conflit interne tout en exposant une API plus naturelle au client Preview.

## `ViewFactory` en detail

`ViewFactory` charge les fichiers `.velt.php`.

### Construction

```php
$views = new ViewFactory(__DIR__ . '/resources/views');
```

`$root` est la racine des vues.

### Resolution du chemin

```php
$views->pathFor('auth.login');
```

donne :

```text
resources/views/auth/login.velt.php
```

La notation par points represente les dossiers.

### Securite du nom de vue

Avant de construire le chemin, `pathFor()` valide le nom :

```php
if (! preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/', $name)) {
    throw new InvalidArgumentException(...);
}
```

Cela autorise :

```text
auth.login
dashboard.index
admin_users.show
```

Cela refuse :

```text
../secret
auth/login
auth..login
.env
```

Le but est d'eviter la traversee de dossiers.

### Chargement

```php
$page = require $path;
```

Le fichier `.velt.php` doit retourner une instance de `Velt\Ui\Page`.

Si le fichier n'existe pas :

```php
throw ViewNotFoundException::forName($name, $path);
```

Si le fichier retourne autre chose qu'une `Page` :

```php
throw new RuntimeException('View [...] must return an instance of Velt\Ui\Page.');
```

## Exemple d'une vue `.velt.php`

```php
<?php

use Velt\Ui\Components\Button;
use Velt\Ui\Components\Form;
use Velt\Ui\Components\Input;
use Velt\Ui\Components\Text;
use Velt\Ui\Page;

return Page::make('Connexion')
    ->layout('auth')
    ->meta(['title' => 'Connexion - Velt App'])
    ->add(
        Form::make()
            ->method('POST')
            ->action('/login')
            ->csrf()
            ->add(Text::make('Connexion')->as('h1'))
            ->add(Input::make('email', 'Email')->type('email')->required())
            ->add(Input::make('password', 'Mot de passe')->type('password')->required())
            ->add(Button::make('Se connecter')->type('submit')->variant('primary'))
    );
```

Ce fichier est declaratif. Il ne doit pas agir comme un controleur.

Il ne devrait pas :

- lire directement la requete ;
- demarrer une session ;
- executer une action metier ;
- envoyer une reponse HTTP ;
- faire un `echo`.

Il doit seulement retourner une `Page`.

## Communication entre fichiers

### Creation de page

```text
fichier .velt.php
  -> utilise Page
  -> utilise Components/*
  -> retourne Page
```

### Rendu HTML

```text
WebRenderer
  -> recoit Page
  -> appelle Page::toArray()
  -> parcourt children
  -> lit type/props/content/name/label/href
  -> utilise Support\Html
  -> retourne une string HTML
```

### Rendu JSON

```text
JsonRenderer
  -> recoit Page
  -> appelle Page::toArray()
  -> transforme le schema interne
  -> encode en JSON
  -> retourne une string JSON
```

### Chargement de vue

```text
kernel
  -> appelle ViewFactory::make('auth.login')
  -> ViewFactory::pathFor()
  -> require resources/views/auth/login.velt.php
  -> verifie que le resultat est Page
  -> retourne Page au kernel
```

## Structures de donnees

### Arbre interne

`Page::toArray()` produit un arbre interne :

```php
[
    'type' => 'page',
    'title' => 'Connexion',
    'layout' => 'auth',
    'meta' => [
        'title' => 'Connexion - Velt App',
    ],
    'children' => [
        [
            'type' => 'form',
            'props' => [
                'method' => 'POST',
                'action' => '/login',
                'csrf' => true,
            ],
            'children' => [
                [
                    'type' => 'input',
                    'props' => [
                        'inputType' => 'email',
                        'required' => true,
                    ],
                    'name' => 'email',
                    'label' => 'Email',
                ],
            ],
        ],
    ],
]
```

### JSON Preview

`JsonRenderer` produit une autre structure :

```json
{
    "schemaVersion": 1,
    "screen": "Connexion",
    "layout": "auth",
    "meta": {
        "title": "Connexion - Velt App"
    },
    "components": [
        {
            "type": "Form",
            "props": {
                "method": "POST",
                "action": "/login",
                "csrf": true
            },
            "children": []
        }
    ]
}
```

Cette structure est le contrat public du client Preview.

## Boucles et recursion utilisees

Le code utilise peu de boucles classiques `for` ou `while`.

Il utilise surtout :

- `array_map()` pour transformer les enfants ;
- `foreach` pour generer les meta tags et attributs HTML ;
- `match` pour router un type de composant vers la bonne methode de rendu.

### `array_map()` dans `Page` et `Component`

Cette boucle convertit les enfants en tableaux.

But : obtenir un arbre complet serialisable.

### `array_map()` dans `WebRenderer`

Cette boucle transforme chaque composant en HTML.

But : convertir une liste de composants en une string HTML.

### Recursion des enfants

La recursion vient des composants imbriques.

Exemple :

```text
Card
  Form
    Input
    Button
```

`WebRenderer` rend `Card`, puis appelle `wrapChildren()`, qui rappelle `renderChildren()`, qui rend `Form`, qui rappelle `wrapChildren()`, etc.

`JsonRenderer` fait la meme chose dans `componentToPreview()` avec les enfants.

## Balises HTML utilisees

`WebRenderer` produit les balises suivantes :

```text
html
head
meta
title
body
section
p
span
strong
em
small
h1
h2
h3
h4
h5
h6
div
form
label
input
button
a
```

Le document complet contient :

```html
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>...</title>
</head>
<body>
...
</body>
</html>
```

Le mode fragment ne rend pas `html`, `head` ou `body`.

## Securite

### Echappement

Tous les textes et attributs passent par :

```php
Html::escape()
```

qui utilise :

```php
htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
```

Cela protege le rendu HTML contre les balises injectees dans les textes et attributs.

### Validation des tags `Text`

`Text::as()` accepte une string, mais `WebRenderer` filtre la valeur.

Si un developpeur ecrit :

```php
Text::make('X')->as('script');
```

le renderer ne rend pas `<script>`. Il rend un `<p>`.

### Validation des vues

`ViewFactory` refuse les noms de vues dangereux avec une regex stricte.

Cela evite qu'un appel comme `../../.env` puisse sortir du dossier de vues.

### CSRF

`velt-ui` ne cree pas le token.

Le kernel ou le module HTTP doit fournir le vrai champ.

Cette separation evite une fausse securite.

## Tests

Les tests sont dans `tests/`.

Ils servent de specification executable.

### `PageAndComponentsTest`

Verifie :

- la creation d'une page ;
- les composants ;
- les props ;
- les enfants ;
- la serialisation `toArray()`.

### `WebRendererTest`

Verifie :

- le document HTML complet ;
- le mode fragment ;
- les balises rendues ;
- l'escaping HTML ;
- les metas ;
- le comportement CSRF.

### `JsonRendererTest`

Verifie :

- `schemaVersion` ;
- `screen` ;
- `layout` ;
- `meta` ;
- les composants Preview ;
- la conversion `inputType` vers `type`.

### `ViewFactoryTest`

Verifie :

- la resolution des chemins ;
- le chargement d'une vue `.velt.php` ;
- les erreurs quand la vue est absente ;
- le refus des noms dangereux ;
- le refus d'une vue qui ne retourne pas `Page`.

### `ContractsTest`

Verifie que les contrats publics restent utilisables.

## Commandes utiles

Installer ou mettre a jour les dependances :

```powershell
composer update
```

Lancer les tests :

```powershell
composer test
```

Lancer PHPUnit directement :

```powershell
vendor\bin\phpunit
```

Lancer l'analyse statique :

```powershell
composer analyse
```

Lancer PHP CS Fixer :

```powershell
composer fix
```

## Comment ajouter un nouveau composant

Exemple : ajouter `Badge`.

1. Creer `src/Components/Badge.php`.

```php
<?php

declare(strict_types=1);

namespace Velt\Ui\Components;

class Badge extends Component
{
    protected string $type = 'badge';

    public static function make(string $label): self
    {
        $instance = new self();
        $instance->content = $label;

        return $instance;
    }

    public function variant(string $variant): self
    {
        return $this->prop('variant', $variant);
    }
}
```

2. Ajouter le rendu HTML dans `WebRenderer::renderComponent()`.

```php
'badge' => $this->renderBadge($component),
```

3. Ajouter `renderBadge()`.

```php
private function renderBadge(array $component): string
{
    return '<span' . Html::attributes($this->classAttribute($component)) . '>'
        . Html::escape($component['content'] ?? '')
        . '</span>';
}
```

4. Ajouter le type Preview dans `JsonRenderer::previewType()`.

```php
'badge' => 'Badge',
```

5. Ajouter des tests :

- serialisation du composant ;
- rendu HTML ;
- rendu JSON Preview.

## Regles de conception a respecter

- Garder la dependance unidirectionnelle `velt-ui -> kernel`.
- Ne pas ajouter de dependance a une `Request` ou `Response` concrete.
- Ne pas generer de token CSRF dans UI.
- Toujours echapper le HTML.
- Garder le JSON Preview sans HTML.
- Ajouter un composant seulement si son contrat est clair.
- Ajouter un test pour chaque nouveau comportement public.
- Preferer des props declaratives a une logique metier dans les composants.

## Resume mental

La facon la plus simple de comprendre ce module est :

```text
Page et Components decrivent l'interface.
toArray() transforme cette interface en arbre interne.
WebRenderer lit cet arbre et produit du HTML.
JsonRenderer lit cet arbre et produit le contrat Preview.
ViewFactory charge une Page depuis un fichier .velt.php.
Le kernel orchestre tout, mais velt-ui reste autonome.
```
