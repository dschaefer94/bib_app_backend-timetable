<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624031000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix import_calendar_for_class function and convert geaenderte_termine.change_type to plain string attribute';
    }

    public function up(Schema $schema): void
    {
        // Convert change type from FK to plain string attribute.
        $this->addSql('ALTER TABLE geaenderte_termine ADD COLUMN IF NOT EXISTS change_type VARCHAR(50)');
        $this->addSql("UPDATE geaenderte_termine gt SET change_type = al.name FROM aenderungs_label al WHERE gt.change_type IS NULL AND gt.change_type_id = al.id");
        $this->addSql("UPDATE geaenderte_termine SET change_type = 'geändert' WHERE change_type IS NULL");
        $this->addSql('ALTER TABLE geaenderte_termine ALTER COLUMN change_type SET NOT NULL');

        $this->addSql('ALTER TABLE geaenderte_termine DROP CONSTRAINT IF EXISTS FK_71261E5C354B2E3A');
        $this->addSql('DROP INDEX IF EXISTS IDX_71261E5C354B2E3A');
        $this->addSql('ALTER TABLE geaenderte_termine DROP COLUMN IF EXISTS change_type_id');
        $this->addSql('DROP TABLE IF EXISTS aenderungs_label');

        // Replace PL/pgSQL import function with corrected logic.
        $this->addSql(<<<'SQL'
CREATE OR REPLACE FUNCTION import_calendar_for_class(p_klassen_id INT, p_events JSONB, p_full_sync BOOLEAN DEFAULT true)
RETURNS JSONB AS $$
DECLARE
    ev JSONB;
    inserted INT := 0;
    updated INT := 0;
    deleted INT := 0;
    details JSONB := '[]'::jsonb;
    fp TEXT;
    existing_id UUID;
    kname TEXT;
    incoming_fps TEXT[] := ARRAY[]::TEXT[];
    cur RECORD;
BEGIN
    SELECT klassenname INTO kname FROM klassen WHERE klassen_id = p_klassen_id;
    IF kname IS NULL THEN
        RAISE EXCEPTION 'Class with id % does not exist', p_klassen_id;
    END IF;

    FOR ev IN SELECT * FROM jsonb_array_elements(p_events) LOOP
        fp := encode(digest(
            coalesce(ev->>'summary','') || '|' ||
            coalesce(ev->>'start','') || '|' ||
            coalesce(ev->>'end','') || '|' ||
            coalesce(ev->>'location','')
        , 'sha256'), 'hex');

        incoming_fps := array_append(incoming_fps, fp);

        SELECT id INTO existing_id FROM stundenplan_neu WHERE fingerprint = fp LIMIT 1;

        IF existing_id IS NOT NULL THEN
            PERFORM 1 FROM stundenplan_neu
             WHERE id = existing_id
               AND summary IS NOT DISTINCT FROM ev->>'summary'
               AND description IS NOT DISTINCT FROM ev->>'description'
               AND location IS NOT DISTINCT FROM ev->>'location'
               AND label IS NOT DISTINCT FROM ev->>'label'
               AND kategorie IS NOT DISTINCT FROM ev->>'kategorie'
               AND start = (ev->>'start')::timestamp
               AND "end" = (ev->>'end')::timestamp;

            IF NOT FOUND THEN
                INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type)
                SELECT gen_random_uuid(), left(sn.summary, 255), sn.description, sn.start, sn."end", left(coalesce(sn.location, ''), 255), left(coalesce(sn.label, ''), 50), left(coalesce(sn.kategorie, ''), 50), to_jsonb(sn), now(), kname, 'geändert'
                  FROM stundenplan_neu sn WHERE sn.id = existing_id;

                UPDATE stundenplan_neu
                   SET summary = ev->>'summary',
                       description = ev->>'description',
                       location = ev->>'location',
                       label = ev->>'label',
                       kategorie = ev->>'kategorie',
                       start = (ev->>'start')::timestamp,
                       "end" = (ev->>'end')::timestamp,
                       updated_at = now(),
                       klasse = kname
                 WHERE id = existing_id;

                updated := updated + 1;
                details := details || jsonb_build_object('op','updated','event_id', existing_id, 'fingerprint', fp);
            ELSE
                details := details || jsonb_build_object('op','unchanged','event_id', existing_id, 'fingerprint', fp);
            END IF;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id)
            VALUES (existing_id, p_klassen_id)
            ON CONFLICT DO NOTHING;
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
                NULL,
                now(),
                kname,
                fp
            )
            RETURNING id INTO existing_id;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id)
            VALUES (existing_id, p_klassen_id)
            ON CONFLICT DO NOTHING;

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
               AND (sn.fingerprint IS NULL OR NOT (sn.fingerprint = ANY(incoming_fps)))
        LOOP
            INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type)
            SELECT gen_random_uuid(), left(sn.summary, 255), sn.description, sn.start, sn."end", left(coalesce(sn.location, ''), 255), left(coalesce(sn.label, ''), 50), left(coalesce(sn.kategorie, ''), 50), to_jsonb(sn), now(), kname, 'gelöscht'
              FROM stundenplan_neu sn
             WHERE sn.id = cur.id;

            DELETE FROM stundenplan_neu_klasse
             WHERE stundenplan_neu_id = cur.id
               AND klassen_id = p_klassen_id;

            deleted := deleted + 1;
            details := details || jsonb_build_object('op','deleted','event_id', cur.id);
        END LOOP;
    END IF;

    RETURN jsonb_build_object('inserted', inserted, 'updated', updated, 'deleted', deleted, 'details', details);
END;
$$ LANGUAGE plpgsql;
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Best effort rollback: restore FK-based column and drop string column.
        $this->addSql('CREATE TABLE IF NOT EXISTS aenderungs_label (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_23AA5C495E237E06 ON aenderungs_label (name)');
        $this->addSql("INSERT INTO aenderungs_label (name) SELECT 'neu' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'neu')");
        $this->addSql("INSERT INTO aenderungs_label (name) SELECT 'gelöscht' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'gelöscht')");
        $this->addSql("INSERT INTO aenderungs_label (name) SELECT 'geändert' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'geändert')");
        $this->addSql('ALTER TABLE geaenderte_termine ADD COLUMN IF NOT EXISTS change_type_id INT');
        $this->addSql('ALTER TABLE geaenderte_termine DROP COLUMN IF EXISTS change_type');

        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_71261E5C354B2E3A ON geaenderte_termine (change_type_id)');
        $this->addSql('ALTER TABLE geaenderte_termine ADD CONSTRAINT FK_71261E5C354B2E3A FOREIGN KEY (change_type_id) REFERENCES aenderungs_label (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
