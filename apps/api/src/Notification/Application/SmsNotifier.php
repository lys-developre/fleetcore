<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Identity\Domain\User;
use App\Notification\Application\Message\SendSms;
use App\Notification\Domain\SmsMessage;
use App\Notification\Domain\SmsType;
use App\Shared\Domain\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Stores the SMS and queues its delivery. Must run inside the use case's transaction: the Doctrine transport
 * shares the connection, so the sms_message row and the queue entry commit or roll back together, and the
 * worker never picks up a message whose row does not exist yet.
 */
final readonly class SmsNotifier
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    public function queue(PhoneNumber $to, SmsType $type, string $body, ?User $recipient = null): SmsMessage
    {
        if (!$this->entityManager->getConnection()->isTransactionActive()) {
            throw new \LogicException('Queue SMS inside a transaction (EntityManager::wrapInTransaction).');
        }

        $message = new SmsMessage($to, $type, $body, $this->clock->now(), $recipient);
        $this->entityManager->persist($message);
        $this->entityManager->flush();
        $this->bus->dispatch(new SendSms($message->getId()));

        return $message;
    }
}
