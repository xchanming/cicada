<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\data\NoReturnSetterMethod;

/**
 * @internal
 */
final class SkipEmptyReturn
{
    private string $name;

    public function setName(string $name): void
    {
        if ($this->name === 'hey') {
            return;
        }

        $this->name = $name;
    }
}
