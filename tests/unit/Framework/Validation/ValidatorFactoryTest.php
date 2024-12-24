<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Framework\Validation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\InAppPurchase\Services\DecodedPurchaseStruct;
use Cicada\Core\Framework\Validation\ValidatorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(ValidatorFactory::class)]
class ValidatorFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $data = [
            'identifier' => 'some-identifier',
            'nextBookingDate' => '2023-10-10',
            'quantity' => 10,
            'sub' => 'some-sub',
        ];

        $result = ValidatorFactory::create($data, DecodedPurchaseStruct::class);

        static::assertInstanceOf(DecodedPurchaseStruct::class, $result);
        static::assertSame('some-identifier', $result->identifier);
        static::assertSame(10, $result->quantity);
    }
}
