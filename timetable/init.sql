-- =========================================================
-- DATABASE SETUP
-- =========================================================
-- Ensure uuid-ossp extension is available for UUID generation
-- This is crucial for Symfony's UuidGenerator to work with PostgreSQL
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =========================================================
-- ENUMS
-- =========================================================

-- Temporarily commented out to avoid conflicts with Doctrine Migrations
-- DO $$ BEGIN
--     IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'aenderungs_label') THEN
--         CREATE TYPE aenderungs_label AS ENUM ('gelöscht', 'neu', 'geändert');
--     END IF;
--     IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'kalender_kategorie') THEN
--         CREATE TYPE kalender_kategorie AS ENUM ('klausur', 'bib-event', 'eigenes-event', 'unterricht', 'projekt', 'ferien', 'prüfung');
--     END IF;
-- END $$;

-- =========================================================
-- WEEK HELPERS
-- =========================================================
-- These functions are general database utilities and can remain here.

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
-- BASIS-TABELLEN (Schema now managed by Doctrine Migrations)
-- =========================================================
-- The actual CREATE TABLE statements for 'benutzer', 'klassen', 'persoenliche_daten',
-- 'stundenplan_neu', 'stundenplan_alt', 'geaenderte_termine'
-- will be generated and applied by Doctrine Migrations.

-- =========================================================
-- DUMMY DATEN (Removed from init.sql - now managed by Doctrine Fixtures)
-- =========================================================

-- STUNDENPLAN TABELLEN (Removed - now managed by Doctrine Migrations)
-- TEST-DATEN (Removed - now managed by Doctrine Migrations or Fixtures)
-- API VIEW (Removed - now managed by Doctrine Entities and custom logic)
