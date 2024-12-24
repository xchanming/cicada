<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command;

use Cicada\Core\Checkout\Gateway\Command\AbstractCheckoutGatewayCommand;
use Cicada\Core\Checkout\Gateway\Command\RemoveShippingMethodCommand;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractCheckoutGatewayCommand::class)]
#[Package('checkout')]
class AbstractCheckoutGatewayCommandTest extends TestCase
{
    public function testCreateFrom(): void
    {
        $command = RemoveShippingMethodCommand::createFromPayload(['shippingMethodTechnicalName' => 'test']);

        static::assertSame('test', $command->shippingMethodTechnicalName);
    }
}
