<?php

declare(strict_types=1);

namespace Velt\Ui\Components;

/**
 * Class Link
 *
 * Composant de lien hypertexte.
 *
 * Exemple :
 *
 * Link::make('Aller au dashboard', '/dashboard')
 *     ->class('text-blue-500 hover:underline')
 *
 * Devient en HTML : <a href="/dashboard" class="text-blue-500 hover:underline">Aller au dashboard</a>
 */
class Link extends Component
{
    protected string $type = 'link';

    protected string $url;

    /**
     * Crée une nouvelle instance de Link.
     *
     * @param string $label Texte du lien
     * @param string $href URL cible
     */
    public static function make(string $label, string $href): self
    {
        $instance = new self();
        $instance->content = $label;
        $instance->url = $href;

        return $instance;
    }

    /**
     * Retourne le tableau du composant avec href.
     */
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['href'] = $this->url;

        return $array;
    }
}
