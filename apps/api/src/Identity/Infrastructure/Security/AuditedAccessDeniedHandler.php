<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Shared\Application\AuditTrail;
use App\Shared\Domain\Actor;
use App\Shared\Domain\AuditResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * 403 for authenticated users, and an audit entry for every denial (formularios-05, sección 9): it is how
 * attempts to reach someone else's reports show up.
 */
final readonly class AuditedAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private AuditTrail $audit, private EntityManagerInterface $entityManager)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $subject = $accessDeniedException->getSubject();

        $this->audit->record(
            'security.access_denied',
            entityType: \is_object($subject) ? new \ReflectionClass($subject)->getShortName() : null,
            entityId: $subject instanceof Actor ? $subject->getId() : null,
            after: [
                'attributes' => array_map(static fn (mixed $attribute): string => \is_scalar($attribute) ? (string) $attribute : get_debug_type($attribute), $accessDeniedException->getAttributes()),
                'route' => $request->attributes->get('_route'),
                'method' => $request->getMethod(),
            ],
            result: AuditResult::Denied,
        );
        $this->entityManager->flush();

        return new JsonResponse(['error' => ['code' => 'forbidden']], 403);
    }
}
