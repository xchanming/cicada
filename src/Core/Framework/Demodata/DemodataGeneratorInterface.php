<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Demodata;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
interface DemodataGeneratorInterface
{
    public function getDefinition(): string;

    /**
     * @param array<string, mixed> $options
     */
    public function generate(int $numberOfItems, DemodataContext $context, array $options = []): void;
}
