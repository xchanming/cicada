<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Gateway\Template;

use Cicada\Core\Checkout\Promotion\Gateway\Template\ActiveDateRange;
use Cicada\Core\Checkout\Promotion\Gateway\Template\PermittedGlobalCodePromotions;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PermittedGlobalCodePromotions::class)]
class PermittedGlobalCodePromotionsTest extends TestCase
{
    private SalesChannelEntity $salesChannel;

    protected function setUp(): void
    {
        $this->salesChannel = new SalesChannelEntity();
        $this->salesChannel->setId('DE');
    }

    /**
     * This test verifies, that we get the
     * expected and defined criteria from the template.
     */
    #[Group('promotions')]
    public function testCriteria(): void
    {
        $codes = ['code-123'];

        $template = new PermittedGlobalCodePromotions($codes, $this->salesChannel->getId());

        static::assertEquals($this->getExpectedFilter($codes)->getQueries(), $template->getQueries());
    }

    /**
     * @param list<string> $codes
     */
    private function getExpectedFilter(array $codes): MultiFilter
    {
        return new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [new EqualsFilter('active', true),
                new EqualsFilter('promotion.salesChannels.salesChannelId', $this->salesChannel->getId()),
                // yes, i know, this is not the best isolation, but its actually what we want
                new ActiveDateRange(),
                new EqualsFilter('useCodes', true),
                new EqualsFilter('useIndividualCodes', false),
                new EqualsAnyFilter('code', $codes),
            ]
        );
    }
}
