# Velt UI — Qu'est‑ce que fait ce projet ?

Ce document décrit concrètement ce que réalise le sous‑module `velt-ui`, où écrire du code, et comment générer les sorties (HTML / JSON) localement.

## But

Velt UI fournit une API PHP déclarative pour construire des écrans UI sous forme d'un arbre d'objets (Page + Components). À partir de cette source unique on peut :

- produire du HTML pour le web (WebRenderer) ;
- produire un JSON stable pour une preview mobile ou API (JsonRenderer) ;
- charger des pages déclaratives depuis des fichiers `.velt.php` (ViewFactory).

Le module ne gère pas la session, l'authentification ou la génération des tokens : il marque les intentions (ex. `csrf`) et le kernel (couche HTTP) fournit les valeurs concrètes si nécessaire.

## Où écrire les pages

Les vues sont des fichiers PHP déclaratifs qui retournent une `Page`. Placer vos vues dans :

- `resources/views/` — exemple : `resources/views/auth/login.velt.php`.

Chaque fichier doit retourner un objet `Velt\Ui\Page`, par exemple :

```php
// resources/views/auth/login.velt.php
return Page::make('Connexion')
    ->layout('auth')
    ->add(Text::make('Bienvenue')->as('h1'));
```

## Comment générer le HTML localement (exemples)

1) Générer un fichier HTML depuis la racine du projet :

```bash
php -r "require 'vendor/autoload.php'; use Velt\Ui\View\ViewFactory; use Velt\Ui\Renderers\WebRenderer; $views=new ViewFactory(getcwd().'/resources/views'); $page=$views->make('auth.login'); file_put_contents('auth-login.html',(new WebRenderer())->render($page)); echo 'genere auth-login.html\n';"
```

Puis ouvrez `auth-login.html` dans votre navigateur.

2) Ou créer un petit front local `public/index.php` et lancer un serveur PHP intégré :

```bash
# depuis la racine du repo
php -S localhost:8000 -t public

# puis ouvrir http://localhost:8000/ dans le navigateur
```

Dans `public/index.php` vous ferez : charger `ViewFactory`, `make('auth.login')` puis `echo (new WebRenderer($csrfResolver))->render($page);`.

## Générer le JSON Preview

```php
use Velt\Ui\View\ViewFactory;
use Velt\Ui\Renderers\JsonRenderer;

$views = new ViewFactory(__DIR__ . '/resources/views');
$page = $views->make('auth.login');
echo (new JsonRenderer())->render($page);
```

Ce JSON contient `schemaVersion`, `screen`, `layout`, `meta` et `components` (sans HTML).

## Points d'intégration avec le kernel

- Le kernel / couche HTTP appelle `ViewFactory::make()` pour charger la page.
- Le kernel fournit éventuellement un `csrfFieldResolver` au `WebRenderer` pour injecter un champ `_token` réel dans les formulaires marqués `csrf`.
- Le kernel gère la session, la validation des formulaires, les redirections et la persistance : `velt-ui` ne fait que décrire et rendre l'UI.

## Commandes utiles

- Lancer les tests unitaires :

```bash
composer test
```

- Regénérer l'autoload Composer après changements :

```bash
composer dump-autoload
```

## Où regarder dans le code

- Page : `src/Page.php`
- Composants : `src/Components/` (Button, Card, Text, Alert, Form, Input, Link)
- Renderers : `src/Renderers/WebRenderer.php`, `src/Renderers/JsonRenderer.php`
- Helper HTML : `src/Support/Html.php`
- Chargement des vues : `src/View/ViewFactory.php`

## Exemple rapide (CLI)

```bash
php -r "require 'vendor/autoload.php'; use Velt\Ui\View\ViewFactory; use Velt\Ui\Renderers\WebRenderer; $views=new ViewFactory(getcwd().'/resources/views'); $page=$views->make('auth.login'); echo (new WebRenderer())->render($page);"
```

Ce module vise à rester un composant pur de rendu déclaratif — simple, testable et indépendant du cycle HTTP complet.

---
Fichier ajouté : [docs/CONCRETEMENT.md](docs/CONCRETEMENT.md)
