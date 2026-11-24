<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Identity\Domain\UserStatus;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ErrorKind;

/**
 * The password was right but the account cannot sign in. Only said after a correct password,
 * so it reveals nothing to someone guessing phones.
 */
final class AccountDisabled extends DomainException
{
    public function __construct(UserStatus $status)
    {
        parent::__construct(
            UserStatus::Deactivated === $status ? 'account_deactivated' : 'account_blocked',
            'This account cannot sign in.',
        );
    }

    public function kind(): ErrorKind
    {
        return ErrorKind::Forbidden;
    }
}
