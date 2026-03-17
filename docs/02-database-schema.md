# Database Schema

## Entity Relationship

```
users 1──M sites
sites 1──M installed_items
sites 1──M update_jobs
sites 1──M health_checks
sites 1──M error_logs
update_jobs 1──M update_job_items
update_jobs 1──1 risk_assessments
update_jobs 1──M health_checks
update_jobs 1──M error_logs
```

## Tables

### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | |
| email | varchar(255) | unique |
| password | varchar(255) | bcrypt |
| remember_token | varchar(100) | |
| timestamps | | |

### sites
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK | owner |
| name | varchar(255) | display name |
| url | varchar(500) | site URL |
| auth_secret | varchar(255) | HMAC shared secret (encrypted) |
| registration_token | varchar(100) | one-time use, nullable |
| status | enum | pending/connected/disconnected/error |
| wp_version | varchar(20) | nullable |
| php_version | varchar(20) | nullable |
| active_theme | varchar(255) | nullable |
| last_seen_at | timestamp | nullable |
| meta | json | nullable, extensible metadata |
| timestamps | | |

### installed_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| site_id | bigint FK | |
| type | enum | plugin/theme/core |
| slug | varchar(255) | e.g. woocommerce, twentytwentyfour |
| name | varchar(255) | display name |
| current_version | varchar(50) | |
| available_version | varchar(50) | nullable |
| is_active | boolean | default true |
| auto_update_enabled | boolean | default false |
| tested_wp_version | varchar(20) | nullable |
| last_updated_at | timestamp | nullable, from WP.org |
| meta | json | nullable |
| timestamps | | |
| unique | site_id + type + slug | |

### update_jobs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| site_id | bigint FK | |
| user_id | bigint FK | who triggered it |
| type | enum | plugin/theme/core/batch |
| status | enum | pending/in_progress/completed/failed/partially_failed |
| started_at | timestamp | nullable |
| completed_at | timestamp | nullable |
| summary | text | nullable, human-readable |
| timestamps | | |

### update_job_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| update_job_id | bigint FK | |
| installed_item_id | bigint FK | nullable (for core) |
| type | enum | plugin/theme/core |
| slug | varchar(255) | |
| old_version | varchar(50) | |
| requested_version | varchar(50) | |
| resulting_version | varchar(50) | nullable |
| status | enum | pending/in_progress/completed/failed |
| raw_result | json | nullable |
| error_message | text | nullable |
| started_at | timestamp | nullable |
| completed_at | timestamp | nullable |
| timestamps | | |

### health_checks
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| site_id | bigint FK | |
| update_job_id | bigint FK | nullable |
| status | enum | pending/passed/degraded/failed |
| checks | json | individual check results |
| summary | text | nullable |
| timestamps | | |

### health_checks.checks JSON structure
```json
{
  "homepage": {
    "url": "https://example.com",
    "status_code": 200,
    "response_time_ms": 450,
    "has_critical_error": false,
    "passed": true
  },
  "wp_login": {
    "url": "https://example.com/wp-login.php",
    "status_code": 200,
    "response_time_ms": 320,
    "passed": true
  },
  "rest_api": {
    "url": "https://example.com/wp-json/wp/v2/",
    "status_code": 200,
    "response_time_ms": 180,
    "passed": true
  },
  "version_check": {
    "expected_version": "6.5.0",
    "actual_version": "6.5.0",
    "passed": true
  }
}
```

### error_logs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| site_id | bigint FK | |
| update_job_id | bigint FK | nullable |
| source | enum | updater/health_check/agent/wp_error/fatal |
| severity | enum | info/warning/error/critical |
| message | text | |
| context | json | nullable, structured error data |
| resolved_at | timestamp | nullable |
| timestamps | | |

### risk_assessments
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| update_job_id | bigint FK | |
| site_id | bigint FK | |
| score | integer | 0-100 |
| level | enum | low/medium/high |
| explanation | text | human-readable summary |
| factors | json | list of triggered risk factors |
| timestamps | | |

### risk_assessments.factors JSON structure
```json
[
  {
    "rule": "major_version_jump",
    "description": "Major version change from 5.x to 6.x",
    "score": 25,
    "data": { "old": "5.9.1", "new": "6.0.0" }
  },
  {
    "rule": "sensitive_category",
    "description": "WooCommerce is a commerce-critical plugin",
    "score": 15,
    "data": { "slug": "woocommerce", "category": "ecommerce" }
  }
]
```

### audit_logs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK | nullable |
| site_id | bigint FK | nullable |
| action | varchar(255) | e.g. site.registered, update.triggered |
| description | text | nullable |
| ip_address | varchar(45) | nullable |
| meta | json | nullable |
| timestamps | | |
