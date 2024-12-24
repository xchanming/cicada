<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PreparedPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RecurringPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\PaymentEvents;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 */
#[Package('core')]
class PaymentHandlerIdentifierSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_METHOD_LOADED_EVENT => 'formatHandlerIdentifier',
            'payment_method.partial_loaded' => 'formatHandlerIdentifier',
        ];
    }

    public function formatHandlerIdentifier(EntityLoadedEvent $event): void
    {
        /** @var Entity $entity */
        foreach ($event->getEntities() as $entity) {
            if (!Feature::isActive('v6.7.0.0')) {
                $entity->assign([
                    'synchronous' => $this->isSynchronous($entity),
                    'asynchronous' => $this->isAsynchronous($entity),
                    'prepared' => $this->isPrepared($entity),
                    'refundable' => $this->isRefundable($entity),
                    'recurring' => $this->isRecurring($entity),
                ]);
            }

            $entity->assign([
                'shortName' => $this->getShortName($entity),
                'formattedHandlerIdentifier' => $this->getHandlerIdentifier($entity),
            ]);
        }
    }

    private function getHandlerIdentifier(Entity $entity): string
    {
        $explodedHandlerIdentifier = explode('\\', (string) $entity->get('handlerIdentifier'));

        if (\count($explodedHandlerIdentifier) < 2) {
            return $entity->get('handlerIdentifier');
        }

        /** @var string|null $firstHandlerIdentifier */
        $firstHandlerIdentifier = array_shift($explodedHandlerIdentifier);
        $lastHandlerIdentifier = array_pop($explodedHandlerIdentifier);
        if ($firstHandlerIdentifier === null || $lastHandlerIdentifier === null) {
            return '';
        }

        return 'handler_'
            . mb_strtolower($firstHandlerIdentifier)
            . '_'
            . mb_strtolower($lastHandlerIdentifier);
    }

    private function getShortName(Entity $entity): string
    {
        $explodedHandlerIdentifier = explode('\\', (string) $entity->get('handlerIdentifier'));

        $last = $explodedHandlerIdentifier[\count($explodedHandlerIdentifier) - 1];

        return (new CamelCaseToSnakeCaseNameConverter())->normalize($last);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, corresponding fields are also removed
     */
    private function isSynchronous(Entity $entity): bool
    {
        if (($app = $entity->get('appPaymentMethod')) !== null) {
            /** @var Entity $app */
            return !$app->get('finalizeUrl');
        }

        return \is_a($entity->get('handlerIdentifier'), SynchronousPaymentHandlerInterface::class, true);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, corresponding fields are also removed
     */
    private function isAsynchronous(Entity $entity): bool
    {
        if (($app = $entity->get('appPaymentMethod')) !== null) {
            /** @var Entity $app */
            return $app->get('payUrl') && $app->get('finalizeUrl');
        }

        return \is_a($entity->get('handlerIdentifier'), AsynchronousPaymentHandlerInterface::class, true);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, corresponding fields are also removed
     */
    private function isPrepared(Entity $entity): bool
    {
        if (($app = $entity->get('appPaymentMethod')) !== null) {
            /** @var Entity $app */
            return $app->get('validateUrl') && $app->get('captureUrl');
        }

        return \is_a($entity->get('handlerIdentifier'), PreparedPaymentHandlerInterface::class, true);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, corresponding fields are also removed
     */
    private function isRefundable(Entity $entity): bool
    {
        if (($app = $entity->get('appPaymentMethod')) !== null) {
            /** @var Entity $app */
            return $app->get('refundUrl') !== null;
        }

        return \is_a($entity->get('handlerIdentifier'), RefundPaymentHandlerInterface::class, true);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, corresponding fields are also removed
     */
    private function isRecurring(Entity $entity): bool
    {
        if (($app = $entity->get('appPaymentMethod')) !== null) {
            /** @var Entity $app */
            return $app->get('recurringUrl') !== null;
        }

        return \is_a($entity->get('handlerIdentifier'), RecurringPaymentHandlerInterface::class, true);
    }
}
