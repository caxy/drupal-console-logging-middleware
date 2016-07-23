<?php

namespace Caxy\Drupal\Logging;

use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Component\Yaml\Yaml;

class YamlConsoleFormatter extends ConsoleFormatter
{
    const SIMPLE_FORMAT = "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message%\n%context%\n%extra%\n";

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        return Yaml::dump($data);
    }
}
