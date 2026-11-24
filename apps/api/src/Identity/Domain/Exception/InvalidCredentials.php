<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ErrorKind;

/** Unknown phone, wrong password or no password yet: one answer for all, so phones cannot be enumerated. */
final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('invalid_credentials', 'Phone or password is not correct.');
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::Unauthenticated;
    }
}
