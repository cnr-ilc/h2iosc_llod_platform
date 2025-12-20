<?php

namespace App\Services\DSpace;

use Exception;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DSpace Authentication Service
 *
 * Handles JWT token management and CSRF token handling for DSpace API.
 * Tokens are cached in Redis with automatic refresh before expiration.
 */
class DSpaceAuthService
{
    /**
     * DSpace base URL
     */
    private string $baseUrl;

    /**
     * DSpace authentication username
     */
    private string $username;

    /**
     * DSpace authentication password
     */
    private string $password;

    /**
     * Cache key for JWT token storage
     */
    private string $cacheKey;

    /**
     * Cache key for CSRF token storage
     */
    private string $csrfCacheKey;

    /**
     * Cache TTL in minutes
     */
    private int $cacheTtl;

    /**
     * Initialize the authentication service with configuration values.
     */
    public function __construct()
    {
        $this->baseUrl = rtrim(config('dspace.base_url'), '/');
        $this->username = config('dspace.username');
        $this->password = config('dspace.password');
        $this->cacheKey = config('dspace.cache.prefix').'jwt_token';
        $this->csrfCacheKey = config('dspace.cache.prefix').'csrf_token';
        $this->cacheTtl = config('dspace.cache.ttl');
    }

    /**
     * Get a valid JWT token (from cache or via login).
     *
     * @return string The JWT token
     *
     * @throws Exception If login fails
     */
    public function getToken(): string
    {
        $cachedData = Cache::get($this->cacheKey);

        if ($cachedData) {
            /** @var array<string, mixed> $tokenData */
            $tokenData = json_decode($cachedData, true);

            if ($this->isTokenValid($tokenData)) {
                Log::debug('DSpace: Using cached JWT token');

                return $tokenData['token'];
            }

            Log::debug('DSpace: Token expiring, attempting refresh');
            try {
                return $this->refreshToken($tokenData['token']);
            } catch (Exception $e) {
                Log::warning('DSpace: Refresh failed, new login required', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::debug('DSpace: Performing login');

        return $this->login();
    }

    /**
     * Get the CSRF token from DSpace.
     *
     * First tries /api/security/csrf (DSpace 8+),
     * then falls back to /api/authn/status (DSpace 7.x).
     *
     * @return string The CSRF token
     *
     * @throws Exception If CSRF token cannot be obtained
     */
    private function getCsrfToken(): string
    {
        // First try the dedicated endpoint (DSpace 8+)
        /** @var Response $response */
        $response = Http::get("{$this->baseUrl}/api/security/csrf");

        $csrfToken = $response->header('DSPACE-XSRF-TOKEN');

        // If not found, try /api/authn/status (DSpace 7.x)
        if (! $csrfToken) {
            Log::debug('DSpace: /api/security/csrf failed, trying /api/authn/status');
            /** @var Response $response */
            $response = Http::get("{$this->baseUrl}/api/authn/status");
            $csrfToken = $response->header('DSPACE-XSRF-TOKEN');
        }

        if (! $csrfToken) {
            throw new Exception('CSRF token not found in response headers');
        }

        Log::debug('DSpace: CSRF token obtained');

        return $csrfToken;
    }

    /**
     * Perform login and obtain a new JWT token.
     *
     * @return string The new JWT token
     *
     * @throws Exception If login fails
     */
    private function login(): string
    {
        $csrfToken = $this->getCsrfToken();

        /** @var Response $response */
        $response = Http::withHeaders([
            'X-XSRF-TOKEN' => $csrfToken,
        ])
            ->withOptions([
                'cookies' => $this->createCookieJar($csrfToken),
            ])
            ->asForm()
            ->post("{$this->baseUrl}/api/authn/login", [
                'user' => $this->username,
                'password' => $this->password,
            ]);

        if (! $response->successful()) {
            throw new Exception("DSpace login failed: {$response->status()} - {$response->body()}");
        }

        $authHeader = $response->header('Authorization');

        if (! $authHeader) {
            throw new Exception('DSpace login did not return Authorization header');
        }

        $token = str_replace('Bearer ', '', $authHeader);

        // Also update CSRF token if present in response
        $newCsrfToken = $response->header('DSPACE-XSRF-TOKEN');

        $this->cacheToken($token, $newCsrfToken);

        Log::info('DSpace: Login successful');

        return $token;
    }

    /**
     * Refresh an existing JWT token.
     *
     * @param  string  $currentToken  The current JWT token to refresh
     * @return string The new JWT token
     *
     * @throws Exception If token refresh fails
     */
    private function refreshToken(string $currentToken): string
    {
        $csrfToken = $this->getCsrfToken();

        /** @var Response $response */
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$currentToken}",
            'X-XSRF-TOKEN' => $csrfToken,
        ])
            ->withOptions([
                'cookies' => $this->createCookieJar($csrfToken),
            ])
            ->post("{$this->baseUrl}/api/authn/login");

        if (! $response->successful()) {
            throw new Exception("Token refresh failed: {$response->status()}");
        }

        $authHeader = $response->header('Authorization');

        if (! $authHeader) {
            throw new Exception('Token refresh did not return new JWT');
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $newCsrfToken = $response->header('DSPACE-XSRF-TOKEN');

        $this->cacheToken($token, $newCsrfToken);

        Log::info('DSpace: Token refreshed successfully');

        return $token;
    }

    /**
     * Create a cookie jar for Guzzle HTTP client.
     *
     * @param  string  $csrfToken  The CSRF token to include as cookie
     * @return CookieJar The cookie jar instance
     */
    private function createCookieJar(string $csrfToken): CookieJar
    {
        /** @var string $domain */
        $domain = parse_url($this->baseUrl, PHP_URL_HOST);

        return CookieJar::fromArray([
            'DSPACE-XSRF-COOKIE' => $csrfToken,
        ], $domain);
    }

    /**
     * Store JWT token (and optionally CSRF token) in cache.
     *
     * @param  string  $token  The JWT token to cache
     * @param  string|null  $csrfToken  Optional CSRF token to cache
     */
    private function cacheToken(string $token, ?string $csrfToken = null): void
    {
        $tokenData = [
            'token' => $token,
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ];

        Cache::put(
            $this->cacheKey,
            json_encode($tokenData),
            now()->addMinutes($this->cacheTtl)
        );

        // Also cache CSRF token if provided (useful for subsequent calls)
        if ($csrfToken) {
            Cache::put(
                $this->csrfCacheKey,
                $csrfToken,
                now()->addMinutes($this->cacheTtl)
            );
        }

        Log::debug('DSpace: Token cached', [
            'cache_expires' => now()->addMinutes($this->cacheTtl)->toDateTimeString(),
        ]);
    }

    /**
     * Check if the token is still valid (with 2-minute margin).
     *
     * @param  array<string, mixed>  $tokenData  Token data from cache
     * @return bool True if token is still valid
     */
    private function isTokenValid(array $tokenData): bool
    {
        $marginSeconds = 120;

        return isset($tokenData['expires_at'])
            && now()->timestamp < $tokenData['expires_at'] - $marginSeconds;
    }

    /**
     * Get the current CSRF token (from cache or fetch new one).
     *
     * Useful for POST/PUT/PATCH/DELETE requests.
     *
     * @return string The CSRF token
     */
    public function getCurrentCsrfToken(): string
    {
        $cached = Cache::get($this->csrfCacheKey);

        if ($cached) {
            return $cached;
        }

        return $this->getCsrfToken();
    }

    /**
     * Logout and invalidate the current token.
     */
    public function logout(): void
    {
        $cachedData = Cache::get($this->cacheKey);

        if ($cachedData) {
            /** @var array<string, mixed> $tokenData */
            $tokenData = json_decode($cachedData, true);

            try {
                $csrfToken = $this->getCsrfToken();

                Http::withHeaders([
                    'Authorization' => "Bearer {$tokenData['token']}",
                    'X-XSRF-TOKEN' => $csrfToken,
                ])
                    ->withOptions([
                        'cookies' => $this->createCookieJar($csrfToken),
                    ])
                    ->post("{$this->baseUrl}/api/authn/logout");

                Log::info('DSpace: Logout successful');
            } catch (Exception $e) {
                Log::warning('DSpace: Logout request failed', ['error' => $e->getMessage()]);
            }
        }

        Cache::forget($this->cacheKey);
        Cache::forget($this->csrfCacheKey);
    }

    /**
     * Force a new login by invalidating the current token.
     *
     * @return string The new JWT token
     */
    public function forceRefresh(): string
    {
        Cache::forget($this->cacheKey);
        Cache::forget($this->csrfCacheKey);

        return $this->login();
    }
}
