<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Migration;

use Cicada\Core\Framework\Migration\MigrationException;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MigrationStep::class)]
class MigrationStepTest extends TestCase
{
    #[DataProvider('throwingMigrationTimestamps')]
    public function testImplausibleMigrationTimestampThrows(int $timestamp): void
    {
        $step = new TimestampMigrationStep($timestamp);

        $this->expectExceptionObject(MigrationException::implausibleCreationTimestamp($timestamp, $step));

        $step->getPlausibleCreationTimestamp();
    }

    public static function throwingMigrationTimestamps(): \Generator
    {
        yield 'negative' => [-1];
        yield 'zero' => [0];
        yield '32 bit max int' => [2147483647];
        yield '64 bit max int' => [9223372036854775807];
    }

    #[DataProvider('validMigrationTimestamps')]
    public function testValidTimestamps(int $timestamp): void
    {
        $step = new TimestampMigrationStep($timestamp);
        static::assertSame($timestamp, $step->getPlausibleCreationTimestamp());
    }

    public static function validMigrationTimestamps(): \Generator
    {
        yield 'one' => [1];
        yield '32 bit max int - 1' => [2147483646];
        yield 'current timestamp' => [time()];
    }
}

/**
 * @internal
 */
class TimestampMigrationStep extends MigrationStep
{
    public function __construct(private int $timestamp)
    {
    }

    public function getCreationTimestamp(): int
    {
        return $this->timestamp;
    }

    public function update(Connection $connection): void
    {
    }
}
