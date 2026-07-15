<?php

namespace App\Service;

final class CalendarEventNormalizer
{
    private const ALLOWED_LABELS = ['neu', 'gelöscht', 'geändert'];
    private const ALLOWED_CATEGORIES = ['selbstlernzeit', 'ferien', 'klausur', 'bib-event', 'eigenes-event'];

    public function normalizeLabel(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($label));

        return in_array($normalized, self::ALLOWED_LABELS, true) ? $normalized : null;
    }

    public function normalizeCategory(?string $category): ?string
    {
        if ($category === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($category));
        if (in_array($normalized, self::ALLOWED_CATEGORIES, true)) {
            return $normalized;
        }

        return match ($normalized) {
            'prüfung', 'pruefung' => 'klausur',
            'unterricht', 'projekt' => 'eigenes-event',
            default => null,
        };
    }

    public function deriveLecturer(?string $summary): ?string
    {
        $parsed = $this->parseSummaryCodes($summary);

        return $parsed['lecturer'];
    }

    public function deriveCategory(?string $summary, ?string $category): ?string
    {
        $parsed = $this->parseSummaryCodes($summary);

        if ($parsed['isExam']) {
            return 'klausur';
        }

        if ($parsed['lecturer'] === null) {
            return 'selbstlernzeit';
        }

        return $this->normalizeCategory($category);
    }

    public function deriveLocation(?string $summary, ?string $location): ?string
    {
        if ($summary !== null) {
            $segments = preg_split('/\s+/', trim($summary));
            if (is_array($segments) && !empty($segments)) {
                $lastSegment = rtrim((string) end($segments), ".,;:");
                if (preg_match('/^P-([A-Za-z0-9]+)/', $lastSegment, $matches) === 1) {
                    $roomValue = strtoupper($matches[1]);
                    if (preg_match('/([A-Z])$/', $roomValue, $letterMatch) === 1) {
                        return $letterMatch[1] . '-Pool';
                    }

                    return 'Raum ' . $roomValue;
                }
            }
        }

        return $location;
    }

    public function mapChangeTypeToCode(?string $changeType): ?int
    {
        if ($changeType === null) {
            return null;
        }

        return match (mb_strtolower(trim($changeType))) {
            'gelöscht', 'geloescht', 'deleted' => 1,
            'neu', 'new' => 2,
            'geändert', 'geaendert', 'updated', 'changed' => 3,
            default => null,
        };
    }

    public function mapChangeTypeToLabel(?string $changeType): ?string
    {
        return match ($this->mapChangeTypeToCode($changeType)) {
            1 => 'gelöscht',
            2 => 'neu',
            3 => 'geändert',
            default => null,
        };
    }

    public function normalizeOriginalEvent(?array $originalEvent): ?array
    {
        if (!is_array($originalEvent)) {
            return null;
        }

        if (
            !array_key_exists('summary', $originalEvent)
            || !array_key_exists('start', $originalEvent)
            || !array_key_exists('end', $originalEvent)
        ) {
            return null;
        }

        $start = $this->normalizeDateTimeValue($originalEvent['start']);
        $end = $this->normalizeDateTimeValue($originalEvent['end']);
        if ($start === null || $end === null) {
            return null;
        }

        return [
            'summary' => (string) $originalEvent['summary'],
            'start' => $start,
            'end' => $end,
            'location' => isset($originalEvent['location']) ? (string) $originalEvent['location'] : null,
        ];
    }

    public function sortEvents(array &$events): void
    {
        usort($events, static function (array $left, array $right): int {
            $leftKey = sprintf(
                '%s|%s|%s|%s',
                (string) ($left['start'] ?? ''),
                (string) ($left['end'] ?? ''),
                (string) ($left['summary'] ?? ''),
                (string) ($left['id'] ?? '')
            );
            $rightKey = sprintf(
                '%s|%s|%s|%s',
                (string) ($right['start'] ?? ''),
                (string) ($right['end'] ?? ''),
                (string) ($right['summary'] ?? ''),
                (string) ($right['id'] ?? '')
            );

            return $leftKey <=> $rightKey;
        });
    }

    private function normalizeDateTimeValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format(DATE_ATOM);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @return array{subject: ?string, lecturer: ?string, isExam: bool}
     */
    private function parseSummaryCodes(?string $summary): array
    {
        if ($summary === null) {
            return ['subject' => null, 'lecturer' => null, 'isExam' => false];
        }

        $trimmed = trim($summary);
        if ($trimmed == '') {
            return ['subject' => null, 'lecturer' => null, 'isExam' => false];
        }

        $isExam = str_starts_with($trimmed, '*');
        $withoutExamMarker = ltrim($isExam ? substr($trimmed, 1) : $trimmed);

        if (preg_match('/^([\p{L}]{3})([\p{L}]{3})?/u', $withoutExamMarker, $matches) !== 1) {
            return ['subject' => null, 'lecturer' => null, 'isExam' => $isExam];
        }

        $subject = isset($matches[1]) ? mb_strtoupper($matches[1]) : null;
        $lecturer = isset($matches[2]) && $matches[2] !== '' ? mb_strtoupper($matches[2]) : null;

        return ['subject' => $subject, 'lecturer' => $lecturer, 'isExam' => $isExam];
    }
}
