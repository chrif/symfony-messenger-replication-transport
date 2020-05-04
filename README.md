# Master/slave replication for Symfony Messenger transports

## Example where all messages sent to the `high_priority` transport are replicated asynchronously on the `replication_destination` transport

### `messenger.yaml`
```yaml
services:
  
  Symfony\Component\Messenger\Bridge\Replication\Example\SlaveTransport:
    arguments:
      $transport: 'messenger.transport.replication_destination'

  my_replication_transport:
    class: 'Symfony\Component\Messenger\Bridge\Replication\Transport\ReplicationTransport'
    public: true
    autowire: true
    arguments:
      $master: '@messenger.transport.high_priority'
      $event_bus: '@messenger.bus.events'
      $replication_name: 'my_replication'
      $slaves:
        - '@SlaveTransport'
    tags: [{ name: messenger.message_handler, bus: messenger.bus.events }]

framework:
  messenger:
    transports:
      replication:
        dsn: 'replication://'
        options:
          service: 'my_replication_transport'
      low_priority: 'redis://localhost:6379/low_priority'
      high_priority: 'redis://localhost:6379/high_priority'
      replication_destination: 'my_transport://'
    routing:
      'AsyncCommand': 'replication'
      'Symfony\Component\Messenger\Bridge\Replication\Transport\ReplicationEvent': 'low_priority'
    default_bus: messenger.bus.commands
    buses:
      messenger.bus.events:
        default_middleware: allow_no_handlers
      messenger.bus.queries: ~
      messenger.bus.commands: ~
```

### `SlaveTransport.php`

```php
<?php

use Symfony\Component\Messenger\Bridge\Replication\Transport\MasterAfterAckEvent;
use Symfony\Component\Messenger\Bridge\Replication\Transport\MasterAfterGetEvent;
use Symfony\Component\Messenger\Bridge\Replication\Transport\MasterAfterRejectEvent;
use Symfony\Component\Messenger\Bridge\Replication\Transport\MasterAfterSendEvent;
use Symfony\Component\Messenger\Bridge\Replication\Transport\SlaveTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class SlaveTransport implements SlaveTransportInterface
{
    /** @var TransportInterface */
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function slaveSend(MasterAfterSendEvent $event): void
    {
        $this->transport->send($event->masterEnvelope());
    }

    public function slaveGet(MasterAfterGetEvent $event): void
    {
        // find by replication_message_id and update delivered_at, etc.
    }

    public function slaveAck(MasterAfterAckEvent $event): void
    {
        // find by replication_message_id and add TransportMessageIdStamp
        $this->transport->ack($event->masterEnvelope());
    }

    public function slaveReject(MasterAfterRejectEvent $event): void
    {
        // find by replication_message_id and add TransportMessageIdStamp
        $this->transport->reject($event->masterEnvelope());
    }
}
```

### Consume `replication` transport, not `high_priority` transport

```shell script
console messenger:consume replication low_priority
```
