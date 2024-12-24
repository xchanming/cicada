<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Exception;

use Cicada\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupPackagerNotFoundException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LineItemGroupPackagerNotFoundException::class)]
class LineItemGroupPackagerNotFoundExceptionTest extends TestCase
{
    /**
     * This test verifies that our provided code is correctly
     * visible in the resulting exception message.
     */
    #[Group('lineitemgroup')]
    public function testCodeInMessage(): void
    {
        $exception = new LineItemGroupPackagerNotFoundException('COUNT');

        static::assertEquals('Packager "COUNT" has not been found!', $exception->getMessage());
    }

    /**
     * This test verifies that our error code is correct
     */
    #[Group('lineitemgroup')]
    public function testErrorCode(): void
    {
        $exception = new LineItemGroupPackagerNotFoundException('');

        static::assertEquals('CHECKOUT__GROUP_PACKAGER_NOT_FOUND', $exception->getErrorCode());
    }

    /**
     * This test verifies that our error code is correct
     */
    #[Group('lineitemgroup')]
    public function testStatusCode(): void
    {
        $exception = new LineItemGroupPackagerNotFoundException('');

        static::assertEquals(400, $exception->getStatusCode());
    }
}
