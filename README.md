# Laravel Multichannel

[![CI](https://github.com/teovictor/laravel-multicanal/actions/workflows/ci.yml/badge.svg)](https://github.com/teovictor/laravel-multicanal/actions/workflows/ci.yml)

Laravel Multichannel is a Laravel 13 application that demonstrates how the same business rules can be reused across isolated entry points:

- Blade web pages
- REST API endpoints
- Artisan commands
- Queued jobs
- Laravel Scheduler tasks

The project keeps business behavior in shared Application Actions. Controllers, commands, jobs, and scheduled tasks are thin entry points that translate their channel-specific input into those shared actions.

## Architecture

The application is intentionally small and direct:

- **Entry points** receive input from Web, API, Console, Queue, or Scheduler.
- **Application Actions** contain the business rules and persistence decisions for each use case.
- **Data objects** carry validated input into actions without coupling actions to HTTP, console, queue, or scheduler infrastructure.
- **Eloquent Models** represent persisted categories and products, including relationships, casts, and database-backed constraints.
- **Channels stay isolated**: controllers do not call other controllers, commands and jobs do not call HTTP endpoints, and scheduled tasks do not duplicate product rules.
- **No speculative abstractions**: there are no repositories, service interfaces, or external integrations because the current scope does not require them.

Relevant structure:

```text
app/
|-- Application/
|   |-- Categories/
|   |   |-- Actions/
|   |   `-- Data/
|   `-- Products/
|       |-- Actions/
|       `-- Data/
|-- Console/Commands/
|-- Http/
|   |-- Controllers/Api/
|   |-- Controllers/Web/
|   |-- Requests/Api/
|   |-- Requests/Web/
|   `-- Resources/
|-- Jobs/
|-- Models/
`-- Schedules/
routes/
|-- api.php
|-- console.php
`-- web.php
```

## Implemented Features

- Category creation from Web, REST API, Artisan, and Queue.
- Product creation from Web, REST API, Artisan, and Queue.
- Products belong to existing categories.
- Product SKUs are unique.
- Product monetary values are stored as integer cents.
- Product stock cannot be negative.
- Categories and products can be created as active or inactive.
- A daily scheduled process deactivates active products whose stock is zero.

## Requirements

- PHP 8.4.1 or newer
- Composer
- Node.js and npm
- SQLite
- PHP extensions used by the CI environment: `mbstring`, `xml`, `curl`, `zip`, `sqlite3`, `bcmath`, and `intl`
- SQLite/PDO SQLite support enabled for PHP

Docker, Redis, RabbitMQ, and external services are not required.

## Installation

```bash
git clone https://github.com/teovictor/laravel-multicanal.git
cd laravel-multicanal

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

npm ci
npm run build
```

Start the local application server:

```bash
php artisan serve
```

By default, Laravel serves the application at `http://127.0.0.1:8000`.

## Usage by Channel

### Web

The web interface provides creation pages only:

- `GET /categories/create` opens the category creation form.
- `POST /categories` stores a category.
- `GET /products/create` opens the product creation form.
- `POST /products` stores a product.

The product form lists existing categories and sends `price` as a decimal value such as `249.90`. The web request converts that value to integer cents before calling the shared product creation action.

### REST API

The API exposes creation endpoints:

- `POST /api/categories`
- `POST /api/products`

Create a category:

```bash
curl -X POST http://127.0.0.1:8000/api/categories \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Office Supplies",
    "description": "Items used by the office team.",
    "is_active": true
  }'
```

Create a product:

```bash
curl -X POST http://127.0.0.1:8000/api/products \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "name": "Mechanical Keyboard",
    "description": "Compact keyboard with brown switches.",
    "sku": "KEY-001",
    "price_cents": 24990,
    "stock": 15,
    "is_active": true
  }'
```

For API requests, `price_cents` must be sent as an integer JSON number. The API rejects the `price` field. `stock` must also be an integer JSON number.

Successful responses use Laravel JSON resources and return `201 Created` with a `data` object.

### Artisan

Interactive category creation:

```bash
php artisan category:create
```

Non-interactive category creation:

```bash
php artisan category:create \
  --name="Office Supplies" \
  --description="Items used by the office team." \
  --no-interaction
```

Inactive category:

```bash
php artisan category:create \
  --name="Archived Supplies" \
  --inactive \
  --no-interaction
```

`category:create` options:

- `--name=` category name. Required when running non-interactively.
- `--description=` optional category description.
- `--inactive` creates the category with `is_active = false`.

Interactive product creation:

```bash
php artisan product:create
```

Non-interactive product creation:

```bash
php artisan product:create \
  --category=1 \
  --name="Mechanical Keyboard" \
  --description="Compact keyboard with brown switches." \
  --sku="KEY-001" \
  --price=249.90 \
  --stock=15 \
  --no-interaction
```

Inactive product:

```bash
php artisan product:create \
  --category=1 \
  --name="Archived Mouse" \
  --sku="MOUSE-ARCHIVED-001" \
  --price=19.90 \
  --stock=0 \
  --inactive \
  --no-interaction
```

`product:create` options:

- `--category=` existing category ID. Required when running non-interactively.
- `--name=` product name. Required when running non-interactively.
- `--description=` optional product description.
- `--sku=` unique product SKU. Required when running non-interactively.
- `--price=` non-negative decimal with at most two decimal places. The command converts it to cents.
- `--stock=` non-negative integer stock quantity.
- `--inactive` creates the product with `is_active = false`.

The fully interactive flows shown above ask whether the category or product should be created as active unless `--inactive` is used.

### Queue

The project includes two queued jobs:

- `App\Jobs\CreateCategoryJob`
- `App\Jobs\CreateProductJob`

The default `.env.example` uses the database queue driver:

```text
QUEUE_CONNECTION=database
```

The jobs table is included in the migrations, so it is available after running `php artisan migrate`.

Start a worker:

```bash
php artisan queue:work
```

Dispatch jobs locally with Tinker:

```bash
php artisan tinker
```

```php
use App\Jobs\CreateCategoryJob;
use App\Jobs\CreateProductJob;

CreateCategoryJob::dispatch('Queued Category', 'Created from Tinker.', true);

CreateProductJob::dispatch(
    1,
    'Queued Product',
    'Created from Tinker.',
    'QUEUE-001',
    1990,
    3,
    true,
);
```

The product job requires an existing category ID.

### Scheduler

The scheduler contains the event:

```text
products:deactivate-out-of-stock
```

It runs daily and calls `App\Schedules\DeactivateOutOfStockProductsTask`, which delegates to the shared `DeactivateOutOfStockProducts` action.

Rule:

- Products with `stock = 0` and `is_active = true` are updated to `is_active = false`.
- Already inactive products are not changed.
- Products with stock greater than zero are not changed.
- The action returns the number of products that were actually deactivated.

Inspect scheduled events:

```bash
php artisan schedule:list
```

Run due scheduled events:

```bash
php artisan schedule:run
```

In production, the server should execute `php artisan schedule:run` every minute so Laravel can decide which scheduled tasks are due.

## Testing and Quality

Run the test suite:

```bash
php artisan test
```

Check PHP formatting:

```bash
./vendor/bin/pint --test
```

Build frontend assets:

```bash
npm run build
```

The GitHub Actions workflow is defined in `.github/workflows/ci.yml`. It installs PHP dependencies, prepares the SQLite database, runs Pint, runs the PHPUnit test suite, installs frontend dependencies with `npm ci`, and builds the frontend assets with Vite.

## Domain Rules

Categories:

- Name is required.
- Description is optional.
- Categories default to active unless the channel explicitly creates them as inactive.
- A category can exist without products.
- The database relationship restricts deleting a category that has products.

Products:

- A product must belong to an existing category.
- Name and SKU are required.
- SKU must be unique.
- Price is stored in `price_cents` as an integer.
- Price cents cannot be negative.
- Stock cannot be negative.
- Products default to active unless the channel explicitly creates them as inactive.
- A product from an inactive category is not considered available.
- The scheduled product task deactivates active products with zero stock.

## Project Scope

This is a demonstration project focused on multichannel application architecture and simple Laravel conventions. It intentionally does not include:

- Authentication
- Docker
- Redis
- RabbitMQ
- External services

Those omissions are scope decisions for this project, not framework limitations.

## License

This project is open-sourced software licensed under the MIT License.
