<?php declare(strict_types=1);

namespace Cicada\Tests\Bench\Cases;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Tests\Bench\AbstractBenchCase;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes as Bench;
use PhpBench\Attributes\BeforeMethods;

/**
 * @internal - only for performance benchmarks
 */
class ProductBench extends AbstractBenchCase
{
    #[BeforeMethods(['setup'])]
    #[AfterMethods(['tearDown'])]
    #[Bench\Assert('mode(variant.time.avg) < 10ms')]
    public function bench_loading_a_simple_product(): void
    {
        $criteria = new Criteria(
            $this->ids->getList(['simple-product'])
        );

        static::getContainer()->get('product.repository')
            ->search($criteria, Context::createDefaultContext());
    }
}
