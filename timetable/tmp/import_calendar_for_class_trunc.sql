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
    INSERT INTO aenderungs_label (name) SELECT 'updated' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'updated');
    INSERT INTO aenderungs_label (name) SELECT 'deleted' WHERE NOT EXISTS (SELECT 1 FROM aenderungs_label WHERE name = 'deleted');
    SELECT id INTO change_type_updated_id FROM aenderungs_label WHERE name = 'updated' LIMIT 1;
    SELECT id INTO change_type_deleted_id FROM aenderungs_label WHERE name = 'deleted' LIMIT 1;

    SELECT klassenname INTO kname FROM klassen WHERE klassen_id = p_klassen_id;

    FOR cur IN SELECT * FROM jsonb_array_elements(p_events) LOOP
        ev := cur;
        fp := encode(digest(coalesce(ev->>'summary','') || '|' || (ev->>'start') || '|' || (ev->>'end') || '|' || coalesce(ev->>'location',''), 'sha256'), 'hex');
        incoming_fps := array_append(incoming_fps, fp);

        SELECT id INTO existing_id FROM stundenplan_neu WHERE fingerprint = fp LIMIT 1;

        IF existing_id IS NOT NULL THEN
            PERFORM 1 FROM stundenplan_neu WHERE id = existing_id AND (summary IS NOT DISTINCT FROM left(ev->>'summary',255)) AND (description IS NOT DISTINCT FROM ev->>'description') AND (location IS NOT DISTINCT FROM left(ev->>'location',255)) AND (label IS NOT DISTINCT FROM left(ev->>'label',50)) AND (kategorie IS NOT DISTINCT FROM left(ev->>'kategorie',50)) AND (start = (ev->>'start')::timestamp) AND ("end" = (ev->>'end')::timestamp);
            IF NOT FOUND THEN
                INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type_id)
                SELECT gen_random_uuid(), summary, description, start, "end", location, label, kategorie, to_jsonb(t), now(), kname, change_type_updated_id FROM stundenplan_neu t WHERE id = existing_id;

                UPDATE stundenplan_neu SET summary = left(ev->>'summary',255), description = ev->>'description', location = left(ev->>'location',255), label = left(ev->>'label',50), kategorie = left(ev->>'kategorie',50), start = (ev->>'start')::timestamp, "end" = (ev->>'end')::timestamp, updated_at = now() WHERE id = existing_id;
                updated := updated + 1;
                details := details || jsonb_build_object('op','updated','event_id', existing_id, 'fingerprint', fp);
            ELSE
                details := details || jsonb_build_object('op','unchanged','event_id', existing_id, 'fingerprint', fp);
            END IF;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (existing_id, p_klassen_id) ON CONFLICT DO NOTHING;
        ELSE
            INSERT INTO stundenplan_neu (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, fingerprint)
            VALUES (gen_random_uuid(), left(ev->>'summary',255), ev->>'description', (ev->>'start')::timestamp, (ev->>'end')::timestamp, left(ev->>'location',255), left(ev->>'label',50), left(ev->>'kategorie',50), NULL, now(), kname, fp)
            RETURNING id INTO existing_id;

            INSERT INTO stundenplan_neu_klasse (stundenplan_neu_id, klassen_id) VALUES (existing_id, p_klassen_id);
            inserted := inserted + 1;
            details := details || jsonb_build_object('op','inserted','event_id', existing_id, 'fingerprint', fp);
        END IF;
    END LOOP;

    IF p_full_sync THEN
        FOR cur IN SELECT sn.id FROM stundenplan_neu sn JOIN stundenplan_neu_klasse snk ON sn.id = snk.stundenplan_neu_id WHERE snk.klassen_id = p_klassen_id AND (sn.fingerprint IS NULL OR NOT (sn.fingerprint = ANY(incoming_fps))) LOOP
            INSERT INTO geaenderte_termine (id, summary, description, start, "end", location, label, kategorie, original_event, updated_at, klasse, change_type_id)
            SELECT gen_random_uuid(), sn.summary, sn.description, sn.start, sn."end", sn.location, sn.label, sn.kategorie, to_jsonb(sn), now(), kname, change_type_deleted_id FROM stundenplan_neu sn WHERE sn.id = cur.id;
            DELETE FROM stundenplan_neu_klasse WHERE stundenplan_neu_id = cur.id AND klassen_id = p_klassen_id;
            deleted := deleted + 1;
            details := details || jsonb_build_object('op','deleted','event_id', cur.id);
        END LOOP;
    END IF;

    RETURN jsonb_build_object('inserted', inserted, 'updated', updated, 'deleted', deleted, 'details', details);
END;
$$ LANGUAGE plpgsql;

