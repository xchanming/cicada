<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command\Handler;

use Cicada\Core\Checkout\Gateway\CheckoutGatewayException;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\AbstractCheckoutGatewayCommand;
use Cicada\Core\Checkout\Gateway\Command\AddPaymentMethodExtensionCommand;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class AddPaymentMethodExtensionsCommandHandler extends AbstractCheckoutGatewayCommandHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ExceptionLogger $logger,
    ) {
    }

    public static function supportedCommands(): array
    {
        return [
            AddPaymentMethodExtensionCommand::class,
        ];
    }

    /**
     * @param AddPaymentMethodExtensionCommand $command
     */
    public function handle(AbstractCheckoutGatewayCommand $command, CheckoutGatewayResponse $response, SalesChannelContext $context): void
    {
        $method = $response->getAvailablePaymentMethods()->filter(function (PaymentMethodEntity $method) use ($command) {
            return $method->getTechnicalName() === $command->paymentMethodTechnicalName;
        })->first();

        if (!$method) {
            $this->logger->logOrThrowException(
                CheckoutGatewayException::handlerException('Payment method "{{ technicalName }}" not found', ['technicalName' => $command->paymentMethodTechnicalName])
            );

            return;
        }

        $method->addExtensions([$command->extensionKey => new ArrayStruct($command->extensionsPayload)]);
    }
}
