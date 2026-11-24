<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Sms;

use App\Notification\Domain\SmsSender;
use App\Shared\Domain\PhoneNumber;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** The SmsSender the application gets: sandbox or Twilio, chosen by SMS_DRIVER. */
#[AsAlias(SmsSender::class)]
final readonly class ConfiguredSmsSender implements SmsSender
{
    private SmsSender $sender;

    public function __construct(
        #[Autowire(env: 'SMS_DRIVER')]
        string $driver,
        SandboxSmsSender $sandbox,
        TwilioSmsSender $twilio,
    ) {
        $this->sender = match ($driver) {
            'sandbox' => $sandbox,
            'twilio' => $twilio,
            default => throw new \InvalidArgumentException(\sprintf('Unknown SMS_DRIVER "%s": use "sandbox" or "twilio".', $driver)),
        };
    }

    public function send(PhoneNumber $to, string $body): string
    {
        return $this->sender->send($to, $body);
    }

    public function name(): string
    {
        return $this->sender->name();
    }
}
