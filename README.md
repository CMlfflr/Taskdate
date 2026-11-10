# Taskdate

Nextcloud-App zur automatischen Erkennung von Datumsangaben im Titel einer Aufgabe (`VTODO`). Beispiel: `Zahnarzt 20261112 1400` wird am 12.11.2026 um 14:00 Uhr fällig.

## Installation

Die Dateien aus diesem Repository in `custom_apps/taskdate` der Nextcloud-Installation ablegen und anschließend als Administrator aktivieren, beispielsweise mit `php occ app:enable taskdate`.

**Testversion 0.2.0:** Die App deklariert Nextcloud **33–35**. Die Nextcloud-35-Kompatibilität ist noch **nicht durch einen echten CalDAV-Integrationstest bestätigt**. Vor der produktiven Nutzung auf einer Testinstanz ausprobieren.

## Erkennung

- Daten: `12.11.2026`, `12.11.26`, `12.11.`, `2026-11-12`, `20261112`
- Relative Angaben: `heute`, `morgen`, `übermorgen`, deutsche Wochentage
- Uhrzeiten: `14:30`, `14.30`, `14 Uhr`, `14.30 Uhr`, vierstellige Schreibweisen wie `1400` (Minuten 00, 15, 30 oder 45)

Ohne erkannte Uhrzeit wird ein ganztägiges `DUE`-Datum gesetzt. Bereits vorhandene manuelle Fälligkeiten bleiben unverändert. Automatisch erzeugte Fälligkeiten werden über `X-TASKDATE-AUTO-DUE` gekennzeichnet und bei Titeländerungen aktualisiert oder bei Entfernen des Datums gelöscht. Wird eine automatisch gesetzte Fälligkeit manuell geändert, fasst TaskDate sie nicht mehr an. Es wird **kein zusätzlicher Kalendereintrag** erstellt: Die bestehende Aufgabe erscheint bei aktivierter Aufgabenanzeige im Kalender.

## Tests

```sh
php tests/date_parser_test.php
```

Eine minimale Praxisprüfung auf Nextcloud 35:

1. Aufgabe `Zahnarzt 20261112 1400` anlegen; Fälligkeit 12.11.2026, 14:00 Uhr prüfen.
2. Titel auf `Zahnarzt 20261113 1430` ändern; Fälligkeit muss aktualisiert werden.
3. Fälligkeit manuell ändern, anschließend Titel ändern; manuelle Fälligkeit muss erhalten bleiben.
4. Neue Aufgabe mit manuell gesetzter Fälligkeit anlegen; diese darf nicht überschrieben werden.
5. Titel einer automatisch terminierten Aufgabe ohne Datum speichern; automatisch gesetzte Fälligkeit muss verschwinden.
6. Aufgabe im Kalender überprüfen (Aufgabenanzeige einschalten).

## Prüfung und bekannte Einschränkungen

Die PHP-Laufzeit steht in der Entwicklungsumgebung derzeit nicht zur Verfügung; PHP-Syntaxprüfung und obige Testfälle sind daher noch auszuführen. Ein Test gegen eine echte Nextcloud-/CalDAV-Instanz steht ebenfalls aus. Insbesondere Zeitzonen, Wiederholungsaufgaben und die Verarbeitung externer Aufgabenclients bleiben zu prüfen.

Die ursprüngliche Metadaten-Lizenz lautet `agpl`, während das Repository eine GPL-3.0-Lizenzdatei enthält. Diese Inkonsistenz sollte mit dem Rechteinhaber geklärt werden.
