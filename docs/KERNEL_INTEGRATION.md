# Integration avec Velt Kernel

`velt/ui` fournit toute son integration au Kernel. La dependance respecte le sens suivant :

```text
velt/ui -> velt/kernel
```

Le Kernel reste une fondation autonome et ne connait aucune classe UI.

## Enregistrement

Une application qui utilise l'UI enregistre le provider fourni par ce package :

```php
use Velt\Kernel\Application;
use Velt\Ui\Providers\UiServiceProvider;

$app = new Application($basePath);
$app->registerProvider(UiServiceProvider::class);
```

Le provider enregistre trois singletons :

| Classe | Alias |
| --- | --- |
| `Velt\Ui\View\ViewFactory` | `view` |
| `Velt\Ui\Renderers\WebRenderer` | `ui.renderer.web` |
| `Velt\Ui\Renderers\JsonRenderer` | `ui.renderer.json` |

## Chemin des vues

Par defaut, `ViewFactory` charge les vues depuis `{basePath}/resources/views`.
Le chemin peut etre surcharge dans la configuration de l'application :

```php
$app = new Application($basePath, [
    'view' => [
        'path' => $basePath . '/custom-views',
    ],
]);
```

## CSRF optionnel

Si le container expose un service `csrf` possedant une methode `field()`, le provider
le connecte au `WebRenderer`. Sans ce service, un formulaire marque `csrf()` conserve
son intention, mais aucun faux token n'est genere.

La session, les requetes, les reponses et les redirections restent sous la
responsabilite des packages HTTP applicatifs.
