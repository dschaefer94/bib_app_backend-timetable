# Zusammenfassung der verwendeten Befehle

Diese Datei listet alle Befehle auf, die während der Fehlersuche und Behebung der Endlosschleife in der Testumgebung verwendet wurden.

---

## 1. PHPUnit Testbefehle

Befehle zum Ausführen von PHPUnit-Tests.

-   **`php bin/phpunit tests/MinimalTest.php`**
    -   Führt den spezifischen Test `MinimalTest.php` aus. Dies wurde verwendet, um den Kernel-Boot-Prozess zu isolieren.
-   **`php bin/phpunit --debug tests/MinimalTest.php`**
    -   Führt den spezifischen Test `MinimalTest.php` im Debug-Modus aus, um detailliertere Ausgaben von PHPUnit zu erhalten und den Zeitpunkt des Hängens genauer zu bestimmen.

---

## 2. Doctrine Datenbankbefehle

Befehle zur Verwaltung des Datenbankschemas und der Daten über Doctrine.

-   **`php bin/console doctrine:database:drop --env=test --force`**
    -   Löscht die Datenbank für die `test`-Umgebung. `--force` ist notwendig, um die Bestätigungsabfrage zu überspringen.
-   **`php bin/console doctrine:database:create --env=test`**
    -   Erstellt die Datenbank für die `test`-Umgebung.
-   **`php bin/console doctrine:migrations:migrate --env=test --no-interaction`**
    -   Führt ausstehende Datenbankmigrationen für die `test`-Umgebung aus. `--no-interaction` überspringt interaktive Abfragen.
-   **`php bin/console doctrine:fixtures:load --env=test --no-interaction`**
    -   Lädt die Daten-Fixtures in die Datenbank für die `test`-Umgebung. `--no-interaction` überspringt interaktive Abfragen.
-   **`php bin/console doctrine:migrations:version --add --all --env=test`**
    -   Markiert alle vorhandenen Migrationen als ausgeführt, ohne sie tatsächlich auszuführen. Nützlich, wenn das Datenbankschema bereits mit den Migrationen übereinstimmt, Doctrine dies aber nicht weiß.
-   **`php bin/console doctrine:schema:validate --env=test`**
    -   Überprüft, ob das aktuelle Datenbankschema mit den Entitäts-Mappings übereinstimmt. Gibt `[OK]` oder `[ERROR]` aus.
-   **`php bin/console doctrine:schema:create --env=test`**
    -   Erstellt das Datenbankschema direkt aus den Entitäten. **Vorsicht: Löscht keine bestehenden Tabellen.**
-   **`php bin/console doctrine:schema:update --dump-sql --env=test`**
    -   Zeigt das SQL an, das Doctrine ausführen würde, um das Datenbankschema mit den Entitäten zu synchronisieren, ohne es tatsächlich auszuführen.
-   **`php bin/console make:migration`**
    -   Generiert eine neue Migrationsdatei basierend auf den Unterschieden zwischen den Entitäten und dem aktuellen Datenbankschema.

---

## 3. Symfony Cache-Befehle

Befehle zur Verwaltung des Symfony-Caches.

-   **`php bin/console cache:clear --env=test`**
    -   Löscht den Symfony-Cache für die `test`-Umgebung. Dies ist oft notwendig, damit Änderungen an Konfigurationen oder Code wirksam werden.

---

## 4. Docker Compose Befehle

Befehle zur Verwaltung der Docker-Container.

-   **`docker-compose down`**
    -   Stoppt und entfernt die Container, die von `docker-compose.yaml` definiert sind. Behält Volumes bei.
-   **`docker-compose up -d`**
    -   Startet die Container im Hintergrund (`-d` für detached mode). Erstellt Container neu, falls notwendig.
-   **`docker-compose down --volumes`**
    -   Stoppt und entfernt die Container **und** die zugehörigen Volumes. Dies ist nützlich für einen wirklich sauberen Start der Datenbank.

---

## 5. Sonstige Befehle

-   **`php bin/console`**
    -   Der Einstiegspunkt für die Symfony Console-Anwendung, über die alle `doctrine:`- und `cache:`-Befehle ausgeführt werden.

---
