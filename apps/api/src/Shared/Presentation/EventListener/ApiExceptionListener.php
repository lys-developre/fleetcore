<?php

declare(strict_types=1);

namespace App\Shared\Presentation\EventListener;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ErrorKind;
use App\Shared\Domain\Exception\TooManyAttempts;
use App\Shared\Domain\Exception\ValidationFailed;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * One error shape for the whole API:
 *   { "error": { "code": "snake_case", ...context } }
 *   { "error": { "code": "validation_failed", "violations": [ { "field": "phone", "code": "phone_taken" } ] } }
 * Request DTOs use snake_case codes as constraint messages, so Validator and use-case errors look the same.
 * Unexpected exceptions are left to Symfony (logged, generic 500).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ApiExceptionListener
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $response = match (true) {
            $exception instanceof DomainException => $this->fromDomain($exception),
            $exception instanceof HttpExceptionInterface => $this->fromHttp($exception),
            default => null,
        };

        if (null !== $response) {
            $event->setResponse($response);
        }
    }

    private function fromDomain(DomainException $exception): JsonResponse
    {
        $status = match ($exception->kind()) {
            ErrorKind::Invalid => 422,
            ErrorKind::NotFound => 404,
            ErrorKind::Conflict => 409,
            ErrorKind::Unauthenticated => 401,
            ErrorKind::Forbidden => 403,
            ErrorKind::Gone => 410,
            ErrorKind::RateLimited => 429,
        };

        $body = ['code' => $exception->errorCode, ...$exception->context];
        if ($exception instanceof ValidationFailed) {
            $body['violations'] = $exception->violations;
        }

        $headers = [];
        if ($exception instanceof TooManyAttempts) {
            $headers['Retry-After'] = (string) max(1, $exception->retryAfter->getTimestamp() - $this->clock->now()->getTimestamp());
        }

        return new JsonResponse(['error' => $body], $status, $headers);
    }

    private function fromHttp(HttpExceptionInterface $exception): JsonResponse
    {
        $status = $exception->getStatusCode();
        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $violations = [];
            foreach ($previous->getViolations() as $violation) {
                $violations[] = ['field' => $violation->getPropertyPath(), 'code' => (string) $violation->getMessage()];
            }

            return new JsonResponse(['error' => ['code' => 'validation_failed', 'violations' => $violations]], 422);
        }

        $code = match ($status) {
            400 => 'invalid_payload',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            415 => 'unsupported_media_type',
            422 => 'invalid_payload',
            429 => 'too_many_attempts',
            default => 'http_error',
        };

        return new JsonResponse(['error' => ['code' => $code]], $status, $exception->getHeaders());
    }
}
