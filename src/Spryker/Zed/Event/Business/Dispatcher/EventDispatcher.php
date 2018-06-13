<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Event\Business\Dispatcher;

use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Business\Logger\EventLoggerInterface;
use Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Event\Dependency\Service\EventToUtilEncodingInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var \Spryker\Zed\Event\Dependency\EventCollectionInterface
     */
    protected $eventCollection;

    /**
     * @var \Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface
     */
    protected $eventQueueProducer;

    /**
     * @var \Spryker\Zed\Event\Business\Logger\EventLoggerInterface
     */
    protected $eventLogger;

    /**
     * @var \Spryker\Zed\Event\Dependency\Service\EventToUtilEncodingInterface
     */
    protected $utilEncodingService;

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     * @param \Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface $eventQueueProducer
     * @param \Spryker\Zed\Event\Business\Logger\EventLoggerInterface $eventLogger
     * @param \Spryker\Zed\Event\Dependency\Service\EventToUtilEncodingInterface $utilEncodingService
     */
    public function __construct(
        EventCollectionInterface $eventCollection,
        EventQueueProducerInterface $eventQueueProducer,
        EventLoggerInterface $eventLogger,
        EventToUtilEncodingInterface $utilEncodingService
    ) {
        $this->eventCollection = $eventCollection;
        $this->eventQueueProducer = $eventQueueProducer;
        $this->eventLogger = $eventLogger;
        $this->utilEncodingService = $utilEncodingService;
    }

    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $eventTransfer
     *
     * @return void
     */
    public function trigger(string $eventName, TransferInterface $eventTransfer): void
    {
        foreach ($this->extractEventListeners($eventName) as $eventListener) {
            if ($eventListener->isHandledInQueue()) {
                $this->eventQueueProducer->enqueueListener($eventName, $eventTransfer, $eventListener->getListenerName(), $eventListener->getQueuePoolName());
            } elseif ($eventListener instanceof EventHandlerInterface) {
                $eventListener->handle($eventTransfer, $eventName);
            }
            $this->logEventHandle($eventName, $eventTransfer, $eventListener);
        }
    }

    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface[] $eventTransfers
     *
     * @return void
     */
    public function triggerBulk(string $eventName, array $eventTransfers): void
    {
        foreach ($this->extractEventListeners($eventName) as $eventListener) {
            if ($eventListener->isHandledInQueue()) {
                $this->eventQueueProducer->enqueueListenerBulk($eventName, $eventTransfers, $eventListener->getListenerName(), $eventListener->getQueuePoolName());
                $this->logEventHandleBulk($eventName, $eventTransfers, $eventListener);
            } elseif ($eventListener instanceof EventHandlerInterface) {
                $this->handleEventListeners($eventName, $eventTransfers, $eventListener);
            }
        }
    }

    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface[] $eventTransfers
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return void
     */
    protected function handleEventListeners(string $eventName, array $eventTransfers, EventListenerContextInterface $eventListener): void
    {
        foreach ($eventTransfers as $eventTransfer) {
            $eventListener->handle($eventTransfer, $eventName);
            $this->logEventHandle($eventName, $eventTransfer, $eventListener);
        }
    }

    /**
     * @param string $eventName
     *
     * @return \SplPriorityQueue|\Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface[]
     */
    protected function extractEventListeners($eventName)
    {
        if (!$this->eventCollection->has($eventName)) {
            return [];
        }

        return $this->eventCollection->get($eventName);
    }

    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface[] $eventTransfers
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return void
     */
    protected function logEventHandleBulk(
        string $eventName,
        array $eventTransfers,
        EventListenerContextInterface $eventListener
    ): void {
        foreach ($eventTransfers as $eventTransfer) {
            $this->logEventHandle($eventName, $eventTransfer, $eventListener);
        }
    }

    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $eventTransfer
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return void
     */
    protected function logEventHandle(
        $eventName,
        TransferInterface $eventTransfer,
        EventListenerContextInterface $eventListener
    ): void {
        $this->eventLogger->log(
            sprintf(
                $this->createHandleMessage($eventListener),
                $eventName,
                $eventListener->getListenerName(),
                get_class($eventTransfer),
                $this->utilEncodingService->encodeJson($eventTransfer->toArray())
            )
        );
    }

    /**
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return string
     */
    protected function createHandleMessage(EventListenerContextInterface $eventListener): string
    {
        if ($eventListener->isHandledInQueue()) {
            return '[async] "%s" listener "%s", sent to the queue, event data: "%s" => "%s".';
        }

        return '[sync] "%s" handled by "%s", event data: "%s" => "%s".';
    }
}
