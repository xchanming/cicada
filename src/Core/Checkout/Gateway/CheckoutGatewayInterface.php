<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway;

use Cicada\Core\Checkout\Gateway\Command\Struct\CheckoutGatewayPayloadStruct;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
interface CheckoutGatewayInterface
{
    public function process(CheckoutGatewayPayloadStruct $payload): CheckoutGatewayResponse;
}
