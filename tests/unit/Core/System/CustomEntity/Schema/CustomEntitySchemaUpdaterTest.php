<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\CustomEntity\Schema;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\CustomEntity\Schema\CustomEntitySchemaUpdater;
use Cicada\Core\System\CustomEntity\Schema\SchemaUpdater;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(CustomEntitySchemaUpdater::class)]
class CustomEntitySchemaUpdaterTest extends TestCase
{
    public function testAddsDoctrineTypeMappingForEnum(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(static::once())
            ->method('registerDoctrineTypeMapping')
            ->with('enum', 'string');

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $updater = new CustomEntitySchemaUpdater(
            $connection,
            $this->createMock(LockFactory::class),
            $this->createMock(SchemaUpdater::class),
        );

        $updater->update();
    }
}
