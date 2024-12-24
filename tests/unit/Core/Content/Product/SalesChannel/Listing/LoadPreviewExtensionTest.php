<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Listing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Extension\LoadPreviewExtension;
use Cicada\Core\Framework\Extensions\ExtensionDispatcher;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Examples\ProductListingCriteriaExtensionExample;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(LoadPreviewExtension::class)]
class LoadPreviewExtensionTest extends TestCase
{
    public function testLoadPreviewExample(): void
    {
        $example = new ProductListingCriteriaExtensionExample();

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($example);

        $extension = new LoadPreviewExtension(
            ['5441aebfd9d048338476f88ba7f07c76'],
            $this->createMock(SalesChannelContext::class)
        );

        $result = (new ExtensionDispatcher($dispatcher))->publish(
            name: LoadPreviewExtension::NAME,
            extension: $extension,
            function: function (array $ids, SalesChannelContext $context): array {
                return array_combine($ids, $ids);
            }
        );

        static::assertIsArray($result);
        static::assertEquals(['5441aebfd9d048338476f88ba7f07c76' => '5441aebfd9d048338476f88ba7f07c76'], $result);
    }
}
