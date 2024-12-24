<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command\Handler;

use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\AbstractCheckoutGatewayCommand;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
abstract class AbstractCheckoutGatewayCommandHandler
{
    abstract public function handle(AbstractCheckoutGatewayCommand $command, CheckoutGatewayResponse $response, SalesChannelContext $context): void;

    /**
     * @return array<class-string<AbstractCheckoutGatewayCommand>>
     */
    abstract public static function supportedCommands(): array;
}
