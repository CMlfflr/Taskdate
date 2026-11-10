<?php
declare(strict_types=1);

namespace OCA\TaskDate;

use DateTimeImmutable;

/**
 * Erkennt Datumsangaben in Titeln.
 * Unterstützt: 12.11.2026, 12.11.26, 12.11., 2026-11-12,
 * heute, morgen, übermorgen, (nächsten) Montag … Sonntag,
 * Kompakt: 20261101
 * Uhrzeit: 14:30, 14.30, 14 Uhr, 14.30 Uhr, 1400
 */
class DateParser {
    private const WEEKDAYS = [
        'montag' => 'monday', 'dienstag' => 'tuesday', 'mittwoch' => 'wednesday',
        'donnerstag' => 'thursday', 'freitag' => 'friday',
        'samstag' => 'saturday', 'sonnabend' => 'saturday', 'sonntag' => 'sunday',
    ];

    /** @return array{0: DateTimeImmutable, 1: bool}|null  [Datum, hatUhrzeit] */
    public static function parse(string $text, DateTimeImmutable $now): ?array {
        $today = $now->setTime(0, 0);
        $date = null;
        $rest = $text; // Text ohne erkanntes Datum, für die Uhrzeitsuche

        if (preg_match('/(?<!\d)(\d{4})-(\d{2})-(\d{2})(?!\d)/', $text, $m)
            || preg_match('/(?<!\d)(20\d{2})(\d{2})(\d{2})(?!\d)/', $text, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                $date = $today->setDate((int)$m[1], (int)$m[2], (int)$m[3]);
                $rest = str_replace($m[0], ' ', $text);
            }
        }

        if ($date === null && preg_match('/(?<!\d)(\d{1,2})\.\s?(\d{1,2})\.(?:(\d{4}|\d{2})(?!\d))?/u', $text, $m)) {
            $d = (int)$m[1];
            $mo = (int)$m[2];
            $explicitYear = isset($m[3]) && $m[3] !== '';
            $y = $explicitYear ? (int)$m[3] : (int)$today->format('Y');
            if ($explicitYear && $y < 100) {
                $y += 2000;
            }
            if (checkdate($mo, $d, $y)) {
                $date = $today->setDate($y, $mo, $d);
                $rest = str_replace($m[0], ' ', $text);
                // Ohne Jahr und schon vorbei -> nächstes Jahr
                if (!$explicitYear && $date < $today && checkdate($mo, $d, $y + 1)) {
                    $date = $date->setDate($y + 1, $mo, $d);
                }
            }
        }

        if ($date === null) {
            $lower = mb_strtolower($text);
            if (preg_match('/\b(übermorgen|uebermorgen)\b/u', $lower)) {
                $date = $today->modify('+2 days');
            } elseif (preg_match('/\bmorgen\b/u', $lower)) {
                $date = $today->modify('+1 day');
            } elseif (preg_match('/\bheute\b/u', $lower)) {
                $date = $today;
            } else {
                foreach (self::WEEKDAYS as $de => $en) {
                    if (preg_match('/\b' . $de . '\b/u', $lower)) {
                        $date = $today->modify('next ' . $en);
                        break;
                    }
                }
            }
        }

        if ($date === null) {
            return null;
        }

        $hasTime = false;
        if (preg_match('/(?<![\d.])(\d{1,2})[.:](\d{2})(?![\d.])/', $rest, $t)
            || preg_match('/(?<!\d)(\d{1,2})(?:[.:](\d{2}))?\s*Uhr\b/iu', $rest, $t)
            // 4-stellig (z. B. 1400); nur volle/viertel Stunden, damit Jahre wie 2026 nicht passen
            || preg_match('/(?<![\d.:])([01]\d|2[0-3])(00|15|30|45)(?![\d.:])/', $rest, $t)) {
            $h = (int)$t[1];
            $min = isset($t[2]) && $t[2] !== '' ? (int)$t[2] : 0;
            if ($h < 24 && $min < 60) {
                $date = $date->setTime($h, $min);
                $hasTime = true;
            }
        }

        return [$date, $hasTime];
    }
}
