# App\OpenApi\Api\CalendarApiInterface

All URIs are relative to *http://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**restapiPhpactiongetCalendarGet**](CalendarApiInterface.md#restapiPhpactiongetCalendarGet) | **GET** /restapi.php?action&#x3D;getCalendar | Aktuellen Stundenplan abrufen
[**restapiPhpactiongetChangesGet**](CalendarApiInterface.md#restapiPhpactiongetChangesGet) | **GET** /restapi.php?action&#x3D;getChanges | Terminänderungen abrufen
[**restapiPhpactiongetNotedChangesGet**](CalendarApiInterface.md#restapiPhpactiongetNotedChangesGet) | **GET** /restapi.php?action&#x3D;getNotedChanges | Gelesene Änderungen abrufen
[**restapiPhpactionwriteNotedChangesPost**](CalendarApiInterface.md#restapiPhpactionwriteNotedChangesPost) | **POST** /restapi.php?action&#x3D;writeNotedChanges | Terminänderung als gelesen markieren


## Service Declaration
```yaml
# config/services.yaml
services:
    # ...
    Acme\MyBundle\Api\CalendarApi:
        tags:
            - { name: "open_api_server.api", api: "calendar" }
    # ...
```

## **restapiPhpactiongetCalendarGet**
> App\OpenApi\Model\CalendarEvent restapiPhpactiongetCalendarGet()

Aktuellen Stundenplan abrufen

Holt den aktuellen Stundenplan für die Klasse des eingeloggten Benutzers

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/CalendarApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\CalendarApiInterface;

class CalendarApi implements CalendarApiInterface
{

    // ...

    /**
     * Implementation of CalendarApiInterface#restapiPhpactiongetCalendarGet
     */
    public function restapiPhpactiongetCalendarGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\CalendarEvent**](../Model/CalendarEvent.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactiongetChangesGet**
> App\OpenApi\Model\ChangeRecord restapiPhpactiongetChangesGet()

Terminänderungen abrufen

Holt die Liste aller geänderten, neuen oder gelöschten Termine für die Klasse

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/CalendarApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\CalendarApiInterface;

class CalendarApi implements CalendarApiInterface
{

    // ...

    /**
     * Implementation of CalendarApiInterface#restapiPhpactiongetChangesGet
     */
    public function restapiPhpactiongetChangesGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\ChangeRecord**](../Model/ChangeRecord.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactiongetNotedChangesGet**
> App\OpenApi\Model\NotedChange restapiPhpactiongetNotedChangesGet()

Gelesene Änderungen abrufen

Holt die Liste aller vom Benutzer als gelesen markierten Terminänderungen

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/CalendarApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\CalendarApiInterface;

class CalendarApi implements CalendarApiInterface
{

    // ...

    /**
     * Implementation of CalendarApiInterface#restapiPhpactiongetNotedChangesGet
     */
    public function restapiPhpactiongetNotedChangesGet(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\NotedChange**](../Model/NotedChange.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

## **restapiPhpactionwriteNotedChangesPost**
> App\OpenApi\Model\RestapiPhpActionWriteNotedChangesPost200Response restapiPhpactionwriteNotedChangesPost($restapiPhpActionWriteNotedChangesPostRequest)

Terminänderung als gelesen markieren

Markiert eine Terminänderung als vom Benutzer gelesen

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/CalendarApiInterface.php

namespace Acme\MyBundle\Api;

use App\OpenApi\Api\CalendarApiInterface;

class CalendarApi implements CalendarApiInterface
{

    // ...

    /**
     * Implementation of CalendarApiInterface#restapiPhpactionwriteNotedChangesPost
     */
    public function restapiPhpactionwriteNotedChangesPost(RestapiPhpActionWriteNotedChangesPostRequest $restapiPhpActionWriteNotedChangesPostRequest, int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **restapiPhpActionWriteNotedChangesPostRequest** | [**App\OpenApi\Model\RestapiPhpActionWriteNotedChangesPostRequest**](../Model/RestapiPhpActionWriteNotedChangesPostRequest.md)|  |

### Return type

[**App\OpenApi\Model\RestapiPhpActionWriteNotedChangesPost200Response**](../Model/RestapiPhpActionWriteNotedChangesPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

