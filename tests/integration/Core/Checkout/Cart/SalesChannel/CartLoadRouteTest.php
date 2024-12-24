<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Cicada\Core\Checkout\Cart\Rule\CartAmountRule;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class CartLoadRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private EntityRepository $productRepository;

    private EntityRepository $paymentMethodRepository;

    private AbstractSalesChannelContextFactory $salesChannelFactory;

    private AbstractCartPersister $cartPersister;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);

        $this->productRepository = static::getContainer()->get('product.repository');
        $this->paymentMethodRepository = static::getContainer()->get('payment_method.repository');
        $this->cartPersister = static::getContainer()->get(CartPersister::class);
        $this->salesChannelFactory = static::getContainer()->get(SalesChannelContextFactory::class);
    }

    public function testEmptyCart(): void
    {
        $this->browser
            ->request(
                'GET',
                '/store-api/checkout/cart',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('cart', $response['apiAlias']);
        static::assertSame(0, $response['price']['totalPrice']);
        static::assertEmpty($response['errors']);
    }

    /**
     * @param array<string, string|array<string, string>>|null $ruleConditions
     */
    #[DataProvider('dataProviderPaymentMethodRule')]
    public function testFilledCart(?array $ruleConditions, int $errorCount): void
    {
        $this->productRepository->create([
            [
                'id' => $this->ids->create('productId'),
                'productNumber' => $this->ids->create('productNumber'),
                'stock' => 1,
                'name' => 'Test',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['id' => $this->ids->create('manufacturerId'), 'name' => 'test'],
                'tax' => ['id' => $this->ids->create('tax'), 'taxRate' => 17, 'name' => 'with id'],
                'active' => true,
                'visibilities' => [
                    ['salesChannelId' => $this->ids->get('sales-channel'), 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ],
        ], Context::createDefaultContext());

        $cart = new Cart($this->ids->create('token'));
        $cart->add(new LineItem($this->ids->create('productId'), LineItem::PRODUCT_LINE_ITEM_TYPE, $this->ids->get('productId')));

        $context = $this->salesChannelFactory->create($this->ids->get('token'), $this->ids->get('sales-channel'));
        $this->cartPersister->save($cart, $context);

        if ($ruleConditions !== null) {
            $this->paymentMethodRepository->update([[
                'id' => $context->getPaymentMethod()->getId(),
                'availabilityRule' => [
                    'name' => 'Test Rule',
                    'priority' => 0,
                    'conditions' => [
                        $ruleConditions,
                    ],
                ],
            ]], Context::createDefaultContext());
        }

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $this->ids->get('token'));

        $this->browser
            ->request(
                'GET',
                '/store-api/checkout/cart',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('cart', $response['apiAlias']);
        static::assertSame(10, $response['price']['totalPrice']);
        static::assertCount(1, $response['lineItems']);
        static::assertSame('Test', $response['lineItems'][0]['label']);
        static::assertCount($errorCount, $response['errors']);
    }

    /**
     * @return array<string, array<int|array<string, string|array<string, string>>|null>>
     */
    public static function dataProviderPaymentMethodRule(): array
    {
        return [
            'No Rule' => [
                null,
                0,
            ],
            'Matching Rule' => [
                ['type' => (new AlwaysValidRule())->getName()],
                0,
            ],
            'Not Matching Rule' => [
                [
                    'type' => (new CartAmountRule())->getName(),
                    'value' => [
                        'operator' => Rule::OPERATOR_EQ,
                        'amount' => '-1.0',
                    ],
                ],
                1,
            ],
        ];
    }
}
