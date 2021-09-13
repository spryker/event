<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Event\Business\Dispatcher;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventCollectionTransfer;
use Generated\Shared\Transfer\EventTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Business\Dispatcher\EventDispatcher;
use Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface;
use Spryker\Zed\Event\Business\Logger\EventLoggerInterface;
use Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface;
use Spryker\Zed\Event\Dependency\EventCollection;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Event\Dependency\Service\EventToUtilEncodingInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Event
 * @group Business
 * @group Dispatcher
 * @group EventDispatcherTest
 * Add your own group annotations below this line
 */
class EventDispatcherTest extends Unit
{
    /**
     * @var string
     */
    public const TEST_EVENT_NAME = 'trigger.before.save';
    /**
     * @var string
     */
    public const LISTENER_NAME = 'Test/Listener';

    /**
     * @return void
     */
    public function testDispatchWhenSynchronousEventTriggeredShouldInvokeHandleWithInternalEventBrokerPlugin(): void
    {
        // Arrange
        $eventCollectionTransfer = $this->createEventCollectionTransfer();

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface $eventListenerMock */
        $eventListenerMock = $this->createEventListenerMock();
        $eventListenerMock
            ->expects($this->once())
            ->method('handle')
            ->with($eventCollectionTransfer->getEvents()[0]->getMessage());

        $eventCollection = $this->createEventCollection();
        $eventCollection->addListener(static::TEST_EVENT_NAME, $eventListenerMock);

        $eventDispatcher = $this->createEventDispatcher($eventCollection);

        // Act
        $eventDispatcher->dispatch($eventCollectionTransfer);
    }

    /**
     * @return void
     */
    public function testDispatchWhenAsynchronousEventTriggeredShouldWriteToQueueWithInternalEventBrokerPlugin(): void
    {
        // Arrange
        $eventCollectionTransfer = $this->createEventCollectionTransfer();

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface $eventListenerMock */
        $eventListenerMock = $this->createEventListenerMock();
        $eventListenerMock->expects($this->never())
            ->method('handle');

        $eventCollection = $this->createEventCollection();
        $eventCollection->addListenerQueued(static::TEST_EVENT_NAME, $eventListenerMock);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface $queueProducerMock */
        $queueProducerMock = $this->createQueueProducerMock();
        $queueProducerMock->expects($this->once())
            ->method('enqueueListener')
            ->with(static::TEST_EVENT_NAME, $eventCollectionTransfer->getEvents()[0]->getMessage());

        $eventDispatcher = $this->createEventDispatcher($eventCollection, $queueProducerMock);

        // Act
        $eventDispatcher->dispatch($eventCollectionTransfer);
    }

    /**
     * @return void
     */
    public function testDispatchWhenEventHandledShouldLogItWithInternalEventBrokerPlugin(): void
    {
        // Arrange
        $eventCollectionTransfer = $this->createEventCollectionTransfer();

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Logger\EventLoggerInterface $eventLoggerMock */
        $eventLoggerMock = $this->createEventLoggerMock();
        $eventLoggerMock->expects($this->once())
            ->method('log');

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface $eventListenerMock */
        $eventListenerMock = $this->createEventListenerMock();
        $eventListenerMock
            ->expects($this->once())
            ->method('handle')
            ->with($eventCollectionTransfer->getEvents()[0]->getMessage());

        $eventCollection = $this->createEventCollection();
        $eventCollection->addListener(static::TEST_EVENT_NAME, $eventListenerMock);

        $eventDispatcher = $this->createEventDispatcher($eventCollection, null, $eventLoggerMock);

        // Act
        $eventDispatcher->dispatch($eventCollectionTransfer);
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     * @param \Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface|null $queueProducerMock
     * @param \Spryker\Zed\Event\Business\Logger\EventLoggerInterface|null $eventLoggerMock
     *
     * @return \Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface
     */
    protected function createEventDispatcher(
        EventCollectionInterface $eventCollection,
        ?EventQueueProducerInterface $queueProducerMock = null,
        ?EventLoggerInterface $eventLoggerMock = null
    ): EventDispatcherInterface {
        if ($queueProducerMock === null) {
            $queueProducerMock = $this->createQueueProducerMock();
        }

        if ($eventLoggerMock === null) {
            $eventLoggerMock = $this->createEventLoggerMock();
        }

        $utilEncodingMock = $this->createUtilEncodingMock();

        return new EventDispatcher($eventCollection, $queueProducerMock, $eventLoggerMock, $utilEncodingMock);
    }

    /**
     * @return \Spryker\Zed\Event\Dependency\EventCollectionInterface
     */
    protected function createEventCollection(): EventCollectionInterface
    {
        return new EventCollection();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface
     */
    protected function createQueueProducerMock(): EventQueueProducerInterface
    {
        return $this->getMockBuilder(EventQueueProducerInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Logger\EventLoggerInterface
     */
    protected function createEventLoggerMock(): EventLoggerInterface
    {
        return $this->getMockBuilder(EventLoggerInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface
     */
    protected function createEventListenerMock(): EventHandlerInterface
    {
        return $this->getMockBuilder(EventHandlerInterface::class)
            ->getMock();
    }

    /**
     * @return \Spryker\Shared\Kernel\Transfer\TransferInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createTransferMock(): TransferInterface
    {
        return $this->getMockBuilder(TransferInterface::class)
            ->getMock();
    }

    /**
     * @return \Generated\Shared\Transfer\EventCollectionTransfer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createEventCollectionTransfer(): EventCollectionTransfer
    {
        $eventTransfer = new EventTransfer();
        $eventTransfer->setEventName(static::TEST_EVENT_NAME)
            ->setMessage($this->createTransferMock());

        $eventTransfers = new ArrayObject();
        $eventTransfers->append($eventTransfer);

        $eventCollectionTransfer = new EventCollectionTransfer();
        $eventCollectionTransfer->setEvents($eventTransfers);

        return $eventCollectionTransfer;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Service\EventToUtilEncodingInterface
     */
    protected function createUtilEncodingMock(): EventToUtilEncodingInterface
    {
        return $this->getMockBuilder(EventToUtilEncodingInterface::class)
            ->getMock();
    }
}
