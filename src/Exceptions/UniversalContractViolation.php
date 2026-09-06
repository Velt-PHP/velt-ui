<?php

declare(strict_types=1);

namespace Velt\Ui\Exceptions;

use RuntimeException;

final class UniversalContractViolation extends RuntimeException
{
    /** @param list<string> $violations */
    public function __construct(private readonly array $violations)
    {
        parent::__construct("Contrat UI universel invalide:\n- " . implode("\n- ", $violations));
    }

    /** @return list<string> */
    public function violations(): array
    {
        return $this->violations;
    }
}