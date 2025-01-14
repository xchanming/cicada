<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Checkout\Shipping\ShippingException;
use Cicada\Core\Content\ImportExport\ImportExportException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\TechnicalNameExceptionHandler;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TechnicalNameExceptionHandler::class)]
class TechnicalNameExceptionHandlerTest extends TestCase
{
    public function testPriority(): void
    {
        static::assertSame(ExceptionHandlerInterface::PRIORITY_DEFAULT, (new TechnicalNameExceptionHandler())->getPriority());
    }

    public function testPaymentException(): void
    {
        $dbalE = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: '
            . '1062 Duplicate entry \'payment_test\' for key \'payment_method.uniq.technical_name\''
        );

        $handler = new TechnicalNameExceptionHandler();
        $e = $handler->matchException($dbalE);

        static::assertInstanceOf(PaymentException::class, $e);
        static::assertSame(PaymentException::PAYMENT_METHOD_DUPLICATE_TECHNICAL_NAME, $e->getErrorCode());
        static::assertSame('The technical name "payment_test" is not unique.', $e->getMessage());
    }

    public function testShippingException(): void
    {
        $dbalE = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: '
            . '1062 Duplicate entry \'shipping_test\' for key \'shipping_method.uniq.technical_name\''
        );

        $handler = new TechnicalNameExceptionHandler();
        $e = $handler->matchException($dbalE);

        static::assertInstanceOf(ShippingException::class, $e);
        static::assertSame(ShippingException::SHIPPING_METHOD_DUPLICATE_TECHNICAL_NAME, $e->getErrorCode());
        static::assertSame('The technical name "shipping_test" is not unique.', $e->getMessage());
    }

    public function testImportExportException(): void
    {
        $dbalE = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: '
            . '1062 Duplicate entry \'import_export_test\' for key \'import_export_profile.uniq.import_export_profile.technical_name\''
        );

        $handler = new TechnicalNameExceptionHandler();
        $e = $handler->matchException($dbalE);

        static::assertInstanceOf(ImportExportException::class, $e);
        static::assertSame(ImportExportException::DUPLICATE_TECHNICAL_NAME, $e->getErrorCode());
        static::assertSame('The technical name "import_export_test" is not unique.', $e->getMessage());
    }

    public function testUnrelatedException(): void
    {
        $dbalE = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: 1451 Cannot delete or update a parent row: '
            . 'a foreign key constraint fails '
            . '(`cicada`.`theme_media`, CONSTRAINT `fk.theme_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE)'
        );

        $handler = new TechnicalNameExceptionHandler();
        $e = $handler->matchException($dbalE);

        static::assertNull($e);
    }
}
