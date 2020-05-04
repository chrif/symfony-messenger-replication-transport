<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

use Exception;
use iterable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class ReplicationTransport implements TransportInterface, MessageSubscriberInterface
{
    /** @var TransportInterface */
    private $master;

    /** @var MessageBusInterface */
    private $event_bus;

    /** @var SlaveTransportInterface[] */
    private $slaves;

    /** @var string */
    private $replication_name;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TransportInterface $master,
        MessageBusInterface $event_bus,
        string $replication_name,
        LoggerInterface $logger,
        SlaveTransportInterface ...$slaves
    )
    {
        $this->master = $master;
        $this->event_bus = $event_bus;
        $this->replication_name = $replication_name;
        $this->logger = $logger;
        $this->slaves = $slaves;
    }

    public static function getHandledMessages(): iterable
    {
        yield MasterAfterSendEvent::class => 'onMasterAfterSend';
        yield MasterAfterGetEvent::class => 'onMasterAfterGet';
        yield MasterAfterAckEvent::class => 'onMasterAfterAck';
        yield MasterAfterRejectEvent::class => 'onMasterAfterReject';
    }

    public static function assertSelf($transport): self
    {
        if (!$transport instanceof self) {
            throw new Exception("transport is not an instance of " . self::class);
        }

        return $transport;
    }

    public function get(): iterable
    {
        $this->logger->info(__METHOD__);

        $envelopes = $this->master->get();

        foreach ($envelopes as $envelope) {
            if (!$envelope->getMessage() instanceof ReplicationEvent) {
                $this->event_bus->dispatch(
                    new MasterAfterGetEvent(
                        $envelope->withoutStampsOfType(NonSendableStampInterface::class)
                    )
                );
            }
        }

        return $envelopes;
    }

    public function ack(Envelope $envelope): void
    {
        $this->logger->info(__METHOD__);

        $this->master->ack($envelope);

        if (!$envelope->getMessage() instanceof ReplicationEvent) {
            $this->event_bus->dispatch(
                new MasterAfterAckEvent(
                    $envelope->withoutStampsOfType(NonSendableStampInterface::class)
                )
            );
        }
    }

    public function reject(Envelope $envelope): void
    {
        $this->logger->info(__METHOD__);

        $this->master->reject($envelope);

        if (!$envelope->getMessage() instanceof ReplicationEvent) {
            $this->event_bus->dispatch(
                new MasterAfterRejectEvent(
                    $envelope->withoutStampsOfType(NonSendableStampInterface::class)
                )
            );
        }
    }

    public function send(Envelope $envelope): Envelope
    {
        $this->logger->info(__METHOD__);

        if (!$envelope->getMessage() instanceof ReplicationEvent) {
            $envelope = $envelope->with(
                new ReplicationMessageIdStamp(),
                new ReplicationNameStamp($this->replication_name)
            );
        }

        $envelope = $this->master->send($envelope);

        if (!$envelope->getMessage() instanceof ReplicationEvent) {
            $this->event_bus->dispatch(
                new MasterAfterSendEvent(
                    $envelope->withoutStampsOfType(NonSendableStampInterface::class)
                )
            );
        }

        return $envelope;
    }

    public function onMasterAfterSend(MasterAfterSendEvent $event): void
    {
        $this->logger->info(__METHOD__);

        if (!$this->supports($event)) {
            return;
        }

        foreach ($this->slaves as $slave) {
            $slave->slaveSend($event);
        }
    }

    private function supports(ReplicationEvent $event): bool
    {
        $stamp = ReplicationNameStamp::assertLast($event->masterEnvelope());

        return $stamp->replicationName() === $this->replication_name;
    }

    public function onMasterAfterGet(MasterAfterGetEvent $event): void
    {
        $this->logger->info(__METHOD__);

        if (!$this->supports($event)) {
            return;
        }

        foreach ($this->slaves as $slave) {
            $slave->slaveGet($event);
        }
    }

    public function onMasterAfterAck(MasterAfterAckEvent $event): void
    {
        $this->logger->info(__METHOD__);

        if (!$this->supports($event)) {
            return;
        }

        foreach ($this->slaves as $slave) {
            $slave->slaveAck($event);
        }
    }

    public function onMasterAfterReject(MasterAfterRejectEvent $event): void
    {
        $this->logger->info(__METHOD__);

        if (!$this->supports($event)) {
            return;
        }

        foreach ($this->slaves as $slave) {
            $slave->slaveReject($event);
        }
    }
}
