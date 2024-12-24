<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command\_fixture;

use Cicada\Core\Checkout\Gateway\Command\AbstractCheckoutGatewayCommand;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
#[Package('checkout')]
class TestCheckoutGatewayCommand extends AbstractCheckoutGatewayCommand
{
    public const COMMAND_KEY = 'test';

    /**
     * @param string[] $paymentMethodTechnicalNames
     */
    public function __construct(
        public readonly array $paymentMethodTechnicalNames
    ) {
    }

    public static function getDefaultKeyName(): string
    {
        return self::COMMAND_KEY;
    }
}
