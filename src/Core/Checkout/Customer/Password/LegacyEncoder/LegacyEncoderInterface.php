<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Password\LegacyEncoder;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
interface LegacyEncoderInterface
{
    public function getName(): string;

    public function isPasswordValid(string $password, string $hash): bool;
}
