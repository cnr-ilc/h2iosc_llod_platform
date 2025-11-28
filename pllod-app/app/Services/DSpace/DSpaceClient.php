<?php

namespace App\Services\DSpace;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use GuzzleHttp\Cookie\CookieJar;

class DSpaceClient
{
    private DSpaceAuthService $authService;
    private string $baseUrl;

    public function __construct(DSpaceAuthService $authService)
    {
        $this->authService = $authService;
        $this->baseUrl = rtrim(config('dspace.base_url'), '/');
    }

    /**
     * GET request (non richiede CSRF)
     */
    public function get(string $endpoint, array $query = []): Response
    {
        return $this->baseRequest()->get($this->url($endpoint), $query);
    }

    /**
     * POST request (richiede CSRF)
     */
    public function post(string $endpoint, array $data = []): Response
    {
        return $this->requestWithCsrf()->post($this->url($endpoint), $data);
    }

    /**
     * POST request con JSON body
     */
    public function postJson(string $endpoint, array $data = []): Response
    {
        return $this->requestWithCsrf()
            ->contentType('application/json')
            ->post($this->url($endpoint), $data);
    }

    /**
     * PUT request (richiede CSRF)
     */
    public function put(string $endpoint, array $data = []): Response
    {
        return $this->requestWithCsrf()->put($this->url($endpoint), $data);
    }

    /**
     * PATCH request (richiede CSRF)
     */
    public function patch(string $endpoint, array $data = []): Response
    {
        return $this->requestWithCsrf()
            ->contentType('application/json')
            ->patch($this->url($endpoint), $data);
    }

    /**
     * DELETE request (richiede CSRF)
     */
    public function delete(string $endpoint): Response
    {
        return $this->requestWithCsrf()->delete($this->url($endpoint));
    }

    /**
     * Upload di un file (multipart)
     */
    public function upload(string $endpoint, string $filePath, string $fieldName = 'file', array $data = []): Response
    {
        $request = $this->requestWithCsrf()->asMultipart();

        foreach ($data as $key => $value) {
            $request->attach($key, $value);
        }

        return $request->attach($fieldName, file_get_contents($filePath), basename($filePath))
            ->post($this->url($endpoint));
    }

    /**
     * Request base con solo JWT (per GET)
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
     * Request con JWT + CSRF token (per POST/PUT/PATCH/DELETE)
     */
    private function requestWithCsrf(): PendingRequest
    {
        $token = $this->authService->getToken();
        $csrfToken = $this->authService->getCurrentCsrfToken();
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
     * Costruisce l'URL completo per l'endpoint
     */
    private function url(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        // Se l'endpoint inizia già con 'api/', non aggiungerlo
        if (str_starts_with($endpoint, 'api/')) {
            return "{$this->baseUrl}/{$endpoint}";
        }

        return "{$this->baseUrl}/api/{$endpoint}";
    }

    /**
     * Ottiene lo stato dell'autenticazione corrente
     */
    public function getAuthStatus(): array
    {
        return $this->get('authn/status')->json();
    }

    /**
     * Verifica se l'utente è autenticato
     */
    public function isAuthenticated(): bool
    {
        $status = $this->getAuthStatus();
        return $status['authenticated'] ?? false;
    }

    /**
     * Esegue il logout
     */
    public function logout(): void
    {
        $this->authService->logout();
    }

    /**
     * Forza il refresh del token
     */
    public function refreshToken(): string
    {
        return $this->authService->forceRefresh();
    }

    /**
     * Ottiene l'URL base configurato
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
