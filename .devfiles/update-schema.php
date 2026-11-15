<?php

/**
 * Bringt das Datenbankschema der lokalen TYPO3-Instanz (task install) auf den Stand der installierten
 * TYPO3-Version: legt fehlende Tabellen und Felder an und passt geänderte Felder an, entfernt aber nichts.
 *
 * Nötig, weil der Dump init.sql aus TYPO3 v12 stammt. `typo3 extension:setup` scheidet dafür aus: ab TYPO3 v14
 * meldet es zuerst den CLI-Backend-Benutzer an und braucht dafür bereits das neue Schema.
 *
 * Aufruf im Verzeichnis der Instanz: php ../.devfiles/update-schema.php
 */

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;

$classLoader = require getcwd() . '/vendor/autoload.php';
SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
$container = Bootstrap::init($classLoader);

$sqlReader = $container->get(SqlReader::class);
$statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
$errors = array_filter($container->get(SchemaMigrator::class)->install($statements));

if ($errors !== []) {
    fwrite(STDERR, "Datenbankschema konnte nicht vollständig aktualisiert werden:\n" . implode("\n", $errors) . "\n");
    exit(1);
}
echo "Datenbankschema aktualisiert.\n";
