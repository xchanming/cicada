<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\Account\Order\AccountOrderPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class AccountOrderPageLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    private SalesChannelContext $salesChannel;

    private EntityRepository $customerRepository;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepository;

    protected function setUp(): void
    {
        $this->salesChannel = $this->createSalesChannelContext();
        $this->customerRepository = static::getContainer()->get('customer.repository');
        $this->orderRepository = static::getContainer()->get('order.repository');
    }

    public function testLogsInGuestById(): void
    {
        $context = Context::createDefaultContext();
        $unexpectedCustomer = $this->createCustomer();
        $expectedCustomer = $this->createCustomer();

        $unexpectedCustomer->setEmail('identical@cicada.com');
        $expectedCustomer->setEmail('identical@cicada.com');
        $expectedCustomer->setGuest(true);

        $this->customerRepository->update([
            [
                'id' => $unexpectedCustomer->getId(),
                'email' => $unexpectedCustomer->getEmail(),
            ],
            [
                'id' => $expectedCustomer->getId(),
                'email' => $expectedCustomer->getEmail(),
                'guest' => $expectedCustomer->getGuest(),
            ],
        ], $context);

        $salesChannel = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            $this->salesChannel->getToken(),
            $this->salesChannel->getSalesChannelId(),
            [SalesChannelContextService::CUSTOMER_ID => $expectedCustomer->getId()],
        );
        $orderId = $this->placeRandomOrder($salesChannel);
        $order = $this->orderRepository->search(new Criteria([$orderId]), $context)->getEntities()->first();
        static::assertNotNull($order);
        $this->orderRepository->update([
            [
                'id' => $order->getId(),
                'deepLinkCode' => $deepLinkCode = Random::getBase64UrlString(32),
                'orderCustomer.customerId' => $expectedCustomer->getId(),
            ],
        ], $context);

        $page = $this->getPageLoader()->load(
            new Request(
                [
                    'deepLinkCode' => $deepLinkCode,
                    'email' => $expectedCustomer->getEmail(),
                    'zipcode' => '12345',
                ],
            ),
            $this->salesChannel
        );

        static::assertSame(
            $expectedCustomer->getId(),
            $page->getOrders()->getEntities()->first()?->getOrderCustomer()?->getCustomerId(),
        );
    }

    protected function getPageLoader(): AccountOrderPageLoader
    {
        return static::getContainer()->get(AccountOrderPageLoader::class);
    }
}
