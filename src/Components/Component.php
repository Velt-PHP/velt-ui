<?php

declare(strict_types=1);

namespace Velt\Ui\Components;

use Velt\Ui\Contracts\ComponentInterface;

/**
 * Class Component
 *
 * Classe abstraite de base pour tous les composants Velt.
 *
 * Cette classe gère :
 * - Le type de composant
 * - Les props (attributs et propriétés)
 * - Les enfants (children)
 * - Le contenu textuel
 * - La sérialisation en tableau
 *
 * Exemple :
 *
 * $button = Button::make('Cliquer')
 *     ->type('submit')
 *     ->class('btn-primary');
 *
 * $card = Card::make()
 *     ->class('p-8')
 *     ->add(Text::make('Bonjour'));
 */
abstract class Component implements ComponentInterface
{
    /**
     * Type du composant.
     *
     * Exemple : 'text', 'button', 'card', 'form', 'input', 'link', 'alert'
     */
    protected string $type;

    /**
     * Propriétés du composant (props).
     *
     * Exemples :
     * - class: 'btn btn-primary'
     * - type: 'submit'
     * - variant: 'primary'
     * - href: '/dashboard'
     * - required: true
     */
    /** @var array<string, mixed> */
    protected array $props = [];

    /**
     * Composants enfants.
     */
    /** @var list<ComponentInterface> */
    protected array $children = [];

    /**
     * Contenu textuel du composant.
     */
    protected ?string $content = null;

    /**
     * Constructeur privé.
     *
     * Force l'utilisation de la factory method.
     */
    final protected function __construct()
    {
    }

    /**
     * Définit une prop sur le composant.
     *
     * Exemple :
     *
     * ->prop('class', 'btn-primary')
     *
     * @param string $key Clé de la prop
     * @param mixed $value Valeur de la prop
     */
    protected function prop(string $key, mixed $value): static
    {
        $this->props[$key] = $value;

        return $this;
    }

    /**
     * Définit la classe CSS du composant.
     *
     * Exemple :
     *
     * ->class('btn btn-primary')
     */
    public function class(string $class): static
    {
        return $this->prop('class', $class);
    }

    public function id(string $id): static
    {
        return $this->prop('id', $id);
    }

    /**
     * Conserve une condition logique pour les renderers qui la comprennent.
     *
     * Le renderer preview la serialise sans l'evaluer dans le Module 1.
     */
    public function showIf(mixed $condition): static
    {
        return $this->prop('showIf', $condition);
    }

    /**
     * Ajoute un enfant au composant.
     *
     * Exemple :
     *
     * ->add(Text::make('Texte'))
     */
    public function add(ComponentInterface $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Définit les enfants du composant.
     *
     * Exemple :
     *
     * ->children([
     *     Text::make('Texte'),
     *     Button::make('Cliquer'),
     * ])
     */
    /** @param list<ComponentInterface> $children */
    public function children(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Retourne les enfants du composant.
     */
    /** @return list<ComponentInterface> */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Retourne les props du composant.
     */
    /** @return array<string, mixed> */
    public function getProps(): array
    {
        return $this->props;
    }

    /**
     * Retourne le type du composant.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Retourne le contenu textuel du composant.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Convertit le composant en tableau.
     *
     * Cette méthode est appelée par les renderers (WebRenderer, JsonRenderer).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->type,
            'props' => $this->props,
        ];

        if ($this->content !== null) {
            $array['content'] = $this->content;
        }

        if (! empty($this->children)) {
            $array['children'] = array_map(
                static fn (ComponentInterface $child): array => $child->toArray(),
                $this->children
            );
        }

        return $array;
    }
}
