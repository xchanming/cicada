<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Order;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Order\LineItemDownloadLoader;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Product\Aggregate\ProductDownload\ProductDownloadEntity;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[CoversClass(LineItemDownloadLoader::class)]
class LineItemDownloadLoaderTest extends TestCase
{
    private MockObject&EntityRepository $productDownloadRepository;

    private LineItemDownloadLoader $loader;

    protected function setUp(): void
    {
        $this->productDownloadRepository = $this->createMock(EntityRepository::class);

        $this->loader = new LineItemDownloadLoader($this->productDownloadRepository);
    }

    public function testLineItemDoesNotExist(): void
    {
        $payload = $this->loader->load([], Context::createDefaultContext());

        static::assertEquals([], $payload);
    }

    public function testLineItemWithoutPayload(): void
    {
        $lineItems = [
            [
                'id' => Uuid::randomHex(),
            ],
        ];

        $payload = $this->loader->load($lineItems, Context::createDefaultContext());

        static::assertEquals([], $payload);
    }

    public function testNoPayloadContinue(): void
    {
        $productDownload = new ProductDownloadEntity();
        $productDownload->setId(Uuid::randomHex());
        $productDownload->setProductId(Uuid::randomHex());

        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $entitySearchResult->method('getEntities')->willReturn(new EntityCollection([$productDownload]));
        $this->productDownloadRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn($entitySearchResult);

        $lineItems = [
            [
                'id' => Uuid::randomHex(),
                'referencedId' => Uuid::randomHex(),
                'states' => [State::IS_DOWNLOAD],
            ],
        ];

        $payload = $this->loader->load($lineItems, Context::createDefaultContext());

        static::assertEquals([], $payload);
    }

    public function testLoadDownloadsPayload(): void
    {
        $productId = Uuid::randomHex();
        $mediaId = Uuid::randomHex();
        $productDownload = new ProductDownloadEntity();
        $productDownload->setId(Uuid::randomHex());
        $productDownload->setPosition(0);
        $productDownload->setProductId($productId);
        $productDownload->setMediaId($mediaId);
        $productDownload->setMedia(new MediaEntity());

        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $entitySearchResult->method('getEntities')->willReturn(new EntityCollection([$productDownload]));
        $this->productDownloadRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn($entitySearchResult);

        $lineItems = [
            [
                'id' => Uuid::randomHex(),
                'referencedId' => $productId,
                'states' => [State::IS_DOWNLOAD],
            ],
        ];

        $payload = $this->loader->load($lineItems, Context::createDefaultContext());

        static::assertEquals([
            [
                [
                    'position' => 0,
                    'mediaId' => $mediaId,
                    'accessGranted' => false,
                ],
            ],
        ], $payload);
    }
}
