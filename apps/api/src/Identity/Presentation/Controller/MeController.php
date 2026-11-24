<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Input\UpdateOwnProfileInput;
use App\Identity\Application\ManageSessions;
use App\Identity\Application\Output\UserView;
use App\Identity\Application\UpdateOwnProfile;
use App\Identity\Domain\User;
use App\Identity\Infrastructure\Security\CurrentSession;
use App\Identity\Presentation\Security\UserVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

/** The signed-in user: who am I, my data, my devices. */
#[Route('/api/me', name: 'api_me_')]
final class MeController extends AbstractController
{
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(UserView::from($user));
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    public function update(#[CurrentUser] User $user, #[MapRequestPayload] UpdateOwnProfileInput $input, UpdateOwnProfile $update): JsonResponse
    {
        return $this->json($update($user, $input));
    }

    #[Route('/sessions', name: 'sessions', methods: ['GET'])]
    public function sessions(#[CurrentUser] User $user, CurrentSession $currentSession, ManageSessions $sessions): JsonResponse
    {
        return $this->json($sessions->list($user, $currentSession->get()));
    }

    #[Route('/sessions/{id}', name: 'session_revoke', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    public function revokeSession(#[CurrentUser] User $user, string $id, ManageSessions $sessions): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::REVOKE_SESSIONS, $user);
        $sessions->revokeOwn($user, Uuid::fromString($id));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
