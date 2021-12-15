<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Event\Business\Dispatcher;

use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Event\Business\Exception\EventListenerAmbiguousException;
use Spryker\Zed\Event\Business\Exception\EventListenerInvalidException;
use Spryker\Zed\Event\Business\Exception\EventListenerNotFoundException;
use Spryker\Zed\Event\Business\Logger\EventLoggerInterface;
use Spryker\Zed\Event\Business\Queue\Producer\EventQueueProducerInterface;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface;
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
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $transfer
     *
     * @return void
     */
    public function trigger(string $eventName, TransferInterface $transfer): void
    {
        $eventListeners = $this->extractEventListeners($eventName);

        if (count($eventListeners) === 0) {
            return;
        }

        foreach (clone $eventListeners as $eventListener) {
            if ($eventListener->isHandledInQueue()) {
                $this->eventQueueProducer->enqueueListener(
                    $eventName,
                    $transfer,
                    $eventListener->getListenerName(),
                    $eventListener->getQueuePoolName(),
                    $eventListener->getEventQueueName(),
                );
            } else {
                $this->eventProducer($eventName, $transfer, $eventListener);
            }
            $this->logEventHandle($eventName, $transfer, $eventListener);
        }
    }

    /**
     * @param string $eventName
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfers
     *
     * @return void
     */
    public function triggerBulk(string $eventName, array $transfers): void
    {
        $eventListeners = $this->extractEventListeners($eventName);

        if (count($eventListeners) === 0) {
            return;
        }

        foreach (clone $eventListeners as $eventListener) {
            if ($eventListener->isHandledInQueue()) {
                $this->eventQueueProducer->enqueueListenerBulk(
                    $eventName,
                    $transfers,
                    $eventListener->getListenerName(),
                    $eventListener->getQueuePoolName(),
                    $eventListener->getEventQueueName(),
                );
            } else {
                $this->eventBulkProducer($eventName, $transfers, $eventListener);
            }

            $this->logEventHandleBulk($eventName, $transfers, $eventListener);
        }
    }

    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $transfer
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @throws \Spryker\Zed\Event\Business\Exception\EventListenerAmbiguousException
     * @throws \Spryker\Zed\Event\Business\Exception\EventListenerInvalidException
     *
     * @return void
     */
    protected function eventProducer(string $eventName, TransferInterface $transfer, EventListenerContextInterface $eventListener): void
    {
        if (is_subclass_of($eventListener->getListenerName(), EventHandlerInterface::class)) {
            $eventListener->handle($transfer, $eventName);
        } elseif (is_subclass_of($eventListener->getListenerName(), EventBulkHandlerInterface::class)) {
            throw new EventListenerAmbiguousException(sprintf('`%s` is using `%s` , you need to use `%s::triggerBulk()` to trigger the events.', $eventListener->getListenerName(), EventBulkHandlerInterface::class, EventFacadeInterface::class));
        } else {
            throw new EventListenerInvalidException(sprintf('`%s` is using invalid interface, use `%s` to fix this.', $eventListener->getListenerName(), EventHandlerInterface::class));
        }
    }

    /**
     * @param string $eventName
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfers
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @throws \Spryker\Zed\Event\Business\Exception\EventListenerInvalidException
     *
     * @return void
     */
    protected function eventBulkProducer(string $eventName, array $transfers, EventListenerContextInterface $eventListener): void
    {
        if (is_subclass_of($eventListener->getListenerName(), EventBulkHandlerInterface::class)) {
            $eventListener->handleBulk($transfers, $eventName);
        } elseif (is_subclass_of($eventListener->getListenerName(), EventHandlerInterface::class)) {
            $this->handleEventListeners($eventName, $transfers, $eventListener);
        } else {
            throw new EventListenerInvalidException(sprintf('`%s` is using invalid interface, use `%s` to fix this.', $eventListener->getListenerName(), EventBulkHandlerInterface::class));
        }
    }

    /**
     * @param string $listenerName
     * @param string $eventName
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfers
     *
     * @throws \Spryker\Zed\Event\Business\Exception\EventListenerNotFoundException
     *
     * @return void
     */
    public function triggerByListenerName(string $listenerName, string $eventName, array $transfers): void
    {
        $listenerContext = $this->findEventListenerContext($listenerName);

        if (is_subclass_of($listenerContext->getListenerName(), EventHandlerInterface::class)) {
            foreach ($transfers as $transfer) {
                $listenerContext->handle($transfer, $eventName);
            }

            return;
        }

        if (is_subclass_of($listenerContext->getListenerName(), EventBulkHandlerInterface::class)) {
            $listenerContext->handleBulk($transfers, $eventName);

            return;
        }

        throw new EventListenerNotFoundException(sprintf('%s is not a listener or class doesn\'t exist', $listenerName));
    }

    /**
     * @param string $listenerName
     *
     * @throws \Spryker\Zed\Event\Business\Exception\EventListenerAmbiguousException
     * @throws \Spryker\Zed\Event\Business\Exception\EventListenerNotFoundException
     *
     * @return \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface
     */
    protected function findEventListenerContext(string $listenerName): EventListenerContextInterface
    {
        if ($this->isFullyQualifiedName($listenerName)) {
            /** @var \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $listener */
            $listener = new $listenerName();

            return $listener;
        }

        $foundListeners = $this->findListenersByShortName($listenerName);

        if (count($foundListeners) === 0) {
            throw new EventListenerNotFoundException(sprintf(
                'Please use Qualified name or Fully qualified name. There is no listener like %s.',
                $listenerName,
            ));
        }

        if (count($foundListeners) > 1) {
            throw new EventListenerAmbiguousException(sprintf(
                "Please use Qualified name or Fully qualified name. Found listeners: \n%s",
                implode(PHP_EOL, array_keys($foundListeners)),
            ));
        }

        return reset($foundListeners);
    }

    /**
     * @param string $desiredListenerName
     *
     * @return array<\Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface>
     */
    protected function findListenersByShortName(string $desiredListenerName): array
    {
        $foundEventListeners = [];

        foreach ($this->eventCollection as $eventName => $eventListeners) {
            foreach ($eventListeners as $eventListener) {
                /** @var \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener */
                $extractedListenerName = $this->extractListenerNameFromFullyQualifiedName($eventListener, $desiredListenerName);

                if ($extractedListenerName === $desiredListenerName) {
                    $foundEventListeners[$eventListener->getListenerName()] = $eventListener;
                }
            }
        }

        return $foundEventListeners;
    }

    /**
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     * @param string $desiredListenerName
     *
     * @return string
     */
    protected function extractListenerNameFromFullyQualifiedName(EventListenerContextInterface $eventListener, string $desiredListenerName): string
    {
        $compareSymbolsFromEnd = -(strlen($desiredListenerName));

        return substr($eventListener->getListenerName(), $compareSymbolsFromEnd);
    }

    /**
     * @param string $listenerName
     *
     * @return bool
     */
    protected function isFullyQualifiedName(string $listenerName): bool
    {
        return strpos($listenerName, '\\') === 0;
    }

    /**
     * @param string $eventName
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfers
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return void
     */
    protected function handleEventListeners(string $eventName, array $transfers, EventListenerContextInterface $eventListener): void
    {
        foreach ($transfers as $transfer) {
            $eventListener->handle($transfer, $eventName);
            $this->logEventHandle($eventName, $transfer, $eventListener);
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
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfers
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return void
     */
    protected function logEventHandleBulk(
        string $eventName,
        array $transfers,
        EventListenerContextInterface $eventListener
    ): void {
        foreach ($transfers as $transfer) {
            $this->logEventHandle($eventName, $transfer, $eventListener);
        }
    }

    /**
     * @param string $eventName
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfer
     * @param \Spryker\Zed\Event\Business\Dispatcher\EventListenerContextInterface $eventListener
     *
     * @return void
     */
    protected function logEventHandle(
        $eventName,
        TransferInterface $transfer,
        EventListenerContextInterface $eventListener
    ): void {
        $this->eventLogger->log(
            sprintf(
                $this->createHandleMessage($eventListener),
                $eventName,
                $eventListener->getListenerName(),
                get_class($transfer),
                $this->utilEncodingService->encodeJson($transfer->toArray()),
            ),
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
