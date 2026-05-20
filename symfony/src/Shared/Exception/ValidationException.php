<?php

namespace App\Shared\Exception;

class ValidationException extends \Exception
{
    public function __construct(string $message = '', private array $errors = [])
    {
        parent::__construct($message, 400);
    }

    public static function withErrors(array $errors): self
    {
        $message = implode(', ', $errors);
        return new self($message, $errors);
    }

    public static function missingField(string $field): self
    {
        return new self("Feld erforderlich: $field");
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

