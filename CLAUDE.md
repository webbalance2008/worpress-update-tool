# WordPress Update Manager

## Dev Server Configuration

The project uses `.claude/launch.json` to define dev servers. Use `preview_start` to start them by name:

- **`laravel-server`** — PHP artisan serve on port 8000. This is the main dashboard.
- **`queue-worker`** — Background queue worker for update jobs, health checks, and sync.
- **`vite-dev`** — Vite HMR dev server for frontend assets (auto-port).

### Quick Start

To start the Laravel dev server, call `preview_start` with name `"laravel-server"`. Do NOT use `php artisan serve` via Bash — always use `preview_start`.

The Laravel project is at the repo root. The `launch.json` points artisan commands there.

### Database

SQLite is used for development (auto-configured by Laravel). All custom migrations are already run. No MySQL/PostgreSQL setup needed for dev.

## Project Layout

- `/` — Laravel application (repo root)
- `dashboard/` — Scaffold source files (canonical copies of app code)
- `wp-agent-plugin/` — WordPress agent plugin (copy to WP `wp-content/plugins/`)
- `docs/` — Architecture, schema, API contract, roadmap

## Tech Notes

- PHP 8.4 via Herd
- Laravel 12 (latest)
- Queue: defaults to `sync` driver in dev (no Redis needed). Set `QUEUE_CONNECTION=redis` for async.
- Auth: Laravel Breeze installed and configured
