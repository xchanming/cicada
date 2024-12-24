<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Gateway\Template;

use Cicada\Core\Checkout\Promotion\Gateway\Template\ActiveDateRange;
use Cicada\Core\Checkout\Promotion\Gateway\Template\PermittedIndividualCodePromotions;
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
#[CoversClass(PermittedIndividualCodePromotions::class)]
class PermittedIndividualCodePromotionsTest extends TestCase
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

        $template = new PermittedIndividualCodePromotions($codes, $this->salesChannel->getId());

        static::assertSame(MultiFilter::CONNECTION_AND, $template->getOperator());
        static::assertCount(7, $template->getQueries());
        static::assertContainsEquals(new EqualsFilter('active', true), $template->getQueries());
        static::assertContainsEquals(new EqualsFilter('promotion.salesChannels.salesChannelId', $this->salesChannel->getId()), $template->getQueries());
        static::assertContainsEquals(new EqualsFilter('useCodes', true), $template->getQueries());
        static::assertContainsEquals(new EqualsFilter('useIndividualCodes', true), $template->getQueries());
        static::assertContainsEquals(new EqualsAnyFilter('promotion.individualCodes.code', $codes), $template->getQueries());
        static::assertContainsEquals(new EqualsFilter('promotion.individualCodes.payload', null), $template->getQueries());
        static::assertTrue($this->containsActiveDateRange($template));
    }

    private function containsActiveDateRange(MultiFilter $filter): bool
    {
        foreach ($filter->getQueries() as $query) {
            if ($query instanceof ActiveDateRange) {
                return true;
            }
        }

        return false;
    }
}
