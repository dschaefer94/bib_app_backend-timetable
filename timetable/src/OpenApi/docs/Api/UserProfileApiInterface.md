# App\OpenApi\Api\UserProfileApiInterface

All URIs are relative to *http://127.0.0.1:8000*

Method | HTTP request | Description
------------- | ------------- | -------------
[**getUserProfile**](UserProfileApiInterface.md#getUserProfile) | **GET** /api/profile/me | Aktuelles Benutzerprofil abrufen
[**updateKlasse**](UserProfileApiInterface.md#updateKlasse) | **POST** /api/profile/update-klasse | Klasse des Benutzers aktualisieren


## Service Declaration
```yaml
# config/services.yaml
services:
    # ...
    Acme\MyBundle\Api\UserProfileApi:
        tags:
            - { name: "open_api_server.api", api: "userProfile" }
    # ...
```

## **getUserProfile**
> App\OpenApi\Model\UserProfile getUserProfile()

Aktuelles Benutzerprofil abrufen

Ruft die Profildaten des authentifizierten Benutzers ab.

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserProfileApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserProfileApiInterface;

class UserProfileApi implements UserProfileApiInterface
{

    // ...

    /**
     * Implementation of UserProfileApiInterface#getUserProfile
     */
    public function getUserProfile(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\UserProfile**](../Model/UserProfile.md)

### Authorization

[BearerAuth](../../README.md#BearerAuth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json, application/problem+json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **updateKlasse**
> App\OpenApi\Model\UpdateKlasse200Response updateKlasse($updateKlasseRequest)

Klasse des Benutzers aktualisieren

Aktualisiert die zugewiesene Klasse für den authentifizierten Benutzer.

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserProfileApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserProfileApiInterface;

class UserProfileApi implements UserProfileApiInterface
{

    // ...

    /**
     * Implementation of UserProfileApiInterface#updateKlasse
     */
    public function updateKlasse(UpdateKlasseRequest $updateKlasseRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **updateKlasseRequest** | [**App\OpenApi\Model\UpdateKlasseRequest**](../Model/UpdateKlasseRequest.md)|  |

### Return type

[**App\OpenApi\Model\UpdateKlasse200Response**](../Model/UpdateKlasse200Response.md)

### Authorization

[BearerAuth](../../README.md#BearerAuth)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json, application/problem+json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

