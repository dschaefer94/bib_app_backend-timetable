# `DatabaseIntegrationTest.php` - Detailed Explanation

This integration test file (`DatabaseIntegrationTest.php`) is designed to verify that your Doctrine entities (`Benutzer`, `PersoenlicheDaten`, `CalendarSource`, `StundenplanNeu`) can be correctly persisted to and retrieved from a real database, and that your `AppFixtures` are loading the expected data. It essentially checks the "integration" between your application's entities, Doctrine, and the database.

## How the Test Works (Step-by-Step)

### `setUp(): void`

This method runs before *each* test function (`testDummyUserAndPersonalDataAreLoaded` and `testStundenplanNeuEntriesAreLoaded`).

1.  **`self::bootKernel();`**: It boots the Symfony kernel, which initializes the entire application environment, including the Dependency Injection Container and Doctrine.
2.  **`$container = self::$kernel->getContainer();`**: It gets the application's service container.
3.  **`$this->entityManager = $container->get('doctrine')->getManager();`**: It retrieves the Doctrine Entity Manager from the container. This is the main interface for interacting with your database.
4.  **`$this->dropAndCreateSchema();`**: This is a crucial step for integration tests. It ensures a clean database state for every test.
5.  **`$this->loadFixtures();`**: After the schema is ready, it loads your application's data fixtures.

### `tearDown(): void`

This method runs after *each* test function.

1.  **`parent::tearDown();`**: Calls the parent `tearDown` method.
2.  **`if ($this->entityManager) { ... }`**: It closes the Doctrine Entity Manager to prevent memory leaks, especially important when running many tests.

### `dropAndCreateSchema(): void`

This private helper method is responsible for preparing the database schema.

1.  **`$metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();`**: It fetches all known Doctrine entity metadata (information about your `Benutzer`, `CalendarSource`, etc., entities and their database mappings).
2.  **`$schemaTool = new SchemaTool($this->entityManager);`**: It creates a `SchemaTool` instance, which is a Doctrine utility for managing database schemas.
3.  **`$schemaTool->dropSchema($metadatas);`**: It drops all tables in the database that correspond to your entities. This ensures that the database is completely empty and clean before each test.
4.  **`$schemaTool->createSchema($metadatas);`**: It then creates all tables in the database based on your current entity definitions. This guarantees that the database schema perfectly matches your code, including any recent changes like the `identityId` column.

### `loadFixtures(): void`

This private helper method populates the database with test data.

1.  **`$purger = new ORMPurger($this->entityManager);`**: Creates a purger that will clear the database before loading fixtures.
2.  **`$executor = new ORMExecutor($this->entityManager, $purger);`**: Creates an executor to run the fixtures.
3.  **`$executor->execute([new AppFixtures()], true);`**: It instantiates and executes your `AppFixtures`. This means all the `CalendarSource`, `Benutzer`, `PersoenlicheDaten`, and `StundenplanNeu` entities defined in `AppFixtures.php` are created and persisted to the database.

---

### `testDummyUserAndPersonalDataAreLoaded(): void`

**What it tests:** This test verifies that the dummy user and their associated personal data (including their class) are correctly loaded into the database by the `AppFixtures` and can be retrieved.

**Steps:**

1.  **`$user = $this->entityManager->getRepository(Benutzer::class)->findOneBy(['email' => 'dummyuser@example.com']);`**: It tries to find the `Benutzer` entity with the email `dummyuser@example.com`. We find by email because the UUID is generated dynamically by Doctrine, but the email is a fixed value in your `AppFixtures`.
2.  **`$this->assertNotNull($user, 'Dummy user should be found.');`**: Asserts that a user was indeed found.
3.  **`$this->assertEquals('dummyuser@example.com', $user->getEmail());`**: Asserts that the found user has the expected email address.
4.  **`$persoenlicheDaten = $this->entityManager->getRepository(PersoenlicheDaten::class)->findOneBy(['benutzer' => $user]);`**: It then tries to find the `PersoenlicheDaten` entity associated with the found dummy user.
5.  **`$this->assertNotNull($persoenlicheDaten, 'Personal data for dummy user should be found.');`**: Asserts that personal data was found.
6.  **`$this->assertEquals('Max', $persoenlicheDaten->getVorname());`**: Asserts the first name.
7.  **`$this->assertEquals('Mustermann', $persoenlicheDaten->getName());`**: Asserts the last name.
8.  **`$calendarSource = $persoenlicheDaten->getKlasse();`**: Retrieves the `CalendarSource` (class) associated with the personal data.
9.  **`$this->assertNotNull($calendarSource, 'Calendar source should be associated with personal data.');`**: Asserts that a calendar source is linked.
10. **`$this->assertEquals('Dummyklasse', $calendarSource->getClassName());`**: Asserts the class name.

**Expected Outcome:** All these assertions should pass, confirming that the `Benutzer`, `PersoenlicheDaten`, and `CalendarSource` entities are correctly created, linked, and retrievable from the database as defined in your fixtures.

---

### `testStundenplanNeuEntriesAreLoaded(): void`

**What it tests:** This test verifies that the `StundenplanNeu` entries (timetable events) for the 'Dummyklasse' are correctly loaded into the database by the `AppFixtures` and can be retrieved.

**Steps:**

1.  **`$klasseName = 'Dummyklasse';`**: Defines the class name to search for.
2.  **`$calendarSource = $this->entityManager->getRepository(CalendarSource::class)->findOneBy(['className' => $klasseName]);`**: Finds the `CalendarSource` entity for 'Dummyklasse'.
3.  **`$this->assertNotNull($calendarSource, 'Calendar source for Dummyklasse should be found.');`**: Asserts that the class was found.
4.  **`$entries = $this->entityManager->getRepository(StundenplanNeu::class)->findBy(['klasse' => $klasseName]);`**: Retrieves all `StundenplanNeu` entries associated with 'Dummyklasse'.
5.  **`$this->assertGreaterThan(0, count($entries), 'At least one StundenplanNeu entry should be found.');`**: Asserts that at least one timetable entry was found for that class.
6.  **`foreach ($entries as $entry) { ... }`**: It then iterates through each found entry and performs several assertions:
    *   **`$this->assertInstanceOf(StundenplanNeu::class, $entry);`**: Checks if the retrieved object is an instance of `StundenplanNeu`.
    *   **`$this->assertNotNull($entry->getId(), 'StundenplanNeu entry should have an ID.');`**: Verifies that each entry has an ID (meaning it was successfully persisted).
    *   **`$this->assertNotEmpty($entry->getSummary(), 'StundenplanNeu entry should have a summary.');`**: Checks if the summary is not empty.
    *   **`$this->assertEquals($klasseName, $entry->getKlasse(), 'StundenplanNeu entry should belong to Dummyklasse.');`**: Confirms the entry belongs to the correct class.
    *   **`$this->assertInstanceOf(\DateTimeImmutable::class, $entry->getStart(), ...);`**: Checks if the start and end dates are `DateTimeImmutable` objects.

**Expected Outcome:** All these assertions should pass, confirming that the `StundenplanNeu` entities are correctly created and retrievable from the database for the specified class.

---

## Which Database is Used for Integration Tests?

This is a very important question! **Integration tests should always run against a separate test database, not your development or production database.**

### How it's Controlled in Symfony/PHPUnit:

1.  **`phpunit.xml.dist` (or `phpunit.xml`)**: Your `phpunit.xml.dist` file (or a local `phpunit.xml` if you've created one) is configured to set the `APP_ENV` environment variable to `test` when running tests. You can usually find a line like this:

    ```xml
    <php>
        <env name="APP_ENV" value="test"/>
        <env name="APP_DEBUG" value="1"/>
        <!-- ... other env variables ... -->
    </php>
    ```

2.  **`.env.test` (or `.env.test.local`)**: When `APP_ENV` is set to `test`, Symfony automatically loads environment variables from the `.env.test` file (if it exists) instead of `.env`. This file is where you define the database connection for your test environment.

    For example, your `.env.test` might look something like this:

    ```
    # .env.test
    DATABASE_URL="postgresql://user:password@127.0.0.1:5432/pbd2h24asc_stundenplan_test_db?serverVersion=16&charset=utf8"
    ```
    Notice the database name (`pbd2h24asc_stundenplan_test_db`) is different from your development database.

### Why a Separate Test Database?

*   **Isolation**: Each test (or test suite) can run independently without affecting other tests or your actual application data.
*   **Reproducibility**: Tests can be run repeatedly, always starting from a known, clean state, ensuring consistent results.
*   **Safety**: You can drop and recreate the test database as many times as needed without fear of losing valuable development or production data.
*   **Speed**: Often, test databases are in-memory (like SQLite) or on a local, fast server, making test execution quicker.

### In your case:

The `dropAndCreateSchema()` method in `DatabaseIntegrationTest.php` is specifically designed to work with this test database. It ensures that for every test run, the `pbd2h24asc_stundenplan_test_db` (or whatever your `DATABASE_URL` in `.env.test` points to) is completely wiped clean and then rebuilt according to your current entity definitions, before your fixtures populate it with test data. This is the standard and recommended practice for Doctrine integration tests in Symfony.
