<?php

namespace App;

use Doctrine\DBAL\Types\Type;
// use App\DBAL\Types\AenderungsLabelEnumType; // Entfernt
// use App\DBAL\Types\KalenderKategorieEnumType; // Entfernt
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Registriere benutzerdefinierte DBAL-Typen, falls noch nicht geschehen
        // if (!Type::hasType(AenderungsLabelEnumType::NAME)) { // Entfernt
        //     Type::addType(AenderungsLabelEnumType::NAME, AenderungsLabelEnumType::class); // Entfernt
        // }
        // if (!Type::hasType(KalenderKategorieEnumType::NAME)) { // Entfernt
        //     Type::addType(KalenderKategorieEnumType::NAME, KalenderKategorieEnumType::class); // Entfernt
        // }
    }
}
