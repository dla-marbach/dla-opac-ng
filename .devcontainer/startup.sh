#!/bin/bash
set -euo pipefail

# Task ist im Image (.devcontainer/Dockerfile), Node.js als Feature (devcontainer.json)

# PHP 8.3, Composer, Apache, MariaDB und TYPO3-Instanz (siehe Taskfile.yml)
task install

# Abhängigkeiten für die PHPUnit-Tests
task test:php:install

# git repositories
[ -e dla-opac-tests/package.json ] || git clone https://github.com/dla-marbach/dla-opac-tests

# playwright
cd dla-opac-tests
npm ci
npx playwright install chromium --with-deps
cd -
