<?php declare(strict_types=1);

namespace Symfony\Component\Messenger\Bridge\Replication\Transport;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class ReplicationTransportFactory implements TransportFactoryInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        return ReplicationTransport::assertSelf(
            $this->container->get($options['service'])
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'replication://');
    }
}
