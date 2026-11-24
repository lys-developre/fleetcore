<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\ChangeUserStatus;
use App\Identity\Application\CreateUser;
use App\Identity\Application\DeleteUser;
use App\Identity\Application\Input\CreateUserInput;
use App\Identity\Application\Input\ReasonInput;
use App\Identity\Application\Input\UpdateUserInput;
use App\Identity\Application\ManageSessions;
use App\Identity\Application\Output\UserView;
use App\Identity\Application\ResendActivation;
use App\Identity\Application\UpdateUser;
use App\Identity\Domain\User;
use App\Identity\Domain\UserRepository;
use App\Identity\Presentation\Security\UserVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/** User management for office and administration (formularios-05, matriz 5.4). Every action asks UserVoter. */
#[Route('/api/users', name: 'api_users_')]
final class UserController extends AbstractController
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(UserRepository $users): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::LIST);
        $now = $this->clock->now();

        return $this->json(array_map(static fn (User $user): UserView => UserView::from($user, $now), $users->all()));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateUserInput $input, CreateUser $createUser): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::ASSIGN_ROLES, $input->roleList());

        return $this->json($createUser($input), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json(UserView::from($user, $this->clock->now()));
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    public function update(#[MapEntity(id: 'id')] User $user, #[MapRequestPayload] UpdateUserInput $input, #[CurrentUser] User $actor, UpdateUser $updateUser): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);
        $this->denyAccessUnlessGranted(UserVoter::ASSIGN_ROLES, $input->roleList());

        return $this->json($updateUser($user, $input, $actor));
    }

    #[Route('/{id}/resend-activation', name: 'resend_activation', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function resendActivation(#[MapEntity(id: 'id')] User $user, ResendActivation $resend): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::RESEND_ACTIVATION, $user);
        $resend($user);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/block', name: 'block', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function block(#[MapEntity(id: 'id')] User $user, #[CurrentUser] User $actor, ChangeUserStatus $status, #[MapRequestPayload] ?ReasonInput $input = null): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::BLOCK, $user);

        return $this->json($status->block($user, $input?->reason, $actor));
    }

    #[Route('/{id}/unblock', name: 'unblock', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function unblock(#[MapEntity(id: 'id')] User $user, ChangeUserStatus $status): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::BLOCK, $user);

        return $this->json($status->unblock($user));
    }

    #[Route('/{id}/unlock', name: 'unlock', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function unlock(#[MapEntity(id: 'id')] User $user, ChangeUserStatus $status): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::BLOCK, $user);

        return $this->json($status->unlock($user));
    }

    #[Route('/{id}/deactivate', name: 'deactivate', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function deactivate(#[MapEntity(id: 'id')] User $user, #[CurrentUser] User $actor, ChangeUserStatus $status, #[MapRequestPayload] ?ReasonInput $input = null): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::DEACTIVATE, $user);

        return $this->json($status->deactivate($user, $input->reason ?? '', $actor));
    }

    #[Route('/{id}/readmit', name: 'readmit', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function readmit(#[MapEntity(id: 'id')] User $user, ChangeUserStatus $status): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::DEACTIVATE, $user);

        return $this->json($status->readmit($user));
    }

    #[Route('/{id}/sessions', name: 'sessions_revoke', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    public function revokeSessions(#[MapEntity(id: 'id')] User $user, ManageSessions $sessions): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::REVOKE_SESSIONS, $user);

        return $this->json(['revoked' => $sessions->revokeAll($user)]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    public function delete(#[MapEntity(id: 'id')] User $user, #[CurrentUser] User $actor, DeleteUser $deleteUser): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);
        $deleteUser($user, $actor);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
