<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Rule\RuleException;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\UnsupportedCommandTypeException;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;

/**
 * @internal
 */
#[CoversClass(RuleException::class)]
class RuleExceptionTest extends TestCase
{
    public function testUnsupportedCommandType(): void
    {
        $definition = new ProductDefinition();
        $definition->compile($this->createMock(DefinitionInstanceRegistry::class));
        $exception = RuleException::unsupportedCommandType(new InsertCommand(
            $definition,
            [],
            [],
            new EntityExistence(ProductDefinition::ENTITY_NAME, [], true, false, false, []),
            ''
        ));

        static::assertInstanceOf(UnsupportedCommandTypeException::class, $exception);
        static::assertSame('Command of class Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand is not supported by product', $exception->getMessage());
    }
}
