# App\OpenApi\Api\UserApiInterface

All URIs are relative to *http://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**apiUsersRegisterPost**](UserApiInterface.md#apiUsersRegisterPost) | **POST** /api/users/register | Neuen Benutzer registrieren
[**restapiPhpactiongetUserGet**](UserApiInterface.md#restapiPhpactiongetUserGet) | **GET** /restapi.php?action&#x3D;getUser | Benutzer-Daten abrufen
[**restapiPhpactionprofileGet**](UserApiInterface.md#restapiPhpactionprofileGet) | **GET** /restapi.php?action&#x3D;profile | Profil des Benutzers abrufen
[**restapiPhpactionupdateProfilePut**](UserApiInterface.md#restapiPhpactionupdateProfilePut) | **PUT** /restapi.php?action&#x3D;updateProfile | Benutzer-Profil aktualisieren
[**restapiPhpactionwriteUserPost**](UserApiInterface.md#restapiPhpactionwriteUserPost) | **POST** /restapi.php?action&#x3D;writeUser | Neuen Benutzer registrieren


## Service Declaration
```yaml
# config/services.yaml
services:
    # ...
    Acme\MyBundle\Api\UserApi:
        tags:
            - { name: "open_api_server.api", api: "user" }
    # ...
```

## **apiUsersRegisterPost**
> App\OpenApi\Model\ApiUsersRegisterPost200Response apiUsersRegisterPost($apiUsersRegisterPostRequest)

Neuen Benutzer registrieren

Registriert einen neuen Benutzer mit den erforderlichen Daten

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserApiInterface;

class UserApi implements UserApiInterface
{

    // ...

    /**
     * Implementation of UserApiInterface#apiUsersRegisterPost
     */
    public function apiUsersRegisterPost(ApiUsersRegisterPostRequest $apiUsersRegisterPostRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **apiUsersRegisterPostRequest** | [**App\OpenApi\Model\ApiUsersRegisterPostRequest**](../Model/ApiUsersRegisterPostRequest.md)|  |

### Return type

[**App\OpenApi\Model\ApiUsersRegisterPost200Response**](../Model/ApiUsersRegisterPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactiongetUserGet**
> App\OpenApi\Model\User restapiPhpactiongetUserGet()

Benutzer-Daten abrufen

Gibt die Daten des eingeloggten Benutzers aus

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserApiInterface;

class UserApi implements UserApiInterface
{

    // ...

    /**
     * Implementation of UserApiInterface#restapiPhpactiongetUserGet
     */
    public function restapiPhpactiongetUserGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\User**](../Model/User.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionprofileGet**
> App\OpenApi\Model\RestapiPhpActionProfileGet200Response restapiPhpactionprofileGet()

Profil des Benutzers abrufen

Holt die Profildaten und verfügbaren Klassen

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserApiInterface;

class UserApi implements UserApiInterface
{

    // ...

    /**
     * Implementation of UserApiInterface#restapiPhpactionprofileGet
     */
    public function restapiPhpactionprofileGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\RestapiPhpActionProfileGet200Response**](../Model/RestapiPhpActionProfileGet200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionupdateProfilePut**
> App\OpenApi\Model\RestapiPhpActionUpdateProfilePut200Response restapiPhpactionupdateProfilePut($restapiPhpActionUpdateProfilePutRequest)

Benutzer-Profil aktualisieren

Aktualisiert die Profildaten des Benutzers (Name, Vorname, E-Mail, Passwort, Klasse)

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserApiInterface;

class UserApi implements UserApiInterface
{

    // ...

    /**
     * Implementation of UserApiInterface#restapiPhpactionupdateProfilePut
     */
    public function restapiPhpactionupdateProfilePut(RestapiPhpActionUpdateProfilePutRequest $restapiPhpActionUpdateProfilePutRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **restapiPhpActionUpdateProfilePutRequest** | [**App\OpenApi\Model\RestapiPhpActionUpdateProfilePutRequest**](../Model/RestapiPhpActionUpdateProfilePutRequest.md)|  |

### Return type

[**App\OpenApi\Model\RestapiPhpActionUpdateProfilePut200Response**](../Model/RestapiPhpActionUpdateProfilePut200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionwriteUserPost**
> App\OpenApi\Model\ApiUsersRegisterPost200Response restapiPhpactionwriteUserPost($apiUsersRegisterPostRequest)

Neuen Benutzer registrieren

Registriert einen neuen Benutzer mit den erforderlichen Daten

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/UserApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\UserApiInterface;

class UserApi implements UserApiInterface
{

    // ...

    /**
     * Implementation of UserApiInterface#restapiPhpactionwriteUserPost
     */
    public function restapiPhpactionwriteUserPost(ApiUsersRegisterPostRequest $apiUsersRegisterPostRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **apiUsersRegisterPostRequest** | [**App\OpenApi\Model\ApiUsersRegisterPostRequest**](../Model/ApiUsersRegisterPostRequest.md)|  |

### Return type

[**App\OpenApi\Model\ApiUsersRegisterPost200Response**](../Model/ApiUsersRegisterPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

