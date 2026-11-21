<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Recurring jobs: signature reminders, document expiry digests, abandoned-draft notices.
 * Tasks are attached module by module as they are introduced (H6+).
 */
#[AsSchedule('default')]
final class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function getSchedule(): Schedule
    {
        return new Schedule()
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);
    }
}
