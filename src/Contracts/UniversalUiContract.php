<?php

declare(strict_types=1);

namespace Velt\Ui\Contracts;

use Velt\Ui\Exceptions\UniversalContractViolation;
use Velt\Ui\Page;

/**
 * Contrat neutre de plateforme pour les renderers Web, NativeWind et Compose.
 */
final class UniversalUiContract
{
    public const VERSION = '2.0';

    /** @var list<string> */
    public const PRIMITIVES = [
        'layout', 'text', 'image', 'icon', 'button', 'input', 'toggle',
        'list', 'scroll', 'navigation', 'modal',
    ];

    /** @var array<string, string> */
    private const ALIASES = [
        'card' => 'layout',
        'form' => 'layout',
        'alert' => 'text',
        'link' => 'navigation',
    ];

    /** @var array<string, list<string>> */
    private const PROPS = [
        'layout' => ['id', 'role', 'ariaLabel', 'direction', 'gap', 'padding', 'style', 'variant'],
        'text' => ['id', 'role', 'ariaLabel', 'as', 'style', 'variant'],
        'image' => ['id', 'role', 'ariaLabel', 'src', 'alt', 'width', 'height', 'style'],
        'icon' => ['id', 'role', 'ariaLabel', 'name', 'size', 'style'],
        'button' => ['id', 'role', 'ariaLabel', 'type', 'variant', 'disabled', 'style', 'onPress'],
        'input' => ['id', 'role', 'ariaLabel', 'inputType', 'required', 'placeholder', 'value', 'style'],
        'toggle' => ['id', 'role', 'ariaLabel', 'checked', 'disabled', 'style', 'onChange'],
        'list' => ['id', 'role', 'ariaLabel', 'style'],
        'scroll' => ['id', 'role', 'ariaLabel', 'direction', 'style'],
        'navigation' => ['id', 'role', 'ariaLabel', 'href', 'variant', 'style', 'onPress'],
        'modal' => ['id', 'role', 'ariaLabel', 'visible', 'style', 'onDismiss'],
    ];

    /** @return array<string, mixed> */
    public static function manifest(): array
    {
        return [
            'contractVersion' => self::VERSION,
            'primitives' => self::PRIMITIVES,
            'capabilities' => ['events', 'focus', 'themes', 'accessibility', 'tokens'],
            'tokens' => [
                'color' => [
                    'light' => ['background' => '#ffffff', 'surface' => '#f8fafc', 'content' => '#0f172a', 'muted' => '#475569', 'primary' => '#2563eb', 'danger' => '#b91c1c', 'focus' => '#1d4ed8'],
                    'dark' => ['background' => '#0f172a', 'surface' => '#1e293b', 'content' => '#f8fafc', 'muted' => '#cbd5e1', 'primary' => '#60a5fa', 'danger' => '#f87171', 'focus' => '#93c5fd'],
                ],
                'space' => ['0' => '0rem', '1' => '0.25rem', '2' => '0.5rem', '3' => '0.75rem', '4' => '1rem', '6' => '1.5rem', '8' => '2rem', '12' => '3rem'],
                'typography' => ['body' => ['size' => '1rem', 'lineHeight' => 1.5], 'label' => ['size' => '0.875rem', 'lineHeight' => 1.25], 'heading' => ['size' => '1.5rem', 'lineHeight' => 1.25], 'caption' => ['size' => '0.75rem', 'lineHeight' => 1.25]],
                'radius' => ['none' => '0rem', 'sm' => '0.125rem', 'md' => '0.375rem', 'lg' => '0.5rem', 'pill' => '9999px'],
                'elevation' => ['none' => ['dp' => 0], 'sm' => ['dp' => 1], 'md' => ['dp' => 4], 'lg' => ['dp' => 8]],
                'themes' => ['light', 'dark'],
            ],
        ];
    }

    /** @return list<string> */
    public static function validate(Page $page): array
    {
        $violations = [];
        foreach ($page->toArray()['children'] ?? [] as $index => $component) {
            self::validateComponent($component, (string) $index, $violations);
        }

        return $violations;
    }

    public static function assertValid(Page $page): void
    {
        $violations = self::validate($page);
        if ($violations !== []) {
            throw new UniversalContractViolation($violations);
        }
    }

    public static function primitive(string $type): string
    {
        return self::ALIASES[$type] ?? $type;
    }

    /**
     * @param array<string, mixed> $component
     * @param list<string> $violations
     */
    private static function validateComponent(array $component, string $path, array &$violations): void
    {
        $type = self::primitive((string) ($component['type'] ?? ''));
        if (! in_array($type, self::PRIMITIVES, true)) {
            $violations[] = $path . '.type: primitive non supportee';
        }

        $props = $component['props'] ?? [];
        if (! is_array($props)) {
            $violations[] = $path . '.props: tableau attendu';
            return;
        }

        foreach ($props as $name => $value) {
            if ($name === 'class') {
                $violations[] = $path . '.props.class: classe CSS non portable, utiliser des tokens';
                continue;
            }
            if (! in_array($name, self::PROPS[$type] ?? [], true)) {
                $violations[] = $path . '.props.' . $name . ': prop non portable ou inconnue';
            }
            if (str_starts_with((string) $name, 'on') && ! is_string($value)) {
                $violations[] = $path . '.props.' . $name . ': identifiant evenement attendu';
            }
        }

        if ($type === 'input' && trim((string) ($component['label'] ?? '')) === '') {
            $violations[] = $path . '.label: libelle accessible obligatoire';
        }
        if ($type === 'button' && trim((string) ($component['content'] ?? '')) === '' && empty($props['ariaLabel'])) {
            $violations[] = $path . '.content: label ou ariaLabel obligatoire';
        }

        foreach ($component['children'] ?? [] as $index => $child) {
            if (is_array($child)) {
                self::validateComponent($child, $path . '.children.' . $index, $violations);
            }
        }
    }
}