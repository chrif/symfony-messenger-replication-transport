<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

interface SlaveTransportInterface
{
    public function slaveSend(MasterAfterSendEvent $event): void;

    public function slaveGet(MasterAfterGetEvent $event): void;

    public function slaveAck(MasterAfterAckEvent $event): void;

    public function slaveReject(MasterAfterRejectEvent $event): void;
}
