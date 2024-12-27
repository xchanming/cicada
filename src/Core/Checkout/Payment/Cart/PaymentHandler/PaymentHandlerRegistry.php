<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Framework\App\Payment\Handler\AppPaymentHandler;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ServiceProviderInterface;

#[Package('checkout')]
class PaymentHandlerRegistry
{
    /**
     * @var array<string, AbstractPaymentHandler>
     */
    private array $handlers = [];

    /**
     * @param ServiceProviderInterface<AbstractPaymentHandler> $paymentHandlers
     *
     * @internal
     */
    public function __construct(
        ServiceProviderInterface $paymentHandlers,
        private readonly Connection $connection
    ) {
        foreach (\array_keys($paymentHandlers->getProvidedServices()) as $serviceId) {
            $handler = $paymentHandlers->get($serviceId);
            $this->handlers[(string) $serviceId] = $handler;
        }
    }

    public function getPaymentMethodHandler(
        string $paymentMethodId,
    ): ?AbstractPaymentHandler {
        $result = $this->connection->createQueryBuilder()
            ->select('
                payment_method.handler_identifier,
                app_payment_method.id as app_payment_method_id
            ')
            ->from('payment_method')
            ->leftJoin(
                'payment_method',
                'app_payment_method',
                'app_payment_method',
                'payment_method.id = app_payment_method.payment_method_id'
            )
            ->andWhere('payment_method.id = :paymentMethodId')
            ->setParameter('paymentMethodId', Uuid::fromHexToBytes($paymentMethodId))
            ->executeQuery()
            ->fetchAssociative();

        if (!$result || !\array_key_exists('handler_identifier', $result)) {
            return null;
        }

        // app payment method is set: we need to resolve an app handler
        if (isset($result['app_payment_method_id'])) {
            return $this->handlers[AppPaymentHandler::class] ?? null;
        }

        $handlerIdentifier = $result['handler_identifier'];

        if (!\array_key_exists($handlerIdentifier, $this->handlers)) {
            return null;
        }

        return $this->handlers[$handlerIdentifier];
    }
}
