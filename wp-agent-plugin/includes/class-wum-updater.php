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
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');
        wp_cache_flush();
        wp_update_plugins();
        wp_update_themes();

        // Log what the transient contains for requested items
        $transient_debug = self::build_transient_debug($items);
        error_log('WUM Updater transient debug: ' . wp_json_encode($transient_debug));

        // Attempt to fix directory permissions before running updates
        self::fix_directory_permissions();

        // Group items by type
        $plugins = array_filter($items, fn($i) => $i['type'] === 'plugin');
        $themes  = array_filter($items, fn($i) => $i['type'] === 'theme');
        $cores   = array_filter($items, fn($i) => $i['type'] === 'core');

        $results = [];

        // Handle core updates first (one at a time)
        foreach ($cores as $item) {
            $result = self::update_core($item);
            $result['update_job_item_id'] = $item['update_job_item_id'];
            $result['slug'] = $item['slug'];
            $result['type'] = $item['type'];
            $results[] = $result;
        }

        // Handle theme updates using bulk_upgrade
        if (! empty($themes)) {
            $theme_results = self::bulk_update_themes($themes);
            $results = array_merge($results, $theme_results);
        }

        // Handle plugin updates using bulk_upgrade
        if (! empty($plugins)) {
            $plugin_results = self::bulk_update_plugins($plugins);
            $results = array_merge($results, $plugin_results);
        }

        // Report results back to dashboard
        self::report_results($update_job_id, $results, $transient_debug);

        return $results;
    }

    /**
     * Bulk update plugins using Plugin_Upgrader::bulk_upgrade().
     * This avoids the transient refresh issue that occurs when upgrading one at a time.
     */
    private static function bulk_update_plugins(array $items): array {
        $results = [];
        $plugin_files = [];
        $item_map = []; // Maps plugin_file => item data

        // Resolve slugs to plugin files and record old versions
        foreach ($items as $item) {
            $plugin_file = self::find_plugin_file($item['slug']);

            if (! $plugin_file) {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $item['slug'],
                    'type'               => 'plugin',
                    'status'             => 'failed',
                    'error_message'      => "Plugin file not found for slug: {$item['slug']}",
                ];
                continue;
            }

            // Fix permissions on this plugin's directory recursively
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
            if (dirname($plugin_file) !== '.' && is_dir($plugin_dir)) {
                self::chmod_recursive($plugin_dir);
            }

            $plugin_files[] = $plugin_file;
            $item_map[$plugin_file] = $item;
            $item_map[$plugin_file]['old_version'] = self::get_plugin_version($plugin_file);
        }

        if (empty($plugin_files)) {
            return $results;
        }

        // Run bulk upgrade — this handles the transient correctly for multiple plugins
        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $bulk_results = $upgrader->bulk_upgrade($plugin_files);

        // Clear plugin cache so we read new versions
        wp_cache_delete('plugins', 'plugins');

        // Map bulk results back to our item format
        foreach ($plugin_files as $plugin_file) {
            $item = $item_map[$plugin_file];
            $old_version = $item['old_version'];
            $new_version = self::get_plugin_version($plugin_file);
            $upgrade_result = $bulk_results[$plugin_file] ?? null;

            if (is_wp_error($upgrade_result)) {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $item['slug'],
                    'type'               => 'plugin',
                    'old_version'        => $old_version,
                    'resulting_version'  => $new_version,
                    'status'             => 'failed',
                    'error_message'      => $upgrade_result->get_error_message(),
                ];
            } elseif ($upgrade_result === false || $upgrade_result === null) {
                // Check why it failed
                $error_detail = '';
                $update_data = get_site_transient('update_plugins');

                // Check if plugin is in the no_update list (already current)
                $in_no_update = isset($update_data->no_update[$plugin_file]);
                $has_package = isset($update_data->response[$plugin_file]->package)
                    && ! empty($update_data->response[$plugin_file]->package);

                if ($in_no_update) {
                    $error_detail = 'No download package available. The plugin is at the latest version.';
                } elseif (! $has_package) {
                    $error_detail = 'No download package available. This is usually a premium plugin that requires an active license key on this site.';
                } else {
                    $error_detail = 'Upgrade returned false. Check file permissions.';
                }

                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $item['slug'],
                    'type'               => 'plugin',
                    'old_version'        => $old_version,
                    'resulting_version'  => $new_version,
                    'status'             => 'failed',
                    'error_message'      => $error_detail,
                ];
            } else {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $item['slug'],
                    'type'               => 'plugin',
                    'old_version'        => $old_version,
                    'resulting_version'  => $new_version,
                    'status'             => 'completed',
                ];
            }
        }

        return $results;
    }

    /**
     * Bulk update themes using Theme_Upgrader::bulk_upgrade().
     */
    private static function bulk_update_themes(array $items): array {
        $results = [];
        $theme_slugs = [];
        $item_map = [];

        foreach ($items as $item) {
            $theme = wp_get_theme($item['slug']);

            if (! $theme->exists()) {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $item['slug'],
                    'type'               => 'theme',
                    'status'             => 'failed',
                    'error_message'      => "Theme not found: {$item['slug']}",
                ];
                continue;
            }

            // Fix permissions on this theme's directory recursively
            $theme_dir = get_theme_root() . '/' . $item['slug'];
            if (is_dir($theme_dir)) {
                self::chmod_recursive($theme_dir);
            }

            $theme_slugs[] = $item['slug'];
            $item_map[$item['slug']] = $item;
            $item_map[$item['slug']]['old_version'] = $theme->get('Version');
        }

        if (empty($theme_slugs)) {
            return $results;
        }

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Theme_Upgrader($skin);
        $bulk_results = $upgrader->bulk_upgrade($theme_slugs);

        wp_clean_themes_cache();

        foreach ($theme_slugs as $slug) {
            $item = $item_map[$slug];
            $old_version = $item['old_version'];
            $theme = wp_get_theme($slug);
            $new_version = $theme->get('Version');
            $upgrade_result = $bulk_results[$slug] ?? null;

            if (is_wp_error($upgrade_result)) {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $slug,
                    'type'               => 'theme',
                    'old_version'        => $old_version,
                    'resulting_version'  => $new_version,
                    'status'             => 'failed',
                    'error_message'      => $upgrade_result->get_error_message(),
                ];
            } elseif ($upgrade_result === false || $upgrade_result === null) {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $slug,
                    'type'               => 'theme',
                    'old_version'        => $old_version,
                    'resulting_version'  => $new_version,
                    'status'             => 'failed',
                    'error_message'      => 'Theme upgrade failed. Check file permissions.',
                ];
            } else {
                $results[] = [
                    'update_job_item_id' => $item['update_job_item_id'],
                    'slug'               => $slug,
                    'type'               => 'theme',
                    'old_version'        => $old_version,
                    'resulting_version'  => $new_version,
                    'status'             => 'completed',
                ];
            }
        }

        return $results;
    }

    /**
     * Update WordPress core.
     */
    private static function update_core(array $item): array {
        $old_version = get_bloginfo('version');

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Core_Upgrader($skin);

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
     * Build debug info about what the update transient contains for requested items.
     */
    private static function build_transient_debug(array $items): array {
        $update_data = get_site_transient('update_plugins');
        $requested_slugs = array_column($items, 'slug');
        $debug = [];

        if ($update_data && isset($update_data->response)) {
            foreach ($update_data->response as $file => $info) {
                $dir = dirname($file);
                if (in_array($dir, $requested_slugs, true) || in_array(basename($file, '.php'), $requested_slugs, true)) {
                    $debug[$file] = [
                        'slug'    => $info->slug ?? 'unknown',
                        'version' => $info->new_version ?? 'unknown',
                        'package' => ! empty($info->package) ? 'present' : 'MISSING',
                    ];
                }
            }
        }

        if ($update_data && isset($update_data->no_update)) {
            foreach ($update_data->no_update as $file => $info) {
                $dir = dirname($file);
                if (in_array($dir, $requested_slugs, true) || in_array(basename($file, '.php'), $requested_slugs, true)) {
                    $debug[$file] = [
                        'slug'    => $info->slug ?? 'unknown',
                        'version' => $info->new_version ?? 'unknown',
                        'status'  => 'NO_UPDATE_NEEDED',
                    ];
                }
            }
        }

        return $debug;
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
    public static function chmod_recursive(string $path): void {
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
