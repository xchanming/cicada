<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Exception;

use Cicada\Core\Content\Flow\Exception\CustomTriggerByNameNotFoundException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CustomTriggerByNameNotFoundException::class)]
class CustomTriggerByNameNotFoundExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new CustomTriggerByNameNotFoundException('event_name_test');
        static::assertEquals('The provided event name event_name_test is invalid or uninstalled and no custom trigger could be found.', $exception->getMessage());
        static::assertEquals('ADMINISTRATION__CUSTOM_TRIGGER_BY_NAME_NOT_FOUND', $exception->getErrorCode());
        static::assertEquals(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
