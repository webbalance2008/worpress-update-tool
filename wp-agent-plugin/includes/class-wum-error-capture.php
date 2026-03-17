<?php
/**
 * Captures WordPress errors and fatal errors for reporting to the dashboard.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_Error_Capture {

    private array $captured_errors = [];

    public function __construct() {
        // Register shutdown handler to catch fatal errors
        register_shutdown_function([$this, 'handle_shutdown']);

        // Hook into WP error logging
        add_action('wp_error_added', [$this, 'capture_wp_error'], 10, 4);
    }

    /**
     * Capture WP_Error instances as they are created during updates.
     */
    public function capture_wp_error(string|int $code, string $message, $data, WP_Error $wp_error): void {
        // Only capture errors during update operations
        if (! doing_action('upgrader_process_complete') && ! doing_action('wum_executing_update')) {
            return;
        }

        $this->captured_errors[] = [
            'source'   => 'wp_error',
            'severity' => 'error',
            'message'  => $message,
            'context'  => [
                'code' => $code,
                'data' => is_scalar($data) ? $data : wp_json_encode($data),
            ],
        ];
    }

    /**
     * Handle PHP shutdown to detect fatal errors.
     */
    public function handle_shutdown(): void {
        $error = error_get_last();

        if (! $error || ! in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $this->captured_errors[] = [
            'source'   => 'fatal',
            'severity' => 'critical',
            'message'  => $error['message'],
            'context'  => [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type'],
            ],
        ];

        // Attempt to send captured errors to the dashboard
        $this->flush_errors();
    }

    /**
     * Send any captured errors to the dashboard.
     */
    public function flush_errors(): void {
        if (empty($this->captured_errors) || ! WUM_Agent::is_connected()) {
            return;
        }

        WUM_API_Client::post('agent/error-report', [
            'errors' => $this->captured_errors,
        ]);

        $this->captured_errors = [];
    }

    /**
     * Get captured errors (for use by the updater before sending results).
     */
    public function get_captured_errors(): array {
        return $this->captured_errors;
    }
}
