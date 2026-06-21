<?php

declare(strict_types=1);

namespace Velt\Ui\Contracts;

/**
 * Contrat d'une vue Velt chargeable.
 *
 * Dans le Module 1, l'implementation concrete est Page. Le contrat separe
 * evite au kernel et a Preview de dependre des details internes de Page.
 */
interface ViewInterface
{
    public function title(): string;

    public function getLayout(): ?string;

    /** @return array<string, mixed> */
    public function getMeta(): array;

    /** @return list<ComponentInterface> */
    public function children(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
