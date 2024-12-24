<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Listing;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Extension\ResolveListingExtension;
use Cicada\Core\Content\Product\Extension\ResolveListingIdsExtension;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Extensions\ExtensionDispatcher;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Tests\Examples\ResolveListingExample;
use Cicada\Tests\Examples\ResolveListingIdsExample;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(ResolveListingIdsExample::class)]
#[CoversClass(ResolveListingExample::class)]
#[CoversClass(ResolveListingExtension::class)]
#[CoversClass(ResolveListingIdsExtension::class)]
class ProductListingLoaderExtensionsTests extends TestCase
{
    public function testResolveListingIdsExtensions(): void
    {
        // @phpstan-ignore-next-line
        $client = $this->createMock(Client::class);
        $client->expects(static::once())
            ->method('get')
            ->willReturn(new Response(200, [], json_encode(['ids' => ['plugin-id'], 'total' => 1], \JSON_THROW_ON_ERROR)));

        $example = new ResolveListingIdsExample($client);

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ResolveListingIdsExtension::NAME . '.pre', $example);

        $extension = new ResolveListingIdsExtension(
            new Criteria(),
            $this->createMock(SalesChannelContext::class)
        );

        $result = (new ExtensionDispatcher($dispatcher))->publish(
            name: ResolveListingIdsExtension::NAME,
            extension: $extension,
            function: static function () {
                return IdSearchResult::fromIds(['core-id'], new Criteria(), Context::createDefaultContext());
            }
        );

        static::assertInstanceOf(IdSearchResult::class, $result);

        static::assertEquals(['plugin-id'], $result->getIds());
    }

    public function testResolveListingExtension(): void
    {
        // @phpstan-ignore-next-line
        $client = $this->createMock(Client::class);
        $client->expects(static::once())
            ->method('get')
            ->willReturn(new Response(200, [], json_encode(['ids' => ['plugin-id'], 'total' => 1], \JSON_THROW_ON_ERROR)));

        /** @var StaticEntityRepository<ProductCollection> $productRepo */
        $productRepo = new StaticEntityRepository([
            [(new ProductEntity())->assign(['id' => 'plugin-id'])],
        ]);
        $example = new ResolveListingExample($client, $productRepo);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($example);

        $extension = new ResolveListingExtension(
            new Criteria(),
            $this->createMock(SalesChannelContext::class),
        );

        $result = (new ExtensionDispatcher($dispatcher))->publish(
            name: ResolveListingExtension::NAME,
            extension: $extension,
            function: function () {
                return new EntitySearchResult(
                    'product',
                    1,
                    new ProductCollection([
                        (new ProductEntity())->assign(['id' => 'plugin-id']),
                    ]),
                    new AggregationResultCollection(),
                    new Criteria(),
                    Context::createDefaultContext()
                );
            }
        );

        static::assertInstanceOf(EntitySearchResult::class, $result);

        static::assertEquals(['plugin-id'], array_values($result->getIds()));
    }
}
