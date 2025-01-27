<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Feature;

use Cicada\Core\Framework\Feature\FeatureException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(FeatureException::class)]
class FeatureExceptionTest extends TestCase
{
    public function testFeatureNotRegistered(): void
    {
        $exception = FeatureException::featureNotRegistered('FEATURE_ABC');

        static::assertSame('FRAMEWORK__FEATURE_NOT_REGISTERED', $exception->getErrorCode());
        static::assertSame('Feature "FEATURE_ABC" is not registered.', $exception->getMessage());
        static::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
    }

    public function testFeatureCannotBeToggled(): void
    {
        $exception = FeatureException::featureCannotBeToggled('FEATURE_ABC');

        static::assertSame('FRAMEWORK__FEATURE_CANNOT_BE_TOGGLE', $exception->getErrorCode());
        static::assertSame('Feature "FEATURE_ABC" cannot be toggled.', $exception->getMessage());
        static::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
    }

    public function testError(): void
    {
        $exception = FeatureException::error('Tried to toggle a feature that does not exist.');

        static::assertSame('FRAMEWORK__FEATURE_ERROR', $exception->getErrorCode());
        static::assertSame('Tried to toggle a feature that does not exist.', $exception->getMessage());
        static::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
    }
}
