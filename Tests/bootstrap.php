<?php

/**
 * Bootstrap für Tests/phpunit.xml: lädt den Unit-Test-Bootstrap des TYPO3 Testing Frameworks
 * aus dem passenden vendor-Verzeichnis. Unterstützt werden
 * - `composer install` direkt im Repository (vendor/ im Repo-Wurzelverzeichnis, z.B. in der CI) und
 * - die DDEV-Instanz t3example/ (Extension unter t3example/.local-ext/dla-dla_opac_ng/, vendor/ in t3example/).
 */

foreach ([dirname(__DIR__), dirname(__DIR__, 3)] as $root) {
    $bootstrap = $root . '/vendor/typo3/testing-framework/Resources/Core/Build/UnitTestsBootstrap.php';
    if (is_file($bootstrap)) {
        require $bootstrap;
        return;
    }
}

fwrite(STDERR, "TYPO3 Testing Framework nicht gefunden: 'composer install' im Repository oder 'task install' ausführen.\n");
exit(1);
