<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\AccessToken;
use Symfony\Contracts\Service\ResetInterface;

/** The device session behind the current request, set by AccessTokenHandler. Needed for logout and "this device". */
final class CurrentSession implements ResetInterface
{
    private ?AccessToken $token = null;

    public function set(AccessToken $token): void
    {
        $this->token = $token;
    }

    public function get(): ?AccessToken
    {
        return $this->token;
    }

    public function reset(): void
    {
        $this->token = null;
    }
}
