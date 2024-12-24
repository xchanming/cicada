<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SystemConfig\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\System\SystemConfig\Exception\ConfigurationNotFoundException;
use Cicada\Core\System\SystemConfig\SystemConfigException;

/**
 * @internal
 */
#[CoversClass(ConfigurationNotFoundException::class)]
class ConfigurationNotFoundExceptionTest extends TestCase
{
    public function testCreation(): void
    {
        $exception = SystemConfigException::configurationNotFound('test');

        static::assertEquals('SYSTEM__SCOPE_NOT_FOUND', $exception->getErrorCode());
        static::assertEquals(404, $exception->getStatusCode());
        static::assertEquals('Configuration for scope "test" not found.', $exception->getMessage());
    }
}
