<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Notification\Application\Message\SendSms;
use App\Notification\Domain\SmsDeliveryFailed;
use App\Notification\Domain\SmsMessage;
use App\Notification\Domain\SmsSender;
use App\Notification\Domain\SmsStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendSmsHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SmsSender $sender,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendSms $command): void
    {
        $message = $this->entityManager->find(SmsMessage::class, $command->smsMessageId);
        // Deleted meanwhile, or already delivered by a previous attempt: nothing to do
        if (null === $message || SmsStatus::Sent === $message->getStatus()) {
            return;
        }

        try {
            $externalId = $this->sender->send($message->getPhone(), $message->getBody());
            $message->markSent($this->sender->name(), $externalId, $this->clock->now());
        } catch (SmsDeliveryFailed $exception) {
            $message->markFailed($this->sender->name(), $exception->getMessage());
            $this->entityManager->flush();
            $this->logger->error('SMS delivery failed', ['sms' => $message->getId()->toRfc4122(), 'error' => $exception->getMessage()]);

            // Let Messenger retry it (retry_strategy on the async transport)
            throw $exception;
        }

        $this->entityManager->flush();
    }
}
