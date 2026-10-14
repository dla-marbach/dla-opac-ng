#!/bin/bash
# Richtet die Entwicklungsumgebung in Claude-Code-Cloud-Sessions ein.
# Synchron (Session wartet): Task und Composer-Abhängigkeiten für die PHPUnit-Tests.
# Im Hintergrund: TYPO3-Instanz (task install bzw. task start), fertig sobald /tmp/typo3-ready existiert.
# Der Container-Zustand wird nach dem ersten Lauf zwischengespeichert, spätere Sessions starten nur noch die Dienste.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "$CLAUDE_PROJECT_DIR"
export COMPOSER_ALLOW_SUPERUSER=1
echo 'export COMPOSER_ALLOW_SUPERUSER=1' >> "${CLAUDE_ENV_FILE:-/dev/null}"
rm -f /tmp/typo3-ready /tmp/typo3-failed

{
  if ! command -v task >/dev/null; then
    curl -fsSL https://github.com/go-task/task/releases/latest/download/task_linux_amd64.tar.gz \
      | tar -xz -C /usr/local/bin task
  fi
  task test:php:install
} >/tmp/session-start.log 2>&1 || { echo "Einrichtung fehlgeschlagen, siehe /tmp/session-start.log:"; tail -20 /tmp/session-start.log; exit 1; }

# TYPO3-Instanz im Hintergrund, damit die Session nicht darauf warten muss
setsid nohup bash -c '
  if [ -f t3example/.installed ]; then task start; else task reinstall; fi \
    && touch /tmp/typo3-ready || touch /tmp/typo3-failed
' >/tmp/typo3-setup.log 2>&1 < /dev/null &

echo "PHPUnit bereit ('task test:php'). TYPO3-Instanz wird im Hintergrund eingerichtet bzw. gestartet (Log: /tmp/typo3-setup.log); bereit, sobald /tmp/typo3-ready existiert (Fehler: /tmp/typo3-failed)."
