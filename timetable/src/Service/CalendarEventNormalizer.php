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
}
