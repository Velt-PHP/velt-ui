<?php

declare(strict_types=1);

namespace Velt\Ui\Renderers;

use Velt\Ui\Contracts\RendererInterface;
use Velt\Ui\Page;

/**
 * Rend une Page vers le contrat JSON stable consomme par Preview.
 *
 * Ce renderer emet uniquement des donnees declaratives. Il ne contient pas de
 * HTML, n'evalue pas showIf et garde schemaVersion explicite pour permettre
 * aux clients mobiles d'evoluer separement des details PHP internes.
 */
final class JsonRenderer implements RendererInterface
{
    public const SCHEMA_VERSION = 1;

    /**
     * Encode l'arbre Preview en JSON.
     */
    /** @param array<string, mixed> $options */
    public function render(Page $page, array $options = []): string
    {
        return json_encode(
            $this->toPreviewArray($page),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Retourne l'arbre Preview avant encodage JSON.
     *
     * Les tests et futurs adaptateurs Preview peuvent inspecter le schema sans
     * parser une chaine JSON.
     */
    /** @return array<string, mixed> */
    public function toPreviewArray(Page $page): array
    {
        $tree = $page->toArray();

        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'screen' => $tree['title'],
            'layout' => $tree['layout'],
            'meta' => $tree['meta'],
            'components' => array_map(
                fn (array $component): array => $this->componentToPreview($component),
                $tree['children'] ?? []
            ),
        ];
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    private function componentToPreview(array $component): array
    {
        $preview = [
            'type' => $this->previewType($component['type'] ?? 'unknown'),
            'props' => $this->propsForPreview($component),
            'children' => array_map(
                fn (array $child): array => $this->componentToPreview($child),
                $component['children'] ?? []
            ),
        ];

        if (array_key_exists('content', $component)) {
            $preview['content'] = $component['content'];
        }

        if (array_key_exists('name', $component)) {
            $preview['name'] = $component['name'];
        }

        if (array_key_exists('label', $component)) {
            $preview['label'] = $component['label'];
        }

        if (array_key_exists('href', $component)) {
            $preview['href'] = $component['href'];
        }

        return $preview;
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    private function propsForPreview(array $component): array
    {
        $props = $component['props'] ?? [];

        // Preview utilise "type" pour le type de champ. L'arbre interne garde
        // "inputType" pour eviter un conflit avec le type du composant.
        if (($component['type'] ?? null) === 'input' && isset($props['inputType'])) {
            $props['type'] = $props['inputType'];
            unset($props['inputType']);
        }

        return $props;
    }

    private function previewType(string $type): string
    {
        // Les composants MVP ont un nom explicite. Les composants custom sont
        // normalises en PascalCase sans registre global.
        return match ($type) {
            'card' => 'Card',
            'text' => 'Text',
            'alert' => 'Alert',
            'form' => 'Form',
            'input' => 'Input',
            'button' => 'Button',
            'link' => 'Link',
            default => str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $type))),
        };
    }
}
