<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\Builder\Order;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestBuilderTrait;

/**
 * @final
 */
#[Package('checkout')]
class OrderTransactionCaptureRefundPositionBuilder
{
    use TestBuilderTrait;

    protected string $id;

    protected CalculatedPrice $amount;

    public function __construct(
        IdsCollection $ids,
        string $key,
        protected string $refundId,
        float $amount = 420.69,
        protected ?string $externalReference = null,
        protected ?string $reason = null,
        protected ?string $orderLineItemId = null
    ) {
        $this->id = $ids->get($key);
        $this->ids = $ids;

        $this->amount($amount);

        if (!$orderLineItemId) {
            $this->add('orderLineItem', (new ProductBuilder($this->ids, '10000'))
                ->add('identifier', $this->ids->get('order_line_item'))
                ->add('quantity', 1)
                ->add('label', 'foo')
                ->add('price', new CalculatedPrice(
                    420.69,
                    420.69,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                ))
                ->build());
        }
    }

    public function amount(float $amount): self
    {
        $this->amount = new CalculatedPrice($amount, $amount, new CalculatedTaxCollection(), new TaxRuleCollection());

        return $this;
    }
}
