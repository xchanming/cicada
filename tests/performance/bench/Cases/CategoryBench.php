<?php declare(strict_types=1);

namespace Cicada\Tests\Bench\Cases;

use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes as Bench;
use PhpBench\Attributes\BeforeMethods;
use Cicada\Core\Content\Category\SalesChannel\NavigationRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Tests\Bench\AbstractBenchCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal - only for performance benchmarks
 */
class CategoryBench extends AbstractBenchCase
{
    #[BeforeMethods(['setup'])]
    #[AfterMethods(['tearDown'])]
    #[Bench\Assert('mode(variant.time.avg) < 10ms')]
    public function bench_load_navigation(): void
    {
        $route = static::getContainer()->get(NavigationRoute::class);

        $route->load(
            $this->ids->get('navigation'),
            $this->ids->get('navigation'),
            new Request(),
            $this->context,
            new Criteria()
        );
    }
}
