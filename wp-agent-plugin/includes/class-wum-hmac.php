<?php
/**
 * HMAC signature generation and verification for agent-dashboard communication.
 */

if (! defined('ABSPATH')) {
    exit;
}

class WUM_HMAC {

    private const MAX_AGE_SECONDS = 300;

    /**
     * Generate signed headers for an outbound request to the dashboard.
     */
    public static function sign_request(string $method, string $path, string $body = ''): array {
        $secret    = WUM_Agent::get_setting('auth_secret');
        $site_id   = WUM_Agent::get_setting('site_id');
        $timestamp = time();
        $body_hash = hash('sha256', $body);
        $payload   = "{$timestamp}.{$method}.{$path}.{$body_hash}";
        $signature = hash_hmac('sha256', $payload, $secret);

        return [
            'X-WUM-Signature' => $signature,
            'X-WUM-Timestamp' => (string) $timestamp,
            'X-WUM-Site-ID'   => (string) $site_id,
        ];
    }

    /**
     * Verify a signed inbound request from the dashboard.
     */
    public static function verify_request(WP_REST_Request $request): bool {
        $secret    = WUM_Agent::get_setting('auth_secret');
        $signature = $request->get_header('X_WUM_Signature');
        $timestamp = (int) $request->get_header('X_WUM_Timestamp');
        $site_id   = $request->get_header('X_WUM_Site_ID');

        if (empty($signature) || empty($timestamp) || empty($site_id)) {
            return false;
        }

        // Verify site ID matches
        if ((string) $site_id !== (string) WUM_Agent::get_setting('site_id')) {
            return false;
        }

        // Replay protection
        if (abs(time() - $timestamp) > self::MAX_AGE_SECONDS) {
            return false;
        }

        $method    = strtoupper($request->get_method());
        $path      = '/' . ltrim($request->get_route(), '/');
        $body_hash = hash('sha256', $request->get_body());
        $payload   = "{$timestamp}.{$method}.{$path}.{$body_hash}";
        $expected  = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
