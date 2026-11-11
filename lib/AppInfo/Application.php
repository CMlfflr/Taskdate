<?php
declare(strict_types=1);

namespace OCA\TaskDate\AppInfo;

use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCA\TaskDate\Listener\TaskDateListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'taskdate';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(CalendarObjectCreatedEvent::class, TaskDateListener::class);
        $context->registerEventListener(CalendarObjectUpdatedEvent::class, TaskDateListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}
