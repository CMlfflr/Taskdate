<?php
declare(strict_types=1);

namespace OCA\TaskDate\Listener;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCA\TaskDate\DateParser;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;

/** @template-implements IEventListener<CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent> */
class TaskDateListener implements IEventListener {
    private const AUTO_DUE_PROPERTY = 'X-TASKDATE-AUTO-DUE';

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
            if ($todo === null || !isset($todo->SUMMARY)) {
                return;
            }

            $managed = isset($todo->{self::AUTO_DUE_PROPERTY});
            $oldAutoDue = $managed ? (string)$todo->{self::AUTO_DUE_PROPERTY} : null;
            $currentDue = isset($todo->DUE) ? (string)$todo->DUE : null;
            // Never replace a due date that was set or changed manually.
            if ($currentDue !== null && (!$managed || $currentDue !== $oldAutoDue)) {
                return;
            }

            $parsed = DateParser::parse((string)$todo->SUMMARY, new \DateTimeImmutable('now'));
            if ($parsed === null) {
                // Remove only dates previously created by TaskDate.
                if ($managed && $currentDue !== null) {
                    unset($todo->DUE);
                    unset($todo->{self::AUTO_DUE_PROPERTY});
                    $this->backend->updateCalendarObject((int)$calendar['id'], $object['uri'], $vobj->serialize());
                }
                return;
            }
            [$date, $hasTime] = $parsed;

            $due = $hasTime ? $date->format('Ymd\THis') : $date->format('Ymd');
            if ($currentDue === $due && $managed) {
                return; // also prevents the update event from writing again
            }

            if (isset($todo->DUE)) {
                unset($todo->DUE);
            }

            if ($hasTime) {
                $todo->add('DUE', $due);
            } else {
                $todo->add('DUE', $due, ['VALUE' => 'DATE']);
            }
            if (isset($todo->{self::AUTO_DUE_PROPERTY})) {
                unset($todo->{self::AUTO_DUE_PROPERTY});
            }
            $todo->add(self::AUTO_DUE_PROPERTY, $due);

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
