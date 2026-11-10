<?php
declare(strict_types=1);

namespace OCA\TaskDate\Listener;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\TaskDate\DateParser;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;

/** @template-implements IEventListener<CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent> */
class TaskDateListener implements IEventListener {
    public function __construct(
        private CalDavBackend $backend,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void {
        if (!($event instanceof CalendarObjectCreatedEvent) && !($event instanceof CalendarObjectUpdatedEvent)) {
            return;
        }

        try {
            $calendar = $event->getCalendarData();
            $object = $event->getObjectData();

            $vobj = Reader::read($object['calendardata']);
            $todo = $vobj->VTODO ?? null;
            if ($todo === null || isset($todo->DUE) || !isset($todo->SUMMARY)) {
                return; // keine Aufgabe, schon Fälligkeit gesetzt, oder kein Titel
            }

            $parsed = DateParser::parse((string)$todo->SUMMARY, new \DateTimeImmutable('now'));
            if ($parsed === null) {
                return;
            }
            [$date, $hasTime] = $parsed;

            if ($hasTime) {
                $todo->add('DUE', $date->format('Ymd\THis'));
            } else {
                $todo->add('DUE', $date->format('Ymd'), ['VALUE' => 'DATE']);
            }

            $this->backend->updateCalendarObject(
                (int)$calendar['id'],
                $object['uri'],
                $vobj->serialize()
            );
        } catch (\Throwable $e) {
            $this->logger->warning('taskdate: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
