<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

use Exception;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;

final class ReplicationNameStamp implements StampInterface
{
    /** @var string */
    private $replication_name;

    public function __construct(string $replication_name)
    {
        $this->replication_name = $replication_name;
    }

    public static function assertLast(Envelope $envelope): self
    {
        $stamp = $envelope->last(self::class);

        if (!$stamp instanceof self) {
            throw new Exception("envelope does not have a last stamp instance of " . self::class);
        }

        return $stamp;
    }

    public function replicationName(): string
    {
        return $this->replication_name;
    }
}
