<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\Api;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupServiceRegistry;
use Cicada\Core\Checkout\Cart\LineItem\Group\Packager\LineItemGroupCountPackager;
use Cicada\Core\Checkout\Cart\LineItem\Group\Sorter\LineItemGroupPriceAscSorter;
use Cicada\Core\Checkout\Promotion\Api\PromotionActionController;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\FilterPickerInterface;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\FilterServiceRegistry;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(PromotionActionController::class)]
class PromotionActionControllerTest extends TestCase
{
    private MockObject&FilterServiceRegistry $filterServiceRegistry;

    private PromotionActionController $promotionActionController;

    protected function setUp(): void
    {
        $this->filterServiceRegistry = $this->createMock(FilterServiceRegistry::class);

        $packager = $this->createMock(LineItemGroupCountPackager::class);
        $packager->method('getKey')->willReturn('test-packager');

        $sorter = $this->createMock(LineItemGroupPriceAscSorter::class);
        $sorter->method('getKey')->willReturn('test-sorter');

        $serviceRegistry = new LineItemGroupServiceRegistry(
            [$packager],
            [$sorter],
        );

        $this->promotionActionController = new PromotionActionController(
            $serviceRegistry,
            $this->filterServiceRegistry,
        );
    }

    public function testSetGroupPackager(): void
    {
        $response = $this->promotionActionController->getSetGroupPackagers();

        $content = $response->getContent();
        static::assertNotFalse($content);

        $json = \json_decode($content, null, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($json);

        static::assertCount(1, $json);
        static::assertContains('test-packager', $json);
    }

    public function testSetGroupSorters(): void
    {
        $response = $this->promotionActionController->getSetGroupSorters();

        $content = $response->getContent();
        static::assertNotFalse($content);

        $json = \json_decode($content, null, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($json);

        static::assertCount(1, $json);
        static::assertContains('test-sorter', $json);
    }

    public function testGetDiscountFilterPickers(): void
    {
        $picker = $this->createMock(FilterPickerInterface::class);
        $picker
            ->expects(static::once())
            ->method('getKey')
            ->willReturn('test-picker');

        $this->filterServiceRegistry
            ->expects(static::once())
            ->method('getPickers')
            ->willReturnCallback(fn () => yield $picker);

        $response = $this->promotionActionController->getDiscountFilterPickers();

        $content = $response->getContent();
        static::assertNotFalse($content);
        $json = \json_decode($content, null, 512, \JSON_THROW_ON_ERROR);

        static::assertIsArray($json);
        static::assertCount(1, $json);
        static::assertContains('test-picker', $json);
    }
}
