# Troubleshooting Findings: Infinite Loop in Symfony/Doctrine Tests

## Problem
Tests enter an infinite loop when running `php bin/phpunit tests/MinimalTest.php`. The `MinimalTest` only calls `self::bootKernel()`, indicating the issue occurs during kernel initialization.

## Initial Analysis & Steps Taken

1.  **Environment Variables & Bootstrap:**
    *   `tests/bootstrap.php` was adjusted to correctly load `.env.test`.
    *   `DATABASE_URL` in `phpunit.xml.dist` and `.env.test` was corrected to `serverVersion=16` to match the `postgres:16-alpine` image used in `docker-compose.yaml`.
    *   The database password `!ChangeMe!` was verified in all configurations.

2.  **Database Initialization:**
    *   `docker-compose down` and `up -d` were executed to ensure a fresh Docker environment.
    *   The test database was successfully dropped, created, migrated, and fixtures loaded using:
        ```bash
        php bin/console doctrine:database:drop --env=test --force
        php bin/console doctrine:database:create --env=test
        php bin/console doctrine:migrations:migrate --env=test --no-interaction
        php bin/console doctrine:fixtures:load --env=test --no-interaction
        ```
    *   The `init.sql` file was modified to comment out the `CREATE TYPE` statements for `aenderungs_label` and `kalender_kategorie` to avoid conflicts with Doctrine Migrations.

3.  **Bundle Isolation (Doctrine):**
    *   Temporarily commenting out `Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle` in `config/bundles.php` resulted in a `LoaderLoadException` (expected, as `doctrine_migrations.yaml` was still loaded). This confirmed the infinite loop was gone when the bundle was disabled.
    *   Temporarily commenting out `Doctrine\Bundle\DoctrineBundle\DoctrineBundle` (and `DoctrineMigrationsBundle`) in `config/bundles.php` also resulted in a `LoaderLoadException` for "doctrine" configuration (expected). This further confirmed the infinite loop was gone when Doctrine was disabled.
    *   This strongly indicated the problem lay within Doctrine's initialization process.

4.  **Custom DBAL Types & Platform Issue:**
    *   The `src/Kernel.php` registered custom DBAL ENUM types: `AenderungsLabelEnumType` and `KalenderKategorieEnumType`.
    *   Initially, `doctrine:schema:validate` failed with `Unknown database type "aenderungs_label" requested, Doctrine\DBAL\Platforms\PostgreSQL120Platform may not support it.`.
    *   This was addressed by explicitly setting `server_version: '16'` in `config/packages/doctrine.yaml` and removing `serverVersion` from `DATABASE_URL` in `.env.test` and `phpunit.xml.dist`.
    *   After these changes, `doctrine:schema:validate` reported `[OK] The mapping files are correct.` and `[ERROR] The database schema is not in sync with the current mapping file.`, confirming the platform issue was resolved and the kernel booted far enough for schema validation.

5.  **Service Autowiring Issue (DBAL Types):**
    *   After resolving the Doctrine platform issue, the `MinimalTest` still resulted in an infinite loop, but with a new error: `Expected to find class "App\DBAL\Types\KalenderKategorieEnumType" ... but it was not found! Check ... config/services.yaml`.
    *   This was due to `../src/DBAL/` being included in service autowiring, attempting to load a class that had been deleted.
    *   This was fixed by adding `../src/DBAL/` to the `exclude` list in `config/services.yaml`.

6.  **OpenAPI Bundle Issue - Detailed Debugging:**
    *   After fixing the DBAL autowiring, the `MinimalTest` still resulted in an infinite loop, triggered by a PHP Deprecation warning in `App\OpenApi\Service\TypeMismatchException.php`.
    *   The `App\OpenApi\OpenAPIServerBundle` was temporarily deactivated in `config/bundles.php`.
    *   However, the `TypeMismatchException` was still being loaded due to `../src/OpenApi/` being included in service autowiring.
    *   This was fixed by adding `../src/OpenApi/` to the `exclude` list in `config/services.yaml` and commenting out the OpenAPI service definitions.
    *   The `TypeMismatchException` deprecation (`Implicitly marking parameter $context as nullable is deprecated`) was addressed by explicitly making the parameter nullable in `src/OpenApi/Service/TypeMismatchException.php`.
    *   **Reactivating the `OpenApi` bundle and its services in `config/bundles.yaml` and `config/services.yaml` consistently brought back the infinite loop.**
    *   **Debugging `OpenAPIServerBundle.php`:**
        *   Adding `echo` statements to `OpenAPIServerBundle::build()` and `OpenAPIServerApiPass::process()` showed that the infinite loop occurred *before* these methods were fully executed.
        *   Adding a custom `__construct()` to `OpenAPIServerBundle` resulted in a `Cannot call constructor` error during `parent::__construct()`, indicating a conflict with Symfony's bundle instantiation.
        *   Removing the custom constructor and reducing `OpenAPIServerBundle` to its absolute minimum (empty class extending `Bundle`) still resulted in an infinite loop when the bundle was active. This indicated the problem was triggered simply by the bundle's presence in `config/bundles.php`.
    *   **Debugging `OpenAPIServerExtension.php`:**
        *   Adding `echo` statements to `OpenAPIServerExtension::load()` showed that the infinite loop occurred *before* this method was fully executed.
        *   Emptying `OpenAPIServerExtension::load()` and `getAlias()` led to a `ServiceNotFoundException` for `open_api_server.service.validator` and a warning about `getAlias()`. This confirmed the extension was being loaded.
        *   Corrected `getAlias()` to return `open_api_server`.
        *   Restored `OpenAPIServerExtension::load()` to load `services.yaml`. This again brought back the infinite loop.
    *   **Conclusion:** The infinite loop is deeply rooted in the initialization of the `App\OpenApi\OpenAPIServerBundle`, likely within its `DependencyInjection` components or the services it attempts to load from `Resources/config/services.yaml`, even with minimal code in the bundle class itself.

8.  **PHPUnit XML Configuration Warnings:**
    *   Warnings like `Element 'coverage', attribute 'processUncoveredFiles': The attribute 'processUncoveredFiles' is not allowed.` and `Element 'extension': This element is not expected.` were present.
    *   The `phpunit.xml.dist` was updated to use `<source>` instead of `<coverage>` and `<extensions>` instead of `<listeners>`, and the `xsi:noNamespaceSchemaLocation` was updated to `11.0/phpunit.xsd`.
    *   The `SYMFONY_PHPUNIT_VERSION` was removed from the `<php>` section.
    *   Finally, the entire `<extensions>` block was commented out in `phpunit.xml.dist` to resolve the last remaining PHPUnit XML validation warning.

## Current State
The `MinimalTest` and `CalendarApiServiceTest` now run successfully with `PHPUnit Finished (Shell Exit Code: 0)`. The infinite loop is resolved, and all PHPUnit XML validation warnings are gone. The `App\OpenApi\OpenAPIServerBundle` is currently deactivated by commenting it out in `config/bundles.php` and excluding `../src/OpenApi/` in `config/services.yaml`.

## Next Steps
1.  **Datenbankschema synchronisieren:** The database schema is now in sync with the mapping files.
2.  **Fixtures anpassen:** `AppFixtures` have been updated to use the new `AenderungsLabel` entity.
3.  **OpenAPI-Spezifikation aktualisieren:** The `openapi.yaml` has been updated to reflect the `changeType` as an integer.
4.  **`App\OpenApi\OpenAPIServerBundle` debuggen (später):** The root cause of the infinite loop within the `OpenApi` bundle needs to be addressed in a separate debugging effort, as it prevents the kernel from booting when active. For now, it remains excluded to allow other development and testing.
5.  **Integrationstests für Datenbankverbindung und Datenverkehr:** Implementierung und Fehlerbehebung der `DatabaseIntegrationTest`s, die die Verbindung zur Datenbank und den Datenabruf bis zu den Entitäten validieren.
