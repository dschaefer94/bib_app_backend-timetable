-- =========================================================
-- ENUMS
-- =========================================================

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'aenderungs_label') THEN
        CREATE TYPE aenderungs_label AS ENUM ('gelöscht', 'neu', 'geändert');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'kalender_kategorie') THEN
        CREATE TYPE kalender_kategorie AS ENUM ('klausur', 'bib-event', 'eigenes-event', 'unterricht', 'projekt', 'ferien', 'prüfung');
    END IF;
END $$;

-- =========================================================
-- WEEK HELPERS
-- =========================================================

CREATE OR REPLACE FUNCTION start_of_week()
RETURNS DATE AS $$
BEGIN
    RETURN (CURRENT_DATE - (EXTRACT(DOW FROM CURRENT_DATE)::INT + 6) % 7);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION weekday_date(day_offset INT)
RETURNS DATE AS $$
BEGIN
    RETURN start_of_week() + day_offset;
END;
$$ LANGUAGE plpgsql;

-- =========================================================
-- BASIS-TABELLEN
-- =========================================================

CREATE TABLE IF NOT EXISTS benutzer (
    benutzer_id UUID PRIMARY KEY,
    passwort VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    istadmin BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS klassen (
    klassen_id SERIAL PRIMARY KEY,
    klassenname VARCHAR(100) NOT NULL UNIQUE,
    ical_link VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS persoenliche_daten (
    benutzer_id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    vorname VARCHAR(100) NOT NULL,
    klassen_id INTEGER,
    CONSTRAINT fk_pers_benutzer FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id),
    CONSTRAINT fk_pers_klasse FOREIGN KEY (klassen_id) REFERENCES klassen(klassen_id)
);

-- =========================================================
-- DUMMY DATEN
-- =========================================================

INSERT INTO klassen (klassen_id, klassenname, ical_link)
VALUES (1, 'Dummyklasse', 'https://bibapp.pbd2h24asc.web.bib.de/empty.ics')
ON CONFLICT (klassen_id) DO NOTHING;

INSERT INTO benutzer (benutzer_id, passwort, email, istadmin)
VALUES ('550e8400-e29b-41d4-a716-446655440000', '$2y$10$8K1p/a0dIXM6lJzi10BGi.p6gqjNenE2no0RvrRZtGJPD7W82dMan', 'dummyuser@example.com', TRUE)
ON CONFLICT (benutzer_id) DO NOTHING;

-- =========================================================
-- STUNDENPLAN TABELLEN
-- =========================================================

CREATE TABLE IF NOT EXISTS dummyklasse_alter_stundenplan (
    termin_id UUID PRIMARY KEY,
    summary VARCHAR(255) NOT NULL,
    description TEXT,
    start_zeit TIMESTAMP NOT NULL,
    end_zeit TIMESTAMP NOT NULL,
    location VARCHAR(255),
    kategorie kalender_kategorie,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dummyklasse_neuer_stundenplan (
    termin_id UUID PRIMARY KEY,
    summary VARCHAR(255) NOT NULL,
    description TEXT,
    start_zeit TIMESTAMP NOT NULL,
    end_zeit TIMESTAMP NOT NULL,
    location VARCHAR(255),
    kategorie kalender_kategorie,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dummyklasse_aenderungen (
    termin_id UUID PRIMARY KEY,
    label aenderungs_label NOT NULL,
    summary_alt VARCHAR(255),
    description_alt TEXT,
    start_alt TIMESTAMP,
    end_alt TIMESTAMP,
    location_alt VARCHAR(255),
    kategorie_alt kalender_kategorie,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- TEST-DATEN
-- =========================================================

INSERT INTO dummyklasse_alter_stundenplan (termin_id, summary, description, start_zeit, end_zeit, location, kategorie)
VALUES
('11111111-1111-1111-1111-111111111111', 'Mathematik', 'Differentialrechnung', weekday_date(0) + TIME '08:00', weekday_date(0) + TIME '09:30', 'Raum B101', 'klausur')
ON CONFLICT DO NOTHING;

INSERT INTO dummyklasse_neuer_stundenplan (termin_id, summary, description, start_zeit, end_zeit, location, kategorie)
VALUES
('11111111-1111-1111-1111-111111111111', 'Mathematik', 'Integralrechnung', weekday_date(0) + TIME '08:00', weekday_date(0) + TIME '10:00', 'Raum B105', 'klausur'),
('33333333-3333-3333-3333-333333333333', 'Projektwoche', 'Kickoff', weekday_date(4) + TIME '09:00', weekday_date(4) + TIME '12:00', 'Audimax', 'eigenes-event')
ON CONFLICT DO NOTHING;

-- =========================================================
-- API VIEW
-- =========================================================

DROP VIEW IF EXISTS dummyklasse_calendar_api;
CREATE OR REPLACE VIEW dummyklasse_calendar_api AS
SELECT
    neu.termin_id AS id,
    neu.summary,
    neu.description,
    neu.start_zeit AS start,
    neu.end_zeit AS "end",
    neu.location,
    aend.label,
    neu.kategorie,
    json_build_object(
        'summary', aend.summary_alt,
        'start', aend.start_alt,
        'end', aend.end_alt,
        'location', aend.location_alt
    ) AS original_event,
    neu.updated_at AS updated_at
FROM dummyklasse_neuer_stundenplan neu
LEFT JOIN dummyklasse_aenderungen aend ON neu.termin_id = aend.termin_id;
