# Implementation Roadmap

## Phase 1: Foundation (Week 1-2)
- [x] Architecture design
- [x] Database schema
- [x] API contract
- [ ] Laravel project setup with auth
- [ ] Database migrations
- [ ] Models with relationships
- [ ] HMAC signing service
- [ ] Basic Blade layout and dashboard shell

## Phase 2: Site Management (Week 2-3)
- [ ] Site registration token generation
- [ ] Agent registration endpoint
- [ ] Heartbeat endpoint
- [ ] Full sync endpoint
- [ ] Site listing dashboard page
- [ ] Site detail page
- [ ] Add site flow

## Phase 3: Update Orchestration (Week 3-4)
- [ ] Update job creation
- [ ] ExecuteUpdateJob (queued)
- [ ] Agent execute-update endpoint (WP plugin)
- [ ] Update result reporting
- [ ] Update history UI
- [ ] Batch update support

## Phase 4: Health Checks (Week 4-5)
- [ ] HealthCheckService implementation
- [ ] RunHealthCheckJob
- [ ] Homepage, admin, REST, version checks
- [ ] Health check results UI
- [ ] Per-site custom URL stubs

## Phase 5: Risk Assessment (Week 5)
- [ ] RiskEngine rules implementation
- [ ] Risk score calculation
- [ ] Pre-update risk display
- [ ] Risk factor explanations

## Phase 6: Error Reporting & Logging (Week 5-6)
- [ ] Error log storage and display
- [ ] Agent error capture hooks
- [ ] Structured error reports
- [ ] Audit logging

## Phase 7: WordPress Plugin (Parallel with Phases 2-6)
- [ ] Plugin bootstrap and activation
- [ ] Admin settings page
- [ ] Registration flow
- [ ] Heartbeat cron
- [ ] Sync cron
- [ ] Update execution via WP upgrader
- [ ] Error capture (shutdown handler, WP_Error)
- [ ] REST API endpoints

## Phase 8: Hardening & Polish (Week 6-7)
- [ ] Input validation review
- [ ] Rate limiting
- [ ] Queue retry policies
- [ ] Timeout handling
- [ ] UI polish
- [ ] Testing

## Queue Configuration
- **Default queue**: general work
- **updates queue**: update execution jobs (separate worker, controlled concurrency)
- **health-checks queue**: health check jobs
- **sync queue**: site sync jobs

## Environment Requirements
- PHP 8.2+
- Laravel 11
- MySQL 8.0+ or PostgreSQL 15+
- Redis 7+
- Composer
- Node.js 18+ (for frontend assets)
- WordPress 6.0+ (for agent plugin)
