<?php

namespace Dla\DlaOpacNg\Cli;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Style\SymfonyStyle;
use \TYPO3\CMS\Core\Core\Bootstrap;
use \TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\MathUtility;

class Import extends Command {

    protected static $tables = [
        'classification' => 'tx_dlaopacng_classification',
        'collection' => 'tx_dlaopacng_tectonic'
    ];

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
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type of data ("collection" or "classification")'
            )
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
        $type = $input->getOption('type');
        $file = fopen($input->getOption('file'), 'r');
        $pid = MathUtility::forceIntegerInRange((int) $input->getOption('pid'), 0);

        if ($type !== 'collection' && $type !== 'classification') {
            $io->error('ERROR: Required parameter --type|-t missing or invalid (has to be "collection" or "classification").');
            exit(1);
        }

        if ($file === false) {
            $io->error('ERROR: Required parameter --file|-f missing or file not readable.');
            exit(1);
        }

        if ($pid === 0) {
            $io->error('ERROR: Required parameter --pid|-p missing or invalid).');
            exit(1);
        }

        // Read field names from first line
        $fields = fgetcsv($file);

        if ($fields === false) {
            $io->error('ERROR: No valid CSV file (' . $input->getOption('file') . ') given.');
            exit(1);
        }

        // Get database connection
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tables[$type]);

        // Start transaction
        $connection->beginTransaction();

        try {
            // Truncate table prior to inserting new data
            $connection->truncate($this->tables[$type]);

            // Read contents from subsequent lines
            while ($record = fgetcsv($file)) {
                // Insert new data
                $connection->insert(
                    $this->tables[$type],
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
