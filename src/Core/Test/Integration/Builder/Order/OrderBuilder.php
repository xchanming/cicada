<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\Builder\Order;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestBuilderTrait;
use Cicada\Core\Test\TestDefaults;

/**
 * @final
 */
#[Package('checkout')]
class OrderBuilder
{
    use BasicTestDataBehaviour;
    use KernelTestBehaviour;
    use TestBuilderTrait;

    protected string $id;

    protected string $orderNumber;

    protected string $currencyId;

    protected float $currencyFactor;

    protected string $billingAddressId;

    protected string $orderDateTime;

    protected CartPrice $price;

    protected CalculatedPrice $shippingCosts;

    /**
     * @var array<mixed>
     */
    protected array $lineItems = [];

    /**
     * @var array<mixed>
     */
    protected array $transactions = [];

    /**
     * @var array<mixed>
     */
    protected array $addresses = [];

    protected string $stateId;

    public function __construct(
        IdsCollection $ids,
        string $orderNumber,
        protected string $salesChannelId = TestDefaults::SALES_CHANNEL
    ) {
        $this->ids = $ids;
        $this->id = $ids->get($orderNumber);
        $this->billingAddressId = $ids->get('billing_address');
        $this->currencyId = Defaults::CURRENCY;
        $this->stateId = $this->getStateMachineState();
        $this->orderNumber = $orderNumber;
        $this->orderDateTime = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $this->currencyFactor = 1.0;

        $this->price(420.69);
        $this->shippingCosts(0);
        $this->add('itemRounding', json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR));
        $this->add('totalRounding', json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function price(float $amount): self
    {
        $this->price = new CartPrice(
            $amount,
            $amount,
            $amount,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        );

        return $this;
    }

    public function shippingCosts(float $amount): self
    {
        $this->shippingCosts = new CalculatedPrice(
            $amount,
            $amount,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        );

        return $this;
    }

    /**
     * @param array<mixed> $customParams
     */
    public function addTransaction(string $key, array $customParams = []): self
    {
        if (\array_key_exists('amount', $customParams)) {
            if (\is_float($customParams['amount'])) {
                $customParams['amount'] = new CalculatedPrice(
                    $customParams['amount'],
                    $customParams['amount'],
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                );
            }
        }

        $transaction = \array_replace([
            'id' => $this->ids->get($key),
            'orderId' => $this->id,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'amount' => new CalculatedPrice(
                420.69,
                420.69,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            ),
            'stateId' => $this->getStateMachineState(
                OrderTransactionStates::STATE_MACHINE,
                OrderTransactionStates::STATE_OPEN
            ),
        ], $customParams);

        $this->transactions[$this->ids->get($key)] = $transaction;

        return $this;
    }

    /**
     * @param array<mixed> $customParams
     */
    public function addAddress(string $key, array $customParams = []): self
    {
        $address = \array_replace([
            'name' => 'Max',
            'city' => 'Bielefeld',
            'street' => 'Buchenweg 5',
            'zipcode' => '33062',
            'country' => [
                'id' => $this->ids->get($key),
                'name' => 'Germany',
            ],
        ], $customParams);

        $this->addresses[$key] = $address;

        return $this;
    }
}
