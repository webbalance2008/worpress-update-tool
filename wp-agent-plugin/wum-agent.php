<?php
/**
 * Plugin Name: WP Update Manager Agent
 * Plugin URI:  https://example.com/wum-agent
 * Description: Connects this WordPress site to a WP Update Manager dashboard for remote update management.
 * Version:     1.0.0
 * Author:      WP Update Manager
 * License:     GPL-2.0-or-later
 * Text Domain: wum-agent
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WUM_AGENT_VERSION', '1.0.0');
define('WUM_AGENT_FILE', __FILE__);
define('WUM_AGENT_DIR', plugin_dir_path(__FILE__));
define('WUM_AGENT_URL', plugin_dir_url(__FILE__));

// Autoload classes
require_once WUM_AGENT_DIR . 'includes/class-wum-hmac.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-api-client.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-registration.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-heartbeat.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-sync.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-updater.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-error-capture.php';
require_once WUM_AGENT_DIR . 'includes/class-wum-rest-api.php';
require_once WUM_AGENT_DIR . 'admin/class-wum-admin.php';

/**
 * Main plugin class.
 */
final class WUM_Agent {

    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        register_activation_hook(WUM_AGENT_FILE, [$this, 'activate']);
        register_deactivation_hook(WUM_AGENT_FILE, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Admin settings page
        if (is_admin()) {
            new WUM_Admin();
        }

        // Error capture hooks (always active when connected)
        if (self::is_connected()) {
            new WUM_Error_Capture();
        }
    }

    public function init(): void {
        // Schedule heartbeat and sync crons if connected
        if (self::is_connected()) {
            WUM_Heartbeat::schedule();
            WUM_Sync::schedule();
        }
    }

    public function register_rest_routes(): void {
        $rest_api = new WUM_REST_API();
        $rest_api->register_routes();
    }

    public function activate(): void {
        // Set default options
        if (! get_option('wum_agent_settings')) {
            update_option('wum_agent_settings', [
                'dashboard_url' => '',
                'site_id'       => '',
                'auth_secret'   => '',
                'connected'     => false,
            ]);
        }

        // Ensure FS_METHOD is set to 'direct' so updates don't require FTP credentials
        self::ensure_fs_method();
    }

    /**
     * Add FS_METHOD direct to wp-config.php if not already defined.
     */
    private static function ensure_fs_method(): void {
        if (defined('FS_METHOD')) {
            return;
        }

        $config_path = ABSPATH . 'wp-config.php';

        if (! file_exists($config_path) || ! is_writable($config_path)) {
            return;
        }

        $config_content = file_get_contents($config_path);

        // Check if FS_METHOD is already defined anywhere in the file
        if (strpos($config_content, 'FS_METHOD') !== false) {
            return;
        }

        // Insert before the "That's all, stop editing!" comment or the require line
        $anchors = [
            "/* That's all, stop editing!",
            "/** Absolute path to the WordPress directory",
            "require_once ABSPATH",
        ];

        foreach ($anchors as $anchor) {
            $pos = strpos($config_content, $anchor);
            if ($pos !== false) {
                $insert = "/** Allow direct filesystem access for updates */\ndefine('FS_METHOD', 'direct');\n\n";
                $config_content = substr_replace($config_content, $insert, $pos, 0);
                file_put_contents($config_path, $config_content);
                return;
            }
        }
    }

    public function deactivate(): void {
        // Clear scheduled events
        wp_clear_scheduled_hook('wum_heartbeat_event');
        wp_clear_scheduled_hook('wum_sync_event');
    }

    /**
     * Check if the plugin is connected to a dashboard.
     */
    public static function is_connected(): bool {
        $settings = get_option('wum_agent_settings', []);
        return ! empty($settings['connected']) && ! empty($settings['auth_secret']);
    }

    /**
     * Get a settings value.
     */
    public static function get_setting(string $key, $default = '') {
        $settings = get_option('wum_agent_settings', []);
        return $settings[$key] ?? $default;
    }

    /**
     * Update a settings value.
     */
    public static function update_setting(string $key, $value): void {
        $settings = get_option('wum_agent_settings', []);
        $settings[$key] = $value;
        update_option('wum_agent_settings', $settings);
    }
}

// Boot the plugin
WUM_Agent::instance();
