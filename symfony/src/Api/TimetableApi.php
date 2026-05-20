<?php

namespace App\Api;

use OpenAPI\Server\Api\TimetableApiInterface;
use OpenAPI\Server\Model\Timetable;

class TimetableApi implements TimetableApiInterface
{
    public function setbearerAuth(?string $value): void
    {
        // Optional: JWT prüfen
        // Für Test erstmal ignorieren
    }

    public function loadTimetable(int &$responseCode, array &$responseHeaders): array|object|null
    {
        $responseCode = 200;
        $responseHeaders = [];

        $timetable = new Timetable();
        $timetable->setName('pbd2h24a');
        $timetable->setTimestamp(new \DateTime());

        return $timetable;
    }
}
