# AbfallRemind - IP-Symcon Modul

## Installation
1. Neues Verzeichnis anlegen: `IP-Symcon/modules/AbfallReminderMK/`
2. enthält Dateien:
   - `form.json`
   - `module.json`
   - `module.php`
   - `README.md`
3. IP-Symcon: Modulverwaltung öffnen und auf "Module neu laden" klicken.
4. Unter Instanzen ein neues "Abfall Reminder" Modul anlegen.

## Konfiguration
- **IMAP_InstanzID**: ID deiner IMAP-Instanz in IP-Symcon.
- **CacheSize**: Wenn nötig, Cache-Größe setzen (wird beim ApplyChanges in IMAP-Instanz geschrieben).
- **MailAbsender**: JSON-Array mit den erlaubten Absendern, z. B. `["noreply@awido.de","noreply@cubefour.de"]`.
- **Abfallarten**: JSON-Array mit Müllarten (z. B. `["Papiertonne","Biomüll","Gelber Sack"]`).
- **OrtFilter**: Optional, nur Treffer mit diesem Ortsstring werden gewertet (z. B. "Krombach").
- **Testdatum**: Optional, setze `YYYY-MM-DD` um Tests zu fahren (z. B. `2025-09-30`).
- **FetchInterval**: Intervall in Sekunden (Standard 3600 = 1 Stunde).
- **EventVariableID**: Event auf Änderung der IMAP Variable 'Letze Nachricht'.

## Variablen (vom Modul angelegt)
- **NächsteAbfalltermine** (String)
- **NächsteAbfalltermine** (HTML)
- **Aktiv** (Boolean) <= relevante Mail gefunden?
- **LetzterFehler** (String)
- **TimeoutCounter** (Integer)

## Manuelle Ausführung
- In der Modulinstanz die Aktion `FetchMails()` ausführen (oder Timer warten).

## Hinweise
- Das Modul benutzt die IMAP-Funktionen `IMAP_GetCachedMails` und `IMAP_GetMailEx`.
- Parser ist zeilenbasiert (robust) — kein gieriges Regex.
----

