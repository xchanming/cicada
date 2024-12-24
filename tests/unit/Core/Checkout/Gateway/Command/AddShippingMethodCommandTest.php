<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Gateway\Command\AddShippingMethodCommand;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[CoversClass(AddShippingMethodCommand::class)]
#[Package('checkout')]
class AddShippingMethodCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $command = new AddShippingMethodCommand('test');

        static::assertSame('test', $command->shippingMethodTechnicalName);
    }

    public function testCommandKey(): void
    {
        static::assertSame(AddShippingMethodCommand::COMMAND_KEY, AddShippingMethodCommand::getDefaultKeyName());
    }
}
