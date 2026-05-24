<?php

namespace App;

use Doctrine\DBAL\Types\Type;
use App\DBAL\Types\AenderungsLabelEnumType;
use App\DBAL\Types\KalenderKategorieEnumType;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Registriere benutzerdefinierte DBAL-Typen, falls noch nicht geschehen
        if (!Type::hasType(AenderungsLabelEnumType::NAME)) {
            Type::addType(AenderungsLabelEnumType::NAME, AenderungsLabelEnumType::class);
        }
        if (!Type::hasType(KalenderKategorieEnumType::NAME)) {
            Type::addType(KalenderKategorieEnumType::NAME, KalenderKategorieEnumType::class);
        }
    }
}
