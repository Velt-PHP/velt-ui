<?php

declare(strict_types=1);

namespace Velt\Ui\Support;

/**
 * Petit helper d'echappement partage par les renderers HTML.
 */
final class Html
{
    /**
     * Echappe le texte et les valeurs d'attributs pour une sortie HTML.
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Rend un tableau associatif en attributs HTML.
     *
     * true devient un attribut booleen, null et false sont ignores.
     *
     * @param array<string, mixed> $attributes
     */
    public static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $name = self::escape($name);

            if ($value === true) {
                $html .= ' ' . $name;
                continue;
            }

            $html .= ' ' . $name . '="' . self::escape($value) . '"';
        }

        return $html;
    }
}
