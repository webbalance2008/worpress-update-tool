# Changelog

## 1.1.0

- Use `bulk_upgrade()` for plugin and theme updates to fix batch update failures
- Fix file permissions recursively on individual plugin/theme directories before updates
- Add self-update REST endpoint for remote plugin pushes from the dashboard
- Initialise `WP_Filesystem` before unzip operations to prevent "Could not access filesystem" errors
- Improve error messages: distinguish between missing download packages, already-current plugins, and permission issues
- Force refresh update transients before running upgrades to ensure package URLs are available
- Add filesystem writability check endpoint for health monitoring

## 1.0.0

- Initial release
- WordPress plugin, theme, and core update execution via REST API
- HMAC-signed request authentication
- Heartbeat and sync endpoints
- Dashboard registration and pairing
- Top-level directory permission fixes before updates
