<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\ValueGenerator;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
interface NumberRangeValueGeneratorInterface
{
    /**
     * generates a new Value while taking Care of States, Events and Connectors
     */
    public function getValue(string $type, Context $context, ?string $salesChannelId, bool $preview = false): string;

    /**
     * generates a preview for a given pattern and start
     */
    public function previewPattern(string $definition, ?string $pattern, int $start): string;
}
