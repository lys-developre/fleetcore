<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\AccountLink;
use App\Identity\Domain\AccountLinkRepository;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Domain\User;
use App\Notification\Application\SmsNotifier;
use App\Notification\Domain\SmsType;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Creates a one-time link and texts it to the user. Earlier unused links of the same type stop working.
 * Call inside a transaction (SmsNotifier queues on the same connection).
 */
final readonly class AccountLinkSender
{
    public function __construct(
        private AccountLinkRepository $links,
        private SmsNotifier $sms,
        private ClockInterface $clock,
        #[Autowire(env: 'PUBLIC_BASE_URL')]
        private string $publicBaseUrl,
        #[Autowire(env: 'COMPANY_NAME')]
        private string $companyName,
    ) {
    }

    /** @return string the link, for the console command that bootstraps the first administrator */
    public function send(User $user, AccountLinkType $type): string
    {
        $now = $this->clock->now();
        $this->links->invalidateFor($user, $type, $now);

        ['link' => $link, 'plain' => $plain] = AccountLink::issue($user, $type, $now);
        $this->links->add($link);

        $baseUrl = rtrim($this->publicBaseUrl, '/');
        $firstName = explode(' ', $user->getFullName())[0];
        $url = \sprintf('%s/%s/%s', $baseUrl, AccountLinkType::Activation === $type ? 'activate' : 'reset-password', $plain);

        // Texts as in formularios-05, sección 6: short, plain, one link
        [$smsType, $body] = match ($type) {
            AccountLinkType::Activation => [
                SmsType::Activation,
                \sprintf('Hola %s, tu acceso a FleetCore de %s: %s', $firstName, $this->companyName, $url),
            ],
            AccountLinkType::PasswordReset => [
                SmsType::PasswordReset,
                \sprintf('FleetCore: para elegir una contraseña nueva entra en %s (caduca en 30 minutos). Si no lo has pedido, ignora este mensaje.', $url),
            ],
        };

        $this->sms->queue($user->getMobilePhone(), $smsType, $body, $user);

        return $url;
    }
}
