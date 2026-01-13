<?php

namespace App\Facades;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Response get(string $endpoint, array $query = [])
 * @method static Response post(string $endpoint, array $data = [])
 * @method static Response postJson(string $endpoint, array $data = [])
 * @method static Response put(string $endpoint, array $data = [])
 * @method static Response patch(string $endpoint, array $data = [])
 * @method static Response delete(string $endpoint)
 * @method static Response upload(string $endpoint, string $filePath, string $fieldName = 'file', array $data = [])
 * @method static array getAuthStatus()
 * @method static bool isAuthenticated()
 * @method static void logout()
 * @method static string refreshToken()
 * @method static string getBaseUrl()
 *
 * @see \App\Services\DSpace\DSpaceClient
 */
class DSpace extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'dspace';
    }
}
