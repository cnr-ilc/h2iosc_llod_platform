<?php

namespace App\Services;

use App\Enums\RdfFormat;
use App\Models\ResourceFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SemanticTurkeyService
{
    private string $baseUrl;

    private const SERVICE_PATH = 'semanticturkey/it.uniroma2.art.semanticturkey/st-core-services';

    private const REMEMBER_ME_COOKIE_NAME = '_spring_security_remember_me';

    private const REMEMBER_ME_COOKIE_VALUE = 'false';

    private const REGISTER_USER_PATH = 'Users/registerUser';

    private const UPDATE_USER_EMAIL_PATH = 'Users/updateUserEmail';

    private const CHANGE_PASSWORD_PATH = 'Users/changePassword';

    private const FORCE_PASSWORD_PATH = 'Users/forcePassword';

    private const LOGIN_PATH = 'Auth/login';

    private const CREATE_PROJECT_PATH = 'Projects/createProject';

    private const DISCONNECT_PROJECT_PATH = 'Projects/disconnectFromProject';

    private const DELETE_PROJECT_PATH = 'Projects/deleteProject';

    private const DELETE_REMOTE_REPOSITORY_PATH = 'Repositories/deleteRemoteRepositories';

    private const PRELOAD_DATA = 'Projects/preloadDataFromFile';

    private const ENABLE_USER_PATH = 'Users/enableUser';

    private const ASSIGN_ROLES_TO_USER_PATH = 'Administration/addRolesToUser';

    private const UPDATE_USER_LANGUAGES_PATH = 'Administration/updateLanguagesOfUserInProject';

    private const GET_FORMAT_FILE_NAME_PATH = 'InputOutput/getParserFormatForFileName';

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Register a new user in Semantic Turkey
     */
    public function registerUser(string $email, string $password, string $givenName = '', string $familyName = ''): array
    {
        $response = Http::asForm()->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::REGISTER_USER_PATH), [
            'email' => $email,
            'password' => $password,
            'givenName' => $givenName,
            'familyName' => $familyName,
            'languageProficiencies' => 'it',
            'customProperties' => '{}',
            'vbHostAddress' => config('vocbench.url'),
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to register user: '.$response->body()];
        }

        return $response->json();
    }

    /**
     * Update user email
     */
    public function updateUserEmail(string $currentEmail, string $newEmail): array
    {
        $response = Http::post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::UPDATE_USER_EMAIL_PATH), [
            'email' => $currentEmail,
            'newEmail' => $newEmail,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to update user email: '.$response->body()];
        }

        return $response->json();
    }

    /**
     * Change user password | User side
     */
    public function changePassword(string $email, string $oldPassword, string $newPassword): array
    {
        $response = Http::asForm()->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::CHANGE_PASSWORD_PATH), [
            'email' => $email,
            'oldPassword' => $oldPassword,
            'newPassword' => $newPassword,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to change password: '.$response->body()];
        }

        return $response->json();
    }

    /**
     * Force user password reset | Admin side
     */
    public function forcePassword(string $email, string $newPassword, array $cookie): array
    {
        $response = Http::withHeaders([
            'Cookie' => $this->getCookieToString($cookie),
        ])->asForm()->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::FORCE_PASSWORD_PATH), [
            'email' => $email,
            'password' => $newPassword,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to force password reset: '.$response->body()];
        }

        return $response->json();
    }

    /**
     * Login to Semantic Turkey
     */
    public function login(string $email, string $password): array
    {

        $response = Http::asForm()->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::LOGIN_PATH), [
            'email' => $email,
            'password' => $password,
            self::REMEMBER_ME_COOKIE_NAME => self::REMEMBER_ME_COOKIE_VALUE,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Login failed: '.$response->body()];
        }

        $responseHeaders = $response->headers();

        if (! isset($responseHeaders['Set-Cookie'])) {
            return ['error' => 'No cookie found'];
        }

        $cookie = $responseHeaders['Set-Cookie'];
        $response = $response->json();
        $response['cookie'] = $this->parseCookie($cookie[0]);

        return $response;
    }

    /**
     * Gets the parser format for a given filename.
     *
     * @param  array  $cookies  - Authentication cookies for the HTTP request
     * @param  string  $filename  - The filename for which to get the format
     * @return array - Request result or error array if the request fails
     */
    private function getParserFormatForFileName(array $cookies, string $filename): array
    {

        $response = Http::withHeaders(['Cookie' => $this->getCookieToString($cookies)])
            ->get(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::GET_FORMAT_FILE_NAME_PATH),
                ['fileName' => $filename]
            );

        if (! $response->successful()) {
            return ['error' => 'Failed to create project: '.$response->body()]; // TODO: not a good choice
        }

        return $response->json();
    }

    /**
     * Preloads a resource file to the server.
     *
     * @param  array  $cookies  - Authentication cookies for the HTTP request
     * @param  ResourceFile  $resourceFile  - The object representing the resource file to preload
     * @param  string  $format  - The format of the file to preload
     * @return array - Request result or error array if the request fails
     */
    private function preloadResource(array $cookies, ResourceFile $resourceFile, string $format): array
    {
        $file_path = $resourceFile->path;
        $file_contents = Storage::read($file_path);

        $response = Http::withHeaders([
            'Cookie' => $this->getCookieToString($cookies),
        ])
            ->attach('preloadedData', $file_contents, $resourceFile->name)
            ->attach('preloadedDataFormat', $format)
            ->post(sprintf(
                '%s/%s/%s',
                $this->baseUrl,
                self::SERVICE_PATH,
                self::PRELOAD_DATA
            ));

        if (! $response->successful()) {
            return ['error' => 'Failed to preload data: '.$response->body()];
        }

        return $response->json()['result'];
    }

    /**
     * Creates a new remote access project using a resource file.
     *
     * This method handles the entire process of:
     * - Determining the file format
     * - Preloading the resource to the server
     * - Creating the project with appropriate configurations
     *
     * @param  array  $cookies  - Authentication cookies for the HTTP request
     * @param  ResourceFile  $resource_file  - The object representing the resource file to use
     * @return array - Information about the created project or error array if the request fails
     */
    public function createRemoteAccessProject(array $cookies, ResourceFile $resource_file): array
    {
        $projectName = $resource_file->name.'_'.uniqid(); // TODO: improve name of project

        Log::info('createRemoteAccessProject starting', [
            'resource_file_id' => $resource_file->id,
            'resource_file_name' => $resource_file->name,
            'project_name' => $projectName
        ]);

        $formatFile = RdfFormat::toSTFormatName($resource_file->mime_type);

        Log::info('getParserFormatForFileName ST response', ['body' => $formatFile]);

        $preloadedResourceResponse = $this->preloadResource($cookies, $resource_file, $formatFile);

        Log::info('preloadResource ST response', ['body' => $preloadedResourceResponse]);

        $graphUri = $resource_file->graphdb_uri;
        $graphDBRepositoryName = $projectName.'_core';

        $projectData = array_merge(
            config('semanticTurkey.default_project_config.base'),
            [
                'projectName' => $projectName,
                'baseURI' => $graphUri,
                'coreRepoID' => $graphDBRepositoryName,
                'supportRepoID' => $projectName.'_support',
                'preloadedDataFileName' => $preloadedResourceResponse['preloadedDataFile'],
                'preloadedDataFormat' => $preloadedResourceResponse['preloadedDataFormat'] ?? config('semanticTurkey.default_project_config.dataFormat'),
                'model' => $preloadedResourceResponse['model'] ?? config('semanticTurkey.default_project_config.model'),
                'lexicalizationModel' => $preloadedResourceResponse['lexicalizationModel'] ?? config('semanticTurkey.default_project_config.lexicalizationModel'),
            ]
        );

        Log::info('createRemoteAccessProject sending request', [
            'url' => sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::CREATE_PROJECT_PATH),
            'project_data' => $projectData,
            'cookie_header' => $this->getCookieToString($cookies)
        ]);

        $response = Http::asForm()
            ->withHeaders(['Cookie' => $this->getCookieToString($cookies)])
            ->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::CREATE_PROJECT_PATH), $projectData);

        Log::info('createRemoteAccessProject ST response', [
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'headers' => $response->headers(),
            'body' => $response->body()
        ]);

        if (! $response->successful()) {
            Log::error('createRemoteAccessProject failed', [
                'status_code' => $response->status(),
                'error_body' => $response->body(),
                'project_data' => $projectData
            ]);
            return ['error' => 'Failed to create project: '.$response->body()];
        }

        return [
            'projectName' => $projectName,
            'graphURI' => $graphUri,
            'graphDBRepositoryName' => $graphDBRepositoryName,
        ];
    }

    /**
     * Enable a user
     */
    public function enableUser(string $email, array $cookies): array
    {
        $response = Http::withHeaders([
            'Cookie' => $this->getCookieToString($cookies),
        ])->asForm()->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::ENABLE_USER_PATH), [
            'email' => $email,
            'enabled' => true,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to enable user: '.$response->body()];
        }

        return $response->json();
    }

    /**
     * Assign roles to a user for a specific project
     */
    public function assignProjectRolesToUser(array $cookies, string $projectName, string $email, ?array $roles = null): array
    {
        $default_roles = config('semanticTurkey.default_project_config.all_user_project_roles');

        // If not defined assign all roles
        $user_roles = $roles ?? $default_roles;

        $response = Http::asForm()
            ->withHeaders(['Cookie' => $this->getCookieToString($cookies)])
            ->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::ASSIGN_ROLES_TO_USER_PATH), [
                'projectName' => $projectName,
                'email' => $email,
                'roles' => implode(',', $roles ?? $default_roles),
            ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to assign roles: '.$response->body()];
        }

        return $response->json();
    }

    /**
     * Update user languages for a specific project
     */
    public function updateUserLanguages(string $projectName, string $email, array $languages): array
    {
        $response = Http::post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::UPDATE_USER_LANGUAGES_PATH), [
            'projectName' => $projectName,
            'email' => $email,
            'languages' => implode(',', $languages),
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to update user languages: '.$response->body()];
        }

        return $response->json();
    }

    public function deleteRemoteAccessProject(array $cookies, string $projectName, string $repositoryGraphDBId): array
    {
        $this->disconnectProject(cookies: $cookies, projectName: $projectName);
        $this->deleteSingleProject(cookies: $cookies, projectName: $projectName);
        $this->deleteGraphDBRepo(cookies: $cookies, repositoryId: $repositoryGraphDBId);

        return ['success' => 'project '.$projectName.' deleted successfully'];
    }

    private function disconnectProject(array $cookies, string $projectName): array
    {
        $response = Http::asForm()
            ->withHeaders(['Cookie' => $this->getCookieToString($cookies)])
            ->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::DISCONNECT_PROJECT_PATH), [
                'consumer' => 'SYSTEM',
                'projectName' => $projectName,
            ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to update user languages: '.$response->body()];
        }

        return $response->json();
    }

    private function deleteSingleProject(array $cookies, string $projectName): array
    {
        $response = Http::asForm()
            ->withHeaders(['Cookie' => $this->getCookieToString($cookies)])
            ->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::DELETE_PROJECT_PATH), [
                'consumer' => 'SYSTEM',
                'projectName' => $projectName,
            ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to update user languages: '.$response->body()];
        }

        return $response->json();
    }

    private function deleteGraphDBRepo(array $cookies, string $repositoryId): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Cookie' => $this->getCookieToString($cookies),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->post(sprintf('%s/%s/%s', $this->baseUrl, self::SERVICE_PATH, self::DELETE_REMOTE_REPOSITORY_PATH), [
                'remoteRepositories' => json_encode([
                    [
                        'serverURL' => env('GRAPHDB_HOST_URL'),
                        'repositoryId' => $repositoryId,
                        'username' => null,
                        'password' => null,
                    ],
                ]),
            ]);

        Log::info($response);

        if (! $response->successful()) {
            return ['error' => 'Failed to update user languages: '.$response->body()];
        }

        return $response->json();
    }

    private function parseCookie(string $cookie): array
    {
        $parts = explode(';', $cookie);
        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part, 2);
                $result[trim($key)] = trim($value);
            } else {
                $result[trim($part)] = true;
            }
        }

        return $result;
    }

    private function getCookieToString(array $cookies): string
    {
        return implode('; ', array_map(function ($key, $value) {
            return is_bool($value) ? $key : "$key=$value";
        }, array_keys($cookies), array_values($cookies)));
    }
}
