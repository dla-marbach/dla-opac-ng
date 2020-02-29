<?php

/**
 * Commands to be executed by TYPO3, where the key of the array
 * is the name of the command (to be called as the first argument after "typo3").
 * Required parameter is the "class" of the command which needs to be a subclass
 * of \Symfony\Component\Console\Command\Command.
 */
return [
    'dla_opac_ng:import' => [
        'class' => Dla\DlaOpacNg\Cli\Import::class
    ]
];
