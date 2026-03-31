# URL Shortener

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?logo=symfony&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red)

A URL shortening service built with Symfony 8, featuring click analytics, GeoIP detection, async message processing, and a clean architecture.

## Features

- **Short URL management** — create and delete short links with unique auto-generated codes
- **Fast redirects** — Redis-cached lookups (1h TTL) with async click tracking
- **Analytics dashboard** — click counts, country breakdown, top referrers, device detection
- **Interactive charts** — click trends over 7/30 days via Chart.js + Symfony UX Live Components
- **GeoIP** — country detection by IP using MaxMind GeoLite2
- **User accounts** — registration, login, remember-me, CSRF protection
- **Async processing** — click tracking and domain events via Symfony Messenger (Redis transport)
- **Authorization** — voters ensure users can only delete their own links

## Architecture

The project follows **Clean Architecture** with strict **DDD** and **CQRS** patterns:

```
src/
├── Domain/                         # Pure business logic, no framework dependencies
│   ├── Click/                      #   Click entity, repository interface, value objects
│   ├── ShortUrl/                   #   ShortUrl aggregate root, events, exceptions, value objects
│   ├── User/                       #   User entity, repository interface, value objects
│   └── Shared/                     #   RecordsEvents interface
│
├── Application/                    # Use cases (CQRS)
│   ├── Click/Command/              #   TrackClickCommand + Handler
│   └── ShortUrl/
│       ├── Command/                #   CreateShortUrl, DeleteShortUrl commands + handlers
│       └── Query/                  #   GetDashboard, GetUserLinks queries + handlers
│
├── Infrastructure/                 # Framework-specific implementations
│   ├── Cache/                      #   RedirectCache (Redis)
│   ├── Messaging/                  #   Domain event dispatcher + subscribers
│   ├── Persistence/Doctrine/       #   ORM mappings (XML) + repositories
│   ├── Security/                   #   LoginFormAuthenticator, ShortUrlVoter
│   └── Service/                    #   GeoIpService, ShortCodeGenerator
│
└── UI/Http/Web/                    # Delivery mechanism
    ├── Controller/                 #   Thin controllers delegating to application layer
    └── Form/                       #   Registration form type
```

### Key patterns

| Pattern | Implementation |
|---|---|
| **Aggregate Root** | `ShortUrl` records domain events (`ShortUrlCreatedEvent`, `ShortUrlVisitedEvent`) |
| **Value Objects** | `Url`, `ShortCode`, `IpAddress`, `Email` — self-validating, immutable |
| **CQRS** | Separate command/query objects with dedicated handlers |
| **Repository Interface** | Domain defines interfaces; Doctrine implements them |
| **Domain Events** | Dispatched post-flush via `DomainEventDispatcher` Doctrine listener |
| **Async Messages** | `TrackClickCommand` routed to Redis transport via Messenger |

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Symfony 8.0 |
| Database | PostgreSQL 16 |
| Cache & Messenger | Redis 7 |
| Server | FrankenPHP (Caddy) with worker mode |
| Frontend | Twig, Tailwind CSS 4, Chart.js, Stimulus, Turbo |
| GeoIP | MaxMind GeoLite2 (geoip2/geoip2) |
| ORM | Doctrine with XML mappings |
| QA | PHPStan, PHP-CS-Fixer, Rector, PHPUnit |

## Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with Docker Compose

### Setup

```bash
cp .env.dev .env        # configure environment
make build              # build Docker images
make up                 # start containers
make geoip              # download GeoLite2 Country database
```

The app will be available at **http://localhost**.

Database migrations run automatically on container startup. To run manually:

```bash
make migrate            # run pending migrations
make db-reset           # drop, recreate, and migrate from scratch
```

### Without Docker

Requirements: PHP 8.4+, PostgreSQL 16, Redis 7, Composer.

```bash
composer install
cp .env.dev .env        # edit DATABASE_URL and MESSENGER_TRANSPORT_DSN
php bin/console doctrine:migrations:migrate
php bin/console messenger:consume async
symfony server:start
```

## Routes

| Method | Path | Name | Description | Auth |
|---|---|---|---|---|
| GET | `/` | `app_home` | Landing page (redirects to dashboard if logged in) | No |
| GET | `/{code}` | `app_redirect` | Redirect to original URL | No |
| GET | `/dashboard` | `app_dashboard` | Analytics dashboard with charts | Yes |
| GET | `/links` | `app_links` | Paginated list of user's links | Yes |
| POST | `/links/create` | `app_links_create` | Create a new short URL | Yes |
| DELETE | `/links/{id}/delete` | `app_links_delete` | Delete a short URL (owner only) | Yes |
| GET\|POST | `/register` | `app_register` | User registration | No |
| GET\|POST | `/login` | `app_login` | Login | No |
| GET | `/logout` | `app_logout` | Logout | Yes |

## Commands

Run `make help` to see all available commands.

| Command | Description |
|---|---|
| `make up` | Start development containers |
| `make down` | Stop containers |
| `make build` | Build Docker images |
| `make build-prod` | Build production images |
| `make shell` | Open shell in PHP container |
| `make logs` | Tail container logs |
| `make ps` | List running containers |
| `make db` | Create database and run migrations |
| `make db-reset` | Drop, recreate, and migrate database |
| `make migrate` | Run pending migrations |
| `make make-migration` | Generate a new migration |
| `make test` | Run PHPUnit tests |
| `make check` | Run all QA checks (stan + cs + rector + test) |
| `make stan` | Run PHPStan static analysis |
| `make cs` | Check coding style (dry-run) |
| `make cs-fix` | Fix coding style |
| `make rector` | Check with Rector (dry-run) |
| `make rector-fix` | Apply Rector fixes |
| `make geoip` | Download GeoLite2 Country database |
| `make tw` | Build Tailwind CSS |
| `make console args="cache:clear"` | Run any Symfony console command |

## Testing

```bash
make test               # run all tests
```

Tests include:

- **Unit tests** — `TrackClickHandler` with mocked repositories
- **Functional tests** — `DashboardController`, `ShortUrlController`, `RegistrationController` against the full framework

## Code Quality

| Tool | Command | Purpose |
|---|---|---|
| PHPStan | `make stan` | Static analysis (level defined in `phpstan.dist.neon`) |
| PHP-CS-Fixer | `make cs` / `make cs-fix` | Coding style enforcement |
| Rector | `make rector` / `make rector-fix` | Automated refactoring and code upgrades |

## Docker Services

| Service | Image | Purpose |
|---|---|---|
| `php` | FrankenPHP 1 (PHP 8.4) | Application server (Caddy + worker mode) |
| `postgres` | postgres:16-alpine | Database |
| `redis` | redis:7-alpine | Cache + Messenger transport |
| `messenger-worker` | FrankenPHP 1 (PHP 8.4) | Async message consumer |
| `mailer` | axllent/mailpit | SMTP testing (dev only) |

### Development extras

- **Mailpit** UI at http://localhost:8025
- **PostgreSQL** exposed on port 5432
- **Xdebug** available (`XDEBUG_MODE=debug` in `.env`)
- **FrankenPHP watch mode** — auto-reloads on file changes

## License

Proprietary. All rights reserved.
