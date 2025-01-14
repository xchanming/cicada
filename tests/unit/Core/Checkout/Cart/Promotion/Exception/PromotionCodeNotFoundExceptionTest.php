<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Promotion\Exception;

use Cicada\Core\Checkout\Promotion\Exception\PromotionCodeNotFoundException;
use Cicada\Core\Framework\Feature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PromotionCodeNotFoundException::class)]
class PromotionCodeNotFoundExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);
    }

    /**
     * This test verifies that our provided code is correctly
     * visible in the resulting exception message.
     */
    #[Group('promotions')]
    public function testCodeInMessage(): void
    {
        $exception = new PromotionCodeNotFoundException('MY-CODE-123');

        static::assertEquals('Promotion Code "MY-CODE-123" has not been found!', $exception->getMessage());
    }

    /**
     * This test verifies that our error code is correct
     */
    #[Group('promotions')]
    public function testErrorCode(): void
    {
        $exception = new PromotionCodeNotFoundException('');

        static::assertEquals('CHECKOUT__CODE_NOT_FOUND', $exception->getErrorCode());
    }

    /**
     * This test verifies that our error code is correct
     */
    #[Group('promotions')]
    public function testStatusCode(): void
    {
        $exception = new PromotionCodeNotFoundException('');

        static::assertEquals(400, $exception->getStatusCode());
    }
}
