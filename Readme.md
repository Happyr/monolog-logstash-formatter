# Monolog to RabbitMQ

This is our custom integration to make the log come to http://log.happyr.io.


Update your services file: 

```yaml
# rawls_monolog.yaml
services:
  rawls.monolog.channel:
    class: Rawls\QueueAdapter\Channel
    factory: 'Rawls\QueueAdapter\ChannelFactory::create'
    arguments:  ['amqp://guest:guest@mq.happyr.io:5672/%2f']

  Rawls\Monolog\AmqpHandler:
    arguments: ['@rawls.monolog.channel', 'log', '200']
    calls:
      - ['setFormatter', ['@rawls.monolog.formatter.logstash']]

  rawls.monolog.formatter.logstash:
    class: Monolog\Formatter\LogstashFormatter
    arguments:
      - "app.project.website.se" # Change me
      - null
      - null
      - 'ctxt_'
      - 1

  monolog.processor.introspection:
    class: Monolog\Processor\IntrospectionProcessor
    tags:
      - { name: monolog.processor, handler: rabbitmq }

  monolog.processor.web:
    class: Monolog\Processor\WebProcessor
    tags:
      - { name: monolog.processor, handler: rabbitmq }
```


```
# config_prod.yml
monolog:
  handlers:
    main:
      type: buffer
      handler: rabbitmq
    rabbitmq:
      type: service
      id: 'Rawls\Monolog\AmqpHandler'
```

```
# config_dev.yml
monolog:
  handlers:
    rabbitmq:
      type: 'null'
     
```

## Processors

If you want to add some data to each log message. Feel free to use the ArbitraryProcessor

```yaml
# rawls_monolog.yaml

services:
    Rawls\Monolog\Processor\ArbitraryProcessor:
        arguments: 
            - 'aws_stage'
            - '%env(resolve:AWS_STAGE)%'
        tags:
            - { name: monolog.processor }
```