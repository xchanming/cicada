<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Write\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteTypeIntendException;
use Cicada\Tests\Integration\Core\Framework\Api\ApiDefinition\EntityDefinition\SimpleDefinition;

/**
 * @internal
 */
#[CoversClass(WriteTypeIntendException::class)]
class WriteTypeIntendExceptionTest extends TestCase
{
    public function testErrorSignalsBadRequest(): void
    {
        $exception = new WriteTypeIntendException(
            new SimpleDefinition(),
            'expected',
            'actual'
        );

        static::assertSame(400, $exception->getStatusCode());
    }

    public function testDoesHintAtCorrectApiUsage(): void
    {
        $exception = new WriteTypeIntendException(
            new SimpleDefinition(),
            UpdateCommand::class,
            InsertCommand::class
        );

        static::assertStringContainsString('Use POST method', $exception->getMessage());
    }
}
