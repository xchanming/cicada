<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Rule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPosition;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\CartVolumeRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel\Helper\CartRuleHelperTrait;

/**
 * @internal
 */
#[Package('services-settings')]
#[Group('rules')]
class CartVolumeRuleTest extends TestCase
{
    use CartRuleHelperTrait;
    use IntegrationTestBehaviour;

    private CartVolumeRule $rule;

    protected function setUp(): void
    {
        $this->rule = new CartVolumeRule();
    }

    #[DataProvider('getMatchingRuleTestData')]
    public function testIfMatchesCorrect(
        string $operator,
        float $volume,
        bool $expected
    ): void {
        $this->rule->assign(['volume' => $volume, 'operator' => $operator]);

        $match = $this->rule->match(new CartRuleScope(
            $this->createCartDummy(),
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    #[DataProvider('getMatchingRuleTestData')]
    public function testIfMatchesCorrectOnNested(
        string $operator,
        float $volume,
        bool $expected
    ): void {
        $this->rule->assign(['volume' => $volume, 'operator' => $operator]);
        $cart = $this->createCartDummy();
        $childLineItemCollection = $cart->getLineItems();

        $containerLineItem = $this->createContainerLineItem($childLineItemCollection);

        $cart->setLineItems(new LineItemCollection([$containerLineItem]));

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: bool}>
     */
    public static function getMatchingRuleTestData(): array
    {
        return [
            // OPERATOR_EQ
            'match / operator equals / same volume' => [Rule::OPERATOR_EQ, 360, true],
            'no match / operator equals / different volume' => [Rule::OPERATOR_EQ, 200, false],
            // OPERATOR_NEQ
            'no match / operator not equals / same volume' => [Rule::OPERATOR_NEQ, 360, false],
            'match / operator not equals / different volume' => [Rule::OPERATOR_NEQ, 200, true],
            // OPERATOR_GT
            'no match / operator greater than / lower volume' => [Rule::OPERATOR_GT, 400, false],
            'no match / operator greater than / same volume' => [Rule::OPERATOR_GT, 360, false],
            'match / operator greater than / higher volume' => [Rule::OPERATOR_GT, 100, true],
            // OPERATOR_GTE
            'no match / operator greater than equals / lower volume' => [Rule::OPERATOR_GTE, 400, false],
            'match / operator greater than equals / same volume' => [Rule::OPERATOR_GTE, 360, true],
            'match / operator greater than equals / higher volume' => [Rule::OPERATOR_GTE, 100, true],
            // OPERATOR_LT
            'match / operator lower than / lower volume' => [Rule::OPERATOR_LT, 400, true],
            'no match / operator lower  than / same volume' => [Rule::OPERATOR_LT, 360, false],
            'no match / operator lower than / higher volume' => [Rule::OPERATOR_LT, 100, false],
            // OPERATOR_LTE
            'match / operator lower than equals / lower volume' => [Rule::OPERATOR_LTE, 400, true],
            'match / operator lower than equals / same volume' => [Rule::OPERATOR_LTE, 360, true],
            'no match / operator lower than equals / higher volume' => [Rule::OPERATOR_LTE, 100, false],
        ];
    }

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $ruleRepository = static::getContainer()->get('rule.repository');
        /** @var EntityRepository<RuleConditionCollection> $conditionRepository */
        $conditionRepository = static::getContainer()->get('rule_condition.repository');

        $ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            Context::createDefaultContext()
        );

        $id = Uuid::randomHex();
        $conditionRepository->create([
            [
                'id' => $id,
                'type' => (new CartVolumeRule())->getName(),
                'ruleId' => $ruleId,
                'value' => [
                    'volume' => 9000.1,
                    'operator' => Rule::OPERATOR_EQ,
                ],
            ],
        ], $context);

        $result = $conditionRepository->search(new Criteria([$id]), $context)->getEntities()->get($id);

        static::assertNotNull($result);
        static::assertSame(9000.1, ($result->getValue() ?? [])['volume']);
        static::assertSame(Rule::OPERATOR_EQ, ($result->getValue() ?? [])['operator']);
    }

    private function createCartDummy(): Cart
    {
        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithDeliveryInfo(false, 3, 10, 40, 3 * Rule::VOLUME_FACTOR, 0.5),
            $this->createLineItemWithDeliveryInfo(true, 3, 10, 40, 3 * Rule::VOLUME_FACTOR, 0.5),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $deliveryPositionCollection = new DeliveryPositionCollection();
        $calculatedPrice = new CalculatedPrice(1.0, 1.0, new CalculatedTaxCollection(), new TaxRuleCollection());
        $deliveryDate = new DeliveryDate(new \DateTimeImmutable('now'), new \DateTimeImmutable('now'));

        foreach ($cart->getLineItems() as $lineItem) {
            $deliveryPositionCollection->add(new DeliveryPosition(
                Uuid::randomHex(),
                $lineItem,
                $lineItem->getQuantity(),
                $calculatedPrice,
                $deliveryDate
            ));
        }

        $cart->setDeliveries(new DeliveryCollection(
            [
                new Delivery(
                    $deliveryPositionCollection,
                    $deliveryDate,
                    new ShippingMethodEntity(),
                    new ShippingLocation(new CountryEntity(), null, null),
                    $calculatedPrice
                ),
            ]
        ));

        return $cart;
    }
}
