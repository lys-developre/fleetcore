<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Controller;

use App\Identity\Domain\Role;
use App\Notification\Application\Output\SmsView;
use App\Notification\Domain\SmsMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sandbox inbox (registro nº 3, decisión 10): the SMS FleetCore would have sent, for office and the demo.
 * Does not exist when SMS_DRIVER sends real messages.
 */
#[Route('/api/sms-sandbox', name: 'api_sms_sandbox', methods: ['GET'])]
final class SandboxInboxController extends AbstractController
{
    private const int LIMIT = 50;

    public function __invoke(
        EntityManagerInterface $entityManager,
        #[Autowire(env: 'SMS_DRIVER')]
        string $smsDriver,
    ): JsonResponse {
        if ('sandbox' !== $smsDriver) {
            throw new NotFoundHttpException();
        }
        $this->denyAccessUnlessGranted(Role::Office->value);

        $messages = $entityManager->getRepository(SmsMessage::class)->findBy([], ['createdAt' => 'DESC'], self::LIMIT);

        return $this->json(array_map(SmsView::from(...), $messages));
    }
}
