<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\ApiOrderCartService;
use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Promotion\Cart\PromotionCollector;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('checkout')]
class ApiOrderCartServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    private SalesChannelContextPersister $contextPersister;

    private SalesChannelContext $salesChannelContext;

    private ApiOrderCartService $adminOrderCartService;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $eventDispatcher = new EventDispatcher();
        $this->contextPersister = new SalesChannelContextPersister($this->connection, $eventDispatcher, static::getContainer()->get(CartPersister::class));
        $this->salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $this->adminOrderCartService = static::getContainer()->get(ApiOrderCartService::class);
    }

    public function testAddPermission(): void
    {
        $this->adminOrderCartService->addPermission($this->salesChannelContext->getToken(), PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $this->salesChannelContext->getSalesChannelId());
        $payload = $this->contextPersister->load($this->salesChannelContext->getToken(), $this->salesChannelContext->getSalesChannelId());
        static::assertArrayHasKey(PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $payload[SalesChannelContextService::PERMISSIONS]);
        static::assertTrue($payload[SalesChannelContextService::PERMISSIONS][PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS]);
    }

    public function testAddMultiplePermissions(): void
    {
        $this->adminOrderCartService->addPermission($this->salesChannelContext->getToken(), PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $this->salesChannelContext->getSalesChannelId());
        $this->adminOrderCartService->addPermission($this->salesChannelContext->getToken(), PromotionCollector::SKIP_PROMOTION, $this->salesChannelContext->getSalesChannelId());
        $payload = $this->contextPersister->load($this->salesChannelContext->getToken(), $this->salesChannelContext->getSalesChannelId());

        static::assertArrayHasKey(SalesChannelContextService::PERMISSIONS, $payload);
        static::assertCount(2, $payload[SalesChannelContextService::PERMISSIONS]);
        static::assertArrayHasKey(PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $payload[SalesChannelContextService::PERMISSIONS]);
        static::assertTrue($payload[SalesChannelContextService::PERMISSIONS][PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS]);

        static::assertArrayHasKey(PromotionCollector::SKIP_PROMOTION, $payload[SalesChannelContextService::PERMISSIONS]);
        static::assertTrue($payload[SalesChannelContextService::PERMISSIONS][PromotionCollector::SKIP_PROMOTION]);
    }

    public function testDeletePermission(): void
    {
        $this->adminOrderCartService->addPermission($this->salesChannelContext->getToken(), PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $this->salesChannelContext->getSalesChannelId());
        $payload = $this->contextPersister->load($this->salesChannelContext->getToken(), $this->salesChannelContext->getSalesChannelId());
        static::assertArrayHasKey(PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $payload[SalesChannelContextService::PERMISSIONS]);
        static::assertTrue($payload[SalesChannelContextService::PERMISSIONS][PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS]);

        $this->adminOrderCartService->deletePermission($this->salesChannelContext->getToken(), PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $this->salesChannelContext->getSalesChannelId());
        $payload = $this->contextPersister->load($this->salesChannelContext->getToken(), $this->salesChannelContext->getSalesChannelId());
        static::assertArrayHasKey(PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS, $payload[SalesChannelContextService::PERMISSIONS]);
        static::assertFalse($payload[SalesChannelContextService::PERMISSIONS][PromotionCollector::SKIP_AUTOMATIC_PROMOTIONS]);
    }
}
