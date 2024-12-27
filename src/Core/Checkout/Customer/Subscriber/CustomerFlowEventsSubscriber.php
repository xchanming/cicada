<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\CustomerEvents;
use Cicada\Core\Checkout\Customer\DataAbstractionLayer\CustomerIndexingMessage;
use Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Cicada\Core\System\SalesChannel\SalesChannelException;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('services-settings')]
class CustomerFlowEventsSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly SalesChannelContextRestorer $restorer,
        private readonly EntityIndexer $customerIndexer,
        private readonly Connection $connection,
    ) {
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
        $context = $event->getContext();
        if ($context->getSource() instanceof SalesChannelApiSource) {
            return;
        }

        $payloads = $event->getPayloads();

        foreach ($payloads as $payload) {
            try {
                if (!empty($payload['createdAt'])) {
                    $this->dispatchCustomerRegisterEvent($payload['id'], $event);
                }
            } catch (SalesChannelException $exception) {
                if ($exception->getErrorCode() !== SalesChannelException::SALES_CHANNEL_LANGUAGE_NOT_AVAILABLE_EXCEPTION) {
                    throw $exception;
                }

                if ($context->getSource() instanceof AdminApiSource && \is_string($payload['id'])) {
                    $this->connection->delete('customer', ['id' => Uuid::fromHexToBytes($payload['id'])]);
                }

                throw $exception;
            }
        }
    }

    private function dispatchCustomerRegisterEvent(string $customerId, EntityWrittenEvent $event): void
    {
        $context = $event->getContext();

        $salesChannelContext = $this->restorer->restoreByCustomer($customerId, $context);
        $message = new CustomerIndexingMessage([$customerId]);
        $this->customerIndexer->handle($message);
        if (!$customer = $salesChannelContext->getCustomer()) {
            return;
        }

        $customerCreated = new CustomerRegisterEvent(
            $salesChannelContext,
            $customer
        );

        $this->dispatcher->dispatch($customerCreated);
    }
}
