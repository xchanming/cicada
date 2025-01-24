<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\SalesChannel;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Content\Media\SalesChannel\MediaRoute;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(MediaRoute::class)]
class MediaRouteTest extends TestCase
{
    private EntityRepository&MockObject $mediaRepository;

    private MediaRoute $mediaRoute;

    protected function setUp(): void
    {
        $this->mediaRepository = $this->createMock(EntityRepository::class);
        $this->mediaRoute = new MediaRoute($this->mediaRepository);
    }

    public function testLoadReturnsMediaRouteResponse(): void
    {
        $ids = ['testMediaId1', 'testMediaId2'];

        $mediaEntity1 = new MediaEntity();
        $mediaEntity1->setId('testMediaId1');
        $mediaEntity1->setPath('testPath1');

        $mediaEntity2 = new MediaEntity();
        $mediaEntity2->setId('testMediaId2');
        $mediaEntity2->setPath('testPath2');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->expects(static::once())
            ->method('getContext')
            ->willReturn(Context::createDefaultContext());

        $request = new Request([], ['ids' => $ids]);

        $mediaEntitySearchResult = new EntitySearchResult(
            'media',
            2,
            new MediaCollection([$mediaEntity1, $mediaEntity2]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $this->mediaRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn($mediaEntitySearchResult);

        $response = $this->mediaRoute->load($request, $salesChannelContext);
        $mediaCollection = $response->getMediaCollection();
        $firstMediaEntity = $mediaCollection->first();

        static::assertCount(2, $mediaCollection);
        static::assertInstanceOf(MediaEntity::class, $firstMediaEntity);
        static::assertSame('testMediaId1', $firstMediaEntity->getId());
        static::assertSame('testPath1', $firstMediaEntity->getPath());
    }

    public function testLoadThrowsMediaExceptionWhenMediaNotFound(): void
    {
        $this->expectException(MediaException::class);
        $this->expectExceptionMessage('A media id must be provided.');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->expects(static::never())
            ->method('getContext')
            ->willReturn(Context::createDefaultContext());

        $request = new Request([], ['ids' => '']);

        $this->mediaRoute->load($request, $salesChannelContext);
    }
}
