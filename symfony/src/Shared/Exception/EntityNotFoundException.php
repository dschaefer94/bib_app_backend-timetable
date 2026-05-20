<?php

namespace App\Shared\Exception;

class EntityNotFoundException extends \Exception
{
    public static function withMessage(string $message, string $code = ''): self
    {
        return new self($message);
    }

    public static function userNotFound(): self
    {
        return new self('Benutzer nicht gefunden', 404);
    }

    public static function classNotFound(): self
    {
        return new self('Klasse nicht gefunden', 404);
    }

    public static function changeNotFound(): self
    {
        return new self('Änderung nicht gefunden', 404);
    }
}

