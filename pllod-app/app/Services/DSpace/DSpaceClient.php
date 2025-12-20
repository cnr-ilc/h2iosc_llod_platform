<?php

namespace App\Services\DSpace;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DSpace REST API Client
 *
 * Provides a high-level interface for communicating with the DSpace REST API.
 * Handles authentication, CSRF tokens, and common HTTP operations.
 */
class DSpaceClient
{
    /**
     * Authentication service for JWT and CSRF token management
     */
    private DSpaceAuthService $authService;

    /**
     * DSpace base URL
     */
    private string $baseUrl;

    /**
     * Initialize the client with the authentication service.
     *
     * @param  DSpaceAuthService  $authService  The authentication service
     */
    public function __construct(DSpaceAuthService $authService)
    {
        $this->authService = $authService;
        $this->baseUrl = rtrim(config('dspace.base_url'), '/');
    }

    /**
     * Perform a GET request (does not require CSRF).
     *
     * @param  string  $endpoint  The API endpoint
     * @param  array<string, mixed>  $query  Optional query parameters
     * @return Response|PromiseInterface The HTTP response
     */
    public function get(string $endpoint, array $query = []): Response|PromiseInterface
    {
        return $this->baseRequest()->get($this->url($endpoint), $query);
    }

    /**
     * Perform a POST request (requires CSRF).
     *
     * @param  string  $endpoint  The API endpoint
     * @param  array<string, mixed>  $data  Optional request data
     * @return Response|PromiseInterface The HTTP response
     */
    public function post(string $endpoint, array $data = []): Response|PromiseInterface
    {
        return $this->requestWithCsrf()->post($this->url($endpoint), $data);
    }

    /**
     * Perform a POST request with JSON body.
     *
     * @param  string  $endpoint  The API endpoint
     * @param  array<string, mixed>  $data  Optional request data
     * @return Response|PromiseInterface The HTTP response
     */
    public function postJson(string $endpoint, array $data = []): Response|PromiseInterface
    {
        return $this->requestWithCsrf()
            ->contentType('application/json')
            ->post($this->url($endpoint), $data);
    }

    /**
     * Perform a PUT request (requires CSRF).
     *
     * @param  string  $endpoint  The API endpoint
     * @param  array<string, mixed>  $data  Optional request data
     * @return Response|PromiseInterface The HTTP response
     */
    public function put(string $endpoint, array $data = []): Response|PromiseInterface
    {
        return $this->requestWithCsrf()->put($this->url($endpoint), $data);
    }

    /**
     * Perform a PATCH request (requires CSRF).
     *
     * @param  string  $endpoint  The API endpoint
     * @param  array<string, mixed>  $data  Optional request data
     * @return Response|PromiseInterface The HTTP response
     */
    public function patch(string $endpoint, array $data = []): Response|PromiseInterface
    {
        return $this->requestWithCsrf()
            ->contentType('application/json')
            ->patch($this->url($endpoint), $data);
    }

    /**
     * Perform a DELETE request (requires CSRF).
     *
     * @param  string  $endpoint  The API endpoint
     * @return Response|PromiseInterface The HTTP response
     */
    public function delete(string $endpoint): Response|PromiseInterface
    {
        return $this->requestWithCsrf()->delete($this->url($endpoint));
    }

    /**
     * Upload a file (multipart request).
     *
     * @param  string  $endpoint  The API endpoint
     * @param  string  $filePath  Path to the file to upload
     * @param  string  $fieldName  Form field name for the file
     * @param  array<string, mixed>  $data  Additional form data
     * @return Response|PromiseInterface The HTTP response
     */
    public function upload(string $endpoint, string $filePath, string $fieldName = 'file', array $data = []): Response|PromiseInterface
    {
        $request = $this->requestWithCsrf()->asMultipart();

        foreach ($data as $key => $value) {
            $request->attach($key, $value);
        }

        return $request->attach($fieldName, file_get_contents($filePath), basename($filePath))
            ->post($this->url($endpoint));
    }

    /**
     * Create a base request with JWT authorization (for GET requests).
     *
     * @return PendingRequest The configured request
     */
    private function baseRequest(): PendingRequest
    {
        $token = $this->authService->getToken();

        return Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->throw();
    }

    /**
     * Create a request with JWT + CSRF token (for POST/PUT/PATCH/DELETE).
     *
     * @return PendingRequest The configured request
     */
    private function requestWithCsrf(): PendingRequest
    {
        $token = $this->authService->getToken();
        $csrfToken = $this->authService->getCurrentCsrfToken();
        /** @var string $domain */
        $domain = parse_url($this->baseUrl, PHP_URL_HOST);

        return Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-XSRF-TOKEN' => $csrfToken,
            'Accept' => 'application/json',
        ])
            ->withOptions([
                'cookies' => CookieJar::fromArray([
                    'DSPACE-XSRF-COOKIE' => $csrfToken,
                ], $domain),
            ])
            ->throw();
    }

    /**
     * Build the full URL for an endpoint.
     *
     * @param  string  $endpoint  The API endpoint
     * @return string The full URL
     */
    private function url(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        // If endpoint already starts with 'api/', don't add it
        if (str_starts_with($endpoint, 'api/')) {
            return "{$this->baseUrl}/{$endpoint}";
        }

        return "{$this->baseUrl}/api/{$endpoint}";
    }

    /**
     * Get the current authentication status.
     *
     * @return array<string, mixed> The authentication status
     */
    public function getAuthStatus(): array
    {
        /** @var Response $response */
        $response = $this->get('authn/status');

        return $response->json();
    }

    /**
     * Check if the user is authenticated.
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        $status = $this->getAuthStatus();

        return $status['authenticated'] ?? false;
    }

    /**
     * Perform logout.
     */
    public function logout(): void
    {
        $this->authService->logout();
    }

    /**
     * Force token refresh.
     *
     * @return string The new JWT token
     */
    public function refreshToken(): string
    {
        return $this->authService->forceRefresh();
    }

    /**
     * Get the configured base URL.
     *
     * @return string The base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Find an item by its PID.
     *
     * @param  string  $pid  The persistent identifier
     * @return array<string, mixed> The item data or empty array if not found
     */
    public function findItemByPid(string $pid): array
    {
        /** @var Response $response */
        $response = $this->get('api/pid/find', ['id' => $pid]);

        if (! $response->successful()) {
            Log::error('DSpace: Error finding item by PID', [
                'pid' => $pid,
                'error' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }
}
