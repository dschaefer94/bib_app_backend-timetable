<?php

namespace App\Tests\Component;

use App\Command\CalendarImportAllCommand;
use App\Service\CalendarImportService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CalendarImportAllCommandTest extends TestCase
{
    public function testImportAllReturnsSuccessWhenNoFailures(): void
    {
        $service = $this->createMock(CalendarImportService::class);
        $service->expects($this->once())
            ->method('importAllSources')
            ->with(true)
            ->willReturn([
                'totalSources' => 2,
                'succeeded' => 2,
                'failed' => 0,
                'results' => [],
            ]);

        $command = new CalendarImportAllCommand($service);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testImportAllReturnsFailureWhenAtLeastOneSourceFails(): void
    {
        $service = $this->createMock(CalendarImportService::class);
        $service->expects($this->once())
            ->method('importAllSources')
            ->with(true)
            ->willReturn([
                'totalSources' => 2,
                'succeeded' => 1,
                'failed' => 1,
                'results' => [],
            ]);

        $command = new CalendarImportAllCommand($service);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
    }
}

