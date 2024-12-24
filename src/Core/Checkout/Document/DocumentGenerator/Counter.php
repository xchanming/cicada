<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\DocumentGenerator;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class Counter
{
    private int $counter = 0;

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function increment(): void
    {
        ++$this->counter;
    }
}
