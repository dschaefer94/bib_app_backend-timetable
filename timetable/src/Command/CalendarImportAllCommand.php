<?php

namespace App\Command;

use App\Service\CalendarImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:calendar:import:all', description: 'Import calendars for all classes')]
class CalendarImportAllCommand extends Command
{
    public function __construct(private CalendarImportService $importService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import calendars for all classes')
            ->addArgument('fullSync', InputArgument::OPTIONAL, 'Full sync flag (true/false)', 'true');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fullSync = filter_var($input->getArgument('fullSync'), FILTER_VALIDATE_BOOLEAN);
        $result = $this->importService->importAllSources($fullSync);

        $output->writeln('Calendar batch import summary:');
        $output->writeln(json_encode($result, JSON_UNESCAPED_UNICODE));

        if (($result['failed'] ?? 0) > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

