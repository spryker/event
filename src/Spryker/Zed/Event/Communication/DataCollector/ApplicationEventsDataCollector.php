<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Event\Communication\DataCollector;

use Spryker\Zed\Event\Business\Logger\EventProfilerLogHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

class ApplicationEventsDataCollector extends DataCollector
{
    protected const string NAME = 'application_events';

    protected const string DATA_KEY_EVENTS = 'events';

    public function __construct(protected EventProfilerLogHandler $profilerHandler)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data[static::DATA_KEY_EVENTS] = $this->profilerHandler->getRecords();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(): array
    {
        return $this->data[static::DATA_KEY_EVENTS] ?? [];
    }

    /**
     * @return int
     */
    public function getEventCount(): int
    {
        return count($this->getEvents());
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        parent::reset();

        $this->profilerHandler->reset();
    }

    /**
     * Pulls latest records from the handler at serialization time.
     *
     * Spryker events are triggered during KernelEvents::TERMINATE
     * (via EventBehaviorEventDispatcherPlugin), which runs AFTER
     * the profiler's collect() phase (KernelEvents::RESPONSE).
     * The profiler serializes during TERMINATE after events fire,
     * so __sleep() captures the complete event data.
     *
     * @return array<int, string>
     */
    public function __sleep(): array
    {
        $this->data[static::DATA_KEY_EVENTS] = $this->profilerHandler->getRecords();

        return ['data'];
    }
}
