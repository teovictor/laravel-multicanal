# Laravel Multichannel

## Project overview

Laravel application for managing generic categories and products through multiple isolated entry points:

* Web interface with Blade
* REST API
* Artisan commands
* Queued jobs
* Scheduled processes

All entry points must reuse the same application use cases and business rules.

## Technology

* PHP 8.5+
* Laravel 13
* SQLite
* Blade
* Database queue driver
* PHPUnit
* Vite
* Git

Do not introduce Docker, Redis, RabbitMQ, authentication, external services, or additional packages unless explicitly requested.

## Architecture

Keep the architecture simple and evolve it only when necessary.

* Controllers, commands, jobs, and scheduled tasks are entry points.
* Entry points must not contain business rules.
* Entry points must call shared application actions or use cases.
* Controllers must not call other controllers.
* Commands and jobs must not call HTTP endpoints.
* Application and domain code must not depend on HTTP requests, Blade views, JSON responses, console output, or queue infrastructure.
* Avoid unnecessary repositories, interfaces, abstractions, and design patterns.

Prefer the following organization when relevant:

```text
app/
├── Application/
│   ├── Categories/
│   └── Products/
├── Domain/
│   ├── Categories/
│   └── Products/
├── Http/
│   ├── Controllers/Api/
│   ├── Controllers/Web/
│   ├── Requests/
│   └── Resources/
├── Console/Commands/
└── Jobs/
```

## Initial domain

### Categories

Initial fields:

* id
* name
* description
* is_active
* timestamps

Rules:

* Name is required.
* A category may exist without products.
* An inactive category remains stored.
* A category with products must not be deleted.

### Products

Initial fields:

* id
* category_id
* name
* description
* sku
* price_cents
* stock
* is_active
* timestamps

Rules:

* Name and SKU are required.
* SKU must be unique.
* A product must belong to an existing category.
* Monetary values are stored in cents as integers.
* Price cents and stock cannot be negative.
* Products and categories may be activated or deactivated.
* A product from an inactive category is not considered available.

## Coding standards

* Follow Laravel conventions and PSR-12.
* Use strict and explicit validation.
* Use expressive names in English.
* Keep classes and methods focused.
* Avoid duplicated business logic.
* Use database transactions when an operation changes multiple related records.
* Do not modify unrelated files.
* Do not add speculative features.

## Tests

Every implementation or behavior change must include appropriate tests.

Run:

```bash
php artisan test
```

Before finishing a task:

1. Run the relevant tests.
2. Run the complete test suite.
3. Report the files changed.
4. Report the tests executed and their results.
5. Mention any unresolved issue or assumption.

## Common commands

```bash
php artisan test
php artisan migrate
php artisan migrate:fresh
php artisan queue:work
php artisan schedule:list
npm install
npm run build
```

## Working rules

* Inspect the existing project before changing code.
* Make small, reviewable changes.
* Implement only the requested task.
* Explain important architectural decisions briefly.
* Ask before adding dependencies or changing the agreed architecture.
* Never commit changes unless explicitly requested.
