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

        // Delete cached transients and force a full refresh from wordpress.org
        // Without this, wp_update_plugins() may return early if transient exists
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');

        // Also clear any object cache to ensure fresh data
        wp_cache_flush();

        wp_update_plugins();
        wp_update_themes();

        // Log what the transient contains for requested items
        $update_data = get_site_transient('update_plugins');
        $requested_slugs = array_column($items, 'slug');
        $transient_debug = [];

        if ($update_data && isset($update_data->response)) {
            foreach ($update_data->response as $file => $info) {
                $dir = dirname($file);
                if (in_array($dir, $requested_slugs, true) || in_array(basename($file, '.php'), $requested_slugs, true)) {
                    $transient_debug[$file] = [
                        'slug'    => $info->slug ?? 'unknown',
                        'version' => $info->new_version ?? 'unknown',
                        'package' => ! empty($info->package) ? 'present' : 'MISSING',
                    ];
                }
            }
        }

        // Also check the no_update list — plugins WP thinks are already current
        if ($update_data && isset($update_data->no_update)) {
            foreach ($update_data->no_update as $file => $info) {
                $dir = dirname($file);
                if (in_array($dir, $requested_slugs, true) || in_array(basename($file, '.php'), $requested_slugs, true)) {
                    $transient_debug[$file] = [
                        'slug'    => $info->slug ?? 'unknown',
                        'version' => $info->new_version ?? 'unknown',
                        'status'  => 'NO_UPDATE_NEEDED',
                    ];
                }
            }
        }

        error_log('WUM Updater transient debug: ' . wp_json_encode($transient_debug));

        // Attempt to fix directory permissions before running updates
        self::fix_directory_permissions();

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
        self::report_results($update_job_id, $results, $transient_debug);

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

        // Fix permissions on this plugin's directory recursively
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        if (dirname($plugin_file) !== '.' && is_dir($plugin_dir)) {
            self::chmod_recursive($plugin_dir);
        }

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
            $messages = $skin->get_upgrade_messages();
            $skin_errors = $skin->get_errors();
            $error_detail = '';

            if ($skin_errors && $skin_errors->has_errors()) {
                $error_detail = implode(' ', $skin_errors->get_error_messages());
            } elseif (! empty($messages)) {
                $error_detail = implode(' ', $messages);
            }

            // Check if the update package URL is missing (common for premium plugins)
            $update_data = get_site_transient('update_plugins');
            $has_package = isset($update_data->response[$plugin_file]->package)
                && ! empty($update_data->response[$plugin_file]->package);

            if (! $has_package) {
                $error_detail = 'No download package available. This is usually a premium plugin that requires an active license key on this site. ' . $error_detail;
            }

            return [
                'old_version'       => $old_version,
                'resulting_version' => $new_version,
                'status'            => 'failed',
                'raw_result'        => ['messages' => $messages],
                'error_message'     => $error_detail ?: 'Upgrade returned false. Check file permissions.',
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

        // Fix permissions on this theme's directory recursively
        $theme_dir = get_theme_root() . '/' . $item['slug'];
        if (is_dir($theme_dir)) {
            self::chmod_recursive($theme_dir);
        }

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
    private static function report_results(int $update_job_id, array $results, array $transient_debug = []): void {
        WUM_API_Client::post('agent/update-result', [
            'update_job_id'   => $update_job_id,
            'items'           => $results,
            'transient_debug' => $transient_debug,
        ]);
    }

    /**
     * Attempt to fix top-level directory permissions for update paths.
     */
    private static function fix_directory_permissions(): void {
        $dirs = [
            WP_CONTENT_DIR,
            WP_PLUGIN_DIR,
            get_theme_root(),
            WP_CONTENT_DIR . '/upgrade',
        ];

        // Ensure the upgrade temp directory exists
        if (! is_dir(WP_CONTENT_DIR . '/upgrade')) {
            @mkdir(WP_CONTENT_DIR . '/upgrade', 0755, true);
        }

        foreach ($dirs as $dir) {
            if (is_dir($dir) && ! is_writable($dir)) {
                @chmod($dir, 0755);
            }
        }
    }

    /**
     * Recursively set permissions: 755 for directories, 644 for files.
     */
    private static function chmod_recursive(string $path): void {
        if (! is_dir($path)) {
            return;
        }

        @chmod($path, 0755);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @chmod($item->getPathname(), 0755);
            } else {
                @chmod($item->getPathname(), 0644);
            }
        }
    }

    /**
     * Check filesystem writability for key directories.
     * Returns an array of paths and their writable status.
     */
    public static function check_filesystem(): array {
        $paths = [
            'wp_content'  => WP_CONTENT_DIR,
            'plugins'     => WP_PLUGIN_DIR,
            'themes'      => get_theme_root(),
            'upgrade'     => WP_CONTENT_DIR . '/upgrade',
            'wp_root'     => ABSPATH,
        ];

        $results = [];
        foreach ($paths as $key => $path) {
            $results[$key] = [
                'path'     => $path,
                'exists'   => file_exists($path),
                'writable' => is_writable($path),
                'owner'    => function_exists('posix_getpwuid') && file_exists($path)
                    ? (posix_getpwuid(fileowner($path))['name'] ?? 'unknown')
                    : 'unknown',
            ];
        }

        $results['fs_method']  = defined('FS_METHOD') ? FS_METHOD : 'not set';
        $results['web_user']   = function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown')
            : 'unknown';
        $results['all_writable'] = ! in_array(false, array_column(
            array_filter($results, 'is_array'),
            'writable'
        ), true);

        return $results;
    }
}
