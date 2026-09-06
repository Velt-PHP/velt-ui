<?php

declare(strict_types=1);

namespace Velt\Ui\Renderers;

use Velt\Ui\Contracts\RendererInterface;
use Velt\Ui\Contracts\UniversalUiContract;
use Velt\Ui\Page;

/** Sérialise uniquement des intentions supportables par Web et Compose. */
final class UniversalRenderer implements RendererInterface
{
    /** @param array<string, mixed> $options */
    public function render(Page $page, array $options = []): string
    {
        return json_encode($this->toArray($page), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    public function toArray(Page $page): array
    {
        UniversalUiContract::assertValid($page);
        $tree = $page->toArray();

        return [
            'contractVersion' => UniversalUiContract::VERSION,
            'screen' => $tree['title'],
            'layout' => $tree['layout'],
            'tokens' => UniversalUiContract::manifest()['tokens'],
            'capabilities' => UniversalUiContract::manifest()['capabilities'],
            'components' => $this->components($tree['children'] ?? []),
        ];
    }

    /**
     * @param list<array<string, mixed>> $components
     * @return list<array<string, mixed>>
     */
    private function components(array $components): array
    {
        $index = 0;

        return array_map(
            function (array $component) use (&$index): array {
                return $this->component($component, (string) $index++);
            },
            $components
        );
    }

    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    private function component(array $component, string $path): array
    {
        $type = UniversalUiContract::primitive((string) $component['type']);
        $props = $component['props'] ?? [];
        $label = $props['ariaLabel'] ?? $component['label'] ?? $component['content'] ?? null;
        $node = [
            'id' => $props['id'] ?? 'node-' . str_replace('.', '-', $path),
            'type' => $type,
            'props' => $this->portableProps($props),
            'accessibility' => [
                'role' => $props['role'] ?? $this->role($type),
                'label' => $label,
                'focusable' => in_array($type, ['button', 'input', 'navigation', 'toggle'], true),
                'targetSize' => in_array($type, ['button', 'input', 'navigation', 'toggle'], true) ? 44 : null,
                'contrast' => 'tokens.required',
            ],
            'events' => $this->events($props),
            'children' => [],
        ];
        if (array_key_exists('content', $component)) {
            $node['content'] = $component['content'];
        }
        foreach ($component['children'] ?? [] as $index => $child) {
            $node['children'][] = $this->component($child, $path . '.' . $index);
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function portableProps(array $props): array
    {
        unset($props['id'], $props['role'], $props['ariaLabel']);
        return $props;
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, string>
     */
    private function events(array $props): array
    {
        return array_filter($props, static fn (mixed $value, string $key): bool => str_starts_with($key, 'on') && is_string($value), ARRAY_FILTER_USE_BOTH);
    }

    private function role(string $type): string
    {
        return match ($type) {
            'button' => 'button', 'input' => 'textbox', 'navigation' => 'link', 'toggle' => 'switch',
            'modal' => 'dialog', 'list' => 'list', default => 'group',
        };
    }
}