<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ImportExport\Event;

use Cicada\Core\Content\ImportExport\Event\ImportExportExceptionImportRecordEvent;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(ImportExportExceptionImportRecordEvent::class)]
class ImportExportExceptionRecordTest extends TestCase
{
    private ImportExportExceptionImportRecordEvent $exceptionRecord;

    protected function setUp(): void
    {
        $exception = $this->createMock(\Throwable::class);
        $context = Context::createDefaultContext();
        $config = $this->createMock(Config::class);

        $this->exceptionRecord = new ImportExportExceptionImportRecordEvent(
            $exception,
            [],
            [],
            $config,
            $context
        );
    }

    public function testHasException(): void
    {
        static::assertTrue($this->exceptionRecord->hasException());
        static::assertInstanceOf(\Throwable::class, $this->exceptionRecord->getException());
    }

    public function testRemoveException(): void
    {
        $this->exceptionRecord->removeException();
        static::assertFalse($this->exceptionRecord->hasException());
        static::assertNull($this->exceptionRecord->getException());
    }

    public function testReplaceException(): void
    {
        $this->exceptionRecord->removeException();
        $newException = $this->createMock(\Throwable::class);
        $this->exceptionRecord->setException($newException);
        static::assertTrue($this->exceptionRecord->hasException());
    }
}
