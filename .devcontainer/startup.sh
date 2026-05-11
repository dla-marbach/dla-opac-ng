#!/bin/bash

# DDEV
bash -c "$(curl --location https://ddev.com/install.sh)"

# go-task
bash -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin

# git repositories
git clone https://github.com/dla-marbach/typo3-find dla-find
git clone https://github.com/dla-marbach/dla-opac-tests --branch develop

# playwright
sudo rm -f /etc/apt/sources.list.d/yarn.list || true ## GPG error: https://dl.yarnpkg.com/debian stable InRelease
cd dla-opac-tests
npm ci
npx playwright install chromium --with-deps
cd -