<?php

namespace App\Service;

use App\Entity\CalendarSource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Types\Types;
use Sabre\VObject\Reader;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendarImportService
{
    private const HTTP_TIMEOUT_SECONDS = 20;
    private const HTTP_RETRY_ATTEMPTS = 3;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Download a calendar from an ical URL, parse and import into DB via import_calendar_for_class
     * @param CalendarSource $source
     * @param bool $fullSync
     * @return array decoded JSON result from DB function
     */
    public function importFromIcalSource(CalendarSource $source, bool $fullSync = true): array
    {
        $url = $source->getIcalLink();
        if ($url === null || trim($url) === '') {
            throw new \InvalidArgumentException('No ical link configured for source id ' . $source->getId());
        }

        $content = $this->downloadIcalContentWithRetry($url);

        $vcalendar = Reader::read($content);

        $events = [];
        foreach ($vcalendar->VEVENT as $vevent) {
            $ev = [];
            $ev['summary'] = isset($vevent->SUMMARY) ? (string)$vevent->SUMMARY : null;
            $ev['description'] = isset($vevent->DESCRIPTION) ? (string)$vevent->DESCRIPTION : null;
            $ev['start'] = (isset($vevent->DTSTART) ? $vevent->DTSTART->getDateTime()->format('Y-m-d H:i:s') : null);
            $ev['end'] = (isset($vevent->DTEND) ? $vevent->DTEND->getDateTime()->format('Y-m-d H:i:s') : null);
            $ev['location'] = isset($vevent->LOCATION) ? (string)$vevent->LOCATION : null;
            $ev['label'] = isset($vevent->CATEGORIES) ? (string)$vevent->CATEGORIES : null;
            $ev['kategorie'] = null; // if not present
            // UID may be present but we rely on fingerprint in DB
            $events[] = $ev;
        }

        $json = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode imported calendar events as JSON: ' . json_last_error_msg());
        }

        $conn = $this->entityManager->getConnection();
        // Use fetchOne helper to execute the statement with parameters. This avoids PDO prepare/execute binding oddities
        // across different driver versions and ensures parameters are passed through DBAL.
        // Some PDO/driver versions may treat a JSON parameter as a JSON *string* scalar when bound,
        // causing jsonb_array_elements() in the DB function to fail. To avoid driver-specific binding
        // quirks in tests, quote the JSON payload and embed it into the SQL as a jsonb literal.
        $quotedJson = $conn->quote($json);
        $klassenId = (int) $source->getId();
        $fullSyncSql = $fullSync ? 'true' : 'false';
        $sql = "SELECT import_calendar_for_class($klassenId, $quotedJson::jsonb, $fullSyncSql)";
        $resText = $conn->fetchOne($sql);

        $source->setLastSyncedAt(new \DateTime());
        $this->entityManager->flush();

        return json_decode($resText, true) ?: [];
    }

    public function importAllSources(bool $fullSync = true): array
    {
        $sources = $this->entityManager->getRepository(CalendarSource::class)->findAll();
        $summary = [
            'totalSources' => count($sources),
            'succeeded' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($sources as $source) {
            if (!$source instanceof CalendarSource) {
                continue;
            }

            try {
                $result = $this->importFromIcalSource($source, $fullSync);
                $summary['succeeded']++;
                $summary['results'][] = [
                    'sourceId' => $source->getId(),
                    'className' => $source->getClassName(),
                    'status' => 'ok',
                    'result' => $result,
                ];
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $summary['results'][] = [
                    'sourceId' => $source->getId(),
                    'className' => $source->getClassName(),
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $summary;
    }

    private function downloadIcalContentWithRetry(string $url): string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::HTTP_RETRY_ATTEMPTS; $attempt++) {
            try {
                $response = $this->httpClient->request('GET', $url, ['timeout' => self::HTTP_TIMEOUT_SECONDS]);
                return $response->getContent();
            } catch (\Throwable $exception) {
                $lastException = $exception;
                if ($attempt < self::HTTP_RETRY_ATTEMPTS) {
                    usleep(250_000);
                }
            }
        }

        throw new \RuntimeException(
            sprintf('Failed to download ICS feed after %d attempts: %s', self::HTTP_RETRY_ATTEMPTS, $url),
            previous: $lastException
        );
    }
}
