# WordPress Update Manager - Technical Architecture

## System Overview

```
┌─────────────────────────────────────────────────────┐
│                 CENTRAL DASHBOARD                     │
│              (Laravel Application)                    │
│                                                       │
│  ┌──────────┐ ┌──────────┐ ┌───────────────────┐    │
│  │  Blade   │ │  Auth    │ │  Queue Workers    │    │
│  │  Views   │ │  Layer   │ │  (Redis/Horizon)  │    │
│  └────┬─────┘ └────┬─────┘ └────────┬──────────┘    │
│       │            │                 │                │
│  ┌────┴────────────┴─────────────────┴──────────┐    │
│  │              Service Layer                    │    │
│  │  ┌────────────┐ ┌──────────┐ ┌────────────┐ │    │
│  │  │ SiteService│ │UpdateSvc │ │HealthCheck │ │    │
│  │  └────────────┘ └──────────┘ └────────────┘ │    │
│  │  ┌────────────┐ ┌──────────┐ ┌────────────┐ │    │
│  │  │ RiskEngine │ │ErrorSvc  │ │ApiClient  │ │    │
│  │  └────────────┘ └──────────┘ └────────────┘ │    │
│  └───────────────────────┬──────────────────────┘    │
│                          │                            │
│  ┌───────────────────────┴──────────────────────┐    │
│  │           Database (MySQL/PostgreSQL)          │    │
│  └───────────────────────────────────────────────┘    │
└──────────────────────────┬──────────────────────────┘
                           │ HTTPS + HMAC Signed Requests
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌───────────────┐ ┌───────────────┐ ┌───────────────┐
│  WP Site A    │ │  WP Site B    │ │  WP Site C    │
│  ┌─────────┐  │ │  ┌─────────┐  │ │  ┌─────────┐  │
│  │  Agent  │  │ │  │  Agent  │  │ │  │  Agent  │  │
│  │ Plugin  │  │ │  │ Plugin  │  │ │  │ Plugin  │  │
│  └─────────┘  │ │  └─────────┘  │ │  └─────────┘  │
└───────────────┘ └───────────────┘ └───────────────┘
```

## Queue Flow for Updates

```
User triggers update
       │
       ▼
┌──────────────────┐
│ UpdateController  │──→ RiskAssessmentService
│ validateRequest() │         │
└────────┬─────────┘         ▼
         │            ┌──────────────┐
         │            │ RiskAssessment│
         │            │ stored in DB  │
         │            └──────────────┘
         ▼
┌──────────────────┐
│ DispatchUpdateJob │
│ (queued on Redis) │
└────────┬─────────┘
         ▼
┌──────────────────┐
│ ExecuteUpdateJob  │
│ - Signs request   │
│ - Calls WP agent  │
│ - Stores result   │
└────────┬─────────┘
         │
         ├── Success ──→ DispatchHealthCheckJob
         │                      │
         │                      ▼
         │              ┌──────────────────┐
         │              │ RunHealthCheckJob │
         │              │ - Homepage check  │
         │              │ - Admin check     │
         │              │ - REST API check  │
         │              │ - Version verify  │
         │              └────────┬─────────┘
         │                       │
         │                       ▼
         │              ┌──────────────┐
         │              │ HealthCheck   │
         │              │ stored in DB  │
         │              └──────────────┘
         │
         └── Failure ──→ ErrorLog created
                         Notification dispatched
```

## Job Lifecycle Example

1. **UpdateJob created** → status: `pending`
2. **Worker picks up job** → status: `in_progress`
3. **API call to WP agent** → signed HMAC request sent
4. **Agent executes update** → uses WP upgrader APIs
5. **Agent returns result** → includes old_version, new_version, raw output
6. **Dashboard records result** → status: `completed` or `failed`
7. **Health check dispatched** → RunHealthCheckJob queued
8. **Health check runs** → HTTP checks against site
9. **Results stored** → HealthCheck record linked to UpdateJob

## Status Enums

### UpdateJob Statuses
- `pending` - Queued, not yet started
- `in_progress` - Worker is executing
- `completed` - Successfully finished
- `failed` - Update failed
- `partially_failed` - Some items in batch failed

### HealthCheck Statuses
- `pending` - Not yet run
- `passed` - All checks passed
- `degraded` - Some checks failed (non-critical)
- `failed` - Critical checks failed

### Risk Levels
- `low` (score 0-30)
- `medium` (score 31-60)
- `high` (score 61-100)

### Site Connection Statuses
- `pending` - Registration initiated, not confirmed
- `connected` - Active and communicating
- `disconnected` - Failed heartbeats
- `error` - Configuration issue

## Authentication Flow

```
SITE REGISTRATION:
1. Admin enters dashboard URL + registration token in WP plugin
2. Plugin sends POST /api/agent/register with token + site metadata
3. Dashboard validates token, creates Site record, generates shared secret
4. Dashboard returns shared secret + site_id to plugin
5. Plugin stores secret, confirms registration

ONGOING REQUESTS (HMAC):
1. Sender builds request body
2. Sender computes HMAC-SHA256(secret, timestamp + method + path + body_hash)
3. Sender includes headers: X-Signature, X-Timestamp, X-Site-ID
4. Receiver recomputes HMAC, validates signature
5. Receiver checks timestamp within ±5 minutes (replay protection)
```

## MVP Screens

1. **Login** - Standard Laravel auth
2. **Dashboard** - Grid/list of all connected sites with status badges
3. **Add Site** - Form to generate registration token
4. **Site Detail** - Tabs for: Overview, Updates, Health, Errors, Risk
5. **Site Updates** - Available updates list with action buttons
6. **Update History** - Chronological log of all update jobs
7. **Batch Update** - Select multiple items to update at once
8. **Settings** - User profile, global preferences
