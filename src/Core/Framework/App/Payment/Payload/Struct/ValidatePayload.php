<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Payment\Payload\Struct;

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
class ValidatePayload implements SourcedPayloadInterface
{
    use CloneTrait;
    use JsonSerializableTrait;
    use RemoveAppTrait;

    protected Source $source;

    /**
     * @param mixed[] $requestData
     */
    public function __construct(
        protected Cart $cart,
        protected array $requestData,
        protected SalesChannelContext $salesChannelContext
    ) {
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * @return mixed[]
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
