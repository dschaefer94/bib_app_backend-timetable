<?php

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class KalenderKategorieEnumType extends StringType
{
    public const NAME = 'kalender_kategorie';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // For PostgreSQL, we return the name of the custom type.
        // The actual ENUM type must be created in the database beforehand (e.g., via init.sql).
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            return self::NAME;
        }

        // Fallback for other platforms or if the ENUM definition is needed directly
        return "ENUM('klausur', 'bib-event', 'eigenes-event', 'unterricht', 'projekt', 'ferien', 'prüfung')";
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
