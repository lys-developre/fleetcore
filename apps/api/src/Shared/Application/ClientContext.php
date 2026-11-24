<?php

declare(strict_types=1);

namespace App\Shared\Application;

use Symfony\Component\HttpFoundation\Request;

/** Where a request comes from: stored on sessions and audit entries. */
final readonly class ClientContext
{
    public function __construct(
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $userAgent = $request->headers->get('User-Agent');

        return new self($request->getClientIp(), null === $userAgent ? null : mb_substr($userAgent, 0, 255));
    }
}
