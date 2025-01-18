<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\DateRangeRule;
use Cicada\Core\Framework\Rule\RuleScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(DateRangeRule::class)]
class DateRangeRuleTest extends TestCase
{
    #[DataProvider('matchDataProvider')]
    public function testMatch(
        ?string $fromDate,
        ?string $toDate,
        bool $useTime,
        string $now,
        bool $expectedResult
    ): void {
        $rule = new DateRangeRule(
            $fromDate ? new \DateTime($fromDate) : null,
            $toDate ? new \DateTime($toDate) : null,
            $useTime
        );
        $scopeMock = $this->createMock(RuleScope::class);
        $scopeMock->method('getCurrentTime')->willReturn(new \DateTimeImmutable($now));

        $matchResult = $rule->match($scopeMock);

        static::assertSame($expectedResult, $matchResult);
    }

    /**
     * @return array<int, array<int, bool|string|null>>
     */
    public static function matchDataProvider(): array
    {
        return [
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 00:00:00 UTC',
                false,
                '2021-01-01 08:00:00 Asia/Shanghai', // 使用北京时间进行测试
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 00:00:00 UTC',
                false,
                '2020-12-31 23:59:59 Asia/Shanghai',
                false,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 00:00:00 UTC',
                false,
                '2021-01-01 08:00:00 Asia/Shanghai',
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 00:00:00 UTC',
                false,
                '2021-01-02 08:00:00 Asia/Shanghai',
                false,
            ],
            [
                '2021-01-01 11:00:00 UTC',
                '2021-01-02 10:00:00 UTC',
                false,
                '2021-01-01 18:00:00 Asia/Shanghai', // 北京时间
                true,
            ],
            [
                '2021-01-01 11:00:00 UTC',
                '2021-01-02 10:00:00 UTC',
                false,
                '2021-01-02 18:00:00 Asia/Shanghai',
                true,
            ],
            [
                '2021-01-01 11:00:00 UTC',
                '2021-01-02 10:00:00 UTC',
                false,
                '2021-01-03 18:00:00 Asia/Shanghai',
                false,
            ],

            // 从和到日期都设置，useTime = true
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 10:00:00 UTC',
                true,
                '2021-01-01 08:00:00 Asia/Shanghai',
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 10:00:00 UTC',
                true,
                '2020-12-31 23:59:59 Asia/Shanghai',
                false,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 10:00:00 UTC',
                true,
                '2021-01-01 09:59:59 Asia/Shanghai',
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 10:00:00 UTC',
                true,
                '2021-01-01 10:00:00 Asia/Shanghai', // 上海时间转换后为 UTC 02:00:00
                true, // 预计返回 true，因为时间在范围内
            ],

            // 仅设置起始日期，useTime = false
            [
                '2021-01-01 00:00:00 UTC',
                null,
                false,
                '2021-01-01 08:00:00 Asia/Shanghai',
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                null,
                false,
                '2020-12-31 23:59:59 Asia/Shanghai',
                false,
            ],

            // 仅设置起始日期，useTime = true
            [
                '2021-01-01 00:00:00 UTC',
                null,
                true,
                '2021-01-01 08:00:00 Asia/Shanghai',
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                null,
                true,
                '2020-12-31 23:59:59 Asia/Shanghai',
                false,
            ],

            // 仅设置结束日期，useTime = false
            [
                null,
                '2021-01-01 00:00:00 UTC',
                false,
                '2021-01-01 08:00:00 Asia/Shanghai',
                true,
            ],
            [
                null,
                '2021-01-01 00:00:00 UTC',
                false,
                '2021-01-02 08:00:00 Asia/Shanghai',
                false,
            ],
            [
                null,
                '2021-01-01 00:00:00 UTC',
                true,
                '2021-01-02 08:00:00 Asia/Shanghai',
                false,
            ],

            // 跨时区测试
            [
                '2021-01-01 10:00:00 UTC',
                '2021-01-01 20:00:00 UTC',
                true,
                '2021-01-01 20:00:00 -01:00', // 美国时间
                false,
            ],
            [
                '2021-01-01 10:00:00 UTC',
                '2021-01-01 20:00:00 UTC',
                true,
                '2021-01-01 20:00:00 +01:00', // 欧洲时间
                true,
            ],
            [
                '2021-01-01 00:00:00 UTC',
                '2021-01-01 00:00:00 UTC',
                false,
                '2021-01-02 10:00:00 +04:00', // +4时区
                false, // 预计返回 false，因为 2021-01-02 10:00:00 +04:00 超出 UTC 时间范围
            ],
            [
                '2021-01-02 00:00:00 +02:00',
                '2021-01-02 00:00:00 +02:00',
                false,
                '2021-01-01 22:00:00 UTC', // 欧洲+2时间
                true,
            ],
            [
                '2021-01-02 00:00:00 +02:00',
                '2021-01-02 00:00:00 +02:00',
                false,
                '2021-01-01 21:59:59 UTC', // 欧洲+2时间
                true,
            ],

            // 一些时区相关的边界测试
            [
                '2021-01-01 10:00:00 +02:00',
                '2021-01-01 20:00:00 +02:00',
                true,
                '2021-01-01 08:00:00 UTC', // UTC时间转换
                true,
            ],
            [
                '2021-01-01 10:00:00 +02:00',
                '2021-01-01 20:00:00 +02:00',
                true,
                '2021-01-01 07:59:59 UTC', // UTC时间转换
                false,
            ],

            // 未设置任何日期
            [
                null,
                null,
                true,
                '2021-01-01 08:00:00 Asia/Shanghai',
                true,
            ],
        ];
    }
}
