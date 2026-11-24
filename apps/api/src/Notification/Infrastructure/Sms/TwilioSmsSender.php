<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Sms;

use App\Notification\Domain\SmsDeliveryFailed;
use App\Notification\Domain\SmsSender;
use App\Shared\Domain\PhoneNumber;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Twilio Programmable Messaging over its REST API. No SDK: one form POST is all it takes. */
final readonly class TwilioSmsSender implements SmsSender
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'TWILIO_SID')]
        private string $accountSid,
        #[Autowire(env: 'TWILIO_TOKEN')]
        private string $authToken,
        #[Autowire(env: 'TWILIO_FROM')]
        private string $from,
    ) {
    }

    public function send(PhoneNumber $to, string $body): string
    {
        if ('' === $this->accountSid || '' === $this->authToken || '' === $this->from) {
            throw new SmsDeliveryFailed('Twilio is not configured: set TWILIO_SID, TWILIO_TOKEN and TWILIO_FROM.');
        }

        try {
            $response = $this->httpClient->request('POST', \sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($this->accountSid)), [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body' => ['To' => $to->value, 'From' => $this->from, 'Body' => $body],
            ]);
            $status = $response->getStatusCode();
            /** @var array{sid?: string, message?: string} $payload */
            $payload = $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            throw new SmsDeliveryFailed('Twilio request failed: '.$exception->getMessage(), previous: $exception);
        }

        if ($status >= 300 || !isset($payload['sid'])) {
            throw new SmsDeliveryFailed(\sprintf('Twilio answered %d: %s', $status, $payload['message'] ?? 'no message'));
        }

        return $payload['sid'];
    }

    public function name(): string
    {
        return 'twilio';
    }
}
