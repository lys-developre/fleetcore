<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Input\SetPasswordInput;
use App\Identity\Application\Output\IssuedSession;
use App\Identity\Domain\AccountLinkType;
use App\Shared\Application\AuditTrail;
use App\Shared\Application\ClientContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** The user opens the SMS link, chooses a password and is signed in straight away (sección 6.2). */
final readonly class ActivateAccount
{
    public function __construct(
        private AccountLinkResolver $resolver,
        private PasswordPolicy $policy,
        private UserPasswordHasherInterface $passwordHasher,
        private SessionIssuer $sessions,
        private AuditTrail $audit,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $plainToken, SetPasswordInput $input, ClientContext $client): IssuedSession
    {
        $link = $this->resolver->resolve($plainToken, AccountLinkType::Activation, $client);
        $user = $link->getUser();
        $this->policy->check($input->password, $user);

        $user->activate($this->passwordHasher->hashPassword($user, $input->password));
        $link->markUsed($this->clock->now());
        $session = $this->sessions->issue($user, $input->sessionProfile(), $client);
        $this->audit->record('user.activated', 'user', $user->getId(), actor: $user);
        $this->entityManager->flush();

        return $session;
    }
}
