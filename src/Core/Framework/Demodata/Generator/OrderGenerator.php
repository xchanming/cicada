<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Demodata\Generator;

use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Order\OrderConversionContext;
use Cicada\Core\Checkout\Cart\Order\OrderConverter;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Demodata\DemodataContext;
use Cicada\Core\Framework\Demodata\DemodataGeneratorInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Doctrine\DBAL\Connection;
use Faker\Generator;

/**
 * @internal
 */
#[Package('core')]
class OrderGenerator implements DemodataGeneratorInterface
{
    /**
     * @var array<string, SalesChannelContext>
     */
    private array $contexts = [];

    private Generator $faker;

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly AbstractSalesChannelContextFactory $contextFactory,
        private readonly CartService $cartService,
        private readonly OrderConverter $orderConverter,
        private readonly EntityWriterInterface $writer,
        private readonly OrderDefinition $orderDefinition,
        private readonly CartCalculator $cartCalculator
    ) {
    }

    public function getDefinition(): string
    {
        return OrderDefinition::class;
    }

    public function generate(int $numberOfItems, DemodataContext $context, array $options = []): void
    {
        $this->faker = $context->getFaker();
        $salesChannelIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM sales_channel');
        $paymentMethodIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM payment_method');
        $productIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) as id FROM `product` ORDER BY RAND() LIMIT 1000');
        $promotionCodes = $this->connection->fetchFirstColumn('SELECT `code` FROM `promotion` ORDER BY RAND() LIMIT 1000');
        $customerIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) as id FROM customer LIMIT 10');
        $tags = $this->getIds('tag');
        $writeContext = WriteContext::createFromContext($context->getContext());

        $context->getConsole()->progressStart($numberOfItems);

        $productLineItems = array_map(
            fn ($productId) => (new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, $this->faker->randomDigit() + 1))
                ->setStackable(true)
                ->setRemovable(true),
            $productIds
        );
        $promotionLineItems = array_map(
            function ($promotionCode) {
                $uniqueKey = 'promotion-' . $promotionCode;

                return (new LineItem(Uuid::fromStringToHex($uniqueKey), LineItem::PROMOTION_LINE_ITEM_TYPE))
                    ->setLabel($uniqueKey)
                    ->setGood(false)
                    ->setReferencedId($promotionCode)
                    ->setPriceDefinition(new PercentagePriceDefinition(0));
            },
            $promotionCodes
        );

        $orders = [];

        for ($i = 1; $i <= $numberOfItems; ++$i) {
            $customerId = $context->getFaker()->randomElement($customerIds);

            $salesChannelContext = $this->getContext($customerId, $salesChannelIds, $paymentMethodIds);

            $cart = $this->cartService->createNew($salesChannelContext->getToken());
            foreach ($this->faker->randomElements($productLineItems, random_int(3, 5)) as $lineItem) {
                $cart->add($lineItem);
            }
            foreach ($this->faker->randomElements($promotionLineItems, random_int(0, 3)) as $lineItem) {
                $cart->add($lineItem);
            }

            $cart = $this->cartCalculator->calculate($cart, $salesChannelContext);
            $tempOrder = $this->orderConverter->convertToOrder($cart, $salesChannelContext, new OrderConversionContext());

            $randomDate = $context->getFaker()->dateTimeBetween('-2 years');

            $tempOrder['orderDateTime'] = $randomDate->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            $tempOrder['tags'] = $this->getTags($tags);

            $orders[] = $tempOrder;

            if (\count($orders) >= 20) {
                $this->writer->upsert($this->orderDefinition, $orders, $writeContext);
                $context->getConsole()->progressAdvance(\count($orders));
                $orders = [];
            }
        }

        if (!empty($orders)) {
            $this->writer->upsert($this->orderDefinition, $orders, $writeContext);
        }

        $context->getConsole()->progressFinish();
    }

    /**
     * @param array<string> $tags
     *
     * @return array<array{id: string}>
     */
    private function getTags(array $tags): array
    {
        $tagAssignments = [];

        if (!empty($tags)) {
            $chosenTags = $this->faker->randomElements($tags, $this->faker->randomDigit(), false);

            if (!empty($chosenTags)) {
                $tagAssignments = array_map(
                    fn (string $id) => ['id' => $id],
                    $chosenTags
                );
            }
        }

        return array_values($tagAssignments);
    }

    /**
     * @return array<string>
     */
    private function getIds(string $table): array
    {
        return $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) as id FROM ' . $table . ' LIMIT 500');
    }

    /**
     * @param array<string> $salesChannelIds
     * @param array<string> $paymentMethodIds
     */
    private function getContext(string $customerId, array $salesChannelIds, array $paymentMethodIds = []): SalesChannelContext
    {
        if (isset($this->contexts[$customerId])) {
            return $this->contexts[$customerId];
        }

        $options = [
            SalesChannelContextService::CUSTOMER_ID => $customerId,
        ];

        if (!empty($paymentMethodIds)) {
            $options[SalesChannelContextService::PAYMENT_METHOD_ID] = $this->faker->randomElement($paymentMethodIds);
        }

        $context = $this->contextFactory->create(Uuid::randomHex(), $salesChannelIds[array_rand($salesChannelIds)], $options);

        return $this->contexts[$customerId] = $context;
    }
}
