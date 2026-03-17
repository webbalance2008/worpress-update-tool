<?php
/**
 * Admin settings page for the WP Update Manager Agent plugin.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'handle_form_actions']);
    }

    /**
     * Register the admin menu page.
     */
    public function add_menu_page(): void {
        add_management_page(
            __('WP Update Manager', 'wum-agent'),
            __('Update Manager', 'wum-agent'),
            'manage_options',
            'wum-agent',
            [$this, 'render_page']
        );
    }

    /**
     * Handle form submissions (connect/disconnect).
     */
    public function handle_form_actions(): void {
        if (! isset($_POST['wum_action']) || ! current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('wum_agent_settings');

        if ($_POST['wum_action'] === 'connect') {
            $dashboard_url = sanitize_url($_POST['dashboard_url'] ?? '');
            $token         = sanitize_text_field($_POST['registration_token'] ?? '');

            if (empty($dashboard_url) || empty($token)) {
                add_settings_error('wum_agent', 'missing_fields', __('Dashboard URL and registration token are required.', 'wum-agent'));
                return;
            }

            // Require HTTPS in production, allow HTTP for localhost/dev
            $is_local = preg_match('/^https?:\/\/(localhost|127\.0\.0\.1|10\.\d|192\.168\.)/', $dashboard_url);
            if (! $is_local && strpos($dashboard_url, 'https://') !== 0) {
                add_settings_error('wum_agent', 'https_required', __('Dashboard URL must use HTTPS (HTTP allowed for localhost only).', 'wum-agent'));
                return;
            }

            $result = WUM_Registration::connect($dashboard_url, $token);

            if ($result['success']) {
                add_settings_error('wum_agent', 'connected', $result['message'], 'success');
            } else {
                add_settings_error('wum_agent', 'connection_failed', $result['message']);
            }
        }

        if ($_POST['wum_action'] === 'disconnect') {
            WUM_Registration::disconnect();
            add_settings_error('wum_agent', 'disconnected', __('Disconnected from dashboard.', 'wum-agent'), 'success');
        }
    }

    /**
     * Render the admin settings page.
     */
    public function render_page(): void {
        if (! current_user_can('manage_options')) {
            return;
        }

        $is_connected  = WUM_Agent::is_connected();
        $dashboard_url = WUM_Agent::get_setting('dashboard_url');
        $site_id       = WUM_Agent::get_setting('site_id');

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Update Manager Agent', 'wum-agent'); ?></h1>

            <?php settings_errors('wum_agent'); ?>

            <?php if ($is_connected) : ?>
                <div class="notice notice-success inline" style="margin: 15px 0;">
                    <p>
                        <strong><?php esc_html_e('Status: Connected', 'wum-agent'); ?></strong><br>
                        <?php
                        printf(
                            /* translators: %s: dashboard URL */
                            esc_html__('Dashboard: %s', 'wum-agent'),
                            '<code>' . esc_html($dashboard_url) . '</code>'
                        );
                        ?>
                        <br>
                        <?php
                        printf(
                            /* translators: %s: site ID */
                            esc_html__('Site ID: %s', 'wum-agent'),
                            '<code>' . esc_html($site_id) . '</code>'
                        );
                        ?>
                    </p>
                </div>

                <form method="post">
                    <?php wp_nonce_field('wum_agent_settings'); ?>
                    <input type="hidden" name="wum_action" value="disconnect">
                    <p>
                        <?php submit_button(__('Disconnect', 'wum-agent'), 'secondary', 'submit', false); ?>
                    </p>
                </form>

                <h2><?php esc_html_e('Connection Info', 'wum-agent'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('WordPress Version', 'wum-agent'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('PHP Version', 'wum-agent'); ?></th>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Plugin Version', 'wum-agent'); ?></th>
                        <td><?php echo esc_html(WUM_AGENT_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Active Theme', 'wum-agent'); ?></th>
                        <td><?php echo esc_html(wp_get_theme()->get('Name')); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Next Heartbeat', 'wum-agent'); ?></th>
                        <td>
                            <?php
                            $next = wp_next_scheduled('wum_heartbeat_event');
                            echo $next ? esc_html(human_time_diff(time(), $next) . ' from now') : esc_html__('Not scheduled', 'wum-agent');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Next Sync', 'wum-agent'); ?></th>
                        <td>
                            <?php
                            $next = wp_next_scheduled('wum_sync_event');
                            echo $next ? esc_html(human_time_diff(time(), $next) . ' from now') : esc_html__('Not scheduled', 'wum-agent');
                            ?>
                        </td>
                    </tr>
                </table>

            <?php else : ?>
                <p><?php esc_html_e('Connect this site to your WP Update Manager dashboard.', 'wum-agent'); ?></p>

                <form method="post">
                    <?php wp_nonce_field('wum_agent_settings'); ?>
                    <input type="hidden" name="wum_action" value="connect">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="dashboard_url"><?php esc_html_e('Dashboard URL', 'wum-agent'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="dashboard_url" id="dashboard_url"
                                       value="<?php echo esc_attr($dashboard_url); ?>"
                                       class="regular-text" required
                                       placeholder="https://your-dashboard.example.com">
                                <p class="description"><?php esc_html_e('The URL of your WP Update Manager dashboard. Must use HTTPS.', 'wum-agent'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="registration_token"><?php esc_html_e('Registration Token', 'wum-agent'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="registration_token" id="registration_token"
                                       class="regular-text" required
                                       placeholder="Enter the token from your dashboard">
                                <p class="description"><?php esc_html_e('The one-time token generated when you added this site to the dashboard.', 'wum-agent'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Connect to Dashboard', 'wum-agent')); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
