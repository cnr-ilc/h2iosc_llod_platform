# DSpace REST API

GitHub: `https://github.com/ufal/clarin-dspace/tree/clarin/dspace-rest`
Server path: `/repository/rest`

## Index

-   `GET /` - Return this page.
-   `GET /test` - Return the string "REST api is running" for testing purposes.
-   `POST /login` - Method for logging into the DSpace RESTful API. You must post User class. Example: `{"email":"test@dspace","password":"pass"}`. Returns a token which must be included in future requests in the "rest-dspace-token" header.
-   `POST /logout` - Method for logging out of the DSpace RESTful API. The request must include the "rest-dspace-token" token
    header.

## Communities

-   `GET /communities` - Return an array of all communities in DSpace.
-   `GET /communities/top-communities` - Returns an array of all top-leve communities in DSpace.
-   `GET /communities/{communityId}` - Returns a community with the specified ID.
-   `GET /communities/{communityId}/collections` - Returns an array of collections of the specified community.
-   `GET /communities/{communityId}/communities` - Returns an array of subcommunities of the specified community.
-   `POST /communities` - Create a new top-level community. You must post a community.
-   `POST /communities/{communityId}/collections` - Create a new collection in the specified community. You must post a collection.
-   `POST /communities/{communityId}/communities` - Create a new subcommunity in the specified community. You must post a community.
-   `PUT /communities/{communityId}` - Update the specified community.
-   `DELETE /communities/{communityId}` - Delete the specified community.
-   `DELETE /communities/{communityId}/collections/{collectionId}` - Delete the specified collection in the specified community.
-   `DELETE /communities/{communityId}/communities/{communityId2}` - Delete the specified subcommunity (communityId2) in the specified community (communityId).

## Collections

-   `GET /collections` - Return all DSpace collections in array.
-   `GET /collections/{collectionId}` - Return a collection with the specified ID.
-   `GET /collections/{collectionId}/items` - Return all items of the specified collection.
-   `POST /collections/{collectionId}/items` - Create an item in the specified collection. You must post an item.
-   `POST /collections/find-collection` - Find a collection by name.
-   `PUT /collections/{collectionId}` - Update the specified collection. You must post a collection.
-   `DELETE /collections/{collectionId}` - Delete the specified collection from DSpace.
-   `DELETE /collections/{collectionId}/items/{itemId}` - Delete the specified item (itemId) in the specified collection (collectionId).

## Items

-   `GET /items` - Return a list of items.
-   `GET /items/{item id}` - Return the specified item.
-   `GET /items/{item id}/metadata` - Return metadata of the specified item.
-   `GET /items/{item id}/bitstreams` - Return bitstreams of the specified item.
-   `POST /items/find-by-metadata-field` - Find items by the specified metadata value.
-   `POST /items/{item id}/metadata` - Add metadata to the specified item.
-   `POST /items/{item id}/bitstreams` - Add a bitstream to the specified item.
-   `PUT /items/{item id}/metadata` - Update metadata in the specified item.
-   `DELETE /items/{item id}` - Delete the specified item.
-   `DELETE /items/{item id}/metadata` - Clear metadata of the specified item.
-   `DELETE /items/{item id}/bitstreams/{bitstream id}` - Delete the specified bitstream of the specified item.

## Bitstreams

-   `GET /bitstreams` - Return all bitstreams in DSpace.
-   `GET /bitstreams/{bitstream id}` - Return the specified bitstream.
-   `GET /bitstreams/{bitstream id}/policy` - Return policies of the specified bitstream.
-   `GET /bitstreams/{bitstream id}/retrieve` - Return the contents of the specified bitstream.
-   `POST /bitstreams/{bitstream id}/policy` - Add a policy to the specified bitstream.
-   `PUT /bitstreams/{bitstream id}/data` - Update the contents of the specified bitstream.
-   `PUT /bitstreams/{bitstream id}` - Update metadata of the specified bitstream.
-   `DELETE /bitstreams/{bitstream id}` - Delete the specified bitstream from DSpace.
-   `DELETE /bitstreams/{bitstream id}/policy/{policy_id}` - Delete the specified bitstream policy.
