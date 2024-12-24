<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ProductStream;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ProductStream\ProductStreamDefinition;
use Cicada\Core\Framework\Api\Controller\SyncController;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('services-settings')]
class ProductStreamSyncTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testSyncProductStream(): void
    {
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $data = [
            [
                'key' => 'test',
                'action' => SyncController::ACTION_UPSERT,
                'entity' => static::getContainer()->get(ProductStreamDefinition::class)->getEntityName(),
                'payload' => [
                    [
                        'id' => $id1,
                        'name' => 'Test stream',
                    ],
                    [
                        'id' => $id2,
                        'name' => 'Test stream - 2',
                    ],
                ],
            ],
        ];

        $this->getBrowser()->request('POST', '/api/_action/sync', [], [], [], json_encode($data, \JSON_THROW_ON_ERROR));
        $response = $this->getBrowser()->getResponse();

        $content = $response->getContent();
        static::assertIsString($content);
        static::assertSame(200, $response->getStatusCode(), $content);

        $result = $this->connection
            ->executeQuery(
                'SELECT * FROM product_stream
                        INNER JOIN product_stream_translation ON product_stream.id = product_stream_translation.product_stream_id
                        WHERE product_stream.id = :id1
                          OR product_stream.id = :id2
                        ORDER BY `name`',
                [
                    'id1' => Uuid::fromHexToBytes($id1),
                    'id2' => Uuid::fromHexToBytes($id2),
                ]
            );

        $firstResult = $result->fetchAssociative();
        static::assertIsArray($firstResult);
        static::assertEquals('Test stream', $firstResult['name']);
        $secondResult = $result->fetchAssociative();
        static::assertIsArray($secondResult);
        static::assertEquals('Test stream - 2', $secondResult['name']);
    }
}
