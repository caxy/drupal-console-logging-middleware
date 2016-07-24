<?php

namespace Caxy\Drupal\Logging;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Log;

class DrupalProfilingProcessor
{
    /**
     * @var Log
     */
    private static $logger;

    public function __construct()
    {
        // Starts timers and logs.
        Timer::start(self::class);
        if (!isset(self::$logger)) {
            self::$logger = Database::startLog(self::class);
        }
    }

    public function __invoke(array $record)
    {
        $record['extra']['time_ms'] = Timer::read(self::class);
        if (isset(self::$logger)) {
            $record['extra']['queries'] = count(self::$logger->get(self::class));
        }

        return $record;
    }
}
