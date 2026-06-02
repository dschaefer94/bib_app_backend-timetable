<?php

namespace App\DataFixtures;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Entity\PersoenlicheDaten;
use App\Entity\StundenplanNeu;
use App\Entity\AenderungsLabel; // Importiere die neue Entität
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class AppFixtures extends Fixture
{
    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Dummyklasse erstellen
        $dummyKlasse = new CalendarSource();
        $dummyKlasse->setClassName('Dummyklasse');
        //TODO: eigene api auf Plesk oder localhost anlegen, Link fehlerhaft
        $dummyKlasse->setIcalLink('https://bibapp.pbd2h24asc.web.bib.de/empty.ics');
        $manager->persist($dummyKlasse);

        // Dummyuser erstellen
        $dummyUser = new Benutzer();
        $dummyUser->setEmail('dummyuser@example.com');
        // Set a dummy identity_id for OIDC integration
        $dummyUser->setIdentityId('dummy-oidc-id-12345'); // Added this line
        // Removed setPassword as user management is now OIDC
        $dummyUser->setIsAdmin(true);
        $manager->persist($dummyUser);

        // Persönliche Daten für Dummyuser erstellen
        $persoenlicheDaten = new PersoenlicheDaten();
        $persoenlicheDaten->setBenutzer($dummyUser); // Setzt die owning side
        $persoenlicheDaten->setName('Mustermann');
        $persoenlicheDaten->setVorname('Max');
        $persoenlicheDaten->setKlasse($dummyKlasse);
        $manager->persist($persoenlicheDaten);

        // WICHTIG: Setze die inverse Seite der OneToOne-Beziehung
        $dummyUser->setPersoenlicheDaten($persoenlicheDaten);
        // Doctrine wird dies beim flush erkennen und die Beziehung korrekt speichern.

        // --- AenderungsLabel Entitäten erstellen ---
        $aenderungsLabels = [];
        foreach (['gelöscht', 'neu', 'geändert'] as $labelName) {
            $aenderungsLabel = new AenderungsLabel();
            $aenderungsLabel->setName($labelName);
            $manager->persist($aenderungsLabel);
            $aenderungsLabels[$labelName] = $aenderungsLabel;
        }

        // --- Zusätzliche Testtermine für StundenplanNeu ---
        $klasseName = $dummyKlasse->getClassName();

        // Kategorien (bleiben Strings für StundenplanNeu)
        $kategorien = ['klausur', 'bib-event', 'eigenes-event', 'unterricht', 'projekt', 'ferien', 'prüfung'];

        // Start der aktuellen Woche (Montag) berechnen
        $now = new \DateTimeImmutable();
        $startOfWeek = $now->modify('last monday');
        if ($now->format('N') == 7) {
            $startOfWeek = $now->modify('monday this week');
        }

        $dayOffset = 0;

        // Termine für jede Kategorie (StundenplanNeu)
        foreach ($kategorien as $kategorie) {
            $event = new StundenplanNeu();
            $event->setSummary("Termin: " . ucfirst($kategorie));
            $event->setDescription("Beschreibung für " . $kategorie . " in der Dummyklasse.");
            $event->setStart($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(9, 0, 0));
            $event->setEnd($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(10, 0, 0));
            $event->setLocation("Raum " . (100 + $dayOffset));
            $event->setKategorie($kategorie); // Kategorie bleibt String
            $event->setKlasse($klasseName);
            $manager->persist($event);

            $dayOffset++;
            if ($dayOffset > 4) {
                $dayOffset = 0;
                $startOfWeek = $startOfWeek->modify('+1 week');
            }
        }

        // Termine für jedes Label (StundenplanNeu) - hier wird weiterhin der String-Label gesetzt
        // Wenn GeaenderteTermine hier erstellt würden, würden wir $aenderungsLabels verwenden
        $dayOffset = 0;
        foreach (['gelöscht', 'neu', 'geändert'] as $label) { // Labels bleiben Strings für StundenplanNeu
            $event = new StundenplanNeu();
            $event->setSummary("Label: " . ucfirst($label));
            $event->setDescription("Beschreibung für Label " . $label . " in der Dummyklasse.");
            $event->setStart($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(11, 0, 0));
            $event->setEnd($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(12, 0, 0));
            $event->setLocation("Labor " . (200 + $dayOffset));
            $event->setLabel($label); // Label bleibt String
            $event->setKlasse($klasseName);
            $manager->persist($event);

            $dayOffset++;
            if ($dayOffset > 4) {
                $dayOffset = 0;
                $startOfWeek = $startOfWeek->modify('+1 week');
            }
        }

        $manager->flush();
    }
}
