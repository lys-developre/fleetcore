<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * 401 in the API's error shape. "unauthenticated": no token sent. "session_expired": a token was sent but is
 * revoked, expired or belongs to an inactive user; the web app clears it and goes to the login screen.
 */
final class JsonAuthenticationResponder implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return self::respond('unauthenticated');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return self::respond('session_expired');
    }

    private static function respond(string $code): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => $code]], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
