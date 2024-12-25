<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Language;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageLoader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(LanguageLoader::class)]
class LanguageLoaderTest extends TestCase
{
    public function testLoadWithoutLanguages(): void
    {
        $connection = $this->getConnectionMockObject();

        $loader = new LanguageLoader($connection);

        static::assertSame([], $loader->loadLanguages());
    }

    public function testLoadLanguages(): void
    {
        $languages = [
            [
                'array_key' => '018dcf1d5c3d701f96a2894079f6e79f',
                'id' => '018dcf1d5c3d701f96a2894079f6e79f',
                'code' => 'zh-CN',
                'parentId' => 'parentId',
                'parentCode' => 'zh-CN',
            ],
            [
                'array_key' => '018de49f23ea7db5b3afb5181b5a12a1',
                'id' => '018de49f23ea7db5b3afb5181b5a12a1',
                'code' => 'en-GB',
                'parentId' => 'parentId',
                'parentCode' => 'zh-CN',
            ],
        ];
        $connection = $this->getConnectionMockObject($languages);

        $loader = new LanguageLoader($connection);

        static::assertSame(FetchModeHelper::groupUnique($languages), $loader->loadLanguages());
    }

    /**
     * @param array<int, array<string, string|null>> $returnData
     */
    private function getConnectionMockObject(array $returnData = []): Connection
    {
        $connection = $this->createMock(Connection::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('leftJoin')->willReturn($queryBuilder);

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($returnData);

        $queryBuilder->method('executeQuery')->willReturn($result);

        $connection
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        return $connection;
    }
}
