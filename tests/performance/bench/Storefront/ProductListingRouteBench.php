<?php declare(strict_types=1);

namespace Cicada\Tests\Bench\Storefront;

use Doctrine\DBAL\Connection;
use PhpBench\Attributes as Bench;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Tests\Bench\AbstractBenchCase;
use Cicada\Tests\Bench\Fixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal - only for performance benchmarks
 */
#[Bench\BeforeMethods(['setupWithLogin'])]
class ProductListingRouteBench extends AbstractBenchCase
{
    use BasicTestDataBehaviour;
    use SalesChannelApiTestBehaviour;

    private const SUBJECT_CUSTOMER = 'customer-0';
    private const CATEGORY_KEY = 'level-2.1';

    public function setupWithLogin(): void
    {
        $this->ids = clone Fixtures::getIds();
        $this->context = Fixtures::context([
            SalesChannelContextService::CUSTOMER_ID => $this->ids->get(self::SUBJECT_CUSTOMER),
        ]);
        if (!$this->context->getCustomer() instanceof CustomerEntity) {
            throw new \Exception('Customer not logged in for bench tests which require it!');
        }

        static::getContainer()->get(Connection::class)->beginTransaction();
    }

    #[Bench\Groups(['custom-pricing'])]
    #[Bench\Assert('mean(variant.time.avg) < 35ms')]
    public function bench_load_product_listing_route_with_logged_out_user(): void
    {
        static::getContainer()->get(ProductListingRoute::class)
            ->load($this->ids->get(self::CATEGORY_KEY), new Request(), $this->context, new Criteria());
    }

    #[Bench\Groups(['custom-pricing'])]
    #[Bench\Assert('mean(variant.time.avg) < 35ms')]
    public function bench_load_product_listing_route_no_criteria(): void
    {
        static::getContainer()->get(ProductListingRoute::class)
            ->load($this->ids->get(self::CATEGORY_KEY), new Request(), $this->context, new Criteria());
    }

    #[Bench\Groups(['custom-pricing'])]
    #[Bench\Assert('mean(variant.time.avg) < 35ms')]
    public function bench_load_product_listing_route_only_active_and_price_below_80(): void
    {
        $criteria = (new Criteria())
            ->addFilter(new RangeFilter('price', [
                RangeFilter::GTE => 0.00,
                RangeFilter::LT => 80.00,
            ]))
            ->addFilter(new EqualsFilter('active', true));
        static::getContainer()->get(ProductListingRoute::class)
            ->load($this->ids->get(self::CATEGORY_KEY), new Request(), $this->context, $criteria);
    }

    protected static function getKernel(): KernelInterface
    {
        return self::getContainer()->get('kernel');
    }
}
