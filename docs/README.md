# Velt UI

Velt UI est le sous-module qui decrit une interface Velt en PHP declaratif, puis la transforme en sorties consommables par le reste du framework.

Pour comprendre l'architecture interne, les classes, les flux entre fichiers et le fonctionnement du code, voir [ARCHITECTURE_DEVELOPPEUR.md](ARCHITECTURE_DEVELOPPEUR.md).

Pour une carte encore plus detaillee des dossiers, fichiers et flux de donnees web/json, voir [ARCHITECTURE_DETAILLEE.md](ARCHITECTURE_DETAILLEE.md).

Pour un rapport directement exploitable en memoire de defense, voir [RAPPORT_MEMOIRE_DEFENSE.md](RAPPORT_MEMOIRE_DEFENSE.md).

Pour une lecture ligne par ligne du code oriente objet du module, voir [EXPLICATION_CODE_OO.md](EXPLICATION_CODE_OO.md).

Pour l'integration avec le kernel Velt, voir [KERNEL_INTEGRATION.md](KERNEL_INTEGRATION.md).

Le module ne gere pas les routes, les controleurs, la session, les assets ou le cycle HTTP complet. Son role est volontairement plus precis :

- construire un arbre UI avec `Page` et les composants MVP ;
- rendre cet arbre en HTML pour le web ;
- rendre cet arbre en JSON stable pour l'application Preview ;
- charger une page depuis un fichier `.velt.php` ;
- exposer des contrats publics simples pour le kernel, HTTP et Preview.

## Installation

Le package utilise le namespace PHP `Velt\Ui` et l'autoload Composer PSR-4 :

```json
{
    "autoload": {
        "psr-4": {
            "Velt\\Ui\\": "src/"
        }
    }
}
```

Apres modification de l'autoload, regenerer l'autoloader :

```bash
composer dump-autoload
```

## Exemple rapide

```php
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Form;
use Velt\Ui\Components\Input;
use Velt\Ui\Components\Text;
use Velt\Ui\Page;
use Velt\Ui\Renderers\JsonRenderer;
use Velt\Ui\Renderers\WebRenderer;

$page = Page::make('Connexion')
    ->layout('auth')
    ->meta(['title' => 'Connexion - Velt App'])
    ->add(
        Card::make()->class('p-8')->add(
            Text::make('Connexion')->as('h1')
        )->add(
            Form::make()
                ->method('POST')
                ->action('/login')
                ->csrf()
                ->add(Input::make('email', 'Email')->type('email')->required())
                ->add(Input::make('password', 'Mot de passe')->type('password')->required())
                ->add(Button::make('Se connecter')->type('submit')->variant('primary'))
        )
    );

$html = (new WebRenderer())->render($page);
$json = (new JsonRenderer())->render($page);
```

## Composants MVP

Les composants disponibles dans le Module 1 sont :

| Composant | Usage |
| --- | --- |
| `Page` | racine d'un ecran Velt |
| `Card` | groupe de contenu |
| `Text` | texte, titre ou fragment textuel via `as()` |
| `Alert` | message d'information ou d'erreur |
| `Form` | formulaire declaratif |
| `Input` | champ de saisie avec label |
| `Button` | action utilisateur |
| `Link` | navigation |

Chaque composant expose des props declaratives via des methodes chainables. Ces props decrivent une intention UI ; elles ne lancent pas de logique metier.

| Methode | Intention |
| --- | --- |
| `class()` | conserve les classes CSS a appliquer au rendu HTML ou a exposer au Preview |
| `as()` | indique le tag logique d'un `Text`, par exemple `h1`, `h2` ou `p` |
| `type()` sur `Button` | indique le type HTML/logique du bouton : `button`, `submit`, `reset` |
| `type()` sur `Input` | indique le type de champ : `text`, `email`, `password`, etc. |
| `variant()` | conserve une variante logique comme `primary`, `secondary`, `danger` |
| `required()` | indique qu'un champ est obligatoire et devient `required` en HTML |
| `placeholder()` | conserve le texte d'aide d'un champ |
| `value()` | conserve la valeur initiale declaree |
| `method()` | conserve la methode d'un formulaire, par exemple `GET` ou `POST` |
| `action()` | conserve l'URL cible d'un formulaire |
| `csrf()` | marque l'intention CSRF sans generer de faux token |
| `showIf()` | conserve une condition logique sans l'evaluer dans le Module 1 |

`class()` est conserve comme intention de style. Velt UI l'applique telle quelle au HTML apres escaping.

`variant()` est conserve comme intention logique. Velt UI ne decide pas du design final ; le renderer HTML l'expose via `data-variant` et Preview le garde dans `props`.

`type()` est conserve comme intention de comportement. Sur `Button`, il devient l'attribut HTML `type`. Sur `Input`, il devient le type du champ.

`required()` est conserve comme intention de validation. Velt UI le rend en attribut HTML `required`, mais ne valide pas la requete.

`csrf()` est conserve comme intention de securite. Velt UI ne genere pas de token dans le Module 1 ; le champ reel vient de HTTP/session.

`showIf()` est conserve comme intention logique. Velt UI ne l'evalue pas dans le Module 1.

## Rendu HTML

`WebRenderer` transforme une page en HTML.

```php
use Velt\Ui\Renderers\WebRenderer;

$html = (new WebRenderer())->render($page);
```

Par defaut, le rendu est un document complet :

```html
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Velt App</title>
</head>
<body>
...
</body>
</html>
```

Pour rendre seulement le fragment des composants :

```php
$fragment = (new WebRenderer())->render($page, ['document' => false]);
```

Mapping HTML MVP :

| Velt UI | HTML |
| --- | --- |
| `Card` | `section` |
| `Text` | `p`, `h1`, `h2`, etc. selon `as()` |
| `Alert` | `div role="alert"` |
| `Form` | `form` |
| `Input` | `label` + `input` |
| `Button` | `button` |
| `Link` | `a` |

Les textes et attributs sont echappes avec `htmlspecialchars`. Les classes declarees sont rendues telles quelles apres escaping. Velt UI n'injecte pas Tailwind ou une feuille de style automatiquement.

## CSRF

`Form::csrf()` ne cree pas de faux token. Il indique seulement que le formulaire attend un champ CSRF.

Sans couche HTTP/session, le renderer ne genere rien :

```php
Form::make()->method('POST')->csrf();
```

Quand le kernel complet dispose d'un service CSRF, il le fournit au renderer :

```php
$renderer = new WebRenderer(
    fn (array $form): string => $csrf->field()
);
```

Cette separation evite a Velt UI de dependre d'une implementation de session.

## Rendu JSON Preview

`JsonRenderer` produit le contrat consomme par l'application Preview.

```php
use Velt\Ui\Renderers\JsonRenderer;

$json = (new JsonRenderer())->render($page);
```

Structure stable :

```json
{
    "schemaVersion": 1,
    "screen": "Connexion",
    "layout": "auth",
    "meta": {
        "title": "Connexion - Velt App"
    },
    "components": []
}
```

Regles du schema :

- `schemaVersion` versionne le contrat Preview.
- `screen` vient du titre de la page.
- `layout` conserve l'intention de layout.
- `meta` conserve les metas de page.
- `components` contient l'arbre des composants.
- `props` conserve les intentions utiles : `class`, `variant`, `type`, `required`, `showIf`, etc.
- aucun HTML n'est inclus dans le JSON.
- `showIf` n'est pas evalue.

Types Preview MVP :

| Type interne | Type Preview |
| --- | --- |
| `card` | `Card` |
| `text` | `Text` |
| `alert` | `Alert` |
| `form` | `Form` |
| `input` | `Input` |
| `button` | `Button` |
| `link` | `Link` |

## Fichiers de vue

Une vue Velt est un fichier PHP declaratif qui retourne une `Page`.

Exemple `resources/views/auth/login.velt.php` :

```php
<?php

use Velt\Ui\Components\Text;
use Velt\Ui\Page;

return Page::make('Connexion')
    ->layout('auth')
    ->meta(['title' => 'Connexion - Velt App'])
    ->add(Text::make('Bienvenue')->as('h1'));
```

Chargement :

```php
use Velt\Ui\View\ViewFactory;

$views = new ViewFactory(__DIR__ . '/resources/views');
$page = $views->make('auth.login');
```

`auth.login` est resolu vers `auth/login.velt.php`.

Securite :

- les noms vides sont refuses ;
- les chemins comme `../secret` sont refuses ;
- une vue absente leve `ViewNotFoundException` ;
- une vue qui ne retourne pas `Page` leve une erreur claire.

## Contrats publics

Velt UI expose trois contrats principaux.

`ComponentInterface` :

- `getType()`
- `getProps()`
- `getChildren()`
- `getContent()`
- `toArray()`

`ViewInterface` :

- `title()`
- `getLayout()`
- `getMeta()`
- `children()`
- `toArray()`

`RendererInterface` :

```php
public function render(Page $page, array $options = []): string;
```

Ces contrats sont la surface publique que les autres modules doivent utiliser. Les renderers ne doivent pas lire des proprietes privees des composants.

## Integration avec le kernel Velt

Dans un projet complet, le kernel peut connecter Velt UI en quatre etapes.

1. Enregistrer une racine de vues :

```php
$views = new ViewFactory($projectRoot . '/resources/views');
```

2. Resoudre une vue depuis une route ou un controleur :

```php
$page = $views->make('auth.login');
```

3. Choisir un renderer selon le contexte :

```php
$renderer = $request->expectsJsonPreview()
    ? new JsonRenderer()
    : new WebRenderer(fn (array $form): string => $csrf->field());
```

4. Retourner la reponse HTTP :

```php
$content = $renderer->render($page);

return new Response(
    $content,
    headers: ['Content-Type' => $request->expectsJsonPreview()
        ? 'application/json'
        : 'text/html; charset=utf-8']
);
```

Responsabilites du kernel :

- router la requete ;
- appeler les controleurs ;
- instancier `ViewFactory` ;
- choisir HTML ou JSON Preview ;
- fournir le champ CSRF reel ;
- creer la reponse HTTP ;
- gerer la session, les erreurs et les middlewares.

Responsabilites de Velt UI :

- decrire la page ;
- charger une page declarative ;
- rendre HTML ou JSON ;
- proteger le rendu HTML via escaping ;
- exposer un schema stable pour Preview.

## Cache et compilation

Le Module 1 ne met pas en place de cache complexe.

Ce qui pourra etre cache plus tard :

- la page chargee depuis `.velt.php` ;
- l'arbre interne serialise ;
- le HTML rendu ;
- le JSON Preview rendu.

Invalidations simples prevues :

- fichier `.velt.php` modifie ;
- debug mode : eviter le cache persistant ;
- production mode : cache explicite par date de modification ou version d'application.

Sujets reportes :

- hydration ;
- hot reload ;
- build assets ;
- compilation avancee ;
- cache distribue.

## Tests

Quand PHP est disponible dans le terminal :

```bash
composer dump-autoload
vendor/bin/phpunit
```

Sur Windows :

```powershell
composer dump-autoload
vendor\bin\phpunit
```

Tests importants :

- `PageAndComponentsTest` : API declarative et serialisation de base.
- `WebRendererTest` : HTML, escaping, metas, CSRF.
- `JsonRendererTest` : schema Preview et composants MVP.
- `ViewFactoryTest` : chargement filesystem et erreurs de vues.
- `ContractsTest` : contrats publics et composant fake.

## Roadmap courte

Priorites naturelles apres le Module 1 :

- ajouter un CLI `velt` ;
- connecter `ViewFactory` au kernel ;
- ajouter un service provider UI ;
- formaliser le contrat CSRF cote HTTP ;
- definir le point d'entree Preview ;
- ajouter les composants UI suivants seulement quand les besoins du framework les justifient.
