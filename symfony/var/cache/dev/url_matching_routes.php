<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/restapi.php?action=getCalendar' => [[['_route' => 'open_api_server_calendar_restapiphpactiongetcalendarget', '_controller' => 'open_api_server.controller.calendar::restapiPhpactiongetCalendarGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=getChanges' => [[['_route' => 'open_api_server_calendar_restapiphpactiongetchangesget', '_controller' => 'open_api_server.controller.calendar::restapiPhpactiongetChangesGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=getNotedChanges' => [[['_route' => 'open_api_server_calendar_restapiphpactiongetnotedchangesget', '_controller' => 'open_api_server.controller.calendar::restapiPhpactiongetNotedChangesGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=writeNotedChanges' => [[['_route' => 'open_api_server_calendar_restapiphpactionwritenotedchangespost', '_controller' => 'open_api_server.controller.calendar::restapiPhpactionwriteNotedChangesPostAction'], null, ['POST' => 0], null, false, false, null]],
        '/restapi.php?action=getClassById' => [[['_route' => 'open_api_server_class_restapiphpactiongetclassbyidget', '_controller' => 'open_api_server.controller.class::restapiPhpactiongetClassByIdGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=getClass' => [[['_route' => 'open_api_server_class_restapiphpactiongetclassget', '_controller' => 'open_api_server.controller.class::restapiPhpactiongetClassGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=writeClass' => [[['_route' => 'open_api_server_class_restapiphpactionwriteclasspost', '_controller' => 'open_api_server.controller.class::restapiPhpactionwriteClassPostAction'], null, ['POST' => 0], null, false, false, null]],
        '/restapi.php?action=requestPasswordReset' => [[['_route' => 'open_api_server_password_restapiphpactionrequestpasswordresetpost', '_controller' => 'open_api_server.controller.password::restapiPhpactionrequestPasswordResetPostAction'], null, ['POST' => 0], null, false, false, null]],
        '/restapi.php?action=resetPassword' => [[['_route' => 'open_api_server_password_restapiphpactionresetpasswordpost', '_controller' => 'open_api_server.controller.password::restapiPhpactionresetPasswordPostAction'], null, ['POST' => 0], null, false, false, null]],
        '/api/users/register' => [[['_route' => 'open_api_server_user_apiusersregisterpost', '_controller' => 'open_api_server.controller.user::apiUsersRegisterPostAction'], null, ['POST' => 0], null, false, false, null]],
        '/restapi.php?action=getUser' => [[['_route' => 'open_api_server_user_restapiphpactiongetuserget', '_controller' => 'open_api_server.controller.user::restapiPhpactiongetUserGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=profile' => [[['_route' => 'open_api_server_user_restapiphpactionprofileget', '_controller' => 'open_api_server.controller.user::restapiPhpactionprofileGetAction'], null, ['GET' => 0], null, false, false, null]],
        '/restapi.php?action=updateProfile' => [[['_route' => 'open_api_server_user_restapiphpactionupdateprofileput', '_controller' => 'open_api_server.controller.user::restapiPhpactionupdateProfilePutAction'], null, ['PUT' => 0], null, false, false, null]],
        '/restapi.php?action=writeUser' => [[['_route' => 'open_api_server_user_restapiphpactionwriteuserpost', '_controller' => 'open_api_server.controller.user::restapiPhpactionwriteUserPostAction'], null, ['POST' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/restapi\\.php\\?action\\=(?'
                    .'|deleteClass&id\\=([a-z0-9]+)(*:95)'
                    .'|updateClass&id\\=([a-z0-9]+)(*:129)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        95 => [[['_route' => 'open_api_server_class_restapiphpactiondeleteclassididdelete', '_controller' => 'open_api_server.controller.class::restapiPhpactiondeleteClassididDeleteAction'], ['id'], ['DELETE' => 0], null, false, true, null]],
        129 => [
            [['_route' => 'open_api_server_class_restapiphpactionupdateclassididput', '_controller' => 'open_api_server.controller.class::restapiPhpactionupdateClassididPutAction'], ['id'], ['PUT' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
