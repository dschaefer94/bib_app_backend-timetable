# App\OpenApi\Api\ClassApiInterface

All URIs are relative to *http://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**restapiPhpactiondeleteClassididDelete**](ClassApiInterface.md#restapiPhpactiondeleteClassididDelete) | **DELETE** /restapi.php?action&#x3D;deleteClass&amp;id&#x3D;{id} | Klasse löschen
[**restapiPhpactiongetClassByIdGet**](ClassApiInterface.md#restapiPhpactiongetClassByIdGet) | **GET** /restapi.php?action&#x3D;getClassById | Klasse des Benutzers abrufen
[**restapiPhpactiongetClassGet**](ClassApiInterface.md#restapiPhpactiongetClassGet) | **GET** /restapi.php?action&#x3D;getClass | Alle Klassen abrufen
[**restapiPhpactionupdateClassididPut**](ClassApiInterface.md#restapiPhpactionupdateClassididPut) | **PUT** /restapi.php?action&#x3D;updateClass&amp;id&#x3D;{id} | Klasse aktualisieren
[**restapiPhpactionwriteClassPost**](ClassApiInterface.md#restapiPhpactionwriteClassPost) | **POST** /restapi.php?action&#x3D;writeClass | Neue Klasse erstellen


## Service Declaration
```yaml
# config/services.yaml
services:
    # ...
    Acme\MyBundle\Api\ClassApi:
        tags:
            - { name: "open_api_server.api", api: "class" }
    # ...
```

## **restapiPhpactiondeleteClassididDelete**
> App\OpenApi\Model\RestapiPhpActionUpdateClassIdIdPut200Response restapiPhpactiondeleteClassididDelete($id)

Klasse löschen

Löscht eine Klasse und alle zugehörigen Daten (nur Admin)

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/ClassApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\ClassApiInterface;

class ClassApi implements ClassApiInterface
{

    // ...

    /**
     * Implementation of ClassApiInterface#restapiPhpactiondeleteClassididDelete
     */
    public function restapiPhpactiondeleteClassididDelete(string $id, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**| Klassen-ID |

### Return type

[**App\OpenApi\Model\RestapiPhpActionUpdateClassIdIdPut200Response**](../Model/RestapiPhpActionUpdateClassIdIdPut200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactiongetClassByIdGet**
> App\OpenApi\Model\ModelClass restapiPhpactiongetClassByIdGet()

Klasse des Benutzers abrufen

Ruft die Klasse des eingeloggten Benutzers ab

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/ClassApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\ClassApiInterface;

class ClassApi implements ClassApiInterface
{

    // ...

    /**
     * Implementation of ClassApiInterface#restapiPhpactiongetClassByIdGet
     */
    public function restapiPhpactiongetClassByIdGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\ModelClass**](../Model/ModelClass.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactiongetClassGet**
> App\OpenApi\Model\ModelClass restapiPhpactiongetClassGet()

Alle Klassen abrufen

Ruft alle verfügbaren Klassen ab

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/ClassApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\ClassApiInterface;

class ClassApi implements ClassApiInterface
{

    // ...

    /**
     * Implementation of ClassApiInterface#restapiPhpactiongetClassGet
     */
    public function restapiPhpactiongetClassGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\ModelClass**](../Model/ModelClass.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionupdateClassididPut**
> App\OpenApi\Model\RestapiPhpActionUpdateClassIdIdPut200Response restapiPhpactionupdateClassididPut($id, $restapiPhpActionUpdateClassIdIdPutRequest)

Klasse aktualisieren

Aktualisiert den Namen und/oder iCal-Link einer Klasse

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/ClassApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\ClassApiInterface;

class ClassApi implements ClassApiInterface
{

    // ...

    /**
     * Implementation of ClassApiInterface#restapiPhpactionupdateClassididPut
     */
    public function restapiPhpactionupdateClassididPut(string $id, RestapiPhpActionUpdateClassIdIdPutRequest $restapiPhpActionUpdateClassIdIdPutRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**| Klassen-ID |
 **restapiPhpActionUpdateClassIdIdPutRequest** | [**App\OpenApi\Model\RestapiPhpActionUpdateClassIdIdPutRequest**](../Model/RestapiPhpActionUpdateClassIdIdPutRequest.md)|  |

### Return type

[**App\OpenApi\Model\RestapiPhpActionUpdateClassIdIdPut200Response**](../Model/RestapiPhpActionUpdateClassIdIdPut200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionwriteClassPost**
> App\OpenApi\Model\RestapiPhpActionWriteClassPost201Response restapiPhpactionwriteClassPost($restapiPhpActionWriteClassPostRequest)

Neue Klasse erstellen

Erstellt eine neue Klasse mit Stundenplan-Link

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/ClassApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\ClassApiInterface;

class ClassApi implements ClassApiInterface
{

    // ...

    /**
     * Implementation of ClassApiInterface#restapiPhpactionwriteClassPost
     */
    public function restapiPhpactionwriteClassPost(RestapiPhpActionWriteClassPostRequest $restapiPhpActionWriteClassPostRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **restapiPhpActionWriteClassPostRequest** | [**App\OpenApi\Model\RestapiPhpActionWriteClassPostRequest**](../Model/RestapiPhpActionWriteClassPostRequest.md)|  |

### Return type

[**App\OpenApi\Model\RestapiPhpActionWriteClassPost201Response**](../Model/RestapiPhpActionWriteClassPost201Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

