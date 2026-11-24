<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Factory\UserFactory;
use App\Identity\Domain\User;
use App\Identity\Domain\UserStatus;
use App\Notification\Application\Message\SendSms;
use App\Notification\Domain\SmsType;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/** Activation by SMS link (formularios-05, sección 6.2) and recovery (sección 6.5). */
final class AccountLinksTest extends ApiTestCase
{
    public function testOfficeRegistersAnOperatorWhoActivatesFromTheSmsLink(): void
    {
        $office = UserFactory::new()->office()->create();

        $created = $this->request('POST', '/api/users', [
            'fullName' => 'Juan Pérez Gil',
            'phone' => '611 22 33 44',
            'operatorNumber' => 'OP-07',
            'roles' => ['ROLE_OPERARIO'],
        ], $this->tokenFor($office));

        self::assertResponseStatusCodeSame(201);
        self::assertIsArray($created);
        self::assertSame('+34611223344', $created['phone']);
        self::assertSame('pending_activation', $created['status']);

        /** @var InMemoryTransport $queue */
        $queue = self::getContainer()->get('messenger.transport.async');
        self::assertCount(1, $queue->getSent());
        self::assertInstanceOf(SendSms::class, $queue->getSent()[0]->getMessage());

        $sms = $this->smsTo('+34611223344');
        self::assertCount(1, $sms);
        self::assertSame(SmsType::Activation, $sms[0]->getType());
        self::assertStringStartsWith('Hola Juan, tu acceso a FleetCore de Excavaciones Demo: http://localhost:8080/activate/', $sms[0]->getBody());
        $token = $this->linkTokenIn($sms[0]);

        $shown = $this->request('GET', '/api/auth/activation/'.$token);
        self::assertResponseIsSuccessful();
        self::assertIsArray($shown);
        self::assertSame('Juan Pérez Gil', $shown['fullName']);

        $session = $this->request('POST', '/api/auth/activation/'.$token, ['password' => 'la retro sube al camion', 'profile' => 'mobile']);
        self::assertResponseIsSuccessful();
        self::assertIsArray($session);
        self::assertSame('mobile', $session['profile']);
        self::assertSame('active', $session['user']['status']);

        $this->request('GET', '/api/me', token: $session['token']);
        self::assertResponseIsSuccessful('signed in straight away');

        $this->request('POST', '/api/auth/login', ['phone' => '611223344', 'password' => 'la retro sube al camion']);
        self::assertResponseIsSuccessful();
    }

    public function testTheActivationLinkWorksOnce(): void
    {
        $token = $this->registerOperator('+34611223344');

        $this->request('POST', '/api/auth/activation/'.$token, ['password' => 'la retro sube al camion']);
        self::assertResponseIsSuccessful();

        self::assertErrorCode('link_invalid', $this->request('GET', '/api/auth/activation/'.$token));
        self::assertResponseStatusCodeSame(410);
        self::assertErrorCode('link_invalid', $this->request('POST', '/api/auth/activation/'.$token, ['password' => 'otra contraseña larga']));
        self::assertResponseStatusCodeSame(410);
    }

    public function testTheActivationLinkExpiresAfter72Hours(): void
    {
        $token = $this->registerOperator('+34611223344');

        self::mockTime('+72 hours');

        self::assertErrorCode('link_invalid', $this->request('GET', '/api/auth/activation/'.$token));
    }

    public function testResendingInvalidatesThePreviousLink(): void
    {
        $office = UserFactory::new()->office()->create();
        $firstToken = $this->registerOperator('+34611223344', $office);
        $operator = $this->entityManager()->getRepository(User::class)->findOneBy(['mobilePhone' => '+34611223344']);
        self::assertNotNull($operator);

        $this->request('POST', '/api/users/'.$operator->getId().'/resend-activation', token: $this->tokenFor($office));
        self::assertResponseStatusCodeSame(204);

        $sms = $this->smsTo('+34611223344');
        self::assertCount(2, $sms);
        self::assertErrorCode('link_invalid', $this->request('GET', '/api/auth/activation/'.$firstToken));
        $this->request('GET', '/api/auth/activation/'.$this->linkTokenIn($sms[1]));
        self::assertResponseIsSuccessful();
    }

    public function testResendingIsOnlyForUsersPendingActivation(): void
    {
        $office = UserFactory::new()->office()->create();
        $operator = UserFactory::new()->operator()->create();

        self::assertErrorCode('user_not_pending_activation', $this->request('POST', '/api/users/'.$operator->getId().'/resend-activation', token: $this->tokenFor($office)));
        self::assertResponseStatusCodeSame(409);
    }

    public function testThePasswordPolicyAppliesOnActivation(): void
    {
        $token = $this->registerOperator('+34611223344');

        self::assertViolation('password', 'password_too_short', $this->request('POST', '/api/auth/activation/'.$token, ['password' => 'corta']));
        self::assertResponseStatusCodeSame(422);
        self::assertViolation('password', 'password_contains_personal_data', $this->request('POST', '/api/auth/activation/'.$token, ['password' => 'juan-de-la-obra-2026']));
        self::assertViolation('password', 'password_contains_personal_data', $this->request('POST', '/api/auth/activation/'.$token, ['password' => 'movil 611223344']));

        $this->request('GET', '/api/auth/activation/'.$token);
        self::assertResponseIsSuccessful('a rejected password does not consume the link');
    }

    public function testMalformedAndUnknownLinks(): void
    {
        $this->request('GET', '/api/auth/activation/short');
        self::assertResponseStatusCodeSame(404);

        self::assertErrorCode('link_invalid', $this->request('GET', '/api/auth/activation/'.str_repeat('x', 43)));
        self::assertResponseStatusCodeSame(410);
    }

    public function testRecoveryAnswersTheSameWhetherThePhoneExistsOrNot(): void
    {
        UserFactory::new()->operator()->create(['phone' => '+34611223344']);

        $known = $this->request('POST', '/api/auth/password-reset', ['phone' => '611 223 344']);
        self::assertResponseStatusCodeSame(202);
        $unknown = $this->request('POST', '/api/auth/password-reset', ['phone' => '699 999 999']);
        self::assertResponseStatusCodeSame(202);
        $invalid = $this->request('POST', '/api/auth/password-reset', ['phone' => 'not a phone']);
        self::assertResponseStatusCodeSame(202);

        self::assertSame($known, $unknown);
        self::assertSame($known, $invalid);
        self::assertCount(1, $this->smsTo('+34611223344'));
        self::assertCount(0, $this->smsTo('+34699999999'));
    }

    public function testNoRecoverySmsForUsersWhoCannotSignIn(): void
    {
        UserFactory::new()->pending()->create(['phone' => '+34611000001']);
        UserFactory::new()->create(['phone' => '+34611000002', 'status' => UserStatus::Blocked]);

        $this->request('POST', '/api/auth/password-reset', ['phone' => '611000001']);
        $this->request('POST', '/api/auth/password-reset', ['phone' => '611000002']);

        self::assertCount(0, $this->smsTo('+34611000001'));
        self::assertCount(0, $this->smsTo('+34611000002'));
    }

    public function testResettingThePasswordClosesOtherSessionsAndSignsThisDeviceIn(): void
    {
        $user = UserFactory::new()->operator()->create(['fullName' => 'Juan Pérez', 'phone' => '+34611223344']);
        $oldSession = $this->tokenFor($user);

        $this->request('POST', '/api/auth/password-reset', ['phone' => '611223344']);
        $sms = $this->smsTo('+34611223344')[0];
        self::assertSame(SmsType::PasswordReset, $sms->getType());
        self::assertStringContainsString('caduca en 30 minutos', $sms->getBody());
        $token = $this->linkTokenIn($sms);

        $session = $this->request('POST', '/api/auth/password-reset/'.$token, ['password' => 'nueva clave de la obra', 'profile' => 'mobile']);
        self::assertResponseIsSuccessful();
        self::assertIsArray($session);

        self::assertErrorCode('session_expired', $this->request('GET', '/api/me', token: $oldSession));
        $this->request('GET', '/api/me', token: $session['token']);
        self::assertResponseIsSuccessful();

        $this->request('POST', '/api/auth/login', ['phone' => '611223344', 'password' => UserFactory::DEFAULT_PASSWORD]);
        self::assertResponseStatusCodeSame(401);
        $this->request('POST', '/api/auth/login', ['phone' => '611223344', 'password' => 'nueva clave de la obra']);
        self::assertResponseIsSuccessful();
    }

    public function testRecoveryLinksExpireAfter30Minutes(): void
    {
        UserFactory::new()->operator()->create(['phone' => '+34611223344']);
        $this->request('POST', '/api/auth/password-reset', ['phone' => '611223344']);
        $token = $this->linkTokenIn($this->smsTo('+34611223344')[0]);

        self::mockTime('+30 minutes');

        self::assertErrorCode('link_invalid', $this->request('POST', '/api/auth/password-reset/'.$token, ['password' => 'nueva clave de la obra']));
    }

    public function testAnActivationLinkIsNotARecoveryLink(): void
    {
        $token = $this->registerOperator('+34611223344');

        self::assertErrorCode('link_invalid', $this->request('GET', '/api/auth/password-reset/'.$token));
    }

    /** @return string the activation token from the SMS */
    private function registerOperator(string $phone, ?User $office = null): string
    {
        $office ??= UserFactory::new()->office()->create();
        $this->request('POST', '/api/users', ['fullName' => 'Juan Pérez', 'phone' => $phone, 'roles' => ['ROLE_OPERARIO']], $this->tokenFor($office));
        self::assertResponseStatusCodeSame(201);

        $sms = $this->smsTo($phone);

        return $this->linkTokenIn($sms[\count($sms) - 1]);
    }
}
