<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command;

use Cicada\Core\Checkout\Gateway\Command\RemoveShippingMethodCommand;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveShippingMethodCommand::class)]
#[Package('checkout')]
class RemoveShippingMethodCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $command = new RemoveShippingMethodCommand('test');

        static::assertSame('test', $command->shippingMethodTechnicalName);
    }

    public function testCommandKey(): void
    {
        static::assertSame(RemoveShippingMethodCommand::COMMAND_KEY, RemoveShippingMethodCommand::getDefaultKeyName());
    }
}
