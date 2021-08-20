<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Event\Stub;

use Generated\Shared\Transfer\EventCollectionTransfer;
use Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface;

class InMemoryEventBrokerPlugin implements EventBrokerPluginInterface
{
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @param \Generated\Shared\Transfer\EventCollectionTransfer $eventCollectionTransfer
     *
     * @return void
     */
    public function putEvents(EventCollectionTransfer $eventCollectionTransfer): void
    {
        if (!isset($this->events[$eventCollectionTransfer->getEventBusName()])) {
            $this->events[$eventCollectionTransfer->getEventBusName()] = [];
        }

        foreach ($eventCollectionTransfer->getEvents() as $eventTransfer) {
            $this->events[$eventCollectionTransfer->getEventBusName()][] = $eventTransfer;
        }
    }

    /**
     * @param string $eventBusName
     *
     * @return bool
     */
    public function isApplicable(string $eventBusName): bool
    {
        return true;
    }

    /**
     * @param string $eventBus
     * @param string $eventName
     *
     * @return \Generated\Shared\Transfer\EventTransfer[]
     */
    public function getEventsForEventBusByEventName(string $eventBus, string $eventName): array
    {
        $events = [];

        if (!isset($this->events[$eventBus])) {
            return $events;
        }

        foreach ($this->events[$eventBus] as $eventTransfer) {
            if ($eventTransfer->getEventName() === $eventName) {
                $events[] = $eventTransfer;
            }
        }

        return $events;
    }
}
