<?php
/**
 * HTTP client for sending signed requests to the central dashboard.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_API_Client {

    /**
     * Send a signed POST request to the dashboard.
     */
    public static function post(string $endpoint, array $data): array {
        $dashboard_url = rtrim(WUM_Agent::get_setting('dashboard_url'), '/');
        $path          = '/api/' . ltrim($endpoint, '/');
        $url           = $dashboard_url . $path;
        $body          = wp_json_encode($data);

        $headers = WUM_HMAC::sign_request('POST', $path, $body);
        $headers['Content-Type'] = 'application/json';
        $headers['Accept']       = 'application/json';

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'http_error',
                    'message' => $response->get_error_message(),
                ],
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            return [
                'success' => false,
                'error'   => $body['error'] ?? [
                    'code'    => 'http_' . $code,
                    'message' => "Dashboard returned HTTP {$code}",
                ],
            ];
        }

        return $body ?: ['success' => true];
    }

    /**
     * Send a registration request (uses token, not HMAC).
     */
    public static function register(string $dashboard_url, string $token): array {
        $url  = rtrim($dashboard_url, '/') . '/api/agent/register';
        $data = [
            'registration_token' => $token,
            'site_url'           => home_url(),
            'wp_version'         => get_bloginfo('version'),
            'php_version'        => phpversion(),
            'active_theme'       => wp_get_theme()->get('Name'),
            'server_software'    => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'plugin_version'     => WUM_AGENT_VERSION,
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode($data),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'http_error',
                    'message' => $response->get_error_message(),
                ],
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body ?: ['success' => false, 'error' => ['message' => 'Empty response']];
    }
}
