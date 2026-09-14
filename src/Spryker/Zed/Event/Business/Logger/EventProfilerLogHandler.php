<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Event\Business\Logger;

use DateTimeInterface;
use Monolog\Handler\AbstractProcessingHandler;

class EventProfilerLogHandler extends AbstractProcessingHandler
{
    protected const string RECORD_KEY_DATETIME = 'datetime';

    protected const string RECORD_KEY_MESSAGE = 'message';

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $records = [];

    /**
     * @param \Monolog\LogRecord|array $record
     */
    protected function write($record): void
    {
        if (is_array($record)) {
            $datetime = $record[static::RECORD_KEY_DATETIME] ?? null;
            $this->records[] = [
                static::RECORD_KEY_DATETIME => $datetime instanceof DateTimeInterface ? $datetime->format('H:i:s.u') : (string)$datetime,
                static::RECORD_KEY_MESSAGE => $record[static::RECORD_KEY_MESSAGE] ?? '',
            ];

            return;
        }

        $this->records[] = [
            static::RECORD_KEY_DATETIME => $record->datetime->format('H:i:s.u'),
            static::RECORD_KEY_MESSAGE => $record->message,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function reset(): void
    {
        parent::reset();

        $this->records = [];
    }
}
