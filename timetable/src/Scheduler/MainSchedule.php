<?php

namespace App\Scheduler;

use App\Message\SyncCalendarsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class MainSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            // Führt den Sync alle 10 Minuten aus
            RecurringMessage::every('10 minutes', new SyncCalendarsMessage())
        );
    }
}