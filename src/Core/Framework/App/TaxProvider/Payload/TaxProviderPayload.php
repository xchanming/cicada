<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\TaxProvider\Payload;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\App\Payload\SourcedPayloadInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\CloneTrait;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal only for use by the app-system
 */
#[Package('checkout')]
class TaxProviderPayload implements SourcedPayloadInterface
{
    use CloneTrait;
    use JsonSerializableTrait;

    private Source $source;

    public function __construct(
        private readonly Cart $cart,
        private readonly SalesChannelContext $context
    ) {
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;
    }
}
