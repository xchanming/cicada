<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order;

use Cicada\Core\Checkout\Order\OrderAddressService;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OrderAddressService::class)]
class OrderAddressServiceTest extends TestCase
{
    /**
     * @param array<int, array{customerAddressId: string, type: string, deliveryId?: string}> $mappings
     */
    #[DataProvider('provideInvalidMappings')]
    public function testValidateInvalidMapping(array $mappings): void
    {
        $orderAddressService = new OrderAddressService(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class)
        );

        $this->expectException(OrderException::class);

        $orderAddressService->updateOrderAddresses(Uuid::randomHex(), $mappings, Context::createDefaultContext());
    }

    public static function provideInvalidMappings(): \Generator
    {
        yield 'missing type' => [
            'mappings' => [
                [
                    'customerAddressId' => '123',
                ],
            ],
        ];

        yield 'missing customerAddressId' => [
            'mappings' => [
                [
                    'type' => 'billing',
                ],
            ],
        ];

        yield 'invalid type' => [
            'mappings' => [
                [
                    'customerAddressId' => '123',
                    'type' => 'invalid',
                ],
            ],
        ];

        yield 'missing deliveryId' => [
            'mappings' => [
                [
                    'customerAddressId' => '123',
                    'type' => 'shipping',
                ],
            ],
        ];

        yield 'multiple billing addresses' => [
            'mappings' => [
                [
                    'customerAddressId' => '123',
                    'type' => 'billing',
                ],
                [
                    'customerAddressId' => '123',
                    'type' => 'billing',
                ],
            ],
        ];
    }

    public function testMissingOrder(): void
    {
        $orderAddressService = new OrderAddressService(
            new StaticEntityRepository([new OrderCollection([])]),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class)
        );

        $this->expectException(OrderException::class);

        $orderAddressService->updateOrderAddresses(Uuid::randomHex(), [], Context::createDefaultContext());
    }
}
