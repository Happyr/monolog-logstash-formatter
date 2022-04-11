# Logstash formatter for Monolog v1 and v2


```yaml
#    config/prod/monolog.yaml
monolog:
    handlers:
        filter_for_errors:
            type: fingers_crossed
            action_level: warning
            handler: cloudwatch
            buffer_size: 100
            excluded_http_codes: []

        cloudwatch:
            type: stream
            path: 'php://stderr'
            formatter: 'app.monolog.formatter.logstash'
            level: info

services:
    app.monolog.formatter.logstash:
        class: Happyr\MonologLogstashFormatter\LogstashFormatter
        arguments:
            - 'app.example.com'

    monolog.processor.uid:
        class: Monolog\Processor\UidProcessor
        autoconfigure: true
        tags:
            - { name: monolog.processor, handler: cloudwatch }
```