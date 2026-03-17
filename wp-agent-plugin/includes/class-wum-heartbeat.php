<?php
/**
 * Periodic heartbeat to the dashboard.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_Heartbeat {

    /**
     * Schedule the heartbeat cron event.
     */
    public static function schedule(): void {
        if (! wp_next_scheduled('wum_heartbeat_event')) {
            wp_schedule_event(time(), 'wum_five_minutes', 'wum_heartbeat_event');
        }

        // Register our custom cron interval
        add_filter('cron_schedules', [self::class, 'add_cron_interval']);
        add_action('wum_heartbeat_event', [self::class, 'run']);
    }

    /**
     * Add a 5-minute cron interval.
     */
    public static function add_cron_interval(array $schedules): array {
        $schedules['wum_five_minutes'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes', 'wum-agent'),
        ];
        return $schedules;
    }

    /**
     * Send heartbeat to the dashboard.
     */
    public static function run(): void {
        if (! WUM_Agent::is_connected()) {
            return;
        }

        $data = [
            'wp_version'     => get_bloginfo('version'),
            'php_version'    => phpversion(),
            'active_theme'   => wp_get_theme()->get('Name'),
            'plugin_version' => WUM_AGENT_VERSION,
            'uptime'         => true,
        ];

        WUM_API_Client::post('agent/heartbeat', $data);
    }
}
