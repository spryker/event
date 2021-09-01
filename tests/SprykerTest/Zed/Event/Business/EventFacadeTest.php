<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Event\Business;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventCollectionTransfer;
use Generated\Shared\Transfer\EventQueueSendMessageBodyTransfer;
use Generated\Shared\Transfer\EventTransfer;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use ReflectionProperty;
use Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface;
use Spryker\Zed\Event\Business\EventBusinessFactory;
use Spryker\Zed\Event\Business\EventFacade;
use Spryker\Zed\Event\Business\Router\EventRouter;
use Spryker\Zed\Event\Business\Router\EventRouterInterface;
use Spryker\Zed\Event\Business\Subscriber\SubscriberMerger;
use Spryker\Zed\Event\Dependency\EventCollection;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\EventSubscriberCollection;
use Spryker\Zed\Event\Dependency\EventSubscriberCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventBaseHandlerInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventSubscriberInterface;
use Spryker\Zed\Event\EventDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerTest\Zed\Event\Stub\TestEventBulkListenerPluginStub;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Event
 * @group Business
 * @group Facade
 * @group EventFacadeTest
 * Add your own group annotations below this line
 */
class EventFacadeTest extends Unit
{
    public const TEST_EVENT_NAME = 'test.event';

    /**
     * @var \SprykerTest\Zed\Event\EventBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    public function testDispatchWhenEventProvidedWithSubscriberShouldHandleListener(): void
    {
        // Arrange
        $reflection = new ReflectionProperty(SubscriberMerger::class, 'eventCollectionBuffer');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);

        $eventFacade = $this->createEventFacade();
        $eventCollectionTransfer = $this->createEventCollectionTransfer();

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface $eventListenerMock */
        $eventListenerMock = $this->createEventListenerMock();
        $eventListenerMock->expects($this->once())
            ->method('handle')
            ->with($eventCollectionTransfer->getEvents()[0]->getMessage());

        $eventCollection = $this->createEventListenerCollection();
        $eventCollection->addListener(static::TEST_EVENT_NAME, $eventListenerMock);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventSubscriberInterface $eventSubscriberMock */
        $eventSubscriberMock = $this->createEventSubscriberMock();
        $eventSubscriberMock->method('getSubscribedEvents')
            ->willReturn($eventCollection);

        $eventSubscriberCollection = $this->createEventSubscriberCollection();
        $eventSubscriberCollection->add($eventSubscriberMock);

        $eventBusinessFactory = $this->createEventBusinessFactory($eventSubscriberCollection);

        $eventFacade->setFactory($eventBusinessFactory);

        // Act
        $eventFacade->dispatch($eventCollectionTransfer);
    }

    /**
     * @return void
     */
    public function testTriggerShouldPutEventsIntoEventBrokerPlugin(): void
    {
        // Arrange
        $objectTransferMock = $this->createTransferObjectMock();
        $testClass = $this;
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface $eventBrokerPluginMock */
        $eventBrokerPluginMock = $this->createEventBrokerPluginMock();
        $eventBrokerPluginMock->method('putEvents')
            ->willReturnCallback(function (EventCollectionTransfer $eventCollectionTransfer) use ($testClass, $objectTransferMock): void {
                $testClass->assertSame($objectTransferMock, $eventCollectionTransfer->getEvents()[0]->getMessage());
            });
        $eventBrokerPluginMock->method('isApplicable')
            ->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface $eventDispatcherMock */
        $eventDispatcherMock = $this->createEventDispatcherMock();
        $eventDispatcherMock->expects($this->once())
            ->method('dispatch');

        $eventRouter = new EventRouter($eventDispatcherMock, $this->tester->getModuleConfig(), [$eventBrokerPluginMock]);

        $eventBusinessFactory = $this->createEventBusinessFactoryMock($eventRouter);

        $eventFacade = $this->createEventFacade();
        $eventFacade->setFactory($eventBusinessFactory);

        // Act
        $eventFacade->trigger(static::TEST_EVENT_NAME, $objectTransferMock);
    }

    /**
     * @return void
     */
    public function testTriggerShouldPutEventsIntoEventBrokerPluginButNotInInternal(): void
    {
        // Arrange
        $objectTransferMock = $this->createTransferObjectMock();
        $testClass = $this;
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface $eventBrokerPluginMock */
        $eventBrokerPluginMock = $this->createEventBrokerPluginMock();
        $eventBrokerPluginMock->method('putEvents')
            ->willReturnCallback(function (EventCollectionTransfer $eventCollectionTransfer) use ($testClass, $objectTransferMock): void {
                $testClass->assertSame($objectTransferMock, $eventCollectionTransfer->getEvents()[0]->getMessage());
            });
        $eventBrokerPluginMock->method('isApplicable')
            ->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface $eventDispatcherMock */
        $eventDispatcherMock = $this->createEventDispatcherMock();
        $eventDispatcherMock->expects($this->any())
            ->method('dispatch');

        $eventRouter = new EventRouter($eventDispatcherMock, $this->tester->getModuleConfig(), [$eventBrokerPluginMock]);

        $eventBusinessFactory = $this->createEventBusinessFactoryMock($eventRouter);

        $eventFacade = $this->createEventFacade();
        $eventFacade->setFactory($eventBusinessFactory);

        // Act
        $eventFacade->trigger(static::TEST_EVENT_NAME, $objectTransferMock, 'eventBusName');
    }

    /**
     * @return void
     */
    public function testTriggerShouldNotPutEventsIntoEventBrokerPlugin(): void
    {
        // Arrange
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface $eventBrokerPluginMock */
        $eventBrokerPluginMock = $this->createEventBrokerPluginMock();
        $eventBrokerPluginMock->expects($this->never())
            ->method('putEvents');
        $eventBrokerPluginMock->method('isApplicable')
            ->willReturn(false);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface $eventDispatcherMock */
        $eventDispatcherMock = $this->createEventDispatcherMock();
        $eventDispatcherMock->expects($this->once())
            ->method('dispatch');

        $eventRouter = new EventRouter($eventDispatcherMock, $this->tester->getModuleConfig(), [$eventBrokerPluginMock]);

        $eventBusinessFactory = $this->createEventBusinessFactoryMock($eventRouter);

        $eventFacade = $this->createEventFacade();
        $eventFacade->setFactory($eventBusinessFactory);

        // Act
        $eventFacade->trigger(static::TEST_EVENT_NAME, $this->createTransferObjectMock());
    }

    /**
     * @return void
     */
    public function testTriggerBulkShouldPutEventsIntoOneEventBrokerPlugin(): void
    {
        // Arrange
        $testClass = $this;
        $objectTransferMocks = [
            $this->createTransferObjectMock(),
            $this->createTransferObjectMock(),
        ];

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface $eventBrokerPluginMockNotApplicable */
        $eventBrokerPluginMockNotApplicable = $this->createEventBrokerPluginMock();
        $eventBrokerPluginMockNotApplicable->expects($this->never())
            ->method('putEvents');
        $eventBrokerPluginMockNotApplicable->method('isApplicable')
            ->willReturn(false);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface $eventBrokerPluginMockApplicable */
        $eventBrokerPluginMockApplicable = $this->createEventBrokerPluginMock();
        $eventBrokerPluginMockApplicable->expects($this->once())
            ->method('putEvents')
            ->willReturnCallback(function (EventCollectionTransfer $eventCollectionTransfer) use ($testClass, $objectTransferMocks): void {
                $testClass->assertSame($objectTransferMocks[0], $eventCollectionTransfer->getEvents()[0]->getMessage());
                $testClass->assertSame($objectTransferMocks[1], $eventCollectionTransfer->getEvents()[1]->getMessage());
            });
        $eventBrokerPluginMockApplicable->method('isApplicable')
            ->willReturn(true);
        $eventBrokerPluginMocks = [
            $eventBrokerPluginMockNotApplicable,
            $eventBrokerPluginMockApplicable,
        ];

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface $eventDispatcherMock */
        $eventDispatcherMock = $this->createEventDispatcherMock();
        $eventDispatcherMock->expects($this->exactly(1))
            ->method('dispatch');

        $eventRouter = new EventRouter($eventDispatcherMock, $this->tester->getModuleConfig(), $eventBrokerPluginMocks);

        $eventBusinessFactory = $this->createEventBusinessFactoryMock($eventRouter);

        $eventFacade = $this->createEventFacade();
        $eventFacade->setFactory($eventBusinessFactory);

        // Act
        $eventFacade->triggerBulk(static::TEST_EVENT_NAME, $objectTransferMocks);
    }

    /**
     * @return void
     */
    public function testProcessEnqueuedMessagesShouldHandleProvidedEvents(): void
    {
        // Arrange
        $eventFacade = $this->createEventFacade();
        $transferObject = $this->createTransferObjectMock();

        $eventCollection = $this->createEventListenerCollection();
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface $eventListenerMock */
        $eventListenerMock = $this->createEventListenerMock();

        $eventCollection->addListenerQueued(static::TEST_EVENT_NAME, $eventListenerMock);

        $queueReceivedMessageTransfer = $this->createQueueReceiveMessageTransfer($eventListenerMock, $transferObject);

        $messages = [
            $queueReceivedMessageTransfer,
        ];

        // Act
        $processedMessages = $eventFacade->processEnqueuedMessages($messages);

        // Assert
        $processedQueueReceivedMessageTransfer = $processedMessages[0];

        $this->assertTrue($processedQueueReceivedMessageTransfer->getAcknowledge());
    }

    /**
     * @return void
     */
    public function testProcessEnqueuedMessagesShouldMarkAsFailedWhenDataIsMissing(): void
    {
        // Arrange
        $eventFacade = $this->createEventFacade();

        $eventCollection = $this->createEventListenerCollection();
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface $eventListenerMock */
        $eventListenerMock = $this->createEventListenerMock();

        $eventCollection->addListenerQueued(static::TEST_EVENT_NAME, $eventListenerMock);

        $queueReceivedMessageTransfer = $this->createQueueReceiveMessageTransfer();

        $messages = [
            $queueReceivedMessageTransfer,
        ];

        // Act
        $processedMessages = $eventFacade->processEnqueuedMessages($messages);

        // Assert
        $processedQueueReceivedMessageTransfer = $processedMessages[0];

        $this->assertFalse($processedQueueReceivedMessageTransfer->getAcknowledge());
        $this->assertTrue($processedQueueReceivedMessageTransfer->getReject());
        $this->assertTrue($processedQueueReceivedMessageTransfer->getHasError());
    }

    /**
     * @return void
     */
    public function testProcessEnqueuedMessageWillSendOnlyErroredMessageFromBulkToRetry(): void
    {
        //Arrange
        $eventCollection = $this->createEventListenerCollection();
        $eventBulkListenerStub = new TestEventBulkListenerPluginStub();
        $eventCollection->addListenerQueued(static::TEST_EVENT_NAME, $eventBulkListenerStub);
        $messages = [
            $this->createQueueReceiveMessageTransfer($eventBulkListenerStub, $this->createTransferObjectMock()),
            $this->createQueueReceiveMessageTransfer($eventBulkListenerStub, $this->createTransferObjectMock()),
        ];

        //Act
        $processedMessages = $this->createEventFacade()->processEnqueuedMessages($messages);

        //Assert
        $this->assertTrue($processedMessages[0]->getAcknowledge());
        $this->assertSame('retry', $processedMessages[0]->getRoutingKey());
        $this->assertTrue($processedMessages[1]->getAcknowledge());
        $this->assertNull($processedMessages[1]->getRoutingKey());
    }

    /**
     * @return \Generated\Shared\Transfer\EventCollectionTransfer
     */
    protected function createEventCollectionTransfer(): EventCollectionTransfer
    {
        $eventTransfer = new EventTransfer();
        $eventTransfer->setEventName(static::TEST_EVENT_NAME)
            ->setMessage($this->createTransferObjectMock());

        $eventTransfers = new ArrayObject();
        $eventTransfers->append($eventTransfer);

        $eventCollectionTransfer = new EventCollectionTransfer();
        $eventCollectionTransfer->setEvents($eventTransfers);

        return $eventCollectionTransfer;
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface
     */
    protected function createEventBulkListenerMock(): EventBulkHandlerInterface
    {
        return $this->getMockBuilder(EventBulkHandlerInterface::class)
            ->getMock();
    }

    /**
     * @return \Spryker\Zed\Event\Dependency\EventCollectionInterface
     */
    protected function createEventListenerCollection(): EventCollectionInterface
    {
        return new EventCollection();
    }

    /**
     * @return \Spryker\Zed\Event\Business\EventFacade
     */
    protected function createEventFacade(): EventFacade
    {
        return new EventFacade();
    }

    /**
     * @return \Spryker\Zed\Event\Dependency\EventSubscriberCollectionInterface
     */
    protected function createEventSubscriberCollection(): EventSubscriberCollectionInterface
    {
        return new EventSubscriberCollection();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\Kernel\Transfer\TransferInterface
     */
    protected function createTransferObjectMock(): TransferInterface
    {
        return $this->getMockBuilder(TransferInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\EventExtension\Dependency\Plugin\EventBrokerPluginInterface
     */
    protected function createEventBrokerPluginMock(): EventBrokerPluginInterface
    {
        return $this->getMockBuilder(EventBrokerPluginInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface
     */
    protected function createEventDispatcherMock(): EventDispatcherInterface
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Event\Dependency\Plugin\EventSubscriberInterface
     */
    protected function createEventSubscriberMock(): EventSubscriberInterface
    {
        return $this->getMockBuilder(EventSubscriberInterface::class)
            ->getMock();
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventSubscriberCollectionInterface $eventSubscriberCollection
     *
     * @return \Spryker\Zed\Event\Business\EventBusinessFactory
     */
    protected function createEventBusinessFactory(EventSubscriberCollectionInterface $eventSubscriberCollection): EventBusinessFactory
    {
        $container = new Container();

        $eventDependencyProvider = new EventDependencyProvider();
        $businessLayerDependencies = $eventDependencyProvider->provideBusinessLayerDependencies($container);

        $container[EventDependencyProvider::EVENT_SUBSCRIBERS] = function () use ($eventSubscriberCollection) {
            return $eventSubscriberCollection;
        };

        $eventBusinessFactory = new EventBusinessFactory();
        $eventBusinessFactory->setContainer($businessLayerDependencies);

        return $eventBusinessFactory;
    }

    /**
     * @param \Spryker\Zed\Event\Business\Router\EventRouterInterface $eventRouter
     *
     * @return \Spryker\Zed\Event\Business\EventBusinessFactory
     */
    protected function createEventBusinessFactoryMock(EventRouterInterface $eventRouter): EventBusinessFactory
    {
        /** @var \Spryker\Zed\Event\Business\EventBusinessFactory|\PHPUnit\Framework\MockObject\MockObject $eventBusinessFactoryMock */
        $eventBusinessFactoryMock = $this->getMockBuilder(EventBusinessFactory::class)
            ->getMock();

        $eventBusinessFactoryMock->method('createEventRouter')
            ->willReturn($eventRouter);

        return $eventBusinessFactoryMock;
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\Plugin\EventBaseHandlerInterface|null $eventListenerMock
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface|null $transferObject
     *
     * @return \Generated\Shared\Transfer\QueueReceiveMessageTransfer
     */
    protected function createQueueReceiveMessageTransfer(
        ?EventBaseHandlerInterface $eventListenerMock = null,
        ?TransferInterface $transferObject = null
    ): QueueReceiveMessageTransfer {
        $message = [
            EventQueueSendMessageBodyTransfer::LISTENER_CLASS_NAME => ($eventListenerMock) ? get_class($eventListenerMock) : null,
            EventQueueSendMessageBodyTransfer::TRANSFER_CLASS_NAME => ($transferObject) ? get_class($transferObject) : null,
            EventQueueSendMessageBodyTransfer::TRANSFER_DATA => ['1', '2', '3'],
            EventQueueSendMessageBodyTransfer::EVENT_NAME => static::TEST_EVENT_NAME,
        ];

        $queueMessageTransfer = new QueueSendMessageTransfer();
        $queueMessageTransfer->setBody(json_encode($message));

        $queueReceivedMessageTransfer = new QueueReceiveMessageTransfer();
        $queueReceivedMessageTransfer->setQueueMessage($queueMessageTransfer);

        return $queueReceivedMessageTransfer;
    }
}
