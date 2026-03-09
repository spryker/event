<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Event\Communication\Plugin\WebProfiler;

use Monolog\Logger as MonologLogger;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface;
use Spryker\Zed\Event\Business\Logger\EventProfilerLogHandler;
use Spryker\Zed\Event\Business\Logger\LoggerConfig;
use Spryker\Zed\Event\Communication\DataCollector\ApplicationEventsDataCollector;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * @method \Spryker\Zed\Event\EventConfig getConfig()
 * @method \Spryker\Zed\Event\Business\EventFacadeInterface getFacade()
 */
class WebProfilerApplicationEventsDataCollectorPlugin extends AbstractPlugin implements WebProfilerDataCollectorPluginInterface
{
    use LoggerTrait;

    protected const string NAME = 'application_events';

    protected const string TEMPLATE_NAME = '@Event/Collector/application_events';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return static::TEMPLATE_NAME;
    }

    /**
     * {@inheritDoc}
     * - Creates an EventProfilerLogHandler and pushes it onto the application_events Monolog Logger.
     * - Returns an ApplicationEventsDataCollector as the data collector.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     */
    public function getDataCollector(ContainerInterface $container): DataCollectorInterface
    {
        $loggerConfig = new LoggerConfig($this->getConfig());
        $logger = $this->getLogger($loggerConfig);

        $profilerHandler = new EventProfilerLogHandler();

        if ($logger instanceof MonologLogger) {
            $logger->pushHandler($profilerHandler);
        }

        return new ApplicationEventsDataCollector($profilerHandler);
    }
}
