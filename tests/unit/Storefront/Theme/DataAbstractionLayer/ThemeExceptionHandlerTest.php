<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\DataAbstractionLayer;

use Cicada\Storefront\Theme\DataAbstractionLayer\ThemeExceptionHandler;
use Cicada\Storefront\Theme\Exception\ThemeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ThemeExceptionHandler::class)]
class ThemeExceptionHandlerTest extends TestCase
{
    public function testMatchException(): void
    {
        $exception = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: 1451 Cannot delete or update a parent row: '
            . 'a foreign key constraint fails '
            . '(`cicada`.`theme_media`, CONSTRAINT `fk.theme_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE)'
        );

        $result = (new ThemeExceptionHandler())->matchException($exception);

        static::assertInstanceOf(ThemeException::class, $result);
    }

    public function testNotMatchUnrelatedFkException(): void
    {
        $exception = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: 1451 Cannot delete or update a parent row: '
            . 'a foreign key constraint fails '
            . '(`cicada`.`product_media`, CONSTRAINT `fk.product_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE)'
        );

        $result = (new ThemeExceptionHandler())->matchException($exception);

        static::assertNull($result);
    }
}
