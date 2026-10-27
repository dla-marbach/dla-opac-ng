<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Cli;

use Dla\DlaOpacNg\Statistics\StatisticsAggregator;
use Dla\DlaOpacNg\Statistics\StatisticsConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fasst die Rohdaten der Nutzungsstatistik zu Tages- und Monatsdateien zusammen (per Cron bzw. Scheduler,
 * z.B. alle 15 Minuten). Läuft nie parallel: Ein zweiter Aufruf beendet sich, solange der erste läuft.
 */
class StatisticsAggregate extends Command
{
    public function configure(): void
    {
        $this
            ->setDescription('Aggregate raw catalogue usage statistics into daily and monthly JSON files.')
            ->setHelp('Processes all completed hours from var/opac-stats/raw/ (or opacStatsPath) and deletes them afterwards.')
            ->addOption(
                'include-current-hour',
                null,
                InputOption::VALUE_NONE,
                'Also process the current hour (for testing only, may lose concurrent writes)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = StatisticsConfiguration::getStorageDirectory();

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $io->error('Cannot create ' . $directory);
            return Command::FAILURE;
        }

        $lock = fopen($directory . '/aggregate.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            $io->warning('Another aggregation is running.');
            return Command::SUCCESS;
        }

        try {
            $result = (new StatisticsAggregator($directory))
                ->aggregate(null, (bool)$input->getOption('include-current-hour'));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $io->success(sprintf(
            'Processed %d file(s) with %d record(s); updated %d day(s) and %d month(s).',
            $result['files'],
            $result['records'],
            count($result['days']),
            count($result['months'])
        ));

        return Command::SUCCESS;
    }
}
