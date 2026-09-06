# Contrat UI universel v2

Le contrat `2.0` est l'intention publique partagee par les renderers Web et Android. `UniversalRenderer` produit du JSON declaratif ; il ne produit ni HTML, ni CSS, ni WebView.

## Primitives et style

Les primitives supportees sont `layout`, `text`, `image`, `icon`, `button`, `input`, `toggle`, `list`, `scroll`, `navigation` et `modal`. Les anciens noms `card`, `form`, `alert` et `link` sont des alias de migration vers `layout`, `layout`, `text` et `navigation`.

Les tokens neutres sont definis dans [ui-contract-v2.json](../resources/velt/ui-contract-v2.json) : couleur, espace, typographie, rayon, elevation et themes `light`/`dark`. Une intention utilise `variant`, `style` ou une reference de token ; une classe CSS arbitraire n'est jamais un contrat mobile.

## Mappings

| Contrat Velt | Tailwind Web | NativeWind | Material 3 Compose |
| --- | --- | --- | --- |
| `color.primary` | theme `colors.primary` | meme nom dans `tailwind.config` | `MaterialTheme.colorScheme.primary` |
| `space.4` | `p-4`, `gap-4` | `p-4`, `gap-4` | `16.dp` via le token partage |
| `typography.heading` | `text-*` du theme | meme theme | `MaterialTheme.typography.headlineSmall` |
| `radius.md` | `rounded-md` | `rounded-md` | `RoundedCornerShape` du token |
| `elevation.sm` | shadow du theme | shadow du theme | `tonalElevation`/`shadowElevation` |
| `button` | composant HTML + utilitaires generes | `Pressable`/composant NativeWind | `Button` Material 3 |
| `input` | `label` + `input` | `TextInput` | `OutlinedTextField` |
| `navigation` | `a` | `Pressable`/navigation host | `NavigationSuite` ou `TextButton` |
| `modal` | dialog accessible | `Modal` | `BasicAlertDialog`/`AlertDialog` |

NativeWind est optionnel : le preset `universal/nativewind` reste experimental tant que les tests web et Android ne sont pas verts. Il ne doit pas etre installe comme dependance cachee de ce package PHP.

## Identifiants, evenements et accessibilite

Chaque noeud possede un `id` explicite ou deterministe (`node-0-1`). Les evenements sont des identifiants (`onPress: "auth.submit"`), jamais du code executable. Le renderer expose role, label, focusabilite, taille cible de 44 dp/px pour les controles et l'obligation de verifier le contraste via les tokens.

## Rejet et budget

Une prop inconnue, `class`, un evenement non textuel, une primitive inconnue ou un controle sans label leve `UniversalContractViolation`. Aucun fallback silencieux n'est autorise.

Budget de reference pour une page de 200 noeuds : serialisation <= 10 ms, diff <= 5 ms, recomposition native <= 16 ms sur appareil de reference. Ces budgets sont des gates CI a mesurer dans les renderers companions ; ce package ne pretend pas les prouver sans Android et web installes.

## Migration des classes CSS

1. Remplacer `class('p-8 rounded-lg shadow')` par des props semantiques, par exemple `style: { padding: '8', radius: 'lg', elevation: 'sm' }` dans le composant universel.
2. Remplacer les couleurs et tailles litterales par les noms du manifeste.
3. Remplacer les pseudo-classes et media queries par des etats/capabilities explicites.
4. Executer `UniversalUiContract::validate($page)` en build ; corriger chaque violation au lieu de la convertir en classe CSS.

Le renderer Web historique accepte encore `class()` pour compatibilite. Cette compatibilite n'est pas portable et est intentionnellement refusee par `UniversalRenderer`.