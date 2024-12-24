<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SearchKeyword;

use Cicada\Core\Content\Product\SearchKeyword\KeywordLoader;
use Cicada\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(KeywordLoader::class)]
class KeywordLoaderTest extends TestCase
{
    public function testFetch(): void
    {
        $slops = ['foo', 'bar'];

        $tokenSlops = [[
            'normal' => [$slops[0]],
            'reversed' => [$slops[1]],
        ]];

        $connection = static::createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQL80Platform());
        $connection->expects(static::once())
            ->method('executeQuery')
            ->with(static::anything(), static::callback(function (array $params) use ($slops) {
                foreach ($slops as $slop) {
                    static::assertContains($slop, $params);
                }

                return true;
            }));

        (new KeywordLoader($connection))->fetch($tokenSlops, Context::createDefaultContext());
    }
}
