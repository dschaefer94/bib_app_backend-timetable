<?php

namespace App\Tests\Service;

use App\Service\CalendarEventNormalizer;
use PHPUnit\Framework\TestCase;

class CalendarEventNormalizerTest extends TestCase
{
    private CalendarEventNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CalendarEventNormalizer();
    }

    public function testDerivesLecturerFromSummaryPrefix(): void
    {
        $this->assertSame('WNS', $this->normalizer->deriveLecturer('IDEWNS Softwareentwicklung'));
    }

    public function testDerivesSelbstlernzeitWhenLecturerMissing(): void
    {
        $this->assertSame('selbstlernzeit', $this->normalizer->deriveCategory('IDE Projektphase', null));
    }

    public function testDerivesKlausurWithHighestPriority(): void
    {
        $this->assertSame('klausur', $this->normalizer->deriveCategory('*IDE Prüfungstermin', 'bib-event'));
    }

    public function testDerivesRoomFromSummarySuffix(): void
    {
        $this->assertSame('Raum 201', $this->normalizer->deriveLocation('IDEWNS Block P-201', null));
        $this->assertSame('B-Pool', $this->normalizer->deriveLocation('IDEWNS Block P-2AB', null));
    }
}

