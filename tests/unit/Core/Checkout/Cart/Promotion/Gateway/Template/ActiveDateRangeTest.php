<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Gateway\Template;

use Cicada\Core\Checkout\Promotion\Gateway\Template\ActiveDateRange;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ActiveDateRange::class)]
class ActiveDateRangeTest extends TestCase
{
    /**
     * This test verifies, that we get the
     * expected and defined criteria from the template.
     */
    #[Group('promotions')]
    public function testCriteria(): void
    {
        $template = new ActiveDateRange();

        static::assertEquals($this->getExpectedDateRangeFilter()->getQueries(), $template->getQueries());
    }

    /**
     * @throws \Exception
     */
    private function getExpectedDateRangeFilter(): MultiFilter
    {
        $today = new \DateTime();
        $today = $today->setTimezone(new \DateTimeZone('Asia/Shanghai'));

        $todayStart = $today->format('Y-m-d H:i:s');
        $todayEnd = $today->format('Y-m-d H:i:s');

        $filterNoDateRange = new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('validFrom', null),
                new EqualsFilter('validUntil', null),
            ]
        );

        $filterStartedNoEndDate = new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new RangeFilter('validFrom', ['lte' => $todayStart]),
                new EqualsFilter('validUntil', null),
            ]
        );

        $filterActiveNoStartDate = new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('validFrom', null),
                new RangeFilter('validUntil', ['gt' => $todayEnd]),
            ]
        );

        $activeDateRangeFilter = new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new RangeFilter('validFrom', ['lte' => $todayStart]),
                new RangeFilter('validUntil', ['gt' => $todayEnd]),
            ]
        );

        return new MultiFilter(
            MultiFilter::CONNECTION_OR,
            [
                $filterNoDateRange,
                $filterActiveNoStartDate,
                $filterStartedNoEndDate,
                $activeDateRangeFilter,
            ]
        );
    }
}
