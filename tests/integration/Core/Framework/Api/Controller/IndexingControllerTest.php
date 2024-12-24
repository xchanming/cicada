<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexer;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexingMessage;
use Cicada\Core\Framework\Api\Controller\IndexingController;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('services-settings')]
class IndexingControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    public function testIterateIndexerApiShouldReturnFinishTrueWithInvalidIndexer(): void
    {
        $this->getBrowser()->request(
            'POST',
            '/api/_action/indexing/test.indexer',
            ['offset' => 0]
        );
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['finish']);
    }

    #[DataProvider('provideOffsets')]
    public function testIterateIndexerApiShouldReturnCorrectOffset(int $offset): void
    {
        $productIndexer = $this->createMock(ProductIndexer::class);
        if ($offset === 100) {
            $productIndexer->method('iterate')->willReturn(null);
        } else {
            $productIndexer->method('iterate')->willReturn(new ProductIndexingMessage(
                [
                    Uuid::randomHex(),
                ],
                ['offset' => $offset + 50]
            ));
        }
        $registry = $this->getMockBuilder(EntityIndexerRegistry::class)->disableOriginalConstructor()->getMock();
        $registry->method('getIndexer')->willReturn($productIndexer);
        $indexer = new IndexingController($registry, static::getContainer()->get('messenger.bus.cicada'));

        $response = $indexer->iterate('product.indexer', new Request([], ['offset' => $offset]));
        $content = $response->getContent();
        static::assertIsString($content);
        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        if ($offset === 100) {
            static::assertTrue($response['finish']);
        } else {
            static::assertFalse($response['finish']);
            static::assertEquals(['offset' => $offset + 50], $response['offset']);
        }
    }

    /**
     * @return array<string, array<int>>
     */
    public static function provideOffsets(): array
    {
        return [
            'offset 0' => [0],
            'offset 50' => [50],
            'offset 100' => [100],
        ];
    }
}
