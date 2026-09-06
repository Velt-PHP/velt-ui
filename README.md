# Velt UI

Velt UI est le système d’interface déclarative du framework. Une page est construite en PHP à partir de composants typés, puis rendue soit en HTML accessible pour le Web, soit en arbre JSON versionné pour Preview et les renderers natifs. Le même modèle peut ainsi alimenter plusieurs plateformes sans envoyer une WebView à Android.

> Statut : préversion. Le renderer HTML et le contrat JSON minimal sont utilisables ; l’état interactif, l’accessibilité multiplateforme et la compatibilité avec le futur renderer Compose restent des gates avant stabilité.

## Installation et démarrage rapide

```bash
composer require velt/ui
```

```php
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Text;
use Velt\Ui\Page;
use Velt\Ui\Renderers\WebRenderer;

$page = Page::make('Dashboard')->add(
    Card::make()->class('rounded-xl border p-6')
        ->add(Text::make('Bienvenue')->as('h1'))
        ->add(Button::make('Continuer')->variant('primary'))
);

$html = (new WebRenderer())->render($page);
```

## Principes publics

- une page est un arbre déterministe, pas du HTML caché ;
- les composants expriment une intention portable ;
- chaque renderer matérialise cette intention sur sa plateforme ;
- textes et attributs Web sont échappés par défaut ;
- le format Preview est versionné indépendamment des classes internes ;
- UI reste indépendant de HTTP, PDO et Android.

Le package ne fournit pas de routeur, ne génère pas de CSRF sans contrat de session et ne transforme pas automatiquement des classes Tailwind en widgets Compose. Les variantes et futurs tokens portables forment la frontière multiplateforme.

Documentation complete : [docs/README.md](docs/README.md)

Contrat universel Web/Android, tokens, mappings et migration CSS : [docs/CONTRAT_UNIVERSEL.md](docs/CONTRAT_UNIVERSEL.md)

Architecture developpeur detaillee : [docs/ARCHITECTURE_DEVELOPPEUR.md](docs/ARCHITECTURE_DEVELOPPEUR.md)

Architecture detaillee des dossiers, fichiers et flux : [docs/ARCHITECTURE_DETAILLEE.md](docs/ARCHITECTURE_DETAILLEE.md)

Rapport pour memoire de defense : [docs/RAPPORT_MEMOIRE_DEFENSE.md](docs/RAPPORT_MEMOIRE_DEFENSE.md)

Integration kernel : [docs/KERNEL_INTEGRATION.md](docs/KERNEL_INTEGRATION.md)

Guide de contribution : [CONTRIBUTING.md](CONTRIBUTING.md)

Politique de securite : [SECURITY.md](SECURITY.md)

## Mission

Ce sous-module est le coeur distinctif de Velt. Il fournit la syntaxe declarative officielle en PHP, construit un arbre UI en memoire, puis le rend soit en HTML pour le Web, soit en JSON pour la preview mobile.

Apres audit, UI doit expliciter ses contrats internes. `Page::make()` est une tres bonne base, mais le moteur doit definir `ComponentInterface`, `RendererInterface` et une strategie de serialisation stable pour eviter que WebRenderer, JsonRenderer et Preview deviennent couples a des details internes.

## Perimetre

Inclus :

- `Page`
- composants `Text`, `Button`, `Card`, `Form`, `Input`, `Link`, `Alert`
- `ComponentInterface`
- `RendererInterface`
- `ViewInterface` ou contrat equivalent pour les pages chargees ;
- props chainables ;
- rendu HTML MVP ;
- rendu JSON MVP ;
- schema JSON versionne ;
- escaping HTML obligatoire ;
- tests snapshot simples.

Exclus :

- compilation type React/Svelte ;
- Tailwind runtime ;
- etat interactif avance ;
- hot reload.

## Comment tester sans HTTP ou Preview

UI doit etre testable comme une bibliotheque pure.

- Une page est instanciee directement en PHP et comparee avec `toArray()`.
- Le HTML est teste par snapshots ou assertions DOM simples, sans lancer de serveur HTTP.
- Le JSON preview est teste avec `json_decode()` et assertions sur `screen`, `schemaVersion` et `components`.
- `ViewFactory` charge des fichiers `.velt.php` depuis un dossier temporaire.
- CSRF ne doit pas generer un vrai token dans UI. UI marque seulement l'intention `csrf: true`; HTTP/session transforme cette intention en champ reel.
- Les composants echappent les textes et attributs dangereux dans le renderer HTML.

L'integration avec `Velt/http` est testee plus tard par le sous-module 07. UI ne doit pas dependre de `Request` ou `Response`.

## Mapping HTML MVP

`WebRenderer` transforme une `Page` en document HTML complet par defaut, ou en fragment avec `render($page, ['document' => false])`.

| Composant | HTML rendu |
| --- | --- |
| `Page` | `<!doctype html>`, `<html>`, `<head>`, metas, `<body>` |
| `Card` | `<section>` |
| `Text` | `<p>`, `<h1>`, `<h2>`, etc. selon `as()` |
| `Alert` | `<div role="alert">` |
| `Form` | `<form>` |
| `Input` | `<label>` + `<input>` |
| `Button` | `<button>` |
| `Link` | `<a>` |

Les textes et attributs sont echappes avec `htmlspecialchars`. Les classes CSS declarees via `class()` sont rendues telles quelles apres escaping. `meta.title` devient `<title>` et les autres metas scalaires deviennent des balises `<meta name="..." content="...">`.

`Form::csrf()` marque seulement l'intention CSRF. Sans contrat HTTP/session fourni au renderer, aucun champ `_token` n'est genere afin d'eviter un faux token silencieux. Quand l'integration HTTP est disponible, elle peut etre deleguee au constructeur :

```php
$renderer = new WebRenderer(
    fn (array $form): string => '<input type="hidden" name="_token" value="...">'
);
```

## Schema JSON Preview

`JsonRenderer` produit le contrat public consomme par Preview. Ce format ne contient pas de HTML et reste separe de l'arbre interne retourne par `Page::toArray()`.

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

Vocabulaire commun UI et Preview :

- `schemaVersion` : version du contrat JSON preview.
- `screen` : nom de l'ecran, derive du titre de la page.
- `layout` : intention de layout declaree par la page.
- `meta` : donnees descriptives de la page.
- `components` : arbre de composants serialise.
- `props` : intentions logiques du composant, par exemple `class`, `variant`, `type`, `required`, `csrf`, `showIf`.
- `children` : composants enfants.

Intentions importantes :

- `class` est conserve comme intention de style et rendu en HTML apres escaping.
- `variant` est conserve comme intention logique ; Velt UI ne decide pas du design final.
- `type` est conserve comme intention de comportement pour `Button` et `Input`.
- `required` est conserve comme intention de validation et rendu en attribut HTML.
- `csrf` est conserve comme intention de securite ; Velt UI ne genere pas de faux token.
- `showIf` est conserve comme intention logique et n'est pas evalue dans le Module 1.

Les composants MVP sont serialises avec des types stables pour Preview : `Card`, `Text`, `Alert`, `Form`, `Input`, `Button`, `Link`.

## Chargement des vues

`ViewFactory` charge des fichiers declaratifs `.velt.php` depuis une racine configurable. Le nom logique `auth.login` pointe vers `auth/login.velt.php`.

```php
use Velt\Ui\View\ViewFactory;

$views = new ViewFactory(__DIR__ . '/resources/views');
$page = $views->make('auth.login');
```

Un fichier de vue doit retourner une instance de `Velt\Ui\Page`. Les noms contenant des segments dangereux comme `../` sont refuses et une vue absente leve `ViewNotFoundException`.

## Contrats publics

Les contrats du Module 1 sont volontairement courts :

- `ComponentInterface` expose type, props, contenu, enfants et serialisation interne.
- `ViewInterface` decrit une page declarative serialisable.
- `RendererInterface` definit `render(Page $page, array $options = []): string`.

Les renderers utilisent ces contrats et les methodes publiques. Les details internes des classes concretes restent hors contrat.

## Cache UI Module 1

Le Module 1 ne compile pas les vues et n'ajoute pas de cache complexe. La direction retenue est de rendre cacheables trois niveaux plus tard :

- page chargee depuis `.velt.php` ;
- arbre serialise interne ;
- sorties rendues, HTML ou JSON preview.

Invalidations simples prevues :

- fichier `.velt.php` modifie : invalider la page chargee, l'arbre et les rendus ;
- debug mode : preferer une lecture directe sans cache persistant ;
- production mode : autoriser un cache explicite avec invalidation par date de modification ou cle de version.

Sujets reportes hors Module 1 : hydration, hot reload, build assets, compilation avancee, analyse statique des templates et cache distribue.

## Architecture, compatibilité et qualité

```text
Page / ComponentInterface
        |-- WebRenderer  -> HTML échappé
        |-- JsonRenderer -> schéma Preview versionné
        `-- Compose renderer Android (cible)
```

`ComponentInterface`, `ViewInterface` et `RendererInterface` forment la frontière publique. Un changement du JSON exige une fixture dans `velt-preview` et un test côté companion. Les composants interactifs doivent utiliser un identifiant d’événement stable et un payload validé, jamais une chaîne de code exécutable.

Les composants doivent pouvoir exprimer libellé accessible, rôle, état, erreur et ordre de focus. Les gates de release couvrent clavier, lecteur d’écran, contraste, agrandissement du texte et cibles tactiles.

```bash
composer install
composer validate --strict
composer test
composer analyse
```

Une modification publique nécessite au minimum un test Web et un test JSON. Les limites connues avant stabilité sont l’état interactif, le renderer Compose, les tokens natifs, le cache/compilateur, l’accessibilité multiplateforme et les migrations automatiques de schéma.

## Feuille de route et issues

- [Issue 01 - Creer Page et composants UI](issues/01-creer-page-composants-ui.md)
- [Issue 02 - Implementer WebRenderer HTML](issues/02-implementer-web-renderer-html.md)
- [Issue 03 - Implementer JsonRenderer preview](issues/03-implementer-json-renderer-preview.md)
- [Issue 04 - Ajouter ViewFactory et chargement des pages Velt](issues/04-ajouter-viewfactory-chargement-pages-velt.md)
- [Issue 05 - Definir contrats Component Renderer View](issues/05-definir-contrats-component-renderer-view.md)
- [Issue 06 - Cadrer cache compilation et schema UI](issues/06-cadrer-cache-compilation-schema-ui.md)

## Exemple officiel (Issue 01)

```php
use Velt\Ui\Page;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Text;
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Form;
use Velt\Ui\Components\Input;

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
                ->add(
                    Input::make('email', 'Email')
                        ->type('email')
                        ->required()
                        ->placeholder('Entrez votre email')
                )->add(
                    Input::make('password', 'Mot de passe')
                        ->type('password')
                        ->required()
                        ->placeholder('Entrez votre mot de passe')
                )->add(
                    Button::make('Se connecter')
                        ->type('submit')
                        ->variant('primary')
                        ->class('w-full')
                )
        )
    );

// Sérialiser en tableau
$data = $page->toArray();

// Sérialiser en JSON
$json = $page->toJson();
```
