<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ErrorKind;

/** Activation or recovery link that does not exist, expired or was already used. */
final class InvalidAccountLink extends DomainException
{
    public function __construct()
    {
        parent::__construct('link_invalid', 'This link is not valid any more.');
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::Gone;
    }
}
