#!/bin/bash

# Fix: iptables-legacy/nftables Split-Brain in Codespaces-VMs kann dazu führen,
# dass Docker-Container keinen Internetzugriff haben (FORWARD-Policy DROP im
# aktiven nft-Regelwerk, während Docker seine ACCEPT-Regeln nur im legacy-Regelwerk anlegt).
sudo update-alternatives --set iptables /usr/sbin/iptables-legacy || true
sudo update-alternatives --set ip6tables /usr/sbin/ip6tables-legacy || true
sudo pkill dockerd; sleep 2; sudo dockerd > /tmp/dockerd.log 2>&1 &

# DDEV
bash -c "$(curl --location https://ddev.com/install.sh)"

# go-task
bash -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin

# git repositories
git clone https://github.com/dla-marbach/typo3-find dla-find
git clone https://github.com/dla-marbach/dla-opac-tests

# playwright
sudo rm -f /etc/apt/sources.list.d/yarn.list || true ## GPG error: https://dl.yarnpkg.com/debian stable InRelease
cd dla-opac-tests
npm ci
npx playwright install chromium --with-deps
cd -