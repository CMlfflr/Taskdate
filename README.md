# Taskdate

Nextcloud-App zur automatischen Erkennung von Datumsangaben im Titel einer Aufgabe (`VTODO`). Wird ein Datum erkannt und die Aufgabe hat noch keine Fälligkeit (`DUE`), setzt die App diese über die CalDAV-Schnittstelle.

## Installation

Die Dateien aus diesem Repository in `custom_apps/taskdate` der Nextcloud-Installation ablegen und anschließend als Administrator aktivieren, beispielsweise mit `php occ app:enable taskdate`.

**Kompatibilität:** Version `0.1.0` aus dem bereitgestellten ZIP deklariert in `appinfo/info.xml` ausschließlich Nextcloud **28–33**. Für Nextcloud 35 ist die App derzeit weder freigegeben noch in einer laufenden Instanz getestet.

## Erkennung

- Daten: `12.11.2026`, `12.11.26`, `12.11.`, `2026-11-12`, `20261112`
- Relative Angaben: `heute`, `morgen`, `übermorgen`, deutsche Wochentage
- Uhrzeiten: `14:30`, `14 Uhr`, `14.30 Uhr`, vierstellige Schreibweisen wie `1400` (nur bestimmte Minuten)

Ohne erkannte Uhrzeit wird ein ganztägiges `DUE`-Datum gesetzt. Bereits vorhandene Fälligkeiten bleiben unverändert. Bei Änderungen des Titels wird eine bereits gesetzte Fälligkeit nicht automatisch neu berechnet.

## Prüfung und bekannte Einschränkungen

Der Quellcode wurde aus dem ZIP in die Repository-Wurzel übernommen. Eine PHP-Syntaxprüfung und ein Test gegen eine echte Nextcloud-/CalDAV-Instanz waren hier mangels PHP-Laufzeit bzw. Testinstanz nicht möglich. Die API-Verträglichkeit, korrekte Zeitzonen, Wiederholungsereignisse und die Datumsinterpretation müssen vor produktiver Nutzung überprüft werden.

Die ursprüngliche Metadaten-Lizenz lautet `agpl`, während das Repository eine GPL-3.0-Lizenzdatei enthält. Diese Inkonsistenz sollte mit dem Rechteinhaber geklärt werden.
