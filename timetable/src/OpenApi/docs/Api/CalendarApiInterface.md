# App\OpenApi\Api\CalendarApiInterface

All URIs are relative to *http://localhost:8000*

Method | HTTP request | Description
------------- | ------------- | -------------
[**getCalendar**](CalendarApiInterface.md#getCalendar) | **GET** /api/calendar | Aktuellen Stundenplan abrufen


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

## **getCalendar**
> App\OpenApi\Model\GetCalendar200Response getCalendar()

Aktuellen Stundenplan abrufen

Holt den aktuellen Stundenplan für die Klasse des Benutzers. Die Authentifizierung und Kontextauflösung erfolgt in einer späteren Version. Alle Termine sind mit optionalen Metadaten (Label, Kategorie, Originaltermin) angereichert.

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
     * Implementation of CalendarApiInterface#getCalendar
     */
    public function getCalendar(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**App\OpenApi\Model\GetCalendar200Response**](../Model/GetCalendar200Response.md)

### Authorization

No authorization required

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json, application/problem+json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

