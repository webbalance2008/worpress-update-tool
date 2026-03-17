<?php
/**
 * Full sync of installed items to the dashboard.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_Sync {

    /**
     * Schedule the sync cron event.
     */
    public static function schedule(): void {
        if (! wp_next_scheduled('wum_sync_event')) {
            wp_schedule_event(time(), 'hourly', 'wum_sync_event');
        }

        add_action('wum_sync_event', [self::class, 'run']);
    }

    /**
     * Run a full sync of installed items to the dashboard.
     */
    public static function run(): void {
        if (! WUM_Agent::is_connected()) {
            return;
        }

        // Force WordPress to check for updates
        wp_update_plugins();
        wp_update_themes();
        wp_version_check();

        $items = array_merge(
            self::get_plugin_items(),
            self::get_theme_items(),
            self::get_core_item()
        );

        $data = [
            'wp_version'      => get_bloginfo('version'),
            'php_version'     => phpversion(),
            'active_theme'    => wp_get_theme()->get('Name'),
            'installed_items' => $items,
            'filesystem'      => WUM_Updater::check_filesystem(),
        ];

        WUM_API_Client::post('agent/sync', $data);
    }

    /**
     * Get all installed plugins with update info.
     */
    private static function get_plugin_items(): array {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins        = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $update_data    = get_site_transient('update_plugins');
        $items          = [];

        foreach ($plugins as $file => $plugin) {
            $slug = dirname($file);
            if ($slug === '.') {
                $slug = basename($file, '.php');
            }

            $available_version = null;
            $tested_wp         = null;

            if (isset($update_data->response[$file])) {
                $available_version = $update_data->response[$file]->new_version ?? null;
                $tested_wp         = $update_data->response[$file]->tested ?? null;
            }

            $items[] = [
                'type'               => 'plugin',
                'slug'               => $slug,
                'name'               => $plugin['Name'],
                'current_version'    => $plugin['Version'],
                'available_version'  => $available_version,
                'is_active'          => in_array($file, $active_plugins, true),
                'auto_update_enabled' => self::is_auto_update_enabled('plugin', $file),
                'tested_wp_version'  => $tested_wp,
            ];
        }

        return $items;
    }

    /**
     * Get all installed themes with update info.
     */
    private static function get_theme_items(): array {
        $themes      = wp_get_themes();
        $update_data = get_site_transient('update_themes');
        $active_slug = get_stylesheet();
        $items       = [];

        foreach ($themes as $slug => $theme) {
            $available_version = null;

            if (isset($update_data->response[$slug])) {
                $available_version = $update_data->response[$slug]['new_version'] ?? null;
            }

            $items[] = [
                'type'               => 'theme',
                'slug'               => $slug,
                'name'               => $theme->get('Name'),
                'current_version'    => $theme->get('Version'),
                'available_version'  => $available_version,
                'is_active'          => ($slug === $active_slug),
                'auto_update_enabled' => self::is_auto_update_enabled('theme', $slug),
                'tested_wp_version'  => null,
            ];
        }

        return $items;
    }

    /**
     * Get WordPress core as an installed item.
     */
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
            'type'               => 'core',
            'slug'               => 'wordpress',
            'name'               => 'WordPress',
            'current_version'    => get_bloginfo('version'),
            'available_version'  => $available_version,
            'is_active'          => true,
            'auto_update_enabled' => self::is_auto_update_enabled('core', 'wordpress'),
            'tested_wp_version'  => null,
        ]];
    }

    /**
     * Check if auto-updates are enabled for an item.
     */
    private static function is_auto_update_enabled(string $type, string $item): bool {
        if ($type === 'plugin') {
            $auto_updates = (array) get_site_option('auto_update_plugins', []);
            return in_array($item, $auto_updates, true);
        }

        if ($type === 'theme') {
            $auto_updates = (array) get_site_option('auto_update_themes', []);
            return in_array($item, $auto_updates, true);
        }

        // Core auto-updates
        return defined('WP_AUTO_UPDATE_CORE') && WP_AUTO_UPDATE_CORE;
    }
}
