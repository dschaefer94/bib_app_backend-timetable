<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MinimalTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        self::bootKernel();
        $this->assertTrue(true); // If we reach here, the kernel booted successfully
    }
}
