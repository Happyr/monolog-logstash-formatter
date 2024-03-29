<?php

declare(strict_types=1);

namespace Happyr\MonologLogstashFormatter;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

/**
 * @author Tim Mower <timothy.mower@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class LogstashFormatter extends NormalizerFormatter
{
    /**
     * @var string the name of the system for the Logstash log message, used to fill the @source field
     */
    private $systemName;

    /**
     * @var string an application name for the Logstash log message, used to fill the @type field
     */
    private $applicationName;

    /**
     * @var string the key for 'extra' fields from the Monolog record
     */
    private $extraKey;

    /**
     * @var string the key for 'context' fields from the Monolog record
     */
    private $contextKey;

    /**
     * @param string      $applicationName The application that sends the data, used as the "type" field of logstash
     * @param string|null $systemName      The system/machine name, used as the "source" field of logstash, defaults to the hostname of the machine
     * @param string      $extraKey        The key for extra keys inside logstash "fields", defaults to extra
     * @param string      $contextKey      The key for context keys inside logstash "fields", defaults to context
     */
    public function __construct(string $applicationName, ?string $systemName = null, string $extraKey = 'extra', string $contextKey = 'context')
    {
        // logstash requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->systemName = null === $systemName ? \gethostname() : $systemName;
        $this->applicationName = $applicationName;
        $this->extraKey = $extraKey;
        $this->contextKey = $contextKey;
    }

    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $record = parent::format($record);

        if (empty($record['datetime'])) {
            $record['datetime'] = \gmdate('c');
        }
        $message = [
            '@timestamp' => $record['datetime'],
            '@version' => 1,
            'host' => $this->systemName,
        ];
        if (isset($record['message'])) {
            $message['message'] = $record['message'];
        }
        if (isset($record['channel'])) {
            $message['type'] = $record['channel'];
            $message['channel'] = $record['channel'];
        }
        if (isset($record['level_name'])) {
            $message['level'] = $record['level_name'];
        }
        if (isset($record['level'])) {
            $message['monolog_level'] = $record['level'];
        }
        if ($this->applicationName) {
            $message['type'] = $this->applicationName;
        }
        if (!empty($record['extra'])) {
            $message[$this->extraKey] = $record['extra'];
        }
        if (!empty($record['context'])) {
            $message[$this->contextKey] = $record['context'];
        }

        // TO support Funcitonbeat 8.x
        if (isset($message[$this->contextKey]['message']) && is_array($message[$this->contextKey]['message'])) {
            $message[$this->contextKey]['message'] = json_encode($message[$this->contextKey]['message']);
        }

        $json = $this->toJson($message);

        // 8000 is the limit by PHP-FPM
        if (\mb_strlen($json) > 8000 && isset($message['context']['exception']['previous'])) {
            $message['context']['exception']['previous'] = ['removed' => 'Message too long'];
            $json = $this->toJson($message);
        }

        // 8000 is the limit by PHP-FPM
        if (\mb_strlen($json) > 8000 && isset($message['context']['exception'])) {
            if ($message['context']['exception'] instanceof \Throwable) {
                $message['context']['exception_class'] = get_class($message['context']['exception']);
            }
            unset($message['context']['exception']);
            $json = $this->toJson($message);
        }

        return $json."\n";
    }
}
