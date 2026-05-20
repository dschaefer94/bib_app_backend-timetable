# OpenAPI\Server\Api\TimetableApiInterface

All URIs are relative to *http://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**loadTimetable**](TimetableApiInterface.md#loadTimetable) | **GET** /timetable | load personal timetable


## Service Declaration
```yaml
# config/services.yaml
services:
    # ...
    Acme\MyBundle\Api\TimetableApi:
        tags:
            - { name: "open_api_server.api", api: "timetable" }
    # ...
```

## **loadTimetable**
> OpenAPI\Server\Model\Timetable loadTimetable()

load personal timetable

### Example Implementation
```php
<?php
// src/Acme/MyBundle/Api/TimetableApiInterface.php

namespace Acme\MyBundle\Api;

use OpenAPI\Server\Api\TimetableApiInterface;

class TimetableApi implements TimetableApiInterface
{

    // ...

    /**
     * Implementation of TimetableApiInterface#loadTimetable
     */
    public function loadTimetable(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // Implement the operation ...
    }

    // ...
}
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**OpenAPI\Server\Model\Timetable**](../Model/Timetable.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

