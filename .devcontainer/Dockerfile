# Base Image mit festem Versionstag, Updates über Dependabot.
# Ubuntu 24.04 (noble) wie in der Entwicklungsumgebung vorgesehen: PHP 8.3 (wie im Produktivsystem)
# kommt aus den Paketquellen der Distribution. PHP, Composer, Apache und MariaDB installiert
# `task install` (siehe Taskfile.yml und startup.sh), Node.js und Docker die Features in devcontainer.json.
FROM mcr.microsoft.com/devcontainers/base:3.0.12-noble

# Task (https://taskfile.dev) mit fester Version. Downloads per URL aktualisiert Dependabot nicht:
# Version bei Pflege des Devcontainers prüfen (https://github.com/go-task/task/releases).
ARG TASK_VERSION=3.53.1
RUN curl -fsSL "https://github.com/go-task/task/releases/download/v${TASK_VERSION}/task_linux_amd64.tar.gz" \
    | tar -xz -C /usr/local/bin task \
    && task --version

USER vscode
