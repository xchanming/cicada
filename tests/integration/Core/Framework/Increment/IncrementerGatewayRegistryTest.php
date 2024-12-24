<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Increment;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Increment\AbstractIncrementer;
use Cicada\Core\Framework\Increment\Exception\IncrementGatewayNotFoundException;
use Cicada\Core\Framework\Increment\IncrementGatewayRegistry;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class IncrementerGatewayRegistryTest extends TestCase
{
    use KernelTestBehaviour;

    public function testGet(): void
    {
        $registry = static::getContainer()->get('cicada.increment.gateway.registry');

        static::assertInstanceOf(AbstractIncrementer::class, $registry->get(IncrementGatewayRegistry::USER_ACTIVITY_POOL));
        static::assertInstanceOf(AbstractIncrementer::class, $registry->get(IncrementGatewayRegistry::MESSAGE_QUEUE_POOL));
    }

    public function testGetWithInvalidPool(): void
    {
        static::expectException(IncrementGatewayNotFoundException::class);
        static::expectExceptionMessage('Increment gateway for pool "custom_pool" was not found.');

        $registry = static::getContainer()->get('cicada.increment.gateway.registry');
        static::assertNull($registry->get('custom_pool'));
    }
}
