<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Twig\Extension;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Twig\Extension\SwSanitizeTwigFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @internal
 */
class SwSanitizeTwigFilterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private SwSanitizeTwigFilter $swSanitize;

    protected function setUp(): void
    {
        $this->swSanitize = static::getContainer()->get(SwSanitizeTwigFilter::class);
    }

    public function testTwigFilterIsRegistered(): void
    {
        $filters = $this->swSanitize->getFilters();

        static::assertCount(1, $filters);
        static::assertEquals('sw_sanitize', $filters[0]->getName());
    }
}
