<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\CustomerEvents;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerChangePasswordSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerWritten',
        ];
    }

    public function onCustomerWritten(EntityWrittenEvent $event): void
    {
        $payloads = $event->getPayloads();
        foreach ($payloads as $payload) {
            if (!empty($payload['password'])) {
                $this->clearLegacyPassword($payload['id']);
            }
        }
    }

    private function clearLegacyPassword(string $customerId): void
    {
        $this->connection->executeStatement(
            'UPDATE `customer` SET `legacy_password` = null, `legacy_encoder` = null WHERE id = :id',
            [
                'id' => Uuid::fromHexToBytes($customerId),
            ]
        );
    }
}
