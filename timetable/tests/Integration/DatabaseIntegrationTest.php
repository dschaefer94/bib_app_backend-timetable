<?php

namespace App\Tests\Integration;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Entity\PersoenlicheDaten;
use App\Entity\StundenplanNeu;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\Tools\SchemaTool; // Added this line

class DatabaseIntegrationTest extends KernelTestCase
{
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        // Ensure the database schema is up-to-date for tests
        // If the test database is not available in the current environment, skip tests gracefully.
        try {
            $this->dropAndCreateSchema();
            // Sicherstellen, dass die Datenbank für jeden Test sauber ist und Fixtures geladen werden
            $this->loadFixtures();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available for integration tests: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Schließt den EntityManager, um Memory Leaks zu vermeiden
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    private function dropAndCreateSchema(): void
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        // Ensure any auxiliary objects created by previous test runs or by migrations are removed
        $conn = $this->entityManager->getConnection();
        try {
            $conn->executeStatement('DROP FUNCTION IF EXISTS import_calendar_for_class(INT, JSONB, BOOLEAN)');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS aenderungs_label CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS stundenplan_neu_klasse');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS klassen CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('ALTER TABLE stundenplan_neu DROP COLUMN IF EXISTS fingerprint');
        } catch (\Throwable $e) {
            // ignore
        }

        $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);
        // Apply additional DB objects created by migrations that are not part of the Doctrine schema
        // (fingerprint column, join table, indexes and PL/pgSQL function).
        // This keeps integration tests working without running the full migration pipeline.
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement("CREATE EXTENSION IF NOT EXISTS pgcrypto");
        $conn->executeStatement('ALTER TABLE stundenplan_neu ADD COLUMN IF NOT EXISTS fingerprint VARCHAR(64) DEFAULT NULL');
        $conn->executeStatement('CREATE TABLE IF NOT EXISTS stundenplan_neu_klasse (stundenplan_neu_id UUID NOT NULL, klassen_id INT NOT NULL, PRIMARY KEY(stundenplan_neu_id, klassen_id))');
        $conn->executeStatement('CREATE INDEX IF NOT EXISTS IDX_STUNDENPLAN_NEU_KLASSE_KLASSEN_ID ON stundenplan_neu_klasse (klassen_id)');
        try {
            $conn->executeStatement('ALTER TABLE stundenplan_neu_klasse ADD CONSTRAINT FK_STUNDENPLAN_NEU_KLASSEN_EVT FOREIGN KEY (stundenplan_neu_id) REFERENCES stundenplan_neu (id) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // ignore - constraint likely exists or DB does not support IF NOT EXISTS in this form
        }
        try {
            $conn->executeStatement('ALTER TABLE stundenplan_neu_klasse ADD CONSTRAINT FK_STUNDENPLAN_NEU_KLASSE_KLASSE FOREIGN KEY (klassen_id) REFERENCES klassen (klassen_id)');
        } catch (\Throwable $e) {
            // ignore
        }
        $conn->executeStatement('CREATE INDEX IF NOT EXISTS IDX_STUNDENPLAN_NEU_START_END ON stundenplan_neu (start, "end")');
        $conn->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_STUNDENPLAN_NEU_FINGERPRINT ON stundenplan_neu (fingerprint)');

        // Create the import_calendar_for_class function used by CalendarImportService tests
        $functionSql = <<<'SQL'
CREATE OR REPLACE FUNCTION import_calendar_for_class(p_klassen_id INT, p_events JSONB, p_full_sync BOOLEAN DEFAULT true)
RETURNS JSONB AS $$
DECLARE
    ev JSONB;
    inserted INT := 0;
    updated INT := 0;
    deleted INT := 0;
    details JSONB := '[]'::jsonb;
    fp TEXT;
    incoming_uid TEXT;
    existing_id UUID;
    kname TEXT;
    incoming_fps TEXT[] := ARRAY[]::TEXT[];
    incoming_uids TEXT[] := ARRAY[]::TEXT[];
    cur JSONB;
BEGIN
    SELECT klassenname INTO kname FROM klassen WHERE klassen_id = p_klassen_id;

    FOR cur IN SELECT * FROM jsonb_array_elements(p_events) LOOP
        ev := cur;
        incoming_uid := nullif(trim(coalesce(ev->>'uid', '')), '');
        fp := encode(digest(coalesce(ev->>'summary','') || '|' || (ev->>'start') || '|' || (ev->>'end') || '|' || coalesce(ev->>'location',''), 'sha256'), 'hex');
        incoming_fps := array_append(incoming_fps, fp);
        IF incoming_uid IS NOT NULL THEN
            incoming_uids := array_append(incoming_uids, incoming_uid);
        END IF;

        existing_id := NULL;
        IF incoming_uid IS NOT NULL THEN
            SELECT sn.id INTO existing_id
              FROM stundenplan_neu sn
              JOIN stundenplan_neu_klasse snk ON sn.id = snk.stundenplan_neu_id
             WHERE snk.klassen_id = p_klassen_id
               AND sn.original_event IS NOT NULL
               AND sn.original_event->>'uid' = incoming_uid
             LIMIT 1;
        END IF;

        IF existing_id IS NULL THEN
            SELECT sn.id INTO existing_id
              FROM stundenplan_neu sn
              JOIN stundenplan_neu_klasse snk ON sn.id = snk.stundenplan_neu_id
             WHERE snk.klassen_id = p_klassen_id
               AND sn.fingerprint = fp
             LIMIT 1;
        END IF;

        IF existing_id IS NOT NULL THEN
            PERFORM 1 FROM stundenplan_neu
             WHERE id = existing_id
               AND summary IS NOT DISTINCT FROM left(coalesce(ev->>'summary',''), 255)
               AND description IS NOT DISTINCT FROM ev->>'description'
               AND location IS NOT DISTINCT FROM left(coalesce(ev->>'location',''), 255)
               AND label IS NOT DISTINCT FROM left(coalesce(ev->>'label',''), 50)
               AND kategorie IS NOT DISTINCT FROM left(coalesce(ev->>'kategorie',''), 50)
               AND start = (ev->>'start')::timestamp
               AND "end" = (ev->>'end')::timestamp;
            IF NOT FOUND THEN
                INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type)
                SELECT gen_random_uuid(), left(summary, 255), description, start, "end", left(coalesce(location, ''), 255), left(coalesce(label, ''), 50), left(coalesce(kategorie, ''), 50), to_jsonb(t), now(), kname, 'geändert'
                  FROM stundenplan_neu t
                 WHERE id = existing_id;

                UPDATE stundenplan_neu
                   SET summary = left(coalesce(ev->>'summary', ''), 255),
                       description = ev->>'description',
                       location = left(coalesce(ev->>'location', ''), 255),
                       label = left(coalesce(ev->>'label', ''), 50),
                       kategorie = left(coalesce(ev->>'kategorie', ''), 50),
                       original_event = CASE WHEN incoming_uid IS NOT NULL THEN jsonb_build_object('uid', incoming_uid) ELSE original_event END,
                       start = (ev->>'start')::timestamp,
                       "end" = (ev->>'end')::timestamp,
                       updated_at = now(),
                       klasse = kname,
                       fingerprint = fp
                 WHERE id = existing_id;
                updated := updated + 1;
                details := details || jsonb_build_object('op','updated','event_id', existing_id, 'fingerprint', fp);
            ELSE
                details := details || jsonb_build_object('op','unchanged','event_id', existing_id, 'fingerprint', fp);
            END IF;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (existing_id, p_klassen_id) ON CONFLICT DO NOTHING;
        ELSE
            INSERT INTO stundenplan_neu (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, fingerprint)
            VALUES (
                gen_random_uuid(),
                left(coalesce(ev->>'summary', ''), 255),
                ev->>'description',
                (ev->>'start')::timestamp,
                (ev->>'end')::timestamp,
                left(coalesce(ev->>'location', ''), 255),
                left(coalesce(ev->>'label', ''), 50),
                left(coalesce(ev->>'kategorie', ''), 50),
                CASE WHEN incoming_uid IS NOT NULL THEN jsonb_build_object('uid', incoming_uid) ELSE NULL END,
                now(),
                kname,
                fp
            )
            RETURNING id INTO existing_id;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (existing_id, p_klassen_id);
            INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type)
            VALUES (
                gen_random_uuid(),
                left(coalesce(ev->>'summary', ''), 255),
                ev->>'description',
                (ev->>'start')::timestamp,
                (ev->>'end')::timestamp,
                left(coalesce(ev->>'location', ''), 255),
                left(coalesce(ev->>'label', ''), 50),
                left(coalesce(ev->>'kategorie', ''), 50),
                NULL,
                now(),
                kname,
                'neu'
            );

            inserted := inserted + 1;
            details := details || jsonb_build_object('op','inserted','event_id', existing_id, 'fingerprint', fp);
        END IF;
    END LOOP;

    IF p_full_sync THEN
        FOR cur IN
            SELECT sn.id
              FROM stundenplan_neu sn
              JOIN stundenplan_neu_klasse snk ON sn.id = snk.stundenplan_neu_id
             WHERE snk.klassen_id = p_klassen_id
               AND (
                    (
                        sn.original_event IS NOT NULL
                        AND nullif(sn.original_event->>'uid', '') IS NOT NULL
                        AND NOT (sn.original_event->>'uid' = ANY(incoming_uids))
                    )
                    OR
                    (
                        (sn.original_event IS NULL OR nullif(sn.original_event->>'uid', '') IS NULL)
                        AND (sn.fingerprint IS NULL OR NOT (sn.fingerprint = ANY(incoming_fps)))
                    )
               )
        LOOP
            INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type)
            SELECT gen_random_uuid(), left(sn.summary, 255), sn.description, sn.start, sn."end", left(coalesce(sn.location, ''), 255), left(coalesce(sn.label, ''), 50), left(coalesce(sn.kategorie, ''), 50), to_jsonb(sn), now(), kname, 'gelöscht' FROM stundenplan_neu sn WHERE sn.id = cur.id;
            DELETE FROM stundenplan_neu_klasse WHERE stundenplan_neu_id = cur.id AND klassen_id = p_klassen_id;
            deleted := deleted + 1;
            details := details || jsonb_build_object('op','deleted','event_id', cur.id);
        END LOOP;
    END IF;

    RETURN jsonb_build_object('inserted', inserted, 'updated', updated, 'deleted', deleted, 'details', details);
END;
$$ LANGUAGE plpgsql;
SQL;
        $conn->executeStatement($functionSql);
    }

    private function loadFixtures(): void
    {
        // Purge und lade Fixtures für einen sauberen Testzustand
        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        // Übergebe den passwordHasher an die AppFixtures
        $executor->execute([new AppFixtures()], true);
    }

    public function testDummyUserAndPersonalDataAreLoaded(): void
    {
        // Find the dummy user by email, as the UUID is dynamically generated
        /** @var Benutzer|null $user */
        $user = $this->entityManager->getRepository(Benutzer::class)->findOneBy(['email' => 'dummyuser@example.com']);
        $this->assertNotNull($user, 'Dummy user should be found.');
        $this->assertEquals('dummyuser@example.com', $user->getEmail());

        /** @var PersoenlicheDaten|null $persoenlicheDaten */
        $persoenlicheDaten = $this->entityManager->getRepository(PersoenlicheDaten::class)->findOneBy(['benutzer' => $user]);
        $this->assertNotNull($persoenlicheDaten, 'Personal data for dummy user should be found.');
        $this->assertEquals('Max', $persoenlicheDaten->getVorname());
        $this->assertEquals('Mustermann', $persoenlicheDaten->getName());

        /** @var CalendarSource|null $calendarSource */
        $calendarSource = $persoenlicheDaten->getKlasse();
        $this->assertNotNull($calendarSource, 'Calendar source should be associated with personal data.');
        $this->assertEquals('Dummyklasse', $calendarSource->getClassName());
    }

    public function testStundenplanNeuEntriesAreLoaded(): void
    {
        $klasseName = 'Dummyklasse';

        /** @var CalendarSource|null $calendarSource */
        $calendarSource = $this->entityManager->getRepository(CalendarSource::class)->findOneBy(['className' => $klasseName]);
        $this->assertNotNull($calendarSource, 'Calendar source for Dummyklasse should be found.');

        /** @var StundenplanNeu[] $entries */
        $entries = $this->entityManager->getRepository(StundenplanNeu::class)->findBy(['klasse' => $klasseName]);
        $this->assertGreaterThan(0, count($entries), 'At least one StundenplanNeu entry should be found.');

        foreach ($entries as $entry) {
            $this->assertInstanceOf(StundenplanNeu::class, $entry);
            $this->assertNotNull($entry->getId(), 'StundenplanNeu entry should have an ID.');
            $this->assertNotEmpty($entry->getSummary(), 'StundenplanNeu entry should have a summary.');
            $this->assertEquals($klasseName, $entry->getKlasse(), 'StundenplanNeu entry should belong to Dummyklasse.');
            $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getStart(), 'StundenplanNeu entry start date should be DateTimeImmutable.');
            $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getEnd(), 'StundenplanNeu entry end date should be DateTimeImmutable.');
        }
    }
}
