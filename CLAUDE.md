# CLAUDE.md

Die Projektanweisungen werden gemeinsam mit GitHub Copilot gepflegt:

@.github/copilot-instructions.md

## Claude Code

- In Cloud-Sessions richtet `.claude/hooks/session-start.sh` die Umgebung ein (Log: `/tmp/session-start.log`): TYPO3 läuft danach unter http://127.0.0.1/, PHPUnit-Abhängigkeiten sind installiert.
- Nach Codeänderungen an der Extension `task cache` ausführen, bevor Seiten unter http://127.0.0.1/ geprüft werden.
