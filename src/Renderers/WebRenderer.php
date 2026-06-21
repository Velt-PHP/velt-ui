<?php

declare(strict_types=1);

namespace Velt\Ui\Renderers;

use Velt\Ui\Contracts\RendererInterface;
use Velt\Ui\Page;
use Velt\Ui\Support\Html;

/**
 * Rend une Page Velt declarative en HTML utilisable par un navigateur.
 *
 * Le renderer reste volontairement petit et pur : il mappe les composants vers
 * du HTML, echappe textes et attributs, et delegue les concerns framework comme
 * la generation CSRF a un resolver optionnel.
 */
final class WebRenderer implements RendererInterface
{
    /**
     * Le resolver CSRF est fourni par la couche HTTP/session quand elle existe.
     * Sans resolver, csrf() reste une intention et aucun faux token n'est emis.
     *
     * @param null|callable(array<string, mixed>): string $csrfFieldResolver
     */
    public function __construct(
        private readonly mixed $csrfFieldResolver = null
    ) {
    }

    /** @param array<string, mixed> $options */
    public function render(Page $page, array $options = []): string
    {
        $fullDocument = $options['document'] ?? true;
        $body = $this->renderChildren($page->toArray()['children'] ?? []);

        // Le mode fragment sert aux tests, previews ou frameworks hotes qui
        // possedent deja l'enveloppe du document.
        if (! $fullDocument) {
            return $body;
        }

        $meta = $page->getMeta();
        $title = $meta['title'] ?? $page->title();

        return implode("\n", [
            '<!doctype html>',
            '<html lang="' . Html::escape($options['lang'] ?? 'fr') . '">',
            '<head>',
            '    <meta charset="' . Html::escape($meta['charset'] ?? 'utf-8') . '">',
            '    <meta name="viewport" content="' . Html::escape($meta['viewport'] ?? 'width=device-width, initial-scale=1') . '">',
            '    <title>' . Html::escape($title) . '</title>',
            $this->renderHeadAssets($meta),
            '</head>',
            '<body>',
            $body,
            '</body>',
            '</html>',
        ]);
    }

    /** @param array<string, mixed> $meta */
    private function renderMetaTags(array $meta): string
    {
        $tags = [];

        foreach ($meta as $name => $content) {
            if (in_array($name, ['title', 'charset', 'viewport', 'stylesheets'], true) || ! is_scalar($content)) {
                continue;
            }

            $tags[] = '    <meta name="' . Html::escape($name) . '" content="' . Html::escape($content) . '">';
        }

        return implode("\n", $tags);
    }

    /** @param array<string, mixed> $meta */
    private function renderHeadAssets(array $meta): string
    {
        return implode("\n", array_filter(
            [$this->renderMetaTags($meta), $this->renderStylesheets($meta)],
            static fn (string $markup): bool => $markup !== ''
        ));
    }

    /** @param array<string, mixed> $meta */
    private function renderStylesheets(array $meta): string
    {
        $stylesheets = $meta['stylesheets'] ?? [];

        if (! is_array($stylesheets)) {
            return '';
        }

        $links = [];

        foreach ($stylesheets as $stylesheet) {
            if (! is_string($stylesheet) || $stylesheet === '') {
                continue;
            }

            $links[] = '    <link rel="stylesheet" href="' . Html::escape($stylesheet) . '">';
        }

        return implode("\n", $links);
    }

    /** @param list<array<string, mixed>> $children */
    private function renderChildren(array $children): string
    {
        return implode("\n", array_map(fn (array $child): string => $this->renderComponent($child), $children));
    }

    /** @param array<string, mixed> $component */
    private function renderComponent(array $component): string
    {
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
    }

    /** @param array<string, mixed> $component */
    private function renderCard(array $component): string
    {
        return '<section' . Html::attributes($this->componentAttributes($component)) . '>'
            . $this->wrapChildren($component)
            . '</section>';
    }

    /** @param array<string, mixed> $component */
    private function renderText(array $component): string
    {
        $tag = $this->textTag($component['props']['as'] ?? 'p');

        return '<' . $tag . Html::attributes($this->componentAttributes($component)) . '>'
            . Html::escape($component['content'] ?? '')
            . '</' . $tag . '>';
    }

    /** @param array<string, mixed> $component */
    private function renderAlert(array $component): string
    {
        $attributes = ['role' => 'alert'] + $this->componentAttributes($component);

        if (isset($component['props']['alertType'])) {
            $attributes['data-alert-type'] = $component['props']['alertType'];
        }

        return '<div' . Html::attributes($attributes) . '>'
            . Html::escape($component['content'] ?? '')
            . '</div>';
    }

    /** @param array<string, mixed> $component */
    private function renderForm(array $component): string
    {
        $props = $component['props'] ?? [];
        $attributes = [
            'method' => $props['method'] ?? 'GET',
            'action' => $props['action'] ?? null,
        ] + $this->componentAttributes($component);

        $csrfField = '';

        if (($props['csrf'] ?? false) === true && is_callable($this->csrfFieldResolver)) {
            $csrfField = (string) call_user_func($this->csrfFieldResolver, $component);
        }

        return '<form' . Html::attributes($attributes) . '>'
            . $csrfField
            . $this->wrapChildren($component)
            . '</form>';
    }

    /** @param array<string, mixed> $component */
    private function renderInput(array $component): string
    {
        $props = $component['props'] ?? [];
        $name = $component['name'] ?? '';
        $id = $props['id'] ?? $name;

        $label = '<label' . Html::attributes(['for' => $id]) . '>'
            . Html::escape($component['label'] ?? '')
            . '</label>';

        $input = '<input' . Html::attributes([
            'id' => $id,
            'type' => $props['inputType'] ?? 'text',
            'name' => $name,
            'value' => $props['value'] ?? null,
            'placeholder' => $props['placeholder'] ?? null,
            'required' => $props['required'] ?? false,
            'class' => $props['class'] ?? null,
        ]) . '>';

        return $label . $input;
    }

    /** @param array<string, mixed> $component */
    private function renderButton(array $component): string
    {
        $props = $component['props'] ?? [];

        $attributes = [
            'type' => $props['type'] ?? 'button',
            'disabled' => $props['disabled'] ?? false,
        ] + $this->componentAttributes($component);

        if (isset($props['variant'])) {
            $attributes['data-variant'] = $props['variant'];
        }

        return '<button' . Html::attributes($attributes) . '>'
            . Html::escape($component['content'] ?? '')
            . '</button>';
    }

    /** @param array<string, mixed> $component */
    private function renderLink(array $component): string
    {
        return '<a' . Html::attributes(
            ['href' => $component['href'] ?? '#'] + $this->componentAttributes($component)
        ) . '>'
            . Html::escape($component['content'] ?? '')
            . '</a>';
    }

    /** @param array<string, mixed> $component */
    private function wrapChildren(array $component): string
    {
        $children = $component['children'] ?? [];

        if ($children === []) {
            return '';
        }

        return "\n" . $this->renderChildren($children) . "\n";
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    private function componentAttributes(array $component): array
    {
        return [
            'id' => $component['props']['id'] ?? null,
            'class' => $component['props']['class'] ?? null,
        ];
    }

    private function textTag(mixed $tag): string
    {
        $tag = strtolower((string) $tag);
        $allowed = ['p', 'span', 'strong', 'em', 'small', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        // Ne pas faire confiance aux tags arbitraires. On revient a un element
        // texte sur plutot que de rendre un tag executable ou destructeur.
        return in_array($tag, $allowed, true) ? $tag : 'p';
    }
}
