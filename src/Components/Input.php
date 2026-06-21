<?php

declare(strict_types=1);

namespace Velt\Ui\Components;

/**
 * Class Input
 *
 * Composant de champ de saisie (input).
 *
 * Exemple :
 *
 * Input::make('email', 'Adresse email')
 *     ->type('email')
 *     ->required()
 *     ->placeholder('Entrez votre email')
 *
 * Devient en HTML : <label>Adresse email</label><input type="email" name="email" required placeholder="Entrez votre email" />
 */
class Input extends Component
{
    protected string $type = 'input';

    protected string $name;

    protected string $label;

    /**
     * Crée une nouvelle instance de Input.
     *
     * @param string $name Attribut name du champ
     * @param string $label Libellé du champ
     */
    public static function make(string $name, string $label): self
    {
        $instance = new self();
        $instance->name = $name;
        $instance->label = $label;

        return $instance;
    }

    /**
     * Définit le type d'input.
     *
     * Exemple : 'text', 'email', 'password', 'number', 'date'
     *
     * @param string $inputType Type d'input
     */
    public function type(string $inputType): self
    {
        return $this->prop('inputType', $inputType);
    }

    /**
     * Marque le champ comme requis.
     */
    public function required(): self
    {
        return $this->prop('required', true);
    }

    /**
     * Définit le placeholder du champ.
     *
     * @param string $placeholder Texte placeholder
     */
    public function placeholder(string $placeholder): self
    {
        return $this->prop('placeholder', $placeholder);
    }

    /**
     * Définit la valeur initiale du champ.
     *
     * @param mixed $value Valeur initiale
     */
    public function value(mixed $value): self
    {
        return $this->prop('value', $value);
    }

    /**
     * Retourne le tableau du composant avec name et label.
     */
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['name'] = $this->name;
        $array['label'] = $this->label;

        return $array;
    }
}
