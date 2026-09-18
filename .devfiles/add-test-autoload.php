<?php

/**
 * Ergänzt die PSR-4-Autoload-Zuordnung für die PHPUnit-Testnamespaces von dla-find und
 * dla_opac_ng im t3example-Projekt (autoload-dev der eingebundenen Pakete wird von Composer
 * beim Root-Projekt ignoriert, daher muss die Zuordnung hier im Root-composer.json ergänzt werden).
 *
 * Aufruf (aus t3example/): php ../.devfiles/add-test-autoload.php
 */

$file = 'composer.json';
$json = json_decode(file_get_contents($file), true);
$json['autoload-dev']['psr-4']['Dla\\Find\\Tests\\'] = 'packages/dla-find/Tests/';
$json['autoload-dev']['psr-4']['Dla\\DlaOpacNg\\Tests\\'] = 'packages/dla-dla_opac_ng/Tests/';
file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
