# Tipps für effektives Debugging in Symfony (insbesondere bei Endlosschleifen)

Es ist extrem frustrierend, wenn man auf Probleme wie Endlosschleifen stößt, die keine klaren Fehlermeldungen liefern. Hier sind einige Strategien und Tipps, wie du als User (mit PHP-Grundkenntnissen, aber neu in Symfony) solche Probleme effektiver mit einem Agenten oder selbst debuggen kannst.

## 1. Kommunikation mit dem Agenten optimieren

*   **Sei explizit und präzise:** Beschreibe das Problem so genau wie möglich. "Endlosschleife" ist gut, aber "Endlosschleife, keine Ausgabe, keine Logs" ist besser.
*   **Gib alle relevanten Ausgaben:** Jede Fehlermeldung, jeder Stack Trace, jede Konsolenausgabe ist Gold wert. Auch wenn sie dir nicht sinnvoll erscheint, kann sie dem Agenten wichtige Hinweise geben.
*   **Berichte über *alle* bereits unternommenen Schritte:** Das spart Zeit und verhindert, dass der Agent bereits ausprobierte Lösungen vorschlägt.
*   **Stelle Fragen zur Strategie:** Wenn eine Debugging-Strategie nicht funktioniert, frage den Agenten explizit nach einer *alternativen* Strategie oder einer *anderen Herangehensweise*. (z.B. "Diese Methode funktioniert nicht, was ist der nächste Schritt, um das Problem zu isolieren?")
*   **Hinterfrage Annahmen:** Wenn du das Gefühl hast, der Agent macht eine Annahme, die nicht zutrifft (z.B. "die Logdatei existiert"), weise explizit darauf hin.
*   **Gib Kontext zu deiner Umgebung:** Betriebssystem (Windows/Linux/macOS), PHP-Version, Docker-Setup – all das kann relevant sein.
*   **Wenn ein Tool nicht funktioniert:** Wenn ein Befehl (z.B. `rmdir` in PowerShell) nicht wie erwartet funktioniert, gib die genaue Fehlermeldung und dein Terminal an.

## 2. Symfony/PHP Debugging-Grundlagen (wenn keine Logs verfügbar sind)

Das größte Problem war hier die fehlende Fehlermeldung. Wenn `var/log/dev.log` leer bleibt und `display_errors = On` im Browser nichts anzeigt, sind das die ersten Probleme, die gelöst werden müssen.

*   **`php --ini` und `php -v`:** Überprüfe immer, welche `php.ini` tatsächlich geladen wird und welche PHP-Version aktiv ist.
*   **`display_errors = On` in `php.ini`:** Stelle sicher, dass diese Einstellung in der *korrekten* `php.ini` aktiv ist.
*   **`APP_DEBUG=1` und `APP_ENV=dev`:** Diese Umgebungsvariablen in deiner `.env` (oder `.env.test` für Tests) sind entscheidend für Symfony's Debug-Modus.
*   **Dateiberechtigungen für `var/cache` und `var/log`:** Unter Windows sind dies häufige Fehlerquellen. Dein Benutzerkonto muss Schreibrechte für diese Verzeichnisse haben. Wenn du sie nicht über die GUI setzen kannst, versuche es temporär als Administrator oder über `icacls` in der Kommandozeile.
*   **`register_shutdown_function`:** Wie wir gesehen haben, kann dies in `tests/bootstrap.php` oder einem einfachen Skript helfen, fatale Fehler abzufangen, die sonst verschluckt werden.
*   **`echo "Debug Point X"; die();`:** Die "Brute-Force"-Methode, um den genauen Punkt zu finden, an dem ein Skript abstürzt oder hängen bleibt. Beginne früh im Lebenszyklus und verschiebe die `die()`-Anweisung schrittweise.

## 3. Isolationsstrategien bei Kernel-Boot-Problemen (Endlosschleifen)

Wenn der Kernel nicht bootet (egal ob im Web, Command oder Test), ist das Problem oft eine zirkuläre Abhängigkeit oder ein Fehler beim Laden von Metadaten.

*   **Bundle-Isolation (Der wichtigste Tipp!):**
    *   Wenn der Kernel hängt und du keine klare Fehlermeldung hast, **deaktiviere alle nicht-essentiellen Bundles in `config/bundles.php`**.
    *   Aktiviere sie dann **eins nach dem anderen** wieder und teste nach jeder Aktivierung, ob der Fehler zurückkehrt.
    *   Dies hilft, das problematische Bundle zu isolieren. In unserem Fall war es das `OpenApi` Bundle.

*   **Service-Container-Isolation:**
    *   Wenn ein Bundle identifiziert wurde, das den Fehler verursacht, schau in dessen `DependencyInjection` Extension (`Extension.php`) und den `Resources/config/services.yaml` Dateien.
    *   **Kommentiere Service-Definitionen schrittweise aus** oder füge `exclude` Pfade in `config/services.yaml` hinzu, um zu verhindern, dass problematische Klassen autogewired werden.

*   **Doctrine-Metadaten-Isolation:**
    *   **`auto_mapping: false` in `config/packages/doctrine.yaml`:** Deaktiviert das automatische Laden aller Entitäten. Wenn der Kernel dann bootet, liegt das Problem in einer Entität.
    *   **Schrittweises Auskommentieren von Entitäten:** Wenn `auto_mapping: false` hilft, kommentiere Entitäten in `src/Entity/` schrittweise aus, um die problematische Entität zu finden.

*   **`composer dump-autoload` und `composer clear-cache`:** Immer wieder ausführen, wenn Autoloading-Probleme oder hartnäckige Cache-Probleme vermutet werden.

## 4. Testumgebung "Hard Reset"

Besonders in Testumgebungen, wo die Datenbank oft neu erstellt wird, ist ein "Hard Reset" unerlässlich.

*   **`docker volume rm <volume_name>`:** Löscht den persistenten Speicher der Datenbank, um sicherzustellen, dass die `init.sql` beim nächsten Start ausgeführt wird.
*   **`doctrine:database:drop --force --env=test`:** Löscht die Testdatenbank.
*   **`doctrine:database:create --env=test`:** Erstellt die Testdatenbank neu.
*   **`doctrine:migrations:migrate --env=test`:** Wendet alle Migrationen an.
*   **`doctrine:fixtures:load --env=test`:** Lädt die Testdaten.
*   **`CREATE TYPE` in erster Migration:** Für PostgreSQL ENUMs ist es in der Testumgebung oft am zuverlässigsten, die `CREATE TYPE`-Anweisungen direkt in die erste Migration einzufügen, da die `init.sql` nicht immer greift.

## 5. Den Kontext wechseln (wie mit dem `boot_kernel.php` Skript)

Wenn ein Tool (wie PHPUnit) selbst das Problem zu sein scheint oder die Fehlermeldungen verschluckt:

*   **Isoliere den problematischen Teil:** Wenn PHPUnit hängt, aber der Kernel booten kann (wie mit `boot_kernel.php`), dann liegt das Problem in der PHPUnit-Integration oder einer spezifischen Testkonfiguration.
*   **Verwende Symfony Commands:** Ein einfacher Symfony Command kann oft die gleiche Logik wie ein Controller oder ein Service ausführen und liefert dabei klarere Konsolenausgaben und Fehlermeldungen.

Indem du diese Strategien anwendest und dem Agenten präzise Informationen gibst, kannst du solche komplexen Debugging-Szenarien in Zukunft effizienter lösen. Der Schlüssel ist, das Problem schrittweise zu isolieren und so viele Informationen wie möglich zu extrahieren, auch wenn das System sich weigert, sie zu geben.
