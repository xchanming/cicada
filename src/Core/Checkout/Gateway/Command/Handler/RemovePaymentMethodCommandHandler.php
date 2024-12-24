<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command\Handler;

use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\AbstractCheckoutGatewayCommand;
use Cicada\Core\Checkout\Gateway\Command\RemovePaymentMethodCommand;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class RemovePaymentMethodCommandHandler extends AbstractCheckoutGatewayCommandHandler
{
    public static function supportedCommands(): array
    {
        return [
            RemovePaymentMethodCommand::class,
        ];
    }

    /**
     * @param RemovePaymentMethodCommand $command
     */
    public function handle(AbstractCheckoutGatewayCommand $command, CheckoutGatewayResponse $response, SalesChannelContext $context): void
    {
        $technicalName = $command->paymentMethodTechnicalName;
        $methods = $response->getAvailablePaymentMethods();

        $methods = $methods->filter(function (PaymentMethodEntity $method) use ($technicalName) {
            return $method->getTechnicalName() !== $technicalName;
        });

        $response->setAvailablePaymentMethods($methods);
    }
}
