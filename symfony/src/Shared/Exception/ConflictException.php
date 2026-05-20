<?php

namespace App\Shared\Exception;

class ConflictException extends \Exception
{
    public static function alreadyExists(string $message): self
    {
        return new self($message, 409);
    }

    public static function classAlreadyExists(): self
    {
        return new self('Klasse bereits vorhanden', 409);
    }

    public static function userAlreadyExists(): self
    {
        return new self('Benutzer existiert bereits', 409);
    }
}

