<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Cli;

use Dla\DlaOpacNg\Statistics\MatomoClient;
use Dla\DlaOpacNg\Statistics\MatomoPusher;
use Dla\DlaOpacNg\Statistics\MatomoRequestBuilder;
use Dla\DlaOpacNg\Statistics\StatisticsConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sendet die Rohdaten der Nutzungsstatistik an Matomo (per Cron, z.B. jede Minute).
 * Läuft nie parallel: Ein zweiter Aufruf beendet sich, solange der erste läuft.
 */
class StatisticsMatomoPush extends Command
{
    public function configure(): void
    {
        $this
            ->setDescription('Send raw catalogue usage statistics to Matomo (Bulk Tracking API).')
            ->setHelp('Sends all completed minutes from var/opac-stats/raw/ (or opacStatsPath) to Matomo and deletes them afterwards.')
            ->addOption(
                'include-current-minute',
                null,
                InputOption::VALUE_NONE,
                'Also send the current minute (for testing only, may lose concurrent writes)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Print the Matomo requests as JSON instead of sending them; nothing is deleted'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = StatisticsConfiguration::getStorageDirectory();
        $includeCurrentMinute = (bool)$input->getOption('include-current-minute');
        $builder = MatomoRequestBuilder::fromConfiguration();

        if ($input->getOption('dry-run')) {
            $pusher = new MatomoPusher($directory, $builder, static fn(array $requests): int => 0);
            $output->writeln((string)json_encode(
                $pusher->preview(null, $includeCurrentMinute),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            ));
            return Command::SUCCESS;
        }

        if (!StatisticsConfiguration::isMatomoConfigured()) {
            $io->error('Matomo is not configured (opacMatomoUrl, opacMatomoSiteId, opacMatomoToken).');
            return Command::FAILURE;
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $io->error('Cannot create ' . $directory);
            return Command::FAILURE;
        }

        $lock = fopen($directory . '/matomo-push.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            $io->warning('Another push is running.');
            return Command::SUCCESS;
        }

        $client = new MatomoClient(
            GeneralUtility::makeInstance(RequestFactory::class),
            StatisticsConfiguration::getMatomoUrl(),
            StatisticsConfiguration::getMatomoToken()
        );

        try {
            $result = (new MatomoPusher($directory, $builder, $client->send(...)))
                ->push(null, $includeCurrentMinute);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $message = sprintf(
            'Sent %d file(s) with %d record(s) as %d request(s); %d rejected by Matomo.',
            $result['files'],
            $result['records'],
            $result['requests'],
            $result['invalid']
        );

        // Verworfene Requests (z.B. falsche Website-ID oder fehlende Rechte) sind verloren: Exit-Code ≠ 0 für das Monitoring.
        if ($result['invalid'] > 0) {
            $io->warning($message);
            return Command::FAILURE;
        }

        $io->success($message);

        return Command::SUCCESS;
    }
}
