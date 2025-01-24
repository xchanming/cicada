<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\Docs\Script;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
interface ScriptReferenceGenerator
{
    /**
     * @return array<string, string>
     */
    public function generate(): array;
}
