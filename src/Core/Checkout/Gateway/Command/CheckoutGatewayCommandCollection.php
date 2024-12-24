<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<AbstractCheckoutGatewayCommand>
 */
#[Package('checkout')]
class CheckoutGatewayCommandCollection extends Collection
{
}
