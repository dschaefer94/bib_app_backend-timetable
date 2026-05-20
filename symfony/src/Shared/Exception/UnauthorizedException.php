<?php

namespace App\Shared\Exception;

class UnauthorizedException extends \Exception
{
    public static function notAuthenticated(): self
    {
        return new self('Nicht authentifiziert', 401);
    }

    public static function notAuthorized(): self
    {
        return new self('Nicht autorisiert', 401);
    }

    public static function adminRequired(): self
    {
        return new self('Admin-Berechtigung erforderlich', 403);
    }

    public static function notLoggedIn(): self
    {
        return new self('Nicht eingeloggt', 401);
    }
}

