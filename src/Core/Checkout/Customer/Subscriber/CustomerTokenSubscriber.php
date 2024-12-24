<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\CustomerEvents;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerTokenSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SalesChannelContextPersister $contextPersister,
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerWritten',
            CustomerEvents::CUSTOMER_DELETED_EVENT => 'onCustomerDeleted',
        ];
    }

    public function onCustomerWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            if ($writeResult->getOperation() !== EntityWriteResult::OPERATION_UPDATE) {
                continue;
            }

            $payload = $writeResult->getPayload();
            if (!$this->customerCredentialsChanged($payload)) {
                continue;
            }

            $customerId = $payload['id'];
            $newToken = $this->invalidateUsingSession($customerId);

            if ($newToken) {
                $this->contextPersister->revokeAllCustomerTokens($customerId, $newToken);
            } else {
                $this->contextPersister->revokeAllCustomerTokens($customerId);
            }
        }
    }

    public function onCustomerDeleted(EntityDeletedEvent $event): void
    {
        foreach ($event->getIds() as $customerId) {
            $this->contextPersister->revokeAllCustomerTokens($customerId);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function customerCredentialsChanged(array $payload): bool
    {
        return isset($payload['password']);
    }

    private function invalidateUsingSession(string $customerId): ?string
    {
        $mainRequest = $this->requestStack->getMainRequest();
        if ($mainRequest === null) {
            return null;
        }

        $context = $mainRequest->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        // Not a storefront request
        if (!$context instanceof SalesChannelContext) {
            return null;
        }

        // The context customer is not the same as logged-in. We don't modify the user session
        if ($context->getCustomer()?->getId() !== $customerId) {
            return null;
        }

        $newToken = $this->contextPersister->replace(
            $context->getToken(),
            $context,
        );

        $context->assign([
            'token' => $newToken,
        ]);

        if (!$mainRequest->hasSession()) {
            return null;
        }

        $session = $mainRequest->getSession();
        $session->migrate();
        $session->set('sessionId', $session->getId());

        $session->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $newToken);
        $mainRequest->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $newToken);

        return $newToken;
    }
}
