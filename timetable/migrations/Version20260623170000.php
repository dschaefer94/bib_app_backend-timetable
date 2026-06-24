<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fingerprint and join table stundenplan_neu_klasse; add import_calendar_for_class PL/pgSQL function';
    }

    public function up(Schema $schema): void
    {
        // enable pgcrypto
        $this->addSql("CREATE EXTENSION IF NOT EXISTS pgcrypto");

        // add fingerprint column
        $this->addSql('ALTER TABLE stundenplan_neu ADD COLUMN fingerprint VARCHAR(64) DEFAULT NULL');

        // create join table
        $this->addSql('CREATE TABLE stundenplan_neu_klasse (stundenplan_neu_id UUID NOT NULL, klassen_id INT NOT NULL, PRIMARY KEY(stundenplan_neu_id, klassen_id))');
        $this->addSql('CREATE INDEX IDX_STUNDENPLAN_NEU_KLASSE_KLASSEN_ID ON stundenplan_neu_klasse (klassen_id)');
        $this->addSql('ALTER TABLE stundenplan_neu_klasse ADD CONSTRAINT FK_STUNDENPLAN_NEU_KLASSEN_EVT FOREIGN KEY (stundenplan_neu_id) REFERENCES stundenplan_neu (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stundenplan_neu_klasse ADD CONSTRAINT FK_STUNDENPLAN_NEU_KLASSEN_KLASSE FOREIGN KEY (klassen_id) REFERENCES klassen (klassen_id) NOT DEFERRABLE');

        // indexes on stundenplan_neu
        $this->addSql('CREATE INDEX IDX_STUNDENPLAN_NEU_START_END ON stundenplan_neu (start, "end")');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_STUNDENPLAN_NEU_FINGERPRINT ON stundenplan_neu (fingerprint)');

        // Backfill: compute fingerprint and populate join table based on existing klasse string -> klassen table
        $this->addSql(<<<'SQL'
DO $$
DECLARE
    rec RECORD;
    k_id INT;
BEGIN
    FOR rec IN SELECT id, summary, start, "end", location, klasse FROM stundenplan_neu LOOP
        -- compute fingerprint
        UPDATE stundenplan_neu SET fingerprint = encode(digest(coalesce(rec.summary, '') || '|' || (rec.start AT TIME ZONE 'UTC')::text || '|' || (rec."end" AT TIME ZONE 'UTC')::text || '|' || coalesce(rec.location, ''), 'sha256'), 'hex') WHERE id = rec.id;

        -- find or create class id by name
        SELECT klassen_id INTO k_id FROM klassen WHERE klassenname = rec.klasse LIMIT 1;
        IF k_id IS NULL THEN
            INSERT INTO klassen (klassenname) VALUES (rec.klasse) RETURNING klassen_id INTO k_id;
        END IF;

        -- insert into join table
        INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (rec.id, k_id) ON CONFLICT DO NOTHING;
    END LOOP;
END$$;
SQL
        );

        // Create PL/pgSQL function import_calendar_for_class
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
    change_type_updated_id INT;
    change_type_deleted_id INT;
    incoming_fps TEXT[] := ARRAY[]::TEXT[];
    cur JSONB;
BEGIN
    -- ensure change type labels exist
    INSERT INTO aenderungs_label (name) SELECT 'updated' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'updated');
    INSERT INTO aenderungs_label (name) SELECT 'deleted' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'deleted');
    SELECT id INTO change_type_updated_id FROM aenderungs_label WHERE name = 'updated' LIMIT 1;
    SELECT id INTO change_type_deleted_id FROM aenderungs_label WHERE name = 'deleted' LIMIT 1;

    SELECT klassenname INTO kname FROM klassen WHERE klassen_id = p_klassen_id;

    FOR cur IN SELECT * FROM jsonb_array_elements(p_events) LOOP
        ev := cur;
        -- compute fingerprint using summary|start|end|location
        fp := encode(digest(coalesce(ev->>'summary','') || '|' || (ev->>'start') || '|' || (ev->>'end') || '|' || coalesce(ev->>'location',''), 'sha256'), 'hex');
        incoming_fps := array_append(incoming_fps, fp);

        SELECT id INTO existing_id FROM stundenplan_neu WHERE fingerprint = fp LIMIT 1;

        IF existing_id IS NOT NULL THEN
            -- check if different fields
            PERFORM 1 FROM stundenplan_neu WHERE id = existing_id AND (summary IS NOT DISTINCT FROM ev->>'summary') AND (description IS NOT DISTINCT FROM ev->>'description') AND (location IS NOT DISTINCT FROM ev->>'location') AND (label IS NOT DISTINCT FROM ev->>'label') AND (kategorie IS NOT DISTINCT FROM ev->>'kategorie') AND (start = (ev->>'start')::timestamp) AND ("end" = (ev->>'end')::timestamp);
            IF NOT FOUND THEN
                -- store original
                INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type_id)
                SELECT gen_random_uuid(), summary, description, start, "end", location, label, kategorie, to_jsonb(t), now(), kname, change_type_updated_id FROM stundenplan_neu t WHERE id = existing_id;

                -- update main row
                UPDATE stundenplan_neu SET summary = ev->>'summary', description = ev->>'description', location = ev->>'location', label = ev->>'label', kategorie = ev->>'kategorie', start = (ev->>'start')::timestamp, "end" = (ev->>'end')::timestamp, updated_at = now() WHERE id = existing_id;
                updated := updated + 1;
                details := details || jsonb_build_object('op','updated','event_id', existing_id, 'fingerprint', fp);
            ELSE
                -- unchanged
                details := details || jsonb_build_object('op','unchanged','event_id', existing_id, 'fingerprint', fp);
            END IF;

            -- ensure mapping exists
            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (existing_id, p_klassen_id) ON CONFLICT DO NOTHING;
        ELSE
            -- insert new event
            INSERT INTO stundenplan_neu (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, fingerprint)
            VALUES (gen_random_uuid(), ev->>'summary', ev->>'description', (ev->>'start')::timestamp, (ev->>'end')::timestamp, ev->>'location', ev->>'label', ev->>'kategorie', NULL, now(), fp)
            RETURNING id INTO existing_id;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (existing_id, p_klassen_id);
            inserted := inserted + 1;
            details := details || jsonb_build_object('op','inserted','event_id', existing_id, 'fingerprint', fp);
        END IF;
    END LOOP;

    IF p_full_sync THEN
        -- delete mappings not present in incoming fps
        DELETE FROM stundenplan_neu_klasse snk
        WHERE snk.klassen_id = p_klassen_id AND NOT (snk.fingerprint = ANY(incoming_fps))
        RETURNING stundenplan_neu_id INTO existing_id;
        -- The above is simplistic; instead do a query to find removed events
        FOR cur IN SELECT sn.id FROM stundenplan_neu sn JOIN stundenplan_neu_klasse snk ON sn.id = snk.stundenplan_neu_id WHERE snk.klassen_id = p_klassen_id AND (sn.fingerprint IS NULL OR NOT (sn.fingerprint = ANY(incoming_fps))) LOOP
            -- mark deleted in history
            INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type_id)
            SELECT gen_random_uuid(), sn.summary, sn.description, sn.start, sn."end", sn.location, sn.label, sn.kategorie, to_jsonb(sn), now(), kname, change_type_deleted_id FROM stundenplan_neu sn WHERE sn.id = cur.id;
            -- remove mapping
            DELETE FROM stundenplan_neu_klasse WHERE stundenplan_neu_id = cur.id AND klassen_id = p_klassen_id;
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
        // drop function
        $this->addSql('DROP FUNCTION IF EXISTS import_calendar_for_class(INT, JSONB, BOOLEAN)');

        // drop join table
        $this->addSql('ALTER TABLE stundenplan_neu_klasse DROP CONSTRAINT IF EXISTS FK_STUNDENPLAN_NEU_KLASSEN_EVT');
        $this->addSql('ALTER TABLE stundenplan_neu_klasse DROP CONSTRAINT IF EXISTS FK_STUNDENPLAN_NEU_KLASSEN_KLASSE');
        $this->addSql('DROP TABLE IF EXISTS stundenplan_neu_klasse');

        // drop indexes
        $this->addSql('DROP INDEX IF EXISTS IDX_STUNDENPLAN_NEU_START_END');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_STUNDENPLAN_NEU_FINGERPRINT');

        // drop fingerprint column
        $this->addSql('ALTER TABLE stundenplan_neu DROP COLUMN IF EXISTS fingerprint');
    }
}

