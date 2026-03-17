<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Http\Request;

class HmacService
{
    /**
     * Maximum age of a signed request in seconds (replay protection).
     */
    private const MAX_AGE_SECONDS = 300;

    /**
     * Generate HMAC signature for an outbound request to an agent.
     */
    public function signRequest(Site $site, string $method, string $path, string $body = ''): array
    {
        $timestamp = time();
        $bodyHash = hash('sha256', $body);
        $payload = "{$timestamp}.{$method}.{$path}.{$bodyHash}";
        $signature = hash_hmac('sha256', $payload, $site->auth_secret);

        return [
            'X-WUM-Signature' => $signature,
            'X-WUM-Timestamp' => (string) $timestamp,
            'X-WUM-Site-ID' => (string) $site->id,
        ];
    }

    /**
     * Verify HMAC signature on an inbound request from an agent.
     */
    public function verifyRequest(Request $request, Site $site): bool
    {
        $signature = $request->header('X-WUM-Signature');
        $timestamp = (int) $request->header('X-WUM-Timestamp');

        if (! $signature || ! $timestamp) {
            return false;
        }

        // Replay protection
        if (abs(time() - $timestamp) > self::MAX_AGE_SECONDS) {
            return false;
        }

        $method = strtoupper($request->method());
        $path = '/' . ltrim($request->path(), '/');
        $bodyHash = hash('sha256', $request->getContent());
        $payload = "{$timestamp}.{$method}.{$path}.{$bodyHash}";
        $expected = hash_hmac('sha256', $payload, $site->auth_secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Generate a cryptographically secure shared secret.
     */
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a one-time registration token.
     */
    public function generateRegistrationToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
