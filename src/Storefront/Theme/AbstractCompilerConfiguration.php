<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal - may be changed in the future
 */
#[Package('framework')]
abstract class AbstractCompilerConfiguration
{
    /**
     * @return array<string, mixed>
     */
    abstract public function getConfiguration(): array;

    /**
     * @return mixed
     */
    abstract public function getValue(string $key);
}
