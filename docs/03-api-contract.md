# API Contract

All requests between the dashboard and agent plugin use HTTPS with HMAC-SHA256 signed authentication.

## Authentication Headers (all signed requests)

```
X-WUM-Signature: hmac_sha256_hex
X-WUM-Timestamp: unix_timestamp
X-WUM-Site-ID: site_uuid_or_id
Content-Type: application/json
```

Signature computed over: `{timestamp}.{method}.{path}.{sha256(body)}`

---

## Agent Plugin → Dashboard (Inbound)

### POST /api/agent/register
Register a new site with the dashboard. Uses one-time registration token (not HMAC).

**Request:**
```json
{
  "registration_token": "abc123...",
  "site_url": "https://example.com",
  "wp_version": "6.5.0",
  "php_version": "8.2.4",
  "active_theme": "flavor theme",
  "server_software": "nginx/1.24",
  "plugin_version": "1.0.0"
}
```

**Response 200:**
```json
{
  "success": true,
  "site_id": 42,
  "auth_secret": "generated_shared_secret_here",
  "dashboard_url": "https://dashboard.example.com"
}
```

### POST /api/agent/heartbeat
Periodic check-in from agent. HMAC signed.

**Request:**
```json
{
  "wp_version": "6.5.0",
  "php_version": "8.2.4",
  "active_theme": "flavor theme",
  "plugin_version": "1.0.0",
  "uptime": true
}
```

**Response 200:**
```json
{
  "success": true,
  "next_heartbeat_seconds": 300
}
```

### POST /api/agent/sync
Full sync of installed items and available updates. HMAC signed.

**Request:**
```json
{
  "wp_version": "6.5.0",
  "php_version": "8.2.4",
  "active_theme": "flavor theme",
  "installed_items": [
    {
      "type": "plugin",
      "slug": "woocommerce",
      "name": "WooCommerce",
      "current_version": "8.5.0",
      "available_version": "8.6.1",
      "is_active": true,
      "auto_update_enabled": false,
      "tested_wp_version": "6.5.0"
    },
    {
      "type": "theme",
      "slug": "flavor theme",
      "name": "flavor theme",
      "current_version": "3.1.0",
      "available_version": null,
      "is_active": true,
      "auto_update_enabled": false,
      "tested_wp_version": null
    },
    {
      "type": "core",
      "slug": "wordpress",
      "name": "WordPress",
      "current_version": "6.5.0",
      "available_version": "6.5.2",
      "is_active": true,
      "auto_update_enabled": false,
      "tested_wp_version": null
    }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "items_synced": 15
}
```

### POST /api/agent/update-result
Report result of an update execution. HMAC signed.

**Request:**
```json
{
  "update_job_id": 123,
  "items": [
    {
      "update_job_item_id": 456,
      "slug": "woocommerce",
      "type": "plugin",
      "old_version": "8.5.0",
      "resulting_version": "8.6.1",
      "status": "completed",
      "raw_result": { "messages": ["Updated successfully"] },
      "error_message": null
    }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "health_check_queued": true
}
```

### POST /api/agent/error-report
Report errors captured by the agent. HMAC signed.

**Request:**
```json
{
  "errors": [
    {
      "source": "updater",
      "severity": "error",
      "message": "Plugin update failed: file permissions",
      "context": {
        "slug": "woocommerce",
        "wp_error_code": "update_failed",
        "wp_error_data": "Could not copy file."
      }
    }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "logged": 1
}
```

---

## Dashboard → Agent Plugin (Outbound)

### POST {site_url}/wp-json/wum-agent/v1/execute-update
Instruct the agent to perform updates. HMAC signed.

**Request:**
```json
{
  "update_job_id": 123,
  "items": [
    {
      "update_job_item_id": 456,
      "type": "plugin",
      "slug": "woocommerce",
      "version": "8.6.1"
    },
    {
      "update_job_item_id": 457,
      "type": "theme",
      "slug": "flavor theme",
      "version": "3.2.0"
    }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "results": [
    {
      "update_job_item_id": 456,
      "slug": "woocommerce",
      "type": "plugin",
      "old_version": "8.5.0",
      "resulting_version": "8.6.1",
      "status": "completed",
      "raw_result": { "messages": ["Updated successfully."] }
    },
    {
      "update_job_item_id": 457,
      "slug": "flavor theme",
      "type": "theme",
      "old_version": "3.1.0",
      "resulting_version": "3.2.0",
      "status": "completed",
      "raw_result": { "messages": ["Updated successfully."] }
    }
  ]
}
```

### POST {site_url}/wp-json/wum-agent/v1/status
Check agent status and connectivity. HMAC signed.

**Request:**
```json
{
  "check": "status"
}
```

**Response 200:**
```json
{
  "success": true,
  "wp_version": "6.5.0",
  "php_version": "8.2.4",
  "plugin_version": "1.0.0",
  "uptime": true
}
```

### GET {site_url}/wp-json/wum-agent/v1/installed-items
Fetch current installed items from agent. HMAC signed.

**Response 200:**
```json
{
  "success": true,
  "items": [
    {
      "type": "plugin",
      "slug": "woocommerce",
      "name": "WooCommerce",
      "current_version": "8.6.1",
      "available_version": null,
      "is_active": true,
      "tested_wp_version": "6.5.0"
    }
  ]
}
```

---

## Error Response Format (all endpoints)

```json
{
  "success": false,
  "error": {
    "code": "invalid_signature",
    "message": "Request signature validation failed."
  }
}
```

Common error codes:
- `invalid_signature` - HMAC validation failed
- `expired_request` - Timestamp outside ±5 minute window
- `site_not_found` - Unknown site ID
- `invalid_token` - Registration token invalid/expired
- `update_in_progress` - Another update already running
- `unauthorized` - Authentication failed
- `validation_error` - Request body validation failed
