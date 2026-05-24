<?php

namespace App\DataFixtures;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Entity\PersoenlicheDaten;
use App\Entity\StundenplanNeu; // Importiere StundenplanNeu
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid; // Uuid wird weiterhin für andere Zwecke benötigt, z.B. für Benutzer-ID, falls manuell gesetzt

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Dummyklasse erstellen
        $dummyKlasse = new CalendarSource();
        $dummyKlasse->setClassName('Dummyklasse');
        $dummyKlasse->setIcalLink('https://bibapp.pbd2h24asc.web.bib.de/empty.ics');
        $manager->persist($dummyKlasse);

        // Dummyuser erstellen
        $dummyUser = new Benutzer();
        $dummyUser->setEmail('dummyuser@example.com');
        $dummyUser->setPassword($this->passwordHasher->hashPassword($dummyUser, 'password'));
        $dummyUser->setIsAdmin(true);
        $manager->persist($dummyUser);

        // Persönliche Daten für Dummyuser erstellen
        $persoenlicheDaten = new PersoenlicheDaten();
        $persoenlicheDaten->setBenutzer($dummyUser);
        $persoenlicheDaten->setName('Mustermann');
        $persoenlicheDaten->setVorname('Max');
        $persoenlicheDaten->setKlasse($dummyKlasse);
        $manager->persist($persoenlicheDaten);

        // --- Zusätzliche Testtermine für StundenplanNeu ---
        $klasseName = $dummyKlasse->getClassName();

        // Labels und Kategorien aus den ENUM-Definitionen
        $labels = ['gelöscht', 'neu', 'geändert'];
        $kategorien = ['klausur', 'bib-event', 'eigenes-event', 'unterricht', 'projekt', 'ferien', 'prüfung'];

        // Start der aktuellen Woche (Montag) berechnen
        $now = new \DateTimeImmutable();
        // Finde den letzten Montag (oder heute, wenn heute Montag ist)
        $startOfWeek = $now->modify('last monday');
        // Wenn heute Sonntag ist, wäre 'last monday' vor 7 Tagen. Wir wollen den Montag dieser Woche.
        if ($now->format('N') == 7) { // Sonntag ist 7
            $startOfWeek = $now->modify('monday this week');
        }
        // Wenn heute Montag ist, ist 'last monday' heute.
        // Wenn heute Dienstag-Samstag ist, ist 'last monday' der Montag dieser Woche.

        $dayOffset = 0; // Start am Montag

        // Termine für jede Kategorie
        foreach ($kategorien as $kategorie) {
            $event = new StundenplanNeu();
            // $event->setId(Uuid::v4()); // ENTFERNT: ID wird automatisch generiert
            $event->setSummary("Termin: " . ucfirst($kategorie));
            $event->setDescription("Beschreibung für " . $kategorie . " in der Dummyklasse.");
            $event->setStart($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(9, 0, 0));
            $event->setEnd($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(10, 0, 0));
            $event->setLocation("Raum " . (100 + $dayOffset));
            $event->setKategorie($kategorie);
            $event->setKlasse($klasseName);
            $manager->persist($event);

            $dayOffset++;
            if ($dayOffset > 4) { // Nur Montag bis Freitag
                $dayOffset = 0;
                $startOfWeek = $startOfWeek->modify('+1 week'); // Nächste Woche, falls mehr als 5 Kategorien
            }
        }

        // Termine für jedes Label (verwenden wir andere Tage oder Zeiten, um Kollisionen zu vermeiden)
        $dayOffset = 0; // Reset für Labels
        foreach ($labels as $label) {
            $event = new StundenplanNeu();
            // $event->setId(Uuid::v4()); // ENTFERNT: ID wird automatisch generiert
            $event->setSummary("Label: " . ucfirst($label));
            $event->setDescription("Beschreibung für Label " . $label . " in der Dummyklasse.");
            $event->setStart($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(11, 0, 0)); // Andere Uhrzeit
            $event->setEnd($startOfWeek->modify('+' . $dayOffset . ' days')->setTime(12, 0, 0));
            $event->setLocation("Labor " . (200 + $dayOffset));
            $event->setLabel($label);
            $event->setKlasse($klasseName);
            $manager->persist($event);

            $dayOffset++;
            if ($dayOffset > 4) { // Nur Montag bis Freitag
                $dayOffset = 0;
                $startOfWeek = $startOfWeek->modify('+1 week'); // Nächste Woche
            }
        }

        $manager->flush();
    }
}
