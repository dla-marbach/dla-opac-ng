<?php

namespace Dla\DlaOpacNg\Cli;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Style\SymfonyStyle;
use \TYPO3\CMS\Core\Core\Bootstrap;
use \TYPO3\CMS\Core\Database\Connection;
use \TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\MathUtility;

class Import extends Command {

    /**
     * Configure the command by defining the name, options and arguments.
     *
     * @return void
     */
    public function configure()
    {
        $this
            ->setDescription('Import new collection hierarchy into database.')
            ->setHelp('')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'CSV file to import'
            )
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'PID to use for importing'
            );
    }

    /**
     * Executes the command to import new collection hierarchy into database.
     *
     * @param InputInterface $input The input parameters
     * @param OutputInterface $output The Symfony interface for outputs on console
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::getInstance()->initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        // Get input parameters
        $file = fopen($input->getOption('file'), 'r');
        $pid = MathUtility::forceIntegerInRange((int) $input->getOption('pid'), 0);

        if ($file === false) {
            $io->error('ERROR: Required parameter --file|-f missing or file not readable.');
            exit(1);
        }

        if ($pid === 0) {
            $io->error('ERROR: No valid PID (' . $input->getOption('pid') . ') given.');
            exit(1);
        }

        // Read field names from first line
        $fields = fgetcsv($file);

        if ($fields === false) {
            $io->error('ERROR: No valid CSV file (' . $input->getOption('file') . ') given.');
            exit(1);
        }

        // Get database connection for table 'tx_dlaopacng_tectonic'
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlaopacng_tectonic');

        // Start transaction
        $connection->beginTransaction();

        try {
            // Truncate table prior to inserting new data
            $connection->truncate('tx_dlaopacng_tectonic');

            // Read contents from subsequent lines
            while ($record = fgetcsv($file)) {
                // Insert new data
                $connection->insert(
                    'tx_dlaopacng_tectonic',
                    array_combine(
                        array_merge($fields, ['pid']),
                        array_merge($record, [$pid])
                    )            
                );
            }

            // Release file handle
            fclose($file);

            // Commit changes
            $connection->commit();
        } catch (\Exception $e) {
            // Something went wrong - roll back!
            $connection->rollBack();

            // Return error
            $io->error('ERROR: ' . $e->getMessage());
            exit(1);
        }

        // That's it!
        $io->success('All done!');
    }
}
