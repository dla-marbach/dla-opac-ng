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
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Sitemap extends Command {

    /**
     * Configure the command by defining the name, options and arguments.
     *
     * @return void
     */
    public function configure()
    {
        $this
            ->setDescription('Preparing IDs for sitemap files')
            ->setHelp('')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'ID file to import'
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
        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dla_opac_ng');
        include_once $extPath . 'Classes/Ajax/EidSettings.php';

        $DETAIL_LINK = $ABSOLUTE_DETAIL_URL;
        $sitemapNamespace = "http://www.sitemaps.org/schemas/sitemap/0.9";

        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        // Get input parameters
        $file = $input->getOption('file');

        if (!file_exists($file)) {
            $io->error('ERROR: Required parameter --file|-f missing or file not readable.');
            exit(1);
        }

        try {
            $lines = $this->fetch_data_from_file($file);

            // build sitemaps with detail urls
            $y = 0;
            $i = 0;
            $fileOpenFlag = false;
            $fileNames = [];
            $today = new \DateTime('today');
            foreach ($lines as $line_number => $line) {
                if (!empty($line)) {
                    if ($y === 0) {
                        $fileOpenFlag = true;
                        $sitemapFileName = 'sitemap' . $i . '.xml';
                        $sitemapFileHandler = fopen($SITEMAP_DIR . $sitemapFileName, 'w');

                        $fileNames[] = $sitemapFileName;

                        $document = new \DOMDocument();
                        $document->encoding = 'utf-8';
                        $document->xmlVersion = '1.0';
                        $document->formatOutput = true;

                        $doc_urlset = $document->createElementNS($sitemapNamespace, "urlset" );
                    }

                    $link = $DETAIL_LINK . $line;
                    $doc_url = $document->createElementNS($sitemapNamespace, "url");
                    $doc_loc = $document->createElementNS($sitemapNamespace, "loc", $link);
                    $doc_lastmod = $document->createElementNS($sitemapNamespace, "lastmod", $today->format('c'));

                    $doc_url->appendChild($doc_lastmod);
                    $doc_url->appendChild($doc_loc);
                    $doc_urlset->appendChild($doc_url);
                    $document->appendChild($doc_urlset);
                    $y++;
                    echo $sitemapFileName . ': ' . $y . ' / 49999', "\r";
                }
                if ($y > 49999) {
                    $i++;
                    $y = 0;

                    fwrite($sitemapFileHandler, $document->saveXML());
                    fclose($sitemapFileHandler);
                    $fileOpenFlag = false;
                }
            }
            if ($fileOpenFlag) {
                fwrite($sitemapFileHandler, $document->saveXML());
                fclose($sitemapFileHandler);
            }

            // build sitemap index file for each file
            $sitemapIndexName = 'sitemap.xml';
            $sitemapFileHandler = fopen($SITEMAP_DIR . $sitemapIndexName, 'w');

            $j = 0;
            foreach ($fileNames as $fileName) {
                if ($j === 0) {
                    $indexDocument = new \DOMDocument();
                    $indexDocument->encoding = 'utf-8';
                    $indexDocument->xmlVersion = '1.0';
                    $indexDocument->formatOutput = true;

                    $sitemapIndexXml = $indexDocument->createElementNS($sitemapNamespace, "sitemapindex" );
                }

                $sitemapXml = $indexDocument->createElementNS($sitemapNamespace, "sitemap");
                $sitemapLocationXml = $indexDocument->createElementNS($sitemapNamespace, "loc", $SITEMAP_URL . $fileName);

                $sitemapXml->appendChild($sitemapLocationXml);
                $sitemapIndexXml->appendChild($sitemapXml);
                $indexDocument->appendChild($sitemapIndexXml);
                $j++;
            }
//            $proc = new \XSLTProcessor();
//            $proc->importStyleSheet($xsl);

            fwrite($sitemapFileHandler, $indexDocument->saveXML());
            fclose($sitemapFileHandler);


        } catch (\Exception $e) {

            // Return error
            $io->error('ERROR: ' . $e->getMessage());
//            return Command::FAILURE;
            return 1;
        }

        // That's it!
        $io->success('All done!');
//        return Command::SUCCESS;
        return 0;
    }

    function fetch_data_from_file(string $file_path) : iterable {
        $resource = fopen($file_path, 'r');
        while (($line = fgets($resource)) !== false) {
            yield trim($line);
        }
    }
}
