<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;

class DSpaceService
{
    private $client;

    private $baseUrl;

    private $token;

    public function __construct(string $baseUrl, Client $client)
    {
        $this->baseUrl = $baseUrl;
        $this->client = $client;
    }

    /**
     * Login to DSpace API
     */
    public function login(): string
    {
        $response = $this->client->post($this->baseUrl.'/login', [
            'json' => [
                'email' => config('dspace.auth.email'),
                'password' => config('dspace.auth.password'),
            ],
        ]);

        $this->token = trim($response->getBody()->getContents());

        return $this->token;
    }

    /**
     * Get the status of the DSpace API
     */
    public function getStatus()
    {
        return $this->get('/status');
    }

    /**
     * Logout from DSpace API
     */
    public function logout(): void
    {
        if (! $this->token) {
            throw new Exception('Not logged in');
        }

        $this->client->post($this->baseUrl.'/logout', [
            'headers' => $this->getHeaders(),
        ]);

        $this->token = null;
    }

    /**
     * Communities Methods
     */
    public function getAllCommunities()
    {
        return $this->get('/communities');
    }

    public function getTopCommunities()
    {
        return $this->get('/communities/top-communities');
    }

    public function getCommunity(int $communityId)
    {
        return $this->get("/communities/{$communityId}");
    }

    public function getCommunityCollections(int $communityId)
    {
        return $this->get("/communities/{$communityId}/collections");
    }

    // public function createCommunity(array $data)
    // {
    //     return $this->post('/communities', $data);
    // }

    /**
     * Collections Methods
     */
    public function getAllCollections()
    {
        return $this->get('/collections');
    }

    public function getCollection(int $collectionId)
    {
        return $this->get("/collections/{$collectionId}");
    }

    public function getCollectionItems(int $collectionId)
    {
        return $this->get("/collections/{$collectionId}/items");
    }

    public function findCollection(string $name)
    {
        return $this->post('/collections/find-collection', ['name' => $name]);
    }

    /**
     * Items Methods
     */
    public function getAllItems()
    {
        return $this->get('/items');
    }

    public function getItem(int $itemId)
    {
        return $this->get("/items/{$itemId}", ['expand' => 'all']);
    }

    public function getItemMetadata(int $itemId)
    {
        return $this->get("/items/{$itemId}/metadata");
    }

    public function getItemBitstreams(int $itemId)
    {
        return $this->get("/items/{$itemId}/bitstreams");
    }

    public function findItemsByMetadata(array $metadata, array $params = [])
    {
        return $this->post('/items/find-by-metadata-field', $metadata, $params);
    }

    // public function addItemMetadata(int $itemId, array $metadata)
    // {
    //     return $this->post("/items/{$itemId}/metadata", $metadata);
    // }

    // public function updateItemMetadata(int $itemId, array $metadata)
    // {
    //     return $this->put("/items/{$itemId}/metadata", $metadata);
    // }

    // public function deleteItem(int $itemId)
    // {
    //     return $this->delete("/items/{$itemId}");
    // }

    /**
     * Bitstreams Methods
     */
    public function getAllBitstreams()
    {
        return $this->get('/bitstreams');
    }

    public function getBitstream(int $bitstreamId)
    {
        return $this->get("/bitstreams/{$bitstreamId}");
    }

    public function getBitstreamPolicy(int $bitstreamId)
    {
        return $this->get("/bitstreams/{$bitstreamId}/policy");
    }

    public function getBitstreamContent(int $bitstreamId)
    {
        return $this->get("/bitstreams/{$bitstreamId}/retrieve");
    }

    // public function addBitstreamPolicy(int $bitstreamId, array $policy)
    // {
    //     return $this->post("/bitstreams/{$bitstreamId}/policy", $policy);
    // }

    // public function updateBitstreamContent(int $bitstreamId, $fileContent)
    // {
    //     return $this->put("/bitstreams/{$bitstreamId}/data", [
    //         'multipart' => [
    //             [
    //                 'name'     => 'file',
    //                 'contents' => $fileContent,
    //             ],
    //         ],
    //     ]);
    // }

    // public function updateBitstreamMetadata(int $bitstreamId, array $metadata)
    // {
    //     return $this->put("/bitstreams/{$bitstreamId}", $metadata);
    // }

    // public function deleteBitstream(int $bitstreamId)
    // {
    //     return $this->delete("/bitstreams/{$bitstreamId}");
    // }

    // public function deleteBitstreamPolicy(int $bitstreamId, int $policyId)
    // {
    //     return $this->delete("/bitstreams/{$bitstreamId}/policy/{$policyId}");
    // }

    /**
     * Helper Methods
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'rest-dspace-token' => $this->token,
        ];
    }

    private function get(string $endpoint, array $params = [])
    {
        $response = $this->client->get($this->baseUrl.$endpoint, [
            'headers' => $this->getHeaders(),
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function post(string $endpoint, array $data = [], array $params = [])
    {
        $response = $this->client->post($this->baseUrl.$endpoint, [
            'headers' => $this->getHeaders(),
            'json' => $data,
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // private function put(string $endpoint, array $data = [])
    // {
    //    $response = $this->client->put($this->baseUrl . $endpoint, [
    //        'headers' => $this->getHeaders(),
    //        'json'    => $data,
    //    ]);
    //
    //    return json_decode($response->getBody()->getContents(), true);
    // }

    // private function delete(string $endpoint)
    // {
    //    $response = $this->client->delete($this->baseUrl . $endpoint, [
    //        'headers' => $this->getHeaders(),
    //    ]);
    //
    //    return $response->getStatusCode() === 204;
    // }
}
