<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Dbal;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\SqlHelper;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(SqlHelper::class)]
class SqlHelperTest extends TestCase
{
    public function testObject(): void
    {
        $sql = SqlHelper::object(['foo' => 'bar', 'foe' => 'boe'], 'table');

        static::assertSame('JSON_OBJECT(\'foo\', bar,\'foe\', boe) as table', $sql);
    }

    public function testObjectArray(): void
    {
        $sql = SqlHelper::objectArray(['foo' => 'bar', 'foe' => 'boe'], 'table');

        static::assertSame('CONCAT(
    \'[\',
         GROUP_CONCAT(DISTINCT
             JSON_OBJECT(
                \'foo\', bar,\'foe\', boe
             )
         ),
    \']\'
) as table', $sql);
    }
}
