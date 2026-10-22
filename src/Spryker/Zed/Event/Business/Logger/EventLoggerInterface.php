<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Event\Business\Logger;

use Closure;

interface EventLoggerInterface
{
    /**
     * @param \Closure(): string $messageProvider
     *
     * @return void
     */
    public function log(Closure $messageProvider): void;
}
