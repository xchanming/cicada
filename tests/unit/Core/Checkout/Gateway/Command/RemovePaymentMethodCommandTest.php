<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Gateway\Command\RemovePaymentMethodCommand;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[CoversClass(RemovePaymentMethodCommand::class)]
#[Package('checkout')]
class RemovePaymentMethodCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $command = new RemovePaymentMethodCommand('test');

        static::assertSame('test', $command->paymentMethodTechnicalName);
    }

    public function testCommandKey(): void
    {
        static::assertSame(RemovePaymentMethodCommand::COMMAND_KEY, RemovePaymentMethodCommand::getDefaultKeyName());
    }
}
