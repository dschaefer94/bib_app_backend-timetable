<?php

namespace App\Command;

use App\Entity\CalendarSource;
use App\Service\CalendarImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:calendar:import', description: 'Import calendar for a class (by CalendarSource id)')]
class CalendarImportCommand extends Command
{

    public function __construct(private EntityManagerInterface $em, private CalendarImportService $importService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import calendar for a class (by CalendarSource id)')
            ->addArgument('sourceId', InputArgument::REQUIRED, 'CalendarSource id')
            ->addArgument('fullSync', InputArgument::OPTIONAL, 'Full sync flag (true/false)', 'true');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceId = (int)$input->getArgument('sourceId');
        $fullSync = filter_var($input->getArgument('fullSync'), FILTER_VALIDATE_BOOLEAN);

        /** @var CalendarSource|null $source */
        $source = $this->em->getRepository(CalendarSource::class)->find($sourceId);
        if (!$source) {
            $output->writeln(sprintf('<error>CalendarSource with id %d not found</error>', $sourceId));
            return Command::FAILURE;
        }

        $output->writeln(sprintf('Importing calendar for source %d (%s)', $sourceId, $source->getClassName()));
        $res = $this->importService->importFromIcalSource($source, $fullSync);
        $output->writeln('Result: ' . json_encode($res));

        return Command::SUCCESS;
    }
}

