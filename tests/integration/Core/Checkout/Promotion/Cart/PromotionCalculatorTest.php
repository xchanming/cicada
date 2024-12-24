<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Promotion\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Cicada\Core\Checkout\Promotion\Cart\Error\PromotionExcludedError;
use Cicada\Core\Checkout\Promotion\Cart\PromotionCalculator;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Traits\LineItemTestFixtureBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class PromotionCalculatorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use LineItemTestFixtureBehaviour;

    private PromotionCalculator $promotionCalculator;

    private SalesChannelContext $salesChannelContext;

    private EntityRepository $promotionRepository;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->promotionCalculator = $container->get(PromotionCalculator::class);
        $this->promotionRepository = $container->get(\sprintf('%s.repository', PromotionDefinition::ENTITY_NAME));

        $salesChannelService = $container->get(SalesChannelContextService::class);
        $this->salesChannelContext = $salesChannelService->get(
            new SalesChannelContextServiceParameters(
                TestDefaults::SALES_CHANNEL,
                Uuid::randomHex()
            )
        );
    }

    public function testCalculateDoesNotAddDiscountItemsWithoutScope(): void
    {
        $discountItem = new LineItem(Uuid::randomHex(), PromotionProcessor::LINE_ITEM_TYPE);
        $discountItems = new LineItemCollection([$discountItem]);
        $original = new Cart(Uuid::randomHex());
        $toCalculate = new Cart(Uuid::randomHex());

        $this->promotionCalculator->calculate($discountItems, $original, $toCalculate, $this->salesChannelContext, new CartBehavior());
        static::assertEmpty($toCalculate->getLineItems());
    }

    public function testCalculateDoesNotAddDiscountItemsWithDeliveryScope(): void
    {
        $discountItem = new LineItem(Uuid::randomHex(), PromotionProcessor::LINE_ITEM_TYPE);
        $discountItem->setPayloadValue('discountScope', PromotionDiscountEntity::SCOPE_DELIVERY);
        $discountItem->setPayloadValue('exclusions', []);
        $discountItems = new LineItemCollection([$discountItem]);
        $original = new Cart(Uuid::randomHex());
        $toCalculate = new Cart(Uuid::randomHex());

        $this->promotionCalculator->calculate($discountItems, $original, $toCalculate, $this->salesChannelContext, new CartBehavior());
        static::assertEmpty($toCalculate->getLineItems());
    }

    public function testCalculateAddsValidPromotionToCalculatedCart(): void
    {
        $promotionId = $this->getPromotionId();
        $discountItem = $this->getDiscountItem($promotionId);

        $discountItems = new LineItemCollection([$discountItem]);
        $original = new Cart(Uuid::randomHex());

        $productLineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $productLineItem->setPrice(new CalculatedPrice(100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $productLineItem->setStackable(true);

        $toCalculate = new Cart(Uuid::randomHex());
        $toCalculate->add($productLineItem);
        $toCalculate->setPrice(new CartPrice(84.03, 100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        $this->promotionCalculator->calculate($discountItems, $original, $toCalculate, $this->salesChannelContext, new CartBehavior());
        static::assertCount(2, $toCalculate->getLineItems());
        $promotionLineItems = $toCalculate->getLineItems()->filterType(PromotionProcessor::LINE_ITEM_TYPE);
        static::assertCount(1, $promotionLineItems);

        $promotionLineItem = $promotionLineItems->first();

        static::assertNotNull($promotionLineItem);
        static::assertNotNull($promotionLineItem->getPrice());

        static::assertSame(-10.0, $promotionLineItem->getPrice()->getTotalPrice());
    }

    public function testCalculateWithPreventedCombination(): void
    {
        $nonePreventedPromotionId = $this->getPromotionId();
        $discountItemToBeExcluded = $this->getDiscountItem($nonePreventedPromotionId);

        $preventedPromotionId = $this->getPromotionId(true);
        $validDiscountItem = $this->getDiscountItem($preventedPromotionId);
        $validDiscountItem->setPayloadValue('preventCombination', true);

        $discountItems = new LineItemCollection([$discountItemToBeExcluded, $validDiscountItem]);

        $original = new Cart(Uuid::randomHex());

        $productLineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $productLineItem->setPrice(new CalculatedPrice(100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $productLineItem->setStackable(true);

        $toCalculate = new Cart(Uuid::randomHex());
        $toCalculate->add($productLineItem);
        $toCalculate->setPrice(new CartPrice(84.03, 100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        // We expect the product plus 1 promotion in the cart so overall a count of 2 items
        $this->promotionCalculator->calculate($discountItems, $original, $toCalculate, $this->salesChannelContext, new CartBehavior());
        static::assertCount(2, $toCalculate->getLineItems());

        // Make sure that only the expected promotion is in the cart
        $promotionLineItems = $toCalculate->getLineItems()->filterType(PromotionProcessor::LINE_ITEM_TYPE);
        static::assertCount(1, $promotionLineItems);

        $promotionItem = $promotionLineItems->first();
        static::assertNotNull($promotionItem);
        static::assertNotNull($promotionItem->getPrice());
        static::assertSame(-10.0, $promotionItem->getPrice()->getTotalPrice());
        static::assertSame($promotionItem->getReferencedId(), $validDiscountItem->getReferencedId());
    }

    public function testAutomaticExclusionsDontAddError(): void
    {
        $firstPromotionId = $this->getPromotionId(true, 1, false);
        $firstDiscountItem = $this->getDiscountItem($firstPromotionId);
        $firstDiscountItem->setPriceDefinition(new AbsolutePriceDefinition(-20.0));
        $firstDiscountItem->setPayloadValue('preventCombination', true);
        $firstDiscountItem->setPayloadValue('priority', 1);

        $secondPromotionId = $this->getPromotionId(true, 2, false);
        $secondDiscountItem = $this->getDiscountItem($secondPromotionId);
        $secondDiscountItem->setPayloadValue('preventCombination', true);
        $secondDiscountItem->setPayloadValue('priority', 2);

        $discountItems = new LineItemCollection([$firstDiscountItem, $secondDiscountItem]);

        $original = new Cart(Uuid::randomHex());

        $productLineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $productLineItem->setPrice(new CalculatedPrice(100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $productLineItem->setStackable(true);

        $toCalculate = new Cart(Uuid::randomHex());
        $toCalculate->add($productLineItem);
        $toCalculate->setPrice(new CartPrice(84.03, 100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        // We expect the product plus 1 promotion in the cart so overall a count of 2 items
        $this->promotionCalculator->calculate($discountItems, $original, $toCalculate, $this->salesChannelContext, new CartBehavior());
        static::assertCount(2, $toCalculate->getLineItems());

        // Make sure that only the expected promotion is in the cart
        $promotionLineItems = $toCalculate->getLineItems()->filterType(PromotionProcessor::LINE_ITEM_TYPE);
        static::assertCount(1, $promotionLineItems);

        $promotionItem = $promotionLineItems->first();
        static::assertNotNull($promotionItem);
        static::assertNotNull($promotionItem->getPrice());
        static::assertSame(-10.0, $promotionItem->getPrice()->getTotalPrice());
        static::assertSame($promotionItem->getReferencedId(), $secondDiscountItem->getReferencedId());

        // Switch priorities and make sure that the other promotion is now in the cart
        $firstDiscountItem->setPayloadValue('priority', 2);
        $secondDiscountItem->setPayloadValue('priority', 1);

        $toCalculate = new Cart(Uuid::randomHex());
        $toCalculate->add($productLineItem);
        $toCalculate->setPrice(new CartPrice(84.03, 100.0, 100.0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        $this->promotionCalculator->calculate($discountItems, $original, $toCalculate, $this->salesChannelContext, new CartBehavior());
        static::assertCount(2, $toCalculate->getLineItems());

        $promotionLineItems = $toCalculate->getLineItems()->filterType(PromotionProcessor::LINE_ITEM_TYPE);
        static::assertCount(1, $promotionLineItems);

        $promotionItem = $promotionLineItems->first();
        static::assertNotNull($promotionItem);
        static::assertNotNull($promotionItem->getPrice());
        static::assertSame(-20.0, $promotionItem->getPrice()->getTotalPrice());
        static::assertSame($promotionItem->getReferencedId(), $firstDiscountItem->getReferencedId());

        $cartErrors = $toCalculate->getErrors();
        foreach ($cartErrors as $cartError) {
            static::assertNotInstanceOf(PromotionExcludedError::class, $cartError);
        }
    }

    private function getPromotionId(bool $preventCombination = false, int $priority = 1, bool $useCodes = true): string
    {
        $promotionId = Uuid::randomHex();

        $promotionData = [
            'id' => $promotionId,
            'active' => true,
            'exclusive' => false,
            'priority' => $priority,
            'code' => \sprintf('phpUnit-%s', $promotionId),
            'useCodes' => $useCodes,
            'useIndividualCodes' => false,
            'useSetGroups' => false,
            'name' => 'PHP Unit promotion',
            'preventCombination' => $preventCombination,
            'discounts' => [
                [
                    'scope' => PromotionDiscountEntity::SCOPE_CART,
                    'type' => PromotionDiscountEntity::TYPE_ABSOLUTE,
                    'value' => 10.0,
                    'considerAdvancedRules' => false,
                ],
            ],
        ];

        if (!$useCodes) {
            unset($promotionData['code']);
        }

        $this->promotionRepository->create(
            [
                $promotionData,
            ],
            $this->salesChannelContext->getContext()
        );

        return $promotionId;
    }

    private function getDiscountItem(string $promotionId): LineItem
    {
        $discountItemToBeExcluded = new LineItem(Uuid::randomHex(), PromotionProcessor::LINE_ITEM_TYPE);
        $discountItemToBeExcluded->setRequirement(null);
        $discountItemToBeExcluded->setPayloadValue('discountScope', PromotionDiscountEntity::SCOPE_CART);
        $discountItemToBeExcluded->setPayloadValue('discountType', PromotionDiscountEntity::TYPE_ABSOLUTE);
        $discountItemToBeExcluded->setPayloadValue('exclusions', []);
        $discountItemToBeExcluded->setPayloadValue('promotionId', $promotionId);
        $discountItemToBeExcluded->setReferencedId($promotionId);
        $discountItemToBeExcluded->setLabel('PHPUnit');
        $discountItemToBeExcluded->setPriceDefinition(new AbsolutePriceDefinition(-10.0));

        return $discountItemToBeExcluded;
    }
}
