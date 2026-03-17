# WordPress Update Manager

A production-minded MVP for managing WordPress updates across multiple sites from a central dashboard.

## Architecture

- **Dashboard** (`/dashboard`) — Laravel 11 application
- **Agent Plugin** (`/wp-agent-plugin`) — WordPress plugin installed on each managed site
- **Docs** (`/docs`) — Architecture documentation

## Project Structure

```
├── dashboard/                    # Laravel Application
│   ├── app/
│   │   ├── Enums/               # Status enums (SiteStatus, UpdateJobStatus, etc.)
│   │   ├── Http/
│   │   │   ├── Controllers/     # Web + API controllers
│   │   │   ├── Middleware/      # HMAC verification middleware
│   │   │   └── Requests/       # Form request validation
│   │   ├── Jobs/                # Queue jobs (ExecuteUpdate, RunHealthCheck, SyncSite)
│   │   ├── Models/              # Eloquent models
│   │   ├── Policies/           # Authorization policies
│   │   └── Services/           # Business logic layer
│   │       ├── RiskEngine/     # Rules-based risk assessment
│   │       │   ├── RiskRule.php         # Interface
│   │       │   └── Rules/               # Individual rule implementations
│   │       ├── AgentApiClient.php       # HTTP client to agent
│   │       ├── ErrorReportingService.php
│   │       ├── HealthCheckService.php
│   │       ├── HmacService.php
│   │       ├── RiskAnalyzerInterface.php  # Abstraction for future LLM
│   │       ├── RiskAssessmentService.php
│   │       ├── SiteService.php
│   │       └── UpdateService.php
│   ├── config/
│   │   └── wum.php              # App-specific config
│   ├── database/migrations/     # 8 migration files
│   ├── resources/views/         # Blade templates
│   │   ├── layouts/app.blade.php
│   │   ├── dashboard/index.blade.php
│   │   ├── sites/{show,create,updates}.blade.php
│   │   └── updates/show.blade.php
│   └── routes/
│       ├── web.php              # Authenticated dashboard routes
│       └── api.php              # Agent API routes
│
├── wp-agent-plugin/             # WordPress Plugin
│   ├── wum-agent.php            # Main plugin file
│   ├── includes/
│   │   ├── class-wum-hmac.php           # HMAC signing/verification
│   │   ├── class-wum-api-client.php     # HTTP client to dashboard
│   │   ├── class-wum-registration.php   # Site registration
│   │   ├── class-wum-heartbeat.php      # Periodic check-in
│   │   ├── class-wum-sync.php           # Full sync of installed items
│   │   ├── class-wum-updater.php        # Update execution via WP APIs
│   │   ├── class-wum-error-capture.php  # Error/fatal capture
│   │   └── class-wum-rest-api.php       # REST endpoints for dashboard
│   └── admin/
│       └── class-wum-admin.php          # Admin settings page
│
└── docs/
    ├── 01-architecture.md
    ├── 02-database-schema.md
    ├── 03-api-contract.md
    └── 04-implementation-roadmap.md
```

## Setup Instructions

### Dashboard (Laravel)

#### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+ or PostgreSQL 15+
- Redis 7+
- Node.js 18+ (for Vite asset compilation)

#### Installation

```bash
# 1. Create a new Laravel 11 project
composer create-project laravel/laravel wp-update-manager
cd wp-update-manager

# 2. Copy the scaffold files into the Laravel project
#    Copy the contents of dashboard/ into the Laravel project root:
#    - app/Enums/          → app/Enums/
#    - app/Http/           → app/Http/          (merge with existing)
#    - app/Jobs/           → app/Jobs/
#    - app/Models/         → app/Models/        (merge with existing)
#    - app/Policies/       → app/Policies/
#    - app/Services/       → app/Services/
#    - config/wum.php      → config/wum.php
#    - database/migrations → database/migrations (merge with existing)
#    - resources/views/    → resources/views/    (merge with existing)
#    - routes/web.php      → routes/web.php      (replace)
#    - routes/api.php      → routes/api.php      (replace)

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env:
#   DB_CONNECTION=mysql
#   DB_DATABASE=wum_dashboard
#   DB_USERNAME=your_user
#   DB_PASSWORD=your_password
#   QUEUE_CONNECTION=redis
#   REDIS_HOST=127.0.0.1

# 4. Install auth scaffolding (Laravel Breeze recommended)
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build

# 5. Register the HMAC middleware
# In bootstrap/app.php, add the alias:
#   ->withMiddleware(function (Middleware $middleware) {
#       $middleware->alias([
#           'verify.agent.hmac' => \App\Http\Middleware\VerifyAgentHmac::class,
#       ]);
#   })

# 6. Register the SitePolicy
# In app/Providers/AppServiceProvider.php boot():
#   use Illuminate\Support\Facades\Gate;
#   Gate::policy(\App\Models\Site::class, \App\Policies\SitePolicy::class);

# 7. Bind the RiskAnalyzerInterface
# In app/Providers/AppServiceProvider.php register():
#   $this->app->bind(
#       \App\Services\RiskAnalyzerInterface::class,
#       \App\Services\RiskAssessmentService::class,
#   );

# 8. Run migrations
php artisan migrate

# 9. Start queue workers
php artisan queue:work --queue=updates,health-checks,sync,default

# 10. Start the dev server
php artisan serve
```

#### Queue Workers (Production)

Use Laravel Horizon or Supervisor to manage queue workers:

```ini
# /etc/supervisor/conf.d/wum-worker.conf
[program:wum-updates]
command=php /path/to/artisan queue:work redis --queue=updates --tries=2 --timeout=120
numprocs=2
autostart=true
autorestart=true

[program:wum-health]
command=php /path/to/artisan queue:work redis --queue=health-checks --tries=2 --timeout=60
numprocs=1
autostart=true
autorestart=true

[program:wum-sync]
command=php /path/to/artisan queue:work redis --queue=sync,default --tries=2 --timeout=30
numprocs=1
autostart=true
autorestart=true
```

### WordPress Agent Plugin

#### Installation

1. Copy the `wp-agent-plugin/` folder into the WordPress site's `wp-content/plugins/` directory
2. Rename the folder to `wum-agent` if desired
3. Activate the plugin from **Plugins > Installed Plugins**
4. Go to **Tools > Update Manager** in the WordPress admin
5. Enter the Dashboard URL and the Registration Token generated from the dashboard
6. Click **Connect to Dashboard**

#### Requirements
- WordPress 6.0+
- PHP 8.0+
- HTTPS (required for secure communication)
- The site must be able to reach the dashboard URL over HTTPS

## Authentication Flow

1. Dashboard admin creates a site and receives a one-time registration token
2. WP admin enters the dashboard URL + token in the plugin settings
3. Plugin sends registration request to dashboard with site metadata
4. Dashboard validates token, generates HMAC shared secret, returns it
5. All subsequent requests are signed with HMAC-SHA256
6. Signature = `HMAC-SHA256(secret, "{timestamp}.{METHOD}.{path}.{SHA256(body)}")`
7. Replay protection: requests older than 5 minutes are rejected

## Queue Flow

```
User clicks "Update" → UpdateService creates UpdateJob + items
    → RiskAssessment computed and stored
    → ExecuteUpdateJob dispatched to "updates" queue
    → Worker sends signed request to WP agent
    → Agent executes updates via WP Upgrader API
    → Agent returns results (also reports back via POST)
    → Dashboard records results, marks job complete/failed
    → RunHealthCheckJob dispatched to "health-checks" queue
    → Worker runs HTTP checks (homepage, admin, REST, version)
    → HealthCheck record created
    → Errors logged if checks fail
```

## Next Steps for Hardening

1. **Rate limiting** — Add throttle middleware to agent API endpoints
2. **Input validation** — Review all request validations for edge cases
3. **Retry policies** — Fine-tune job retry backoff strategies
4. **Timeout handling** — Handle long-running updates gracefully
5. **Notifications** — Email/Slack alerts for failed updates or health checks
6. **Testing** — Unit tests for services, feature tests for API endpoints
7. **CI/CD** — GitHub Actions for tests, linting, deployment
8. **Logging** — Structured logging with context for debugging
9. **CORS** — Configure if dashboard and sites are on different domains
10. **Secret rotation** — Ability to rotate HMAC secrets without downtime
11. **Multi-tenancy** — If multiple users need isolated site groups
12. **LLM integration** — Swap `RiskAnalyzerInterface` binding for AI summaries
13. **JS error capture** — Add frontend error reporting agent (placeholder in error model)
14. **Custom health check URLs** — Per-site custom URL configuration
15. **Scheduled checks** — Periodic health checks independent of updates
