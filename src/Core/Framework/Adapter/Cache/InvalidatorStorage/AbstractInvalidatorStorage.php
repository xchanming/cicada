<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\InvalidatorStorage;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class AbstractInvalidatorStorage
{
    /**
     * @param array<string> $tags
     */
    abstract public function store(array $tags): void;

    /**
     * @return list<string>
     */
    abstract public function loadAndDelete(): array;
}
