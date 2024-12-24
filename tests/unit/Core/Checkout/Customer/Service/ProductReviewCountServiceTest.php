<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Service;

use Cicada\Core\Checkout\Customer\Service\ProductReviewCountService;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ProductReviewCountService::class)]
class ProductReviewCountServiceTest extends TestCase
{
    private ProductReviewCountService $productReviewCountService;

    private MockObject&Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->productReviewCountService = new ProductReviewCountService($this->connection);
    }

    public function testUpdateReviewCountWithInvalidReviewIds(): void
    {
        $this->connection->expects(static::once())->method('fetchFirstColumn')->willReturn([]);
        $this->connection->expects(static::never())->method('executeStatement');

        $this->productReviewCountService->updateReviewCount([]);
    }

    public function testUpdateReviewCount(): void
    {
        $this->connection->expects(static::once())->method('fetchFirstColumn')->willReturn(['foobar', 'barfoo']);
        $this->connection->expects(static::exactly(2))->method('executeStatement');

        $this->productReviewCountService->updateReviewCount([]);
    }
}
