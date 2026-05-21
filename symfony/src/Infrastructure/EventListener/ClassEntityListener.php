<?php

namespace App\Infrastructure\EventListener;

use App\Domain\Class\Entity\ClassEntity;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Event Listener für Klasse-Entity
 * Wird nach Persistierung aufgerufen um Kalender zu aktualisieren
 */
#[AsEntityListener(event: 'postPersist', entity: ClassEntity::class)]
#[AsEntityListener(event: 'postUpdate', entity: ClassEntity::class)]
class ClassEntityListener
{
    /**
     * Wird nach Einfügen einer neuen Klasse aufgerufen
     * TODO: kalenderupdater aufrufen und Tabellen initialisieren
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ClassEntity) {
            return;
        }

        // TODO: kalenderupdater aufrufen
        // $this->kalenderUpdater->updateCalendar($entity->getKlassenname(), $entity->getIcalLink());
    }

    /**
     * Wird nach Update einer Klasse aufgerufen
     * TODO: kalenderupdater aufrufen wenn ical_link sich geändert hat
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ClassEntity) {
            return;
        }

        $changeSet = $args->getEntityChangeSet();

        // Nur wenn ical_link geändert wurde
        if (isset($changeSet['icalLink'])) {
            // TODO: kalenderupdater aufrufen
            // TODO: aenderungen-Tabelle leeren
        }
    }
}

