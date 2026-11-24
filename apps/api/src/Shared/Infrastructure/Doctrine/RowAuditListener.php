<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Domain\Actor;
use App\Shared\Domain\RowAudited;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;

/**
 * Fills created_* and updated_* on RowAudited entities from the clock and the authenticated user.
 * Updates are stamped in onFlush, not preUpdate: preUpdate ignores changes to fields outside the changeset.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::onFlush)]
final readonly class RowAuditListener
{
    public function __construct(private ClockInterface $clock, private Security $security)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof RowAudited) {
            $entity->stampCreation($this->clock->now(), $this->actor()?->getId());
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof RowAudited) {
                continue;
            }
            $entity->stampUpdate($this->clock->now(), $this->actor()?->getId());
            $unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata($entity::class), $entity);
        }
    }

    private function actor(): ?Actor
    {
        $user = $this->security->getUser();

        return $user instanceof Actor ? $user : null;
    }
}
