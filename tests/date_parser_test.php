<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/DateParser.php';

use OCA\TaskDate\DateParser;

$now = new DateTimeImmutable('2026-11-10 09:00:00', new DateTimeZone('Europe/Berlin'));
$cases = [
    'Zahnarzt 20261112 1400' => ['2026-11-12 14:00', true],
    'Zahnarzt 20261112 1430' => ['2026-11-12 14:30', true],
    'Zahnarzt 12.11.2026 14:30' => ['2026-11-12 14:30', true],
    'Zahnarzt 12.11.26 14.30 Uhr' => ['2026-11-12 14:30', true],
    'Zahnarzt 2026-11-12' => ['2026-11-12 00:00', false],
    'Zahnarzt 12.11.' => ['2026-11-12 00:00', false],
    'Bericht morgen 14 Uhr' => ['2026-11-11 14:00', true],
    'Steuer Freitag' => ['2026-11-13 00:00', false],
    'Anruf heute' => ['2026-11-10 00:00', false],
    'Unbekannt 20261340' => null,
    'Nur ein Titel' => null,
];

foreach ($cases as $title => $expected) {
    $actual = DateParser::parse($title, $now);
    if ($expected === null) {
        if ($actual !== null) {
            throw new RuntimeException("Unexpected date for: $title");
        }
        continue;
    }
    if ($actual === null || $actual[0]->format('Y-m-d H:i') !== $expected[0] || $actual[1] !== $expected[1]) {
        throw new RuntimeException("Wrong result for: $title; got " . var_export($actual, true));
    }
}

echo 'DateParser: ' . count($cases) . " cases passed\n";
