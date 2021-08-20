<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Event\Helper;

use Codeception\TestInterface;
use PHPUnit\Framework\Assert;
use Spryker\Zed\Event\EventDependencyProvider;
use SprykerTest\Shared\Testify\Helper\AbstractHelper;
use SprykerTest\Zed\Event\Stub\InMemoryEventBrokerPlugin;
use SprykerTest\Zed\Testify\Helper\Business\DependencyProviderHelperTrait;

class InMemoryEventBrokerHelper extends AbstractHelper
{
    use DependencyProviderHelperTrait;

    /**
     * @var \SprykerTest\Zed\Event\Stub\InMemoryEventBrokerPlugin
     */
    protected $inMemoryEventBrokerPlugin;

    /**
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _before(TestInterface $test): void
    {
        $this->inMemoryEventBrokerPlugin = new InMemoryEventBrokerPlugin();

        $this->getDependencyProviderHelper()->setDependency(EventDependencyProvider::EVENT_BROKER_PLUGINS, [$this->inMemoryEventBrokerPlugin]);
    }

    /**
     * @param string $eventBus
     * @param string $eventName
     *
     * @return void
     */
    public function assertEventBusHasEventsByEventName(string $eventBus, string $eventName): void
    {
        $events = $this->inMemoryEventBrokerPlugin->getEventsForEventBusByEventName($eventBus, $eventName);

        Assert::assertGreaterThan(0, count($events));
    }
}
