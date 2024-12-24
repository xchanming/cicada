<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\Filter;

use Cicada\Core\Checkout\Customer\Service\EmailIdnConverter;
use Cicada\Core\Framework\Adapter\Twig\Filter\EmailIdnTwigFilter;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(EmailIdnTwigFilter::class)]
class EmailIdnTwigFilterTest extends TestCase
{
    public function testIdnFilter(): void
    {
        $filter = new EmailIdnTwigFilter();

        static::assertCount(2, $filter->getFilters());

        static::assertSame($filter->getFilters()[0]->getName(), 'decodeIdnEmail');
        static::assertSame([EmailIdnConverter::class, 'decode'], $filter->getFilters()[0]->getCallable());

        static::assertSame($filter->getFilters()[1]->getName(), 'encodeIdnEmail');
        static::assertSame([EmailIdnConverter::class, 'encode'], $filter->getFilters()[1]->getCallable());
    }
}
