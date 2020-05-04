<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

use Symfony\Component\Messenger\Envelope;

interface ReplicationEvent
{
    public function masterEnvelope(): Envelope;
}
