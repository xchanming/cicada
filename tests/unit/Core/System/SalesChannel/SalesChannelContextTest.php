<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(SalesChannelContext::class)]
class SalesChannelContextTest extends TestCase
{
    public function testGetRuleIdsByAreas(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();

        $idA = Uuid::randomHex();
        $idB = Uuid::randomHex();
        $idC = Uuid::randomHex();
        $idD = Uuid::randomHex();

        $areaRuleIds = [
            'a' => [$idA, $idB],
            'b' => [$idA, $idC, $idD],
            'c' => [$idB],
            'd' => [$idC],
        ];

        $salesChannelContext->setAreaRuleIds($areaRuleIds);

        static::assertEquals($areaRuleIds, $salesChannelContext->getAreaRuleIds());

        static::assertEquals([$idA, $idB], $salesChannelContext->getRuleIdsByAreas(['a']));
        static::assertEquals([$idA, $idB, $idC, $idD], $salesChannelContext->getRuleIdsByAreas(['a', 'b']));
        static::assertEquals([$idA, $idB], $salesChannelContext->getRuleIdsByAreas(['a', 'c']));
        static::assertEquals([$idC], $salesChannelContext->getRuleIdsByAreas(['d']));
        static::assertEquals([], $salesChannelContext->getRuleIdsByAreas(['f']));
    }
}
