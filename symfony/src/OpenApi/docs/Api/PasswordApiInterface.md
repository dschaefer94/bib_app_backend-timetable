# App\OpenApi\Api\PasswordApiInterface

All URIs are relative to *http://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**restapiPhpactionrequestPasswordResetPost**](PasswordApiInterface.md#restapiPhpactionrequestPasswordResetPost) | **POST** /restapi.php?action&#x3D;requestPasswordReset | Passwort-Reset anfordern
[**restapiPhpactionresetPasswordPost**](PasswordApiInterface.md#restapiPhpactionresetPasswordPost) | **POST** /restapi.php?action&#x3D;resetPassword | Passwort zurücksetzen


## Service Declaration
```yaml
# config/services.yaml
services:
    # ...
    Acme\MyBundle\Api\PasswordApi:
        tags:
            - { name: "open_api_server.api", api: "password" }
    # ...
```

## **restapiPhpactionrequestPasswordResetPost**
> App\OpenApi\Model\RestapiPhpActionRequestPasswordResetPost200Response restapiPhpactionrequestPasswordResetPost($restapiPhpActionRequestPasswordResetPostRequest)

Passwort-Reset anfordern

Sendet einen Reset-Link an die E-Mail-Adresse des Benutzers

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/PasswordApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\PasswordApiInterface;

class PasswordApi implements PasswordApiInterface
{

    // ...

    /**
     * Implementation of PasswordApiInterface#restapiPhpactionrequestPasswordResetPost
     */
    public function restapiPhpactionrequestPasswordResetPost(RestapiPhpActionRequestPasswordResetPostRequest $restapiPhpActionRequestPasswordResetPostRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **restapiPhpActionRequestPasswordResetPostRequest** | [**App\OpenApi\Model\RestapiPhpActionRequestPasswordResetPostRequest**](../Model/RestapiPhpActionRequestPasswordResetPostRequest.md)|  |

### Return type

[**App\OpenApi\Model\RestapiPhpActionRequestPasswordResetPost200Response**](../Model/RestapiPhpActionRequestPasswordResetPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionresetPasswordPost**
> App\OpenApi\Model\RestapiPhpActionRequestPasswordResetPost200Response restapiPhpactionresetPasswordPost($restapiPhpActionResetPasswordPostRequest)

Passwort zurücksetzen

Setzt das Passwort mit einem gültigen Reset-Token

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/PasswordApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\PasswordApiInterface;

class PasswordApi implements PasswordApiInterface
{

    // ...

    /**
     * Implementation of PasswordApiInterface#restapiPhpactionresetPasswordPost
     */
    public function restapiPhpactionresetPasswordPost(RestapiPhpActionResetPasswordPostRequest $restapiPhpActionResetPasswordPostRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **restapiPhpActionResetPasswordPostRequest** | [**App\OpenApi\Model\RestapiPhpActionResetPasswordPostRequest**](../Model/RestapiPhpActionResetPasswordPostRequest.md)|  |

### Return type

[**App\OpenApi\Model\RestapiPhpActionRequestPasswordResetPost200Response**](../Model/RestapiPhpActionRequestPasswordResetPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

