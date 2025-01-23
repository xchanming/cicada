<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Controller\Exception;

use Cicada\Administration\Controller\Exception\AppByNameNotFoundException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(AppByNameNotFoundException::class)]
class AppByNameNotFoundExceptionTest extends TestCase
{
    public function testAppByNameNotFoundException(): void
    {
        $exception = new AppByNameNotFoundException('appName');

        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
        static::assertSame('ADMINISTRATION__APP_BY_NAME_NOT_FOUND', $exception->getErrorCode());
        static::assertSame('The provided name appName is invalid and no app could be found.', $exception->getMessage());
        static::assertSame(['name' => 'appName'], $exception->getParameters());
    }
}
