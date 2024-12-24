<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\FieldType;

use Cicada\Core\Framework\DataAbstractionLayer\FieldType\CronInterval;
use Cicada\Core\Framework\Log\Package;
use Cron\CronExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CronInterval::class)]
class CronIntervalTest extends TestCase
{
    public function testEquals(): void
    {
        $cronInterval = new CronInterval('0 0 * * *');
        $cronInterval2 = new CronInterval('0 0 * * *');
        static::assertTrue($cronInterval->equals($cronInterval2));
    }

    public function testNotEquals(): void
    {
        $cronInterval = new CronInterval('0 0 * * *');
        $cronInterval2 = new CronInterval('0 * * * *');
        static::assertFalse($cronInterval->equals($cronInterval2));
    }

    public function testIsEmpty(): void
    {
        $cronInterval = new CronInterval('* * * * *');
        static::assertTrue($cronInterval->isEmpty());
    }

    public function testNotIsEmpty(): void
    {
        $cronInterval = new CronInterval('0 * * * *');
        static::assertFalse($cronInterval->isEmpty());
    }

    public function testCreateFromCronExpression(): void
    {
        $cronExpression = new CronExpression('0 * * * *');
        $cronInterval = CronInterval::createFromCronExpression($cronExpression);
        static::assertSame($cronInterval->getExpression(), $cronExpression->getExpression());
    }
}
