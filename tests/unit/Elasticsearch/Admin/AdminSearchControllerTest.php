<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Admin;

use Cicada\Core\Checkout\Promotion\PromotionEntity;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Elasticsearch\Admin\AdminElasticsearchHelper;
use Cicada\Elasticsearch\Admin\AdminSearchController;
use Cicada\Elasticsearch\Admin\AdminSearcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(AdminSearchController::class)]
class AdminSearchControllerTest extends TestCase
{
    private AdminSearcher $searcher;

    protected function setUp(): void
    {
        $this->searcher = $this->getMockBuilder(AdminSearcher::class)->disableOriginalConstructor()->getMock();

        $promotion = new PromotionEntity();
        $promotion->setUniqueIdentifier(Uuid::randomHex());
        $this->searcher->method('search')->willReturn([
            'promotion' => [
                'total' => 1,
                'data' => new EntityCollection([$promotion]),
                'indexer' => 'promotion-listing',
                'index' => 'sw-admin-promotion-listing',
            ],
        ]);
    }

    public function testElasticSearchWithElasticSearchNotEnable(): void
    {
        $controller = new AdminSearchController(
            $this->getMockBuilder(AdminSearcher::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(DefinitionInstanceRegistry::class),
            $this->createMock(JsonEntityEncoder::class),
            new AdminElasticsearchHelper(false, false, 'sw-admin')
        );

        $request = new Request();
        $request->request->set('term', 'test');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Admin elasticsearch is not enabled');

        $controller->elastic($request, Context::createDefaultContext());
    }

    public function testElasticSearchWithEmptySearchTerm(): void
    {
        $controller = new AdminSearchController(
            $this->getMockBuilder(AdminSearcher::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(DefinitionInstanceRegistry::class),
            $this->createMock(JsonEntityEncoder::class),
            new AdminElasticsearchHelper(true, false, 'sw-admin')
        );

        $request = new Request();
        $request->request->set('term', '   ');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Parameter "term" is missing.');

        $controller->elastic($request, Context::createDefaultContext());
    }

    public function testElasticSearch(): void
    {
        $controller = new AdminSearchController(
            $this->searcher,
            $this->createMock(DefinitionInstanceRegistry::class),
            $this->createMock(JsonEntityEncoder::class),
            new AdminElasticsearchHelper(true, false, 'sw-admin')
        );

        $request = new Request();
        $request->request->set('term', 'test');
        $response = $controller->elastic($request, Context::createDefaultContext());

        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        static::assertIsString($content);
        $content = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        $data = $content['data'];

        static::assertNotEmpty($data['promotion']);

        static::assertEquals(1, $data['promotion']['total']);
        static::assertNotEmpty($data['promotion']['data']);
        static::assertEquals('promotion-listing', $data['promotion']['indexer']);
        static::assertEquals('sw-admin-promotion-listing', $data['promotion']['index']);
    }
}
