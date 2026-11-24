<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\ActivateAccount;
use App\Identity\Application\Input\LoginInput;
use App\Identity\Application\Input\RequestPasswordResetInput;
use App\Identity\Application\Input\SetPasswordInput;
use App\Identity\Application\LogIn;
use App\Identity\Application\LogOut;
use App\Identity\Application\RequestPasswordReset;
use App\Identity\Application\ResetPassword;
use App\Identity\Application\ShowAccountLink;
use App\Identity\Domain\AccountLinkType;
use App\Identity\Infrastructure\Security\CurrentSession;
use App\Shared\Application\ClientContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/** Sign-in, sign-out and the two SMS link flows. Public except logout (security.yaml access_control). */
#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{
    private const string LINK_TOKEN = '[A-Za-z0-9_-]{20,100}';

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginInput $input, Request $request, LogIn $logIn): JsonResponse
    {
        return $this->json($logIn($input, ClientContext::fromRequest($request)));
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(CurrentSession $currentSession, LogOut $logOut): Response
    {
        $token = $currentSession->get();
        if (null !== $token) {
            $logOut($token);
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/activation/{token}', name: 'activation_show', requirements: ['token' => self::LINK_TOKEN], methods: ['GET'])]
    public function showActivation(string $token, Request $request, ShowAccountLink $show): JsonResponse
    {
        return $this->json($show($token, AccountLinkType::Activation, ClientContext::fromRequest($request)));
    }

    #[Route('/activation/{token}', name: 'activation', requirements: ['token' => self::LINK_TOKEN], methods: ['POST'])]
    public function activate(string $token, #[MapRequestPayload] SetPasswordInput $input, Request $request, ActivateAccount $activate): JsonResponse
    {
        return $this->json($activate($token, $input, ClientContext::fromRequest($request)));
    }

    /** Always 202 with the same body, whether the phone exists or not (sección 6.5). */
    #[Route('/password-reset', name: 'password_reset_request', methods: ['POST'])]
    public function requestPasswordReset(#[MapRequestPayload] RequestPasswordResetInput $input, Request $request, RequestPasswordReset $requestReset): JsonResponse
    {
        $requestReset($input, ClientContext::fromRequest($request));

        return $this->json(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }

    #[Route('/password-reset/{token}', name: 'password_reset_show', requirements: ['token' => self::LINK_TOKEN], methods: ['GET'])]
    public function showPasswordReset(string $token, Request $request, ShowAccountLink $show): JsonResponse
    {
        return $this->json($show($token, AccountLinkType::PasswordReset, ClientContext::fromRequest($request)));
    }

    #[Route('/password-reset/{token}', name: 'password_reset', requirements: ['token' => self::LINK_TOKEN], methods: ['POST'])]
    public function resetPassword(string $token, #[MapRequestPayload] SetPasswordInput $input, Request $request, ResetPassword $reset): JsonResponse
    {
        return $this->json($reset($token, $input, ClientContext::fromRequest($request)));
    }
}
