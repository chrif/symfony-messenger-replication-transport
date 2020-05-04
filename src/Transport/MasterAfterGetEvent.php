<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

use Symfony\Component\Messenger\Envelope;

final class MasterAfterGetEvent implements ReplicationEvent
{
    /** @var Envelope */
    private $master_envelope;

    public function __construct(Envelope $masterEnvelope)
    {
        $this->master_envelope = $masterEnvelope;
    }

    public function masterEnvelope(): Envelope
    {
        return $this->master_envelope;
    }
}
