#!/bin/bash

# go-task
curl -fsSL https://github.com/go-task/task/releases/latest/download/task_linux_amd64.tar.gz \
  | sudo tar -xz -C /usr/local/bin task

# PHP, Composer, MariaDB und TYPO3-Instanz ohne Docker/DDEV (siehe Taskfile.yml)
task install

# Abhängigkeiten für die PHPUnit-Tests (task test:php)
COMPOSER_NO_SECURITY_BLOCKING=1 composer install --no-interaction --no-progress

# git repositories
git clone https://github.com/dla-marbach/dla-opac-tests

# playwright
cd dla-opac-tests
npm ci
npx playwright install chromium --with-deps
cd -
