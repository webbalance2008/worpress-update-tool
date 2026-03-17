<?php
/**
 * Handles site registration and disconnection with the dashboard.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_Registration {

    /**
     * Attempt to register this site with the dashboard.
     *
     * @param string $dashboard_url The dashboard base URL.
     * @param string $token         The one-time registration token from the dashboard.
     * @return array{success: bool, message: string}
     */
    public static function connect(string $dashboard_url, string $token): array {
        $result = WUM_API_Client::register($dashboard_url, $token);

        if (empty($result['success'])) {
            $message = $result['error']['message'] ?? 'Unknown error during registration.';
            return ['success' => false, 'message' => $message];
        }

        // Store the connection settings
        update_option('wum_agent_settings', [
            'dashboard_url' => rtrim($dashboard_url, '/'),
            'site_id'       => $result['site_id'],
            'auth_secret'   => $result['auth_secret'],
            'connected'     => true,
        ]);

        // Schedule heartbeat and sync
        WUM_Heartbeat::schedule();
        WUM_Sync::schedule();

        // Trigger an immediate sync
        WUM_Sync::run();

        return ['success' => true, 'message' => 'Connected successfully.'];
    }

    /**
     * Disconnect from the dashboard.
     */
    public static function disconnect(): void {
        // Clear scheduled events
        wp_clear_scheduled_hook('wum_heartbeat_event');
        wp_clear_scheduled_hook('wum_sync_event');

        // Reset settings but keep dashboard_url for easy reconnection
        $dashboard_url = WUM_Agent::get_setting('dashboard_url');

        update_option('wum_agent_settings', [
            'dashboard_url' => $dashboard_url,
            'site_id'       => '',
            'auth_secret'   => '',
            'connected'     => false,
        ]);
    }
}
