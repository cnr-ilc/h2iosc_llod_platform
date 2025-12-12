<?php

namespace App\Services\DSpace;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DSpaceAuthService
{
    private string $baseUrl;

    private string $username;

    private string $password;

    private string $cacheKey;

    private string $csrfCacheKey;

    private int $cacheTtl;

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
     * Ottiene un token JWT valido (da cache o tramite login)
     */
    public function getToken(): string
    {
        $cachedData = Cache::get($this->cacheKey);

        if ($cachedData) {
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
     * Ottiene il CSRF token
     *
     * Prova prima l'endpoint /api/security/csrf (DSpace 8+)
     * Se fallisce, usa /api/authn/status (DSpace 7.x)
     */
    private function getCsrfToken(): string
    {
        // Prima prova l'endpoint dedicato (DSpace 8+)
        $response = Http::get("{$this->baseUrl}/api/security/csrf");

        $csrfToken = $response->header('DSPACE-XSRF-TOKEN');

        // Se non c'è nell'endpoint dedicato, prova con /api/authn/status
        if (! $csrfToken) {
            Log::debug('DSpace: /api/security/csrf failed, trying /api/authn/status');
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
     * Esegue il login e ottiene un nuovo JWT
     */
    private function login(): string
    {
        $csrfToken = $this->getCsrfToken();

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

        // Aggiorna anche il CSRF token se presente nella response
        $newCsrfToken = $response->header('DSPACE-XSRF-TOKEN');

        $this->cacheToken($token, $newCsrfToken);

        Log::info('DSpace: Login successful');

        return $token;
    }

    /**
     * Refresha un token JWT esistente
     */
    private function refreshToken(string $currentToken): string
    {
        $csrfToken = $this->getCsrfToken();

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
     * Crea il cookie jar per Guzzle
     */
    private function createCookieJar(string $csrfToken): \GuzzleHttp\Cookie\CookieJar
    {
        $domain = parse_url($this->baseUrl, PHP_URL_HOST);

        return \GuzzleHttp\Cookie\CookieJar::fromArray([
            'DSPACE-XSRF-COOKIE' => $csrfToken,
        ], $domain);
    }

    /**
     * Salva il token JWT (e opzionalmente il CSRF) in Redis
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

        // Salva anche il CSRF token se fornito (utile per le chiamate successive)
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
     * Verifica se il token è ancora valido (con margine di 2 minuti)
     */
    private function isTokenValid(array $tokenData): bool
    {
        $marginSeconds = 120;

        return isset($tokenData['expires_at'])
            && now()->timestamp < $tokenData['expires_at'] - $marginSeconds;
    }

    /**
     * Ottiene il CSRF token corrente (da cache o nuovo)
     * Utile per le chiamate POST/PUT/PATCH/DELETE
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
     * Logout e invalida il token
     */
    public function logout(): void
    {
        $cachedData = Cache::get($this->cacheKey);

        if ($cachedData) {
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
     * Forza un nuovo login invalidando il token corrente
     */
    public function forceRefresh(): string
    {
        Cache::forget($this->cacheKey);
        Cache::forget($this->csrfCacheKey);

        return $this->login();
    }
}
