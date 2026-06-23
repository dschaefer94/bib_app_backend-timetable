# AGENTS.md: AI Agent Guide for Timetable Backend

This is a Symfony 7.4 REST API backend for a school timetable/calendar management system. The codebase follows an **API-First architecture** with OpenAPI 3.0 specifications driving implementation.

## Quick Start for AI Agents

### Essential Commands
```bash
# Start development environment (clean DB restart)
docker-compose down -v && docker-compose up -d

# Database initialization & migrations
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load  # Loads AppFixtures (test data)

# Testing
php bin/phpunit tests/
php bin/phpunit tests/MinimalTest.php  # Bootstrap only test

# OpenAPI code generation (when openapi.yaml changes)
./openapi_generate.ps1
```

### Project Structure
```
timetable/
├── src/
│   ├── Entity/          # Doctrine ORM entities (Benutzer, PersoenlicheDaten, StundenplanNeu, etc.)
│   ├── Repository/      # Doctrine repositories for data access
│   ├── Api/             # Business logic services (CalendarApiService, CalendarSyncService)
│   ├── Service/         # Domain services
│   ├── Controller/      # Symfony controllers (manual + auto-generated from OpenAPI)
│   ├── OpenApi/         # Auto-generated from openapi.yaml (ignore most changes here)
│   ├── Security/        # JWT authentication handlers
│   ├── Scheduler/       # Messenger tasks for background jobs
│   ├── DBAL/            # Custom Doctrine types
│   └── Kernel.php
├── config/
│   ├── routes.yaml      # Route configuration (both manual + openapi_controllers)
│   ├── services.yaml    # DI service configuration (excludes OpenApi bundle)
│   ├── bundles.php      # Symfony bundles
│   └── jwt/             # JWT key pair for Keycloak integration
├── migrations/          # Doctrine migration files
├── tests/
│   ├── Integration/     # Database integration tests
│   ├── MinimalTest.php  # Kernel bootstrap verification
│   └── bootstrap.php
├── openapi.yaml         # API specification (single source of truth)
├── openapi_generate.ps1 # Script to regenerate OpenAPI controllers from spec
└── docker-compose.yaml  # Multi-service setup (PHP, PostgreSQL, Keycloak)
```

## Architecture Decisions

### API-First Design
- **openapi.yaml** is the single source of truth for the API contract
- Running `openapi_generate.ps1` auto-generates controllers and models in `src/OpenApi/`
- **WARNING**: Do NOT manually edit generated OpenApi code; edit `openapi.yaml` instead
- Manual controllers in `src/Controller/` override or complement generated ones

### Authentication & Authorization
- **JWT-based** authentication via Keycloak/external Identity Provider
- `Benutzer` entity uses `identityId` field (maps to JWT `sub` claim), not passwords
- Security filters validate Bearer tokens and populate `$this->getUser()` in controllers
- Route `#[IsGranted('IS_AUTHENTICATED_FULLY')]` ensures authenticated-only endpoints
- See `config/jwt/` for key pairs

### Database Design (PostgreSQL 16)
- **UUID primary keys** on all entities: `$id` generated via `Symfony\Component\Uid\Uuid`
- **Entity-Relationship Model**:
  - `Benutzer` (1:1) → `PersoenlicheDaten` (includes Klasse reference)
  - `PersoenlicheDaten` (N:1) → `CalendarSource` (Klasse entity)
  - `CalendarSource` (1:N) → `StundenplanNeu` (timetable entries per class)
  - `StundenplanNeu` can have optional `AenderungsLabel` (change notes)
- Migrations auto-generated via `make:migration`, applied with `migrations:migrate`
- Test data loaded from `src/DataFixtures/AppFixtures.php`

### Service Layer Pattern
```
Request → Controller → Service → Repository → Entity → Database
                ↓           ↓
         (HTTP response)  (Business logic)
```
- Controllers delegate business logic to Services (e.g., `CalendarApiService`)
- Services interact with repositories for data access
- JMS Serializer converts entities to JSON (`@JMS\Serializer\Annotation\`)

### Response Format (Problem+JSON)
All API responses follow RFC 7807 Problem Details:
```json
{
  "type": "/problems/calendar-not-found",
  "title": "Calendar not found",
  "status": 404,
  "detail": "No calendar for this user.",
  "instance": "/api/calendar#error"
}
```

## Critical Developer Workflows

### Adding a New API Endpoint
1. Edit `openapi.yaml`: add path, parameters, response schema
2. Run `./openapi_generate.ps1` to regenerate controllers/models
3. Implement business logic in a Service class
4. Wire service into generated controller or create manual override
5. Add integration tests in `tests/Integration/`

### Database Schema Changes
1. Modify entity in `src/Entity/EntityName.php`
2. Run `php bin/console make:migration`
3. Review generated migration in `migrations/`
4. Run `php bin/console doctrine:migrations:migrate`
5. If testing locally, clean DB first: `docker-compose down -v && docker-compose up -d`

### Testing Integration
1. Integration tests use `DatabaseIntegrationTest` base class pattern
2. Each test gets fresh DB via `dropAndCreateSchema()` + `loadFixtures()`
3. Common setup: `$this->entityManager->getRepository(Entity::class)->findBy(...)`
4. Run: `php bin/phpunit tests/Integration/` or `php bin/phpunit tests/MinimalTest.php`

### Docker Development Workflow
- **Start fresh**: `docker-compose down -v && docker-compose up -d`
  - Removes old database, starts PHP (8000), PostgreSQL (5432), Keycloak (8080)
  - Keycloak admin: `admin:admin` @ http://localhost:8080
- **View logs**: `docker-compose logs -f php` or `docker-compose logs -f database`
- **Execute in container**: `docker exec -it timetable-php-1 php bin/console ...`

## Important Codebase Patterns

### Entity Mapping
- All entities use Doctrine attributes: `#[ORM\Entity]`, `#[ORM\Column]`, etc.
- No XML/YAML mapping files
- Relationships: `#[ORM\OneToOne]`, `#[ORM\OneToMany]`, `#[ORM\ManyToOne]`

### Fixtures & Test Data
- Dummy user: `identityId='550e8400-e29b-41d4-a716-446655440000'`, email `'dummyuser@example.com'`
- Dummy class: `'Dummyklasse'` with associated `StundenplanNeu` entries
- Located in `src/DataFixtures/AppFixtures.php`, loaded via `doctrine:fixtures:load`

### Configuration
- Environment variables in `.env` file (not committed)
- Database URL: `DATABASE_URL=postgresql://symfony:!ChangeMe!@database:5432/pbd2h24asc_stundenplan_db`
- Services auto-configured via `services.yaml` (excludes `OpenApi/` and `DBAL/`)

### Security Context
- `Benutzer` implements `UserInterface` (Symfony security)
- No password field; `eraseCredentials()` is a no-op
- Roles stored as JSON array in `roles` column
- JWT token extracted from `Authorization: Bearer <token>` header

## Common Gotchas

1. **OpenAPI regeneration**: After `openapi_generate.ps1`, don't commit generated code changes without re-checking openapi.yaml
2. **Database inconsistencies**: If migrations are out of sync, use `docker-compose down -v` for a clean slate
3. **Timezone handling**: Dates stored as `DateTimeImmutable` in entities; ensure consistent timezone in API
4. **Service exclusions**: `src/OpenApi/` is excluded from autowiring in `services.yaml`—manually register if needed
5. **Test environment**: PHPUnit uses separate `pbd2h24asc_stundenplan_test_db` database

## Integration Points

- **Keycloak/Identity Provider**: JWT validation via `LexikJWTAuthenticationBundle`
- **PostgreSQL**: Timetable data persistence
- **Doctrine ORM**: Database abstraction layer
- **JMS Serializer**: JSON serialization for API responses
- **Symfony Messenger**: Background job scheduling (worker service in docker-compose)

## Resources
- API Spec: `openapi.yaml`
- Troubleshooting: `troubleshooting_findings.md`, `progress.md`
- Migration history: `migrations/`
- Test database setup: `tests/bootstrap.php`, `phpunit.xml.dist`

