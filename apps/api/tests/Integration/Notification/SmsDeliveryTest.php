<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification;

use App\Factory\UserFactory;
use App\Notification\Application\Message\SendSms;
use App\Notification\Application\SendSmsHandler;
use App\Notification\Application\SmsNotifier;
use App\Notification\Domain\SmsDeliveryFailed;
use App\Notification\Domain\SmsMessage;
use App\Notification\Domain\SmsStatus;
use App\Notification\Domain\SmsType;
use App\Notification\Infrastructure\Sms\TwilioSmsSender;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Zenstruck\Foundry\Test\Factories;

/** Delivery of stored SMS by the worker, in sandbox, and the Twilio adapter against a mocked API. */
final class SmsDeliveryTest extends KernelTestCase
{
    use Factories;

    public function testTheWorkerMarksSandboxMessagesAsSent(): void
    {
        self::bootKernel();
        $user = UserFactory::new()->create();
        $message = self::getContainer()->get(EntityManagerInterface::class)->wrapInTransaction(
            static fn (): SmsMessage => self::getContainer()->get(SmsNotifier::class)->queue($user->getMobilePhone(), SmsType::Activation, 'Hola', $user),
        );

        self::getContainer()->get(SendSmsHandler::class)(new SendSms($message->getId()));

        $stored = self::getContainer()->get(EntityManagerInterface::class)->find(SmsMessage::class, $message->getId());
        self::assertNotNull($stored);
        self::assertSame(SmsStatus::Sent, $stored->getStatus());
        self::assertSame('sandbox', $stored->getDriver());
    }

    public function testQueueingOutsideATransactionIsRefused(): void
    {
        self::bootKernel();
        $user = UserFactory::new()->create();

        $this->expectException(\LogicException::class);

        self::getContainer()->get(SmsNotifier::class)->queue($user->getMobilePhone(), SmsType::Activation, 'Hola', $user);
    }

    public function testTwilioReceivesTheMessageAsAFormPost(): void
    {
        $http = new MockHttpClient(static function (string $method, string $url, array $options): JsonMockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json', $url);
            self::assertStringContainsString('To=%2B34611223344', $options['body']);
            self::assertStringContainsString('Body=Hola+Juan', $options['body']);

            return new JsonMockResponse(['sid' => 'SM42'], ['http_code' => 201]);
        });

        $sid = new TwilioSmsSender($http, 'AC123', 'secret', '+34900000000')->send(PhoneNumber::fromString('611223344'), 'Hola Juan');

        self::assertSame('SM42', $sid);
    }

    public function testTwilioErrorsBecomeDeliveryFailures(): void
    {
        $http = new MockHttpClient(new JsonMockResponse(['message' => 'The To number is not valid'], ['http_code' => 400]));

        $this->expectException(SmsDeliveryFailed::class);
        $this->expectExceptionMessage('The To number is not valid');

        new TwilioSmsSender($http, 'AC123', 'secret', '+34900000000')->send(PhoneNumber::fromString('611223344'), 'Hola');
    }

    public function testTwilioWithoutCredentialsFailsLoudly(): void
    {
        $this->expectException(SmsDeliveryFailed::class);

        new TwilioSmsSender(new MockHttpClient(), '', '', '')->send(PhoneNumber::fromString('611223344'), 'Hola');
    }
}
