<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Rule\CartHasDeliveryFreeItemRule;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel\Helper\CartRuleHelperTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[Group('rules')]
class CartHasFreeDeliveryItemRuleTest extends TestCase
{
    use CartRuleHelperTrait;
    use IntegrationTestBehaviour;

    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->conditionRepository = static::getContainer()->get('rule_condition.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testIfShippingFreeLineItemsAreCaught(): void
    {
        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithDeliveryInfo(false),
            $this->createLineItemWithDeliveryInfo(true),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = (new CartHasDeliveryFreeItemRule())
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertTrue($match);
    }

    public function testIfShippingFreeNestedLineItemsAreCaught(): void
    {
        $childLineItemCollection = new LineItemCollection([
            $this->createLineItemWithDeliveryInfo(false),
            $this->createLineItemWithDeliveryInfo(true),
        ]);

        $containerLineItem = $this->createContainerLineItem($childLineItemCollection);

        $cart = $this->createCart(new LineItemCollection([$containerLineItem]));

        $match = (new CartHasDeliveryFreeItemRule())
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertTrue($match);
    }

    public function testNotContainsFreeDeliveryItems(): void
    {
        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithDeliveryInfo(false),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = (new CartHasDeliveryFreeItemRule())
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertFalse($match);
    }

    public function testEmptyDeliveryItems(): void
    {
        $cart = $this->createCart(new LineItemCollection());

        $match = (new CartHasDeliveryFreeItemRule())
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertFalse($match);

        $match = (new CartHasDeliveryFreeItemRule())->assign(['allowed' => false])
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertTrue($match);
    }

    public function testNotContainsFreeDeliveryItemsMatchesNotAllowed(): void
    {
        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithDeliveryInfo(false),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = (new CartHasDeliveryFreeItemRule())->assign(['allowed' => false])
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertTrue($match);
    }

    public function testNotContainsFreeDeliveryItemsWithDeliveryFreeItem(): void
    {
        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithDeliveryInfo(false),
            $this->createLineItemWithDeliveryInfo(true),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = (new CartHasDeliveryFreeItemRule())->assign(['allowed' => false])
            ->match(new CartRuleScope($cart, $this->createMock(SalesChannelContext::class)));

        static::assertFalse($match);
    }

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();

        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            Context::createDefaultContext()
        );

        $id = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new CartHasDeliveryFreeItemRule())->getName(),
                'ruleId' => $ruleId,
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));
    }

    #[DataProvider('getLineItemFreeDeliveryTestData')]
    public function testLineItemIsFreeDelivery(bool $ruleActive, bool $isFreeDelivery, bool $expected): void
    {
        $lineItem = $this->createLineItemWithDeliveryInfo($isFreeDelivery);

        $match = (new CartHasDeliveryFreeItemRule())->assign(['allowed' => $ruleActive])
            ->match(new LineItemScope($lineItem, $this->createMock(SalesChannelContext::class)));

        static::assertSame($expected, $match);
    }

    /**
     * @return array<string, array<bool>>
     */
    public static function getLineItemFreeDeliveryTestData(): array
    {
        return [
            'rule yes / shipping free yes' => [true, true, true],
            'rule yes / shipping free no' => [true, false, false],
            'rule no / shipping free yes' => [false, true, false],
            'rule no / shipping free no' => [false, false, true],
        ];
    }
}
