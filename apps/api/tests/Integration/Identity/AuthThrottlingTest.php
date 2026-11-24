<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use App\Factory\UserFactory;
use App\Identity\Application\Input\LoginInput;
use App\Identity\Application\Input\RequestPasswordResetInput;
use App\Identity\Application\LogIn;
use App\Identity\Application\RequestPasswordReset;
use App\Identity\Domain\Exception\InvalidCredentials;
use App\Notification\Domain\SmsMessage;
use App\Shared\Application\ClientContext;
use App\Shared\Domain\Exception\TooManyAttempts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Foundry\Test\Factories;

/**
 * Rate limits on the public auth endpoints (formularios-05, secciones 6.4 y 6.5). Called on the use cases
 * within one kernel: HTTP tests reset the in-memory limiter storage between requests.
 */
final class AuthThrottlingTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use Factories;

    protected function setUp(): void
    {
        self::bootKernel();
        self::mockTime('2026-11-24 09:00:00');
    }

    public function testUnknownPhonesAreThrottledAfterFiveAttemptsLikeAnAccount(): void
    {
        $logIn = self::getContainer()->get(LogIn::class);
        $client = new ClientContext('203.0.113.7');

        for ($i = 0; $i < 5; ++$i) {
            try {
                $logIn(new LoginInput('699999999', 'whatever-password'), $client);
                self::fail('Expected InvalidCredentials');
            } catch (InvalidCredentials) {
            }
        }

        $this->expectException(TooManyAttempts::class);
        $logIn(new LoginInput('699999999', 'whatever-password'), $client);
    }

    public function testAKnownAccountLocksAfterFiveFailuresAndRefusesEvenTheRightPassword(): void
    {
        $user = UserFactory::new()->operator()->create(['phone' => '+34611111111']);
        $logIn = self::getContainer()->get(LogIn::class);
        $client = new ClientContext('203.0.113.8');

        for ($i = 0; $i < 4; ++$i) {
            try {
                $logIn(new LoginInput('611111111', 'wrong-password'), $client);
            } catch (InvalidCredentials) {
            }
        }
        try {
            $logIn(new LoginInput('611111111', 'wrong-password'), $client);
            self::fail('The fifth failure must lock');
        } catch (TooManyAttempts $exception) {
            self::assertEquals(new \DateTimeImmutable('2026-11-24 09:15:00'), $exception->retryAfter);
        }

        try {
            $logIn(new LoginInput('611111111', UserFactory::DEFAULT_PASSWORD), $client);
            self::fail('Locked accounts refuse the right password too');
        } catch (TooManyAttempts) {
        }

        self::mockTime('+15 minutes');
        $session = $logIn(new LoginInput('611111111', UserFactory::DEFAULT_PASSWORD), $client);
        self::assertSame($user->getId()->toRfc4122(), $session->user->id);
    }

    public function testOneAddressIsThrottledAcrossPhones(): void
    {
        $logIn = self::getContainer()->get(LogIn::class);
        $client = new ClientContext('203.0.113.9');

        $this->expectException(TooManyAttempts::class);
        for ($i = 0; $i < 31; ++$i) {
            try {
                $logIn(new LoginInput(\sprintf('6990000%02d', $i), 'whatever-password'), $client);
            } catch (InvalidCredentials) {
            }
        }
    }

    public function testRecoverySmsAreCappedAtThreePerHourPerPhone(): void
    {
        UserFactory::new()->operator()->create(['phone' => '+34622222222']);
        $request = self::getContainer()->get(RequestPasswordReset::class);
        $client = new ClientContext('203.0.113.10');

        for ($i = 0; $i < 5; ++$i) {
            $request(new RequestPasswordResetInput('622 222 222'), $client);
        }

        self::assertCount(3, self::getContainer()->get(EntityManagerInterface::class)->getRepository(SmsMessage::class)->findBy(['phone' => '+34622222222']));
    }
}
