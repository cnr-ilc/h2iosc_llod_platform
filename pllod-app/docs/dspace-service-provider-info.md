# DSpace Service Provider Info

## ENV

DSPACE_API_URL=https://dspace-clarin-it.ilc.cnr.it/repository/rest
DSPACE_EMAIL=your-email@example.com
DSPACE_PASSWORD=your-password

## Import Facade

```php
use App\Facades\DSpace;
```

## Login

```php
DSpace::login();
```

## Get all communities

```php
$communities = DSpace::getAllCommunities();
```

## Get specific item metadata

```php
$metadata = DSpace::getItemMetadata($itemId);
```

## Logout

```php
DSpace::logout();
```
