<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Output\AccountLinkView;
use App\Identity\Domain\AccountLinkType;
use App\Shared\Application\ClientContext;

/** What the link page shows before asking for the password. */
final readonly class ShowAccountLink
{
    public function __construct(private AccountLinkResolver $resolver)
    {
    }

    public function __invoke(string $plainToken, AccountLinkType $type, ClientContext $client): AccountLinkView
    {
        $link = $this->resolver->resolve($plainToken, $type, $client);

        return new AccountLinkView($type->value, $link->getUser()->getFullName(), $link->getExpiresAt());
    }
}
