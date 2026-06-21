<?php

declare(strict_types=1);

namespace Velt\Ui\Contracts;

/**
 * Contrat public de chaque composant declaratif Velt UI.
 *
 * Les renderers doivent pouvoir inspecter un composant via ce contrat sans
 * connaitre sa classe concrete.
 */
interface ComponentInterface
{
    /**
     * Type interne du composant, par exemple "text", "button" ou "form".
     */
    public function getType(): string;

    /**
     * Options declaratives attachees au composant.
     *
     * @return array<string, mixed>
     */
    public function getProps(): array;

    /**
     * Composants enfants declares sous ce composant.
     *
     * @return list<ComponentInterface>
     */
    public function getChildren(): array;

    /**
     * Contenu textuel optionnel.
     */
    public function getContent(): ?string;

    /**
     * Representation interne stable utilisee par les renderers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
