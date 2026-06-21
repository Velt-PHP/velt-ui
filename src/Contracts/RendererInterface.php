<?php

declare(strict_types=1);

namespace Velt\Ui\Contracts;

use Velt\Ui\Page;

/**
 * Contrat commun pour les sorties Velt UI.
 *
 * Un renderer recoit une Page declarative et retourne une sortie texte comme
 * du HTML navigateur ou du JSON Preview.
 */
interface RendererInterface
{
    /** @param array<string, mixed> $options */
    public function render(Page $page, array $options = []): string;
}
