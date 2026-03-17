<?php
/**
 * Executes WordPress updates using core upgrader APIs.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_Updater {

    /**
     * Execute a list of update requests and return results.
     *
     * @param int   $update_job_id Job ID from the dashboard.
     * @param array $items         Array of items to update.
     * @return array Results for each item.
     */
    public static function execute(int $update_job_id, array $items): array {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $results = [];

        foreach ($items as $item) {
            $result = match ($item['type']) {
                'plugin' => self::update_plugin($item),
                'theme'  => self::update_theme($item),
                'core'   => self::update_core($item),
                default  => [
                    'status'        => 'failed',
                    'error_message' => "Unknown item type: {$item['type']}",
                ],
            };

            $result['update_job_item_id'] = $item['update_job_item_id'];
            $result['slug']               = $item['slug'];
            $result['type']               = $item['type'];
            $results[]                    = $result;
        }

        // Report results back to dashboard
        self::report_results($update_job_id, $results);

        return $results;
    }

    /**
     * Update a single plugin.
     */
    private static function update_plugin(array $item): array {
        $plugin_file = self::find_plugin_file($item['slug']);

        if (! $plugin_file) {
            return [
                'status'        => 'failed',
                'error_message' => "Plugin file not found for slug: {$item['slug']}",
            ];
        }

        $old_version = self::get_plugin_version($plugin_file);

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result   = $upgrader->upgrade($plugin_file);

        // Clear plugin cache so we read the new version
        wp_cache_delete('plugins', 'plugins');

        $new_version = self::get_plugin_version($plugin_file);

        if (is_wp_error($result)) {
            return [
                'old_version'       => $old_version,
                'resulting_version' => $new_version,
                'status'            => 'failed',
                'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
                'error_message'     => $result->get_error_message(),
            ];
        }

        if ($result === false) {
            return [
                'old_version'       => $old_version,
                'resulting_version' => $new_version,
                'status'            => 'failed',
                'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
                'error_message'     => 'Upgrade returned false. Check file permissions.',
            ];
        }

        return [
            'old_version'       => $old_version,
            'resulting_version' => $new_version,
            'status'            => 'completed',
            'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
        ];
    }

    /**
     * Update a single theme.
     */
    private static function update_theme(array $item): array {
        $theme = wp_get_theme($item['slug']);

        if (! $theme->exists()) {
            return [
                'status'        => 'failed',
                'error_message' => "Theme not found: {$item['slug']}",
            ];
        }

        $old_version = $theme->get('Version');

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Theme_Upgrader($skin);
        $result   = $upgrader->upgrade($item['slug']);

        // Refresh theme data
        wp_clean_themes_cache();
        $theme       = wp_get_theme($item['slug']);
        $new_version = $theme->get('Version');

        if (is_wp_error($result)) {
            return [
                'old_version'       => $old_version,
                'resulting_version' => $new_version,
                'status'            => 'failed',
                'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
                'error_message'     => $result->get_error_message(),
            ];
        }

        return [
            'old_version'       => $old_version,
            'resulting_version' => $new_version,
            'status'            => 'completed',
            'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
        ];
    }

    /**
     * Update WordPress core.
     */
    private static function update_core(array $item): array {
        $old_version = get_bloginfo('version');

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Core_Upgrader($skin);

        // Get the update object
        $updates = get_site_transient('update_core');
        $update  = null;

        if (! empty($updates->updates)) {
            foreach ($updates->updates as $u) {
                if ($u->response === 'upgrade') {
                    $update = $u;
                    break;
                }
            }
        }

        if (! $update) {
            return [
                'old_version'       => $old_version,
                'resulting_version' => $old_version,
                'status'            => 'failed',
                'error_message'     => 'No core update available.',
            ];
        }

        $result = $upgrader->upgrade($update);

        $new_version = get_bloginfo('version');

        if (is_wp_error($result)) {
            return [
                'old_version'       => $old_version,
                'resulting_version' => $new_version,
                'status'            => 'failed',
                'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
                'error_message'     => $result->get_error_message(),
            ];
        }

        return [
            'old_version'       => $old_version,
            'resulting_version' => $new_version,
            'status'            => 'completed',
            'raw_result'        => ['messages' => $skin->get_upgrade_messages()],
        ];
    }

    /**
     * Find the plugin file path from a slug.
     */
    private static function find_plugin_file(string $slug): ?string {
        $plugins = get_plugins();

        foreach ($plugins as $file => $data) {
            if (dirname($file) === $slug || basename($file, '.php') === $slug) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Get the current version of a plugin by file.
     */
    private static function get_plugin_version(string $plugin_file): string {
        $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false);
        return $data['Version'] ?? 'unknown';
    }

    /**
     * Report update results back to the dashboard.
     */
    private static function report_results(int $update_job_id, array $results): void {
        WUM_API_Client::post('agent/update-result', [
            'update_job_id' => $update_job_id,
            'items'         => $results,
        ]);
    }
}
