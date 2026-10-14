#!/bin/bash
set -euo pipefail

# GPG error: https://dl.yarnpkg.com/debian stable InRelease
sudo rm -f /etc/apt/sources.list.d/yarn.list || true

# go-task
curl -fsSL https://github.com/go-task/task/releases/latest/download/task_linux_amd64.tar.gz \
  | sudo tar -xz -C /usr/local/bin task

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
