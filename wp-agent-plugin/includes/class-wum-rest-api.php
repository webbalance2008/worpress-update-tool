<?php
/**
 * REST API endpoints exposed by the agent plugin for the dashboard to call.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_REST_API {

    private const NAMESPACE = 'wum-agent/v1';

    /**
     * Register REST routes.
     */
    public function register_routes(): void {

        // Execute update — called by dashboard
        register_rest_route(self::NAMESPACE, '/execute-update', [
            'methods'             => 'POST',
            'callback'            => [$this, 'execute_update'],
            'permission_callback' => [$this, 'verify_hmac'],
        ]);

        // Status check — called by dashboard
        register_rest_route(self::NAMESPACE, '/status', [
            'methods'             => 'POST',
            'callback'            => [$this, 'status'],
            'permission_callback' => [$this, 'verify_hmac'],
        ]);

        // Get installed items — called by dashboard
        register_rest_route(self::NAMESPACE, '/installed-items', [
            'methods'             => 'GET',
            'callback'            => [$this, 'installed_items'],
            'permission_callback' => [$this, 'verify_hmac'],
        ]);

        // Self-update the agent plugin — called by dashboard
        register_rest_route(self::NAMESPACE, '/self-update', [
            'methods'             => 'POST',
            'callback'            => [$this, 'self_update'],
            'permission_callback' => [$this, 'verify_hmac'],
        ]);
    }

    /**
     * HMAC permission callback.
     */
    public function verify_hmac(WP_REST_Request $request): bool {
        if (! WUM_Agent::is_connected()) {
            return false;
        }

        return WUM_HMAC::verify_request($request);
    }

    /**
     * Execute updates requested by the dashboard.
     */
    public function execute_update(WP_REST_Request $request): WP_REST_Response {
        $update_job_id = $request->get_param('update_job_id');
        $items         = $request->get_param('items');

        if (empty($update_job_id) || empty($items) || ! is_array($items)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => ['code' => 'validation_error', 'message' => 'update_job_id and items are required.'],
            ], 400);
        }

        // Signal that we're executing an update (for error capture hooks)
        do_action('wum_executing_update');

        $results = WUM_Updater::execute($update_job_id, $items);

        return new WP_REST_Response([
            'success' => true,
            'results' => $results,
        ], 200);
    }

    /**
     * Return agent status.
     */
    public function status(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'success'        => true,
            'wp_version'     => get_bloginfo('version'),
            'php_version'    => phpversion(),
            'plugin_version' => WUM_AGENT_VERSION,
            'uptime'         => true,
        ], 200);
    }

    /**
     * Return installed items.
     */
    public function installed_items(WP_REST_Request $request): WP_REST_Response {
        // Trigger a fresh check
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Reuse the sync logic to build the items list
        $items = array_merge(
            self::get_items_from_sync('plugin'),
            self::get_items_from_sync('theme'),
            self::get_items_from_sync('core')
        );

        return new WP_REST_Response([
            'success' => true,
            'items'   => $items,
        ], 200);
    }

    /**
     * Delegate to WUM_Sync methods via a quick run.
     */
    private static function get_items_from_sync(string $type): array {
        // WUM_Sync::run() handles the full process, but for the REST endpoint
        // we use a direct approach. This mirrors WUM_Sync logic.
        switch ($type) {
            case 'plugin':
                return self::get_plugin_items();
            case 'theme':
                return self::get_theme_items();
            case 'core':
                return self::get_core_item();
            default:
                return [];
        }
    }

    private static function get_plugin_items(): array {
        $plugins        = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $update_data    = get_site_transient('update_plugins');
        $items          = [];

        foreach ($plugins as $file => $plugin) {
            $slug = dirname($file);
            if ($slug === '.') {
                $slug = basename($file, '.php');
            }

            $available_version = isset($update_data->response[$file])
                ? ($update_data->response[$file]->new_version ?? null)
                : null;

            $items[] = [
                'type'              => 'plugin',
                'slug'              => $slug,
                'name'              => $plugin['Name'],
                'current_version'   => $plugin['Version'],
                'available_version' => $available_version,
                'is_active'         => in_array($file, $active_plugins, true),
                'tested_wp_version' => isset($update_data->response[$file])
                    ? ($update_data->response[$file]->tested ?? null)
                    : null,
            ];
        }

        return $items;
    }

    private static function get_theme_items(): array {
        $themes      = wp_get_themes();
        $update_data = get_site_transient('update_themes');
        $active_slug = get_stylesheet();
        $items       = [];

        foreach ($themes as $slug => $theme) {
            $available_version = isset($update_data->response[$slug])
                ? ($update_data->response[$slug]['new_version'] ?? null)
                : null;

            $items[] = [
                'type'              => 'theme',
                'slug'              => $slug,
                'name'              => $theme->get('Name'),
                'current_version'   => $theme->get('Version'),
                'available_version' => $available_version,
                'is_active'         => ($slug === $active_slug),
                'tested_wp_version' => null,
            ];
        }

        return $items;
    }

    /**
     * Self-update the agent plugin from a zip provided by the dashboard.
     */
    public function self_update(WP_REST_Request $request): WP_REST_Response {
        $download_url = $request->get_param('download_url');

        if (empty($download_url)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => ['code' => 'missing_url', 'message' => 'download_url is required.'],
            ], 400);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        // Download the zip to a temp file
        $temp_file = download_url($download_url, 60);

        if (is_wp_error($temp_file)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => ['code' => 'download_failed', 'message' => $temp_file->get_error_message()],
            ], 500);
        }

        $plugin_dir = WP_PLUGIN_DIR . '/wum-agent';

        // Extract zip over the existing plugin directory
        $unzip_result = unzip_file($temp_file, $plugin_dir);
        @unlink($temp_file);

        if (is_wp_error($unzip_result)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => ['code' => 'unzip_failed', 'message' => $unzip_result->get_error_message()],
            ], 500);
        }

        // Read the new version from the updated plugin file
        $new_version = WUM_AGENT_VERSION;
        $plugin_file = $plugin_dir . '/wum-agent.php';
        if (file_exists($plugin_file)) {
            $plugin_data = get_file_data($plugin_file, ['Version' => 'Version']);
            $new_version = $plugin_data['Version'] ?? WUM_AGENT_VERSION;
        }

        return new WP_REST_Response([
            'success'     => true,
            'new_version' => $new_version,
            'message'     => 'Agent plugin updated successfully.',
        ], 200);
    }

    private static function get_core_item(): array {
        $update_data       = get_site_transient('update_core');
        $available_version = null;

        if (! empty($update_data->updates)) {
            foreach ($update_data->updates as $update) {
                if ($update->response === 'upgrade') {
                    $available_version = $update->version;
                    break;
                }
            }
        }

        return [[
            'type'              => 'core',
            'slug'              => 'wordpress',
            'name'              => 'WordPress',
            'current_version'   => get_bloginfo('version'),
            'available_version' => $available_version,
            'is_active'         => true,
            'tested_wp_version' => null,
        ]];
    }
}
