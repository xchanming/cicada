<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Error;

use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Cart\Error\GenericCartError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ErrorCollection::class)]
class ErrorCollectionTest extends TestCase
{
    public function testHashing(): void
    {
        $collection = new ErrorCollection();

        static::assertSame('', $collection->getUniqueHash());

        $collection->add(new GenericCartError('12', 'asd', [], Error::LEVEL_ERROR, false, false, false));

        static::assertSame('8412c377d151321a', $collection->getUniqueHash());
    }
}
