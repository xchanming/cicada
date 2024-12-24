<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command;

use Cicada\Core\Checkout\Gateway\Command\AddShippingMethodExtensionCommand;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AddShippingMethodExtensionCommand::class)]
#[Package('checkout')]
class AddShippingMethodExtensionCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $command = new AddShippingMethodExtensionCommand('test', 'foo', ['foo' => 'bar', 1 => 2]);

        static::assertSame('test', $command->shippingMethodTechnicalName);
        static::assertSame('foo', $command->extensionKey);
        static::assertSame(['foo' => 'bar', 1 => 2], $command->extensionsPayload);
    }

    public function testCommandKey(): void
    {
        static::assertSame(AddShippingMethodExtensionCommand::COMMAND_KEY, AddShippingMethodExtensionCommand::getDefaultKeyName());
    }
}
