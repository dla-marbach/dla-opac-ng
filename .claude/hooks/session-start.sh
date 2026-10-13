#!/bin/bash
# Richtet die Entwicklungsumgebung in Claude-Code-Cloud-Sessions ein (ohne Docker/DDEV):
# Task, PHPUnit-Abhängigkeiten und eine laufende TYPO3-Instanz (MariaDB + PHP-Webserver, siehe Taskfile.yml).
# Der Container-Zustand wird nach dem ersten Lauf zwischengespeichert, spätere Sessions starten nur noch die Dienste.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "$CLAUDE_PROJECT_DIR"
LOG=/tmp/session-start.log
export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_SECURITY_BLOCKING=1
echo 'export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_SECURITY_BLOCKING=1' >> "${CLAUDE_ENV_FILE:-/dev/null}"

{
  if ! command -v task >/dev/null; then
    curl -fsSL https://github.com/go-task/task/releases/latest/download/task_linux_amd64.tar.gz \
      | tar -xz -C /usr/local/bin task
  fi
  task install_packages
  composer install --no-interaction --no-progress
  if [ -f t3example/public/index.php ]; then
    task start
  else
    task reinstall
  fi
} >"$LOG" 2>&1 || { echo "Einrichtung fehlgeschlagen, siehe $LOG:"; tail -20 "$LOG"; exit 1; }

echo "Entwicklungsumgebung bereit: TYPO3 auf http://127.0.0.1/ (Backend /typo3, admin / Dla1337!), PHPUnit mit 'task test:php'. Log: $LOG"
