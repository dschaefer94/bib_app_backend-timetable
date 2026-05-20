<?php

namespace App\Infrastructure\Kalender;

/**
 * Adapter für das bestehende Kalender-System
 *
 * Dies ist eine Brücke zwischen dem DDD-System und dem bestehenden
 * Kalender-Code aus dem PHP/Laravel System.
 *
 * TODO: Integration mit SDP\Kalender\kalenderrunner.php
 */
class KalenderAdapter
{
    /**
     * Startet die Kalender-Update für eine Klasse
     *
     * @param string $klassenname
     * @param string $icalLink
     * @param \PDO $pdo
     * @throws \Exception
     */
    public static function updateCalendar(
        string $klassenname,
        string $icalLink,
        \PDO $pdo
    ): void {
        try {
            // TODO: kalenderrunner aufrufen
            // \SDP\Updater\kalenderupdater($klassenname, $pdo);

            // Oder:
            // $runner = new \SDP\Kalender\kalenderrunner();
            // $runner->run($icalLink, $klassenname);

            throw new \Exception('Kalender-Integration noch nicht implementiert', 500);
        } catch (\Exception $e) {
            throw new \Exception("Kalender konnte nicht aktualisiert werden: " . $e->getMessage(), 500);
        }
    }

    /**
     * Löscht die Kalender-Tabellen für eine Klasse
     */
    public static function deleteCalendarTables(
        string $klassenname,
        \PDO $pdo
    ): void {
        try {
            $tables = [
                "{$klassenname}_alter_stundenplan",
                "{$klassenname}_neuer_stundenplan",
                "{$klassenname}_aenderungen",
            ];

            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }
        } catch (\Exception $e) {
            throw new \Exception("Tabellen konnten nicht gelöscht werden: " . $e->getMessage(), 500);
        }
    }

    /**
     * Truncates die Änderungstabelle
     */
    public static function clearChanges(string $klassenname, \PDO $pdo): void
    {
        try {
            $table = "{$klassenname}_aenderungen";
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        } catch (\Exception $e) {
            throw new \Exception("Änderungstabelle konnte nicht geleert werden: " . $e->getMessage(), 500);
        }
    }
}

