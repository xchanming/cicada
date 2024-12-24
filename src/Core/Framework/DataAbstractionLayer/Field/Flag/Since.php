<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Flag;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class Since extends Flag
{
    public function __construct(private readonly string $since)
    {
    }

    public function parse(): \Generator
    {
        yield 'since' => $this->since;
    }

    public function getSince(): string
    {
        return $this->since;
    }
}
