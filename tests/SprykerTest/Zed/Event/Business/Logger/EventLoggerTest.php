<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\Event\Business\Logger;

use Codeception\Test\Unit;
use Spryker\Shared\Log\Config\LoggerConfigInterface;
use Spryker\Zed\Event\Business\Logger\EventLogger;
use Spryker\Zed\Event\EventConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Event
 * @group Business
 * @group Logger
 * @group EventLoggerTest
 * Add your own group annotations below this line
 */
class EventLoggerTest extends Unit
{
    public function testLogInvokesMessageProviderWhenLoggerIsActivated(): void
    {
        // Arrange
        $eventLogger = $this->createEventLogger(true, 'event-logger-test-activated');
        $isMessageProviderInvoked = false;

        // Act
        $eventLogger->log(function () use (&$isMessageProviderInvoked): string {
            $isMessageProviderInvoked = true;

            return 'message';
        });

        // Assert
        $this->assertTrue($isMessageProviderInvoked);
    }

    public function testLogDoesNotInvokeMessageProviderWhenLoggerIsNotActivated(): void
    {
        // Arrange
        $eventLogger = $this->createEventLogger(false, 'event-logger-test-deactivated');
        $isMessageProviderInvoked = false;

        // Act
        $eventLogger->log(function () use (&$isMessageProviderInvoked): string {
            $isMessageProviderInvoked = true;

            return 'message';
        });

        // Assert
        $this->assertFalse($isMessageProviderInvoked);
    }

    protected function createEventLogger(bool $isLoggerActivated, string $channelName): EventLogger
    {
        $loggerConfigMock = $this->getMockBuilder(LoggerConfigInterface::class)->getMock();
        $loggerConfigMock->method('getChannelName')->willReturn($channelName);
        $loggerConfigMock->method('getHandlers')->willReturn([]);
        $loggerConfigMock->method('getProcessors')->willReturn([]);

        $eventConfigMock = $this->getMockBuilder(EventConfig::class)
            ->onlyMethods(['isLoggerActivated'])
            ->getMock();
        $eventConfigMock->method('isLoggerActivated')
            ->willReturn($isLoggerActivated);

        return new EventLogger($loggerConfigMock, $eventConfigMock);
    }
}
