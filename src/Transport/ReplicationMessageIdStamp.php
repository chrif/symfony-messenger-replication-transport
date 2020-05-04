<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Stamp\StampInterface;

final class ReplicationMessageIdStamp implements StampInterface
{
    /** @var string */
    private $replication_message_id;

    public function __construct()
    {
        $this->replication_message_id = Uuid::uuid4()->toString();
    }

    public function replicationMessageId(): string
    {
        return $this->replication_message_id;
    }
}
